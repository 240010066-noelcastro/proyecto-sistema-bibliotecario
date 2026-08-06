<?php

namespace App\Http\Controllers;

use App\Models\Sancion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Requests\StoreSancionRequest;
use App\Http\Requests\UpdateSancionRequest;

class SancionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            $filtroTipo = $request->input('filtroTipo');
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
                'inventario_unidades.EstadoDisponibilidad',
                /* 🏛️ ELÁSTICO: Traemos la raíz lógica para que la tabla unificada sepa si es una baja real */
                'inventario_unidades.EstadoDisponibilidad_Logico'
            );

            // Filtro por Tipo de Recurso
            if ($filtroTipo && $filtroTipo !== 'Todos') {
                $query->where('recursos_catalogo.TipoRecurso', $filtroTipo);
            }

            // NUEVO: Filtro Exacto de Baja guiado por la regla heredada
            if ($filtroBaja && $filtroBaja !== 'Todos') {
                if ($filtroBaja === 'Si') {
                    $query->where('inventario_unidades.EstadoDisponibilidad_Logico', 'Baja');
                } else if ($filtroBaja === 'No') {
                    $query->where('inventario_unidades.EstadoDisponibilidad_Logico', '!=', 'Baja');
                }
            }

            // BUSCADOR UNIFICADO INTELIGENTE (LATINO DD/MM/YYYY + RANGOS)
            $isRangeSearch = false;
            if ($search) {
                $search = trim($search);

                // 1. RANGO DE FECHAS: "13/07/2026 a 17/07/2026"
                if (preg_match('/^(\d{2}\/\d{2}\/\d{4})\s+a\s+(\d{2}\/\d{2}\/\d{4})$/i', $search, $matches)) {
                    $fInicio = \Carbon\Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
                    $fFin    = \Carbon\Carbon::createFromFormat('d/m/Y', $matches[2])->format('Y-m-d');

                    $query->whereBetween(DB::raw('DATE(sanciones.FechaGeneracion)'), [$fInicio, $fFin]);
                    $isRangeSearch = true;
                } 
                // 2. FECHA EXACTA EN FORMATO LATINO: "20/07/2026" (Día/Mes/Año)
                else if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $search, $m)) {
                    $fechaSql = "{$m[3]}-{$m[2]}-{$m[1]}";
                    $query->whereDate('sanciones.FechaGeneracion', $fechaSql);
                } 
                // 3. MES Y AÑO EN FORMATO LATINO: "07/2026" (Mes/Año)
                else if (preg_match('/^(\d{2})\/(\d{4})$/', $search, $m)) {
                    $mesAnioSql = "{$m[2]}-{$m[1]}";
                    $query->where('sanciones.FechaGeneracion', 'LIKE', "%{$mesAnioSql}%");
                } 
                // 4. BÚSQUEDA GENERAL
                else {
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
            }

            // Si se filtró por rango muestra primero la fecha más cercana; si no, ordena por ID descendente
            if ($isRangeSearch) {
                $query->orderBy('sanciones.FechaGeneracion', 'asc');
            } else {
                $query->orderBy('sanciones.Sancion_ID', 'desc');
            }

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
                ->whereIn('prestamos.EstadoPrestamo_Logico', ['Activo', 'Atrasado'])
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

    public function store(StoreSancionRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // 🏛️ MAPEAR ACCIÓN RAÍZ HEREDADA
            $estadoSancionLogico = $this->getLogicaRaiz('Sanciones', 'estados_sancion', $validated['EstadoSancion']);

            $sancion = Sancion::create([
                'Usuario_ID'           => $validated['Usuario_ID'],
                'DetallesPrestamo_ID'  => $validated['DetallesPrestamo_ID'] ?? null,
                'TipoSancion'          => $validated['TipoSancion'],
                'MontoPago'            => $validated['MontoPago'],
                'EstadoSancion'        => $validated['EstadoSancion'],
                'EstadoSancion_Logico' => $estadoSancionLogico,
                'FechaGeneracion'      => $validated['FechaGeneracion'],
                'FechaPago'            => $validated['EstadoSancion'] === 'Pagado' ? ($validated['FechaPago'] ?? null) : null,
                'Observaciones'        => $validated['Observaciones'] ?? null,
            ]);

            if (!empty($validated['DetallesPrestamo_ID'])) {
                $detalle = DB::table('detalles_prestamo')->where('DetallesPrestamo_ID', $validated['DetallesPrestamo_ID'])->first();
                if ($detalle) {
                    if (!empty($validated['DarDeBaja'])) {
                        $estadoFisico = ($validated['TipoSancion'] === 'Material Extraviado') ? 'Extraviado' : 'Malo / Dañado';

                        DB::table('inventario_unidades')
                            ->where('Unidad_ID', $detalle->Unidad_ID)
                            ->update([
                                'EstadoDisponibilidad'        => $this->getVisualPorDefecto('Inventario', 'Baja', 'Baja'), 
                                'EstadoDisponibilidad_Logico' => 'Baja', 
                                'EstadoFisicoInicial'          => $estadoFisico,
                                'updated_at'                  => now()
                            ]);

                        DB::table('prestamos')
                            ->where('Prestamo_ID', $detalle->Prestamo_ID)
                            ->update([
                                'EstadoPrestamo'        => $this->getVisualPorDefecto('Prestamos', 'Finalizado (Sanción)', 'Finalizado (Sanción)'), 
                                'EstadoPrestamo_Logico' => 'Finalizado (Sanción)', 
                                'updated_at'            => now()
                            ]);
                    } else if (in_array($estadoSancionLogico, ['Pagado', 'Condonado'])) {
                        DB::table('prestamos')
                            ->where('Prestamo_ID', $detalle->Prestamo_ID)
                            ->update([
                                'EstadoPrestamo'        => $this->getVisualPorDefecto('Prestamos', 'Devuelto', 'Devuelto'), 
                                'EstadoPrestamo_Logico' => 'Devuelto', 
                                'updated_at'            => now()
                            ]);

                        DB::table('inventario_unidades')
                            ->where('Unidad_ID', $detalle->Unidad_ID)
                            ->update([
                                'EstadoDisponibilidad'        => $this->getVisualPorDefecto('Inventario', 'Disponible', 'Disponible'), 
                                'EstadoDisponibilidad_Logico' => 'Disponible', 
                                'updated_at'                  => now()
                            ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sanción registrada exitosamente',
                'data'    => $sancion
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateSancionRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $sancion = Sancion::findOrFail($id);
            $validated = $request->validated();

            // 🏛️ MAPEAR ACCIÓN RAÍZ HEREDADA
            $estadoSancionLogico = $this->getLogicaRaiz('Sanciones', 'estados_sancion', $validated['EstadoSancion']);

            $sancion->update([
                'Usuario_ID'           => $validated['Usuario_ID'],
                'DetallesPrestamo_ID'  => $validated['DetallesPrestamo_ID'] ?? null,
                'TipoSancion'          => $validated['TipoSancion'],
                'MontoPago'            => $validated['MontoPago'],
                'EstadoSancion'        => $validated['EstadoSancion'],
                'EstadoSancion_Logico' => $estadoSancionLogico,
                'FechaGeneracion'      => $validated['FechaGeneracion'],
                'FechaPago'            => $validated['EstadoSancion'] === 'Pagado' ? ($validated['FechaPago'] ?? null) : null,
                'Observaciones'        => $validated['Observaciones'] ?? null,
            ]);

            if (!empty($validated['DetallesPrestamo_ID'])) {
                $detalle = DB::table('detalles_prestamo')->where('DetallesPrestamo_ID', $validated['DetallesPrestamo_ID'])->first();
                if ($detalle) {
                    $unidad = DB::table('inventario_unidades')->where('Unidad_ID', $detalle->Unidad_ID)->first();
                    
                    if ($unidad) {
                        // 1. SINCRONIZAR EL ESTADO DEL PRÉSTAMO
                        if ($unidad->EstadoDisponibilidad_Logico === 'Baja') {
                            DB::table('prestamos')
                                ->where('Prestamo_ID', $detalle->Prestamo_ID)
                                ->update([
                                    'EstadoPrestamo'        => $this->getVisualPorDefecto('Prestamos', 'Finalizado (Sanción)', 'Finalizado (Sanción)'), 
                                    'EstadoPrestamo_Logico' => 'Finalizado (Sanción)', 
                                    'updated_at'            => now()
                                ]);
                        } else {
                            if (in_array($estadoSancionLogico, ['Pagado', 'Condonado'])) {
                                DB::table('prestamos')
                                    ->where('Prestamo_ID', $detalle->Prestamo_ID)
                                    ->update([
                                        'EstadoPrestamo'        => $this->getVisualPorDefecto('Prestamos', 'Devuelto', 'Devuelto'), 
                                        'EstadoPrestamo_Logico' => 'Devuelto', 
                                        'updated_at'            => now()
                                    ]);
                            } else if ($estadoSancionLogico === 'Pendiente') {
                                DB::table('prestamos')
                                    ->where('Prestamo_ID', $detalle->Prestamo_ID)
                                    ->update([
                                        'EstadoPrestamo'        => $this->getVisualPorDefecto('Prestamos', 'Atrasado', 'Atrasado'), 
                                        'EstadoPrestamo_Logico' => 'Atrasado', 
                                        'updated_at'            => now()
                                    ]);
                            }
                        }

                        // 2. LA REVERSA: SINCRONIZAR EL INVENTARIO
                        if ($unidad->EstadoDisponibilidad_Logico !== 'Baja') {
                            if (in_array($estadoSancionLogico, ['Pagado', 'Condonado'])) {
                                DB::table('inventario_unidades')
                                    ->where('Unidad_ID', $detalle->Unidad_ID)
                                    ->update([
                                        'EstadoDisponibilidad'        => $this->getVisualPorDefecto('Inventario', 'Disponible', 'Disponible'), 
                                        'EstadoDisponibilidad_Logico' => 'Disponible', 
                                        'updated_at'                  => now()
                                    ]);
                            } else if ($estadoSancionLogico === 'Pendiente') {
                                DB::table('inventario_unidades')
                                    ->where('Unidad_ID', $detalle->Unidad_ID)
                                    ->update([
                                        'EstadoDisponibilidad'        => $this->getVisualPorDefecto('Inventario', 'Prestado', 'Prestado'), 
                                        'EstadoDisponibilidad_Logico' => 'Prestado', 
                                        'updated_at'                  => now()
                                    ]);
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

    // 🏛️ EXTRACTOR DE HERENCIA DE REGLAS DE NEGOCIO
    private function getLogicaRaiz($modulo, $clave, $valorVisual)
    {
        $config = DB::table('configuraciones_sistema')->where('Modulo', $modulo)->where('Clave', $clave)->first();
        if ($config) {
            $items = json_decode($config->Valor, true);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (($item['label'] ?? '') === $valorVisual) {
                        return $item['action'] ?? $item['label'];
                    }
                }
            }
        }
        return $valorVisual;
    }

    // 🏛️ EXTRACTOR POLIMÓRFICO DE VALORES VISUALES POR ACCIÓN LÓGICA BASE
    private function getVisualPorDefecto($tipoModulo, $logicaRaiz, $valorBase)
    {
        if ($tipoModulo === 'Inventario') {
            $sufijoClave = 'defecto_disp_';
        } elseif ($tipoModulo === 'Prestamos') {
            $sufijoClave = 'defecto_prestamo_';
        } else {
            $sufijoClave = 'defecto_sancion_';
        }

        $config = DB::table('configuraciones_sistema')
            ->where('Modulo', 'Catalogo')
            ->where('Clave', $sufijoClave . $logicaRaiz)
            ->first();
            
        return $config ? $config->Valor : $valorBase;
    }
}