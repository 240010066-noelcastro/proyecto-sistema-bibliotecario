<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // 🏛️ Evita errores 500 al interactuar con MySQL
use Carbon\Carbon;

class InicioController extends Controller
{
    /* ==========================================================================
       1. ESTADÍSTICAS GENERALES DEL DASHBOARD
       ========================================================================== */
    public function getStats(Request $request)
    {
        try {
            $usuarioId = $request->user()->Usuario_ID; 

            $prestamosActivos = DB::table('prestamos')
                ->where('Usuario_ID', $usuarioId)
                ->whereIn(DB::raw('LOWER(EstadoPrestamo_Logico)'), ['activo', 'atrasado'])
                ->count();

            $atrasosCount = DB::table('prestamos')
                ->where('Usuario_ID', $usuarioId)
                ->where(function($q) {
                    $q->where(DB::raw('LOWER(EstadoPrestamo_Logico)'), 'atrasado')
                      ->orWhere(function($sub) {
                          $sub->where(DB::raw('LOWER(EstadoPrestamo_Logico)'), 'activo')
                              ->whereDate('FechaDevolucionEstablecida', '<', Carbon::today());
                      });
                })
                ->count();

            $multasPendientes = DB::table('sanciones')
                ->where('Usuario_ID', $usuarioId)
                /* 🏛️ ELÁSTICO: Apuntamos a la raíz lógica para que sea inmune a tus estados personalizados */
                ->where('EstadoSancion_Logico', 'Pendiente')
                ->sum('MontoPago');

            $novedades = DB::table('recursos_catalogo')
                ->leftJoin('temas_catalogo', 'recursos_catalogo.Tema_ID', '=', 'temas_catalogo.Tema_ID')
                ->select(
                    'recursos_catalogo.Recurso_ID as id', 
                    'recursos_catalogo.Titulo', 
                    'recursos_catalogo.TipoRecurso_ID', 
                    'recursos_catalogo.Imagen_path',
                    'temas_catalogo.NombreTema as TemaRecurso'
                )
                ->orderBy('recursos_catalogo.Recurso_ID', 'DESC') 
                ->take(100) 
                ->get()
                ->unique('Titulo') // 🏛️ REPARACIÓN: Elimina duplicados en el catálogo si tienen el mismo título
                ->values();        // 🏛️ REPARACIÓN: Reindexa el arreglo para que mande un JSON limpio

            foreach ($novedades as $item) {
                if (!empty($item->Imagen_path)) {
                    $imgHD = str_replace('zoom=1', 'zoom=2', $item->Imagen_path);
                    $item->Imagen = str_starts_with($item->Imagen_path, 'http') ? $imgHD : url('storage/' . $item->Imagen_path);
                } else {
                    $item->Imagen = null;
                }
            }

            return response()->json([
                'success' => true,
                'prestamos_activos' => $prestamosActivos,
                'atrasos'           => $atrasosCount,
                'multas_pendientes' => number_format($multasPendientes, 2),
                'novedades'         => $novedades
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error en inicio'], 500);
        }
    }

    /* ==========================================================================
       2. PROCESAMIENTO DE ALTAS Y BAJAS DE FAVORITOS (CORAZÓN)
       ========================================================================== */
    public function toggleFavorito(Request $request, $id)
    {
        try {
            $usuarioId = $request->user()->Usuario_ID; 
            $esFavorito = $request->input('favorito'); 

            if ($esFavorito) {
                DB::table('favoritos')->updateOrInsert(
                    ['Usuario_ID' => $usuarioId, 'Recurso_ID' => $id],
                    ['created_at' => now(), 'updated_at' => now()]
                ); 
            } else {
                DB::table('favoritos')
                    ->where('Usuario_ID', $usuarioId)
                    ->where('Recurso_ID', $id)
                    ->delete(); 
            }

            return response()->json(['success' => true]); 

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500); 
        }
    }

    /* ==========================================================================
       3. ENDPOINT EXCLUSIVO: PEDIDOS ACTIVOS EN POSESIÓN
       ========================================================================== */
    public function getPedidosActivos(Request $request)
    {
        try {
            $usuarioId = $request->user()->Usuario_ID;

            $prestamosRaw = DB::table('prestamos')
                ->join('detalles_prestamo', 'prestamos.Prestamo_ID', '=', 'detalles_prestamo.Prestamo_ID')
                ->join('inventario_unidades', 'detalles_prestamo.Unidad_ID', '=', 'inventario_unidades.Unidad_ID')
                ->join('recursos_catalogo', 'inventario_unidades.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
                ->leftJoin('tipos_recursos', 'recursos_catalogo.TipoRecurso_ID', '=', 'tipos_recursos.TipoRecurso_ID')
                ->leftJoin('usuarios as entrega', 'prestamos.PersonalEntrega_ID', '=', 'entrega.Usuario_ID')
                ->leftJoin('sanciones', 'detalles_prestamo.DetallesPrestamo_ID', '=', 'sanciones.DetallesPrestamo_ID')
                ->select(
                    'prestamos.*',
                    'recursos_catalogo.Recurso_ID',
                    'recursos_catalogo.Titulo',
                    'recursos_catalogo.Imagen_path', 
                    'tipos_recursos.NombreTipo as TipoRecurso',
                    'entrega.NombreUsuario as StaffEntrega',
                    'sanciones.MontoPago as MontoSancion'
                )
                ->where('prestamos.Usuario_ID', $usuarioId)
                ->whereIn(DB::raw('LOWER(prestamos.EstadoPrestamo_Logico)'), ['activo', 'atrasado'])
                ->orderBy('prestamos.Prestamo_ID', 'DESC')
                ->get();

            $pedidosFormateados = [];

            foreach ($prestamosRaw as $p) {
                $imagenUrl = null;
                if (!empty($p->Imagen_path)) {
                    $imagenUrl = str_starts_with($p->Imagen_path, 'http') ? $p->Imagen_path : url('storage/' . $p->Imagen_path);
                }

                $pedidosFormateados[] = [
                    'Estado'        => $p->EstadoPrestamo ?? 'Activo', 
                    'FechaPrestamo' => !empty($p->FechaSalida) ? Carbon::parse($p->FechaSalida)->format('Y-m-d') : '---',
                    'FechaLimite'   => !empty($p->FechaDevolucionEstablecida) ? Carbon::parse($p->FechaDevolucionEstablecida)->format('Y-m-d') : '---',
                    'MontoMulta'    => number_format($p->MontoSancion ?? 0, 2),
                    'EntregadoPor'  => $p->StaffEntrega ?? 'Personal de Guardia',
                    'recurso'       => [
                        'id'          => $p->Recurso_ID, 
                        'Titulo'      => $p->Titulo ?? 'Recurso de Investigación',
                        'TipoRecurso' => $p->TipoRecurso ?? 'Material General',
                        'Imagen'      => $imagenUrl 
                    ]
                ];
            }

            return response()->json(['success' => true, 'data' => $pedidosFormateados]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ==========================================================================
       4. ENDPOINT EXCLUSIVO: HISTORIAL GENERAL DE DEVOLUCIONES PASADAS
       ========================================================================== */
    public function getHistorialPrestamos(Request $request)
    {
        try {
            $usuarioId = $request->user()->Usuario_ID;

            $prestamosRaw = DB::table('prestamos')
                ->join('detalles_prestamo', 'prestamos.Prestamo_ID', '=', 'detalles_prestamo.Prestamo_ID')
                ->join('inventario_unidades', 'detalles_prestamo.Unidad_ID', '=', 'inventario_unidades.Unidad_ID')
                ->join('recursos_catalogo', 'inventario_unidades.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
                ->leftJoin('tipos_recursos', 'recursos_catalogo.TipoRecurso_ID', '=', 'tipos_recursos.TipoRecurso_ID')
                ->leftJoin('usuarios as entrega', 'prestamos.PersonalEntrega_ID', '=', 'entrega.Usuario_ID')
                ->leftJoin('usuarios as recibe', 'prestamos.PersonalRecibe_ID', '=', 'recibe.Usuario_ID')
                ->leftJoin('sanciones', 'detalles_prestamo.DetallesPrestamo_ID', '=', 'sanciones.DetallesPrestamo_ID')
                ->select(
                    'prestamos.*',
                    'recursos_catalogo.Recurso_ID',
                    'recursos_catalogo.Titulo',
                    'recursos_catalogo.Imagen_path', 
                    'tipos_recursos.NombreTipo as TipoRecurso',
                    'entrega.NombreUsuario as StaffEntrega',
                    'recibe.NombreUsuario as StaffRecibe',
                    'sanciones.MontoPago as MontoSancion'
                )
                ->where('prestamos.Usuario_ID', $usuarioId)
                ->whereIn(DB::raw('LOWER(prestamos.EstadoPrestamo_Logico)'), ['devuelto', 'devuelto con retraso'])
                ->orderBy('prestamos.Prestamo_ID', 'DESC')
                ->get();

            $pedidosFormateados = [];

            foreach ($prestamosRaw as $p) {
                $fechaFinal = $p->updated_at;
                
                $diasUso = 0;
                if (!empty($fechaFinal) && !empty($p->FechaSalida)) {
                    $diasUso = (int) ceil(Carbon::parse($p->FechaSalida)->diffInMinutes(Carbon::parse($fechaFinal)) / 1440);
                }

                $imagenUrl = null;
                if (!empty($p->Imagen_path)) {
                    $imagenUrl = str_starts_with($p->Imagen_path, 'http') ? $p->Imagen_path : url('storage/' . $p->Imagen_path);
                }

                $pedidosFormateados[] = [
                    'Estado'        => $p->EstadoPrestamo ?? 'Devuelto', 
                    'FechaPrestamo' => !empty($p->FechaSalida) ? Carbon::parse($p->FechaSalida)->format('Y-m-d') : '---',
                    'FechaDevolucion'=> !empty($fechaFinal) ? Carbon::parse($fechaFinal)->format('Y-m-d') : '---',
                    'MontoMulta'    => number_format($p->MontoSancion ?? 0, 2),
                    'DiasPrestamo'  => $diasUso,
                    'EntregadoPor'  => $p->StaffEntrega ?? 'Personal de Guardia',
                    'RecibidoPor'   => $p->StaffRecibe ?? 'Mostrador de Control',
                    'recurso'       => [
                        'id'          => $p->Recurso_ID, 
                        'Titulo'      => $p->Titulo ?? 'Recurso de Investigación',
                        'TipoRecurso' => $p->TipoRecurso ?? 'Material General',
                        'Imagen'      => $imagenUrl 
                    ]
                ];
            }

            return response()->json(['success' => true, 'data' => $pedidosFormateados]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}