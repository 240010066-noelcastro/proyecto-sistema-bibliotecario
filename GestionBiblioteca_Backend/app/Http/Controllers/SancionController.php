<?php

namespace App\Http\Controllers;

use App\Models\Sancion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; 

class SancionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            $filtroTipo = $request->input('filtroTipo');
            $rangoFecha = $request->input('rangoFecha'); 
            $filtroBaja = $request->input('filtroBaja');

            $query = DB::table('sanciones')
                ->join('usuarios', 'sanciones.Usuario_ID', '=', 'usuarios.Usuario_ID')
                ->leftJoin('detalles_prestamo', 'sanciones.DetallesPrestamo_ID', '=', 'detalles_prestamo.DetallesPrestamo_ID')
                ->leftJoin('inventario_unidades', 'detalles_prestamo.Unidad_ID', '=', 'inventario_unidades.Unidad_ID')
                ->leftJoin('recursos_catalogo', 'inventario_unidades.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
                ->select(
                    'sanciones.*',
                    DB::raw("CONCAT(usuarios.NombreUsuario, ' ', IFNULL(usuarios.ApellidoPaterno, '')) AS NombreEstudiante"),
                    'usuarios.Matricula',
                    'detalles_prestamo.Unidad_ID',
                    'recursos_catalogo.Titulo',
                    'recursos_catalogo.TipoRecurso',
                    'inventario_unidades.EstadoDisponibilidad'
                );

            // Filtro por Tipo de Recurso
            if ($filtroTipo && $filtroTipo !== 'Todos') {
                $query->where('recursos_catalogo.TipoRecurso', $filtroTipo);
            }

            // NUEVO: Filtro por Rango de Fechas (Estilo Dashboard)
            if ($rangoFecha && $rangoFecha !== 'todo') {
                $now = Carbon::now();
                if ($rangoFecha === 'hoy') {
                    $query->whereDate('sanciones.FechaGeneracion', $now->toDateString());
                } else if ($rangoFecha === '7_dias') {
                    $query->whereBetween('sanciones.FechaGeneracion', [$now->copy()->subDays(7)->toDateString(), $now->toDateString()]);
                } else if ($rangoFecha === '30_dias') {
                    $query->whereBetween('sanciones.FechaGeneracion', [$now->copy()->subDays(30)->toDateString(), $now->toDateString()]);
                } else if (preg_match('/^\d{4}-\d{2}$/', $rangoFecha)) {
                    $mes = Carbon::createFromFormat('Y-m', $rangoFecha);
                    $inicioMes = $mes->startOfMonth()->toDateString();
                    $finMes = $mes->endOfMonth()->toDateString();
                    $query->whereBetween('sanciones.FechaGeneracion', [$inicioMes, $finMes]);
                }
            }

            // NUEVO: Filtro Exacto de Baja (A prueba de errores de texto)
            if ($filtroBaja && $filtroBaja !== 'Todos') {
                if ($filtroBaja === 'Si') {
                    $query->where('inventario_unidades.EstadoDisponibilidad', 'Baja');
                } else if ($filtroBaja === 'No') {
                    $query->where('inventario_unidades.EstadoDisponibilidad', '!=', 'Baja');
                }
            }

            // Buscador por texto (Incluye la Fecha de Generación)
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('sanciones.Sancion_ID', 'LIKE', "%{$search}%")
                      ->orWhere('usuarios.Matricula', 'LIKE', "%{$search}%")
                      ->orWhereRaw("CONCAT(usuarios.NombreUsuario, ' ', IFNULL(usuarios.ApellidoPaterno, '')) LIKE ?", ["%{$search}%"])
                      ->orWhere('sanciones.TipoSancion', 'LIKE', "%{$search}%")
                      ->orWhere('recursos_catalogo.Titulo', 'LIKE', "%{$search}%")
                      ->orWhere('inventario_unidades.Unidad_ID', 'LIKE', "%{$search}%")
                      ->orWhere('sanciones.EstadoSancion', 'LIKE', "%{$search}%")
                      ->orWhere('inventario_unidades.EstadoDisponibilidad', 'LIKE', "%{$search}%")
                      ->orWhereRaw("CAST(sanciones.FechaGeneracion AS CHAR) LIKE ?", ["%{$search}%"]);
                });
            }

            $query->orderBy('sanciones.Sancion_ID', 'desc');

            if ($request->has('all')) {
                return response()->json(['success' => true, 'data' => $query->get()]);
            }

            return response()->json(['success' => true, 'data' => $query->paginate(6)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getCandidatos()
    {
        try {
            $candidatos = DB::table('detalles_prestamo')
                ->join('prestamos', 'detalles_prestamo.Prestamo_ID', '=', 'prestamos.Prestamo_ID')
                ->join('usuarios', 'prestamos.Usuario_ID', '=', 'usuarios.Usuario_ID')
                ->join('inventario_unidades', 'detalles_prestamo.Unidad_ID', '=', 'inventario_unidades.Unidad_ID')
                ->join('recursos_catalogo', 'inventario_unidades.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
                ->leftJoin('sanciones', 'detalles_prestamo.DetallesPrestamo_ID', '=', 'sanciones.DetallesPrestamo_ID')
                ->whereNull('sanciones.Sancion_ID') 
                ->whereIn('prestamos.EstadoPrestamo', ['Activo', 'Atrasado'])
                ->select(
                    'detalles_prestamo.DetallesPrestamo_ID',
                    'prestamos.Prestamo_ID',
                    'prestamos.EstadoPrestamo',
                    'usuarios.Usuario_ID',
                    'usuarios.Matricula',
                    DB::raw("CONCAT(usuarios.NombreUsuario, ' ', IFNULL(usuarios.ApellidoPaterno, '')) AS NombreEstudiante"),
                    'inventario_unidades.Unidad_ID',
                    'recursos_catalogo.Titulo'
                )->get();
            return response()->json(['success' => true, 'data' => $candidatos]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
            'Usuario_ID' => 'required|integer|exists:usuarios,Usuario_ID', 
            'DetallesPrestamo_ID' => 'nullable|integer|exists:detalles_prestamo,DetallesPrestamo_ID',
            'TipoSancion' => 'required|string|max:50',
            'MontoPago' => 'required|numeric|min:0',
            'EstadoSancion' => 'required|string|max:30',
            'FechaGeneracion' => 'required|date',
            'FechaPago' => 'nullable|date', // NUEVA VALIDACIÓN
            'Observaciones' => 'nullable|string|max:250',
        ]);

        $sancion = Sancion::create([
            'Usuario_ID' => $request->Usuario_ID,
            'DetallesPrestamo_ID' => $request->DetallesPrestamo_ID,
            'TipoSancion' => $request->TipoSancion,
            'MontoPago' => $request->MontoPago,
            'EstadoSancion' => $request->EstadoSancion,
            'FechaGeneracion' => $request->FechaGeneracion,
            // NUEVO: Solo guarda la fecha de pago si la sanción se marca como Pagada
            'FechaPago' => $request->EstadoSancion === 'Pagado' ? $request->FechaPago : null,
            'Observaciones' => $request->Observaciones,
        ]);

            if ($request->DetallesPrestamo_ID) {
                $detalle = DB::table('detalles_prestamo')->where('DetallesPrestamo_ID', $request->DetallesPrestamo_ID)->first();
                if ($detalle) {
                    if ($request->DarDeBaja) {
                        $estadoFisico = ($request->TipoSancion === 'Material Extraviado') ? 'Extraviado' : 'Malo / Dañado';
                        DB::table('inventario_unidades')
                            ->where('Unidad_ID', $detalle->Unidad_ID)
                            ->update([
                                'EstadoDisponibilidad' => 'Baja', 
                                'EstadoFisicoInicial' => $estadoFisico,
                                'updated_at' => now()
                            ]);
                    }

                    // CIERRE DE PRÉSTAMO INTELIGENTE: Evita que se pisen los estados
                    if ($request->DarDeBaja) {
                        // Si es baja, el préstamo muere con estatus administrativo especial de por vida
                        DB::table('prestamos')
                            ->where('Prestamo_ID', $detalle->Prestamo_ID)
                            ->update([
                                'EstadoPrestamo' => 'Finalizado (Sanción)',
                                'updated_at' => now()
                            ]);
                    } else if (in_array($request->EstadoSancion, ['Pagado', 'Condonado'])) {
                        // Si NO es baja y ya se resolvió el pago, vuelve como devolución limpia
                        DB::table('prestamos')
                            ->where('Prestamo_ID', $detalle->Prestamo_ID)
                            ->update([
                                'EstadoPrestamo' => 'Devuelto',
                                'updated_at' => now()
                            ]);

                        DB::table('inventario_unidades')
                            ->where('Unidad_ID', $detalle->Unidad_ID)
                            ->update([
                                'EstadoDisponibilidad' => 'Disponible',
                                'updated_at' => now()
                            ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sanción registrada exitosamente',
                'data' => $sancion
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $sancion = Sancion::findOrFail($id);
            
            $request->validate([
            'Usuario_ID' => 'required|integer|exists:usuarios,Usuario_ID', 
            'DetallesPrestamo_ID' => 'nullable|integer|exists:detalles_prestamo,DetallesPrestamo_ID',
            'TipoSancion' => 'required|string|max:50',
            'MontoPago' => 'required|numeric|min:0',
            'EstadoSancion' => 'required|string|max:30',
            'FechaGeneracion' => 'required|date',
            'FechaPago' => 'nullable|date', // NUEVA VALIDACIÓN
            'Observaciones' => 'nullable|string|max:250',
        ]);

            $sancion->update([
                'Usuario_ID' => $request->Usuario_ID,
                'DetallesPrestamo_ID' => $request->DetallesPrestamo_ID,
                'TipoSancion' => $request->TipoSancion,
                'MontoPago' => $request->MontoPago,
                'EstadoSancion' => $request->EstadoSancion,
                'FechaGeneracion' => $request->FechaGeneracion,
                // NUEVO: Control de actualización de pago
                'FechaPago' => $request->EstadoSancion === 'Pagado' ? $request->FechaPago : null,
                'Observaciones' => $request->Observaciones,
            ]);

            if ($request->DetallesPrestamo_ID) {
                $detalle = DB::table('detalles_prestamo')->where('DetallesPrestamo_ID', $request->DetallesPrestamo_ID)->first();
                if ($detalle) {
                    $unidad = DB::table('inventario_unidades')->where('Unidad_ID', $detalle->Unidad_ID)->first();
                    
                    if ($unidad) {
                        // 1. SINCRONIZAR EL ESTADO DEL PRÉSTAMO
                        if ($unidad->EstadoDisponibilidad === 'Baja') {
                            // Si el libro ya es una baja en el sistema, el préstamo se queda cerrado por sanción obligatoriamente
                            DB::table('prestamos')
                                ->where('Prestamo_ID', $detalle->Prestamo_ID)
                                ->update(['EstadoPrestamo' => 'Finalizado (Sanción)', 'updated_at' => now()]);
                        } else {
                            if (in_array($request->EstadoSancion, ['Pagado', 'Condonado'])) {
                                DB::table('prestamos')
                                    ->where('Prestamo_ID', $detalle->Prestamo_ID)
                                    ->update(['EstadoPrestamo' => 'Devuelto', 'updated_at' => now()]);
                            } else if ($request->EstadoSancion === 'Pendiente') {
                                DB::table('prestamos')
                                    ->where('Prestamo_ID', $detalle->Prestamo_ID)
                                    ->update(['EstadoPrestamo' => 'Atrasado', 'updated_at' => now()]);
                            }
                        }

                        // 2. LA REVERSA: SINCRONIZAR EL INVENTARIO
                        if ($unidad->EstadoDisponibilidad !== 'Baja') {
                            if (in_array($request->EstadoSancion, ['Pagado', 'Condonado'])) {
                                DB::table('inventario_unidades')
                                    ->where('Unidad_ID', $detalle->Unidad_ID)
                                    ->update(['EstadoDisponibilidad' => 'Disponible', 'updated_at' => now()]);
                            } else if ($request->EstadoSancion === 'Pendiente') {
                                DB::table('inventario_unidades')
                                    ->where('Unidad_ID', $detalle->Unidad_ID)
                                    ->update(['EstadoDisponibilidad' => 'Prestado', 'updated_at' => now()]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'data' => $sancion], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Sancion::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Sanción eliminada'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}