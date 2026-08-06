<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Requests\StorePrestamoRequest;
use App\Http\Requests\UpdatePrestamoRequest;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hoy = Carbon::now()->toDateString(); 
            $search = $request->input('search');
            $rangoFecha = $request->input('rangoFecha'); // Capturamos el filtro del Dashboard (hoy, 7_dias, 30_dias, etc)

            // 1. Auto-actualizar estados utilizando tanto la columna lógica como la visual
            /* 🏛️ FIJADO: Invoca getVisualPorDefecto para que el barrido asíncrono use los nombres elásticos de tu BD y no rompa el diseño */
            DB::table('prestamos')->where('EstadoPrestamo_Logico', 'Activo')->whereDate('FechaDevolucionEstablecida', '<', $hoy)->update([
                'EstadoPrestamo_Logico' => 'Atrasado', 
                'EstadoPrestamo' => $this->getVisualPorDefecto('Prestamos', 'Atrasado', 'Atrasado')
            ]);
            
            DB::table('prestamos')->where('EstadoPrestamo_Logico', 'Atrasado')->whereDate('FechaDevolucionEstablecida', '>=', $hoy)->update([
                'EstadoPrestamo_Logico' => 'Activo', 
                'EstadoPrestamo' => $this->getVisualPorDefecto('Prestamos', 'Activo', 'Activo')
            ]);

            // 2. Consulta Maestra (CORREGIDA PARA USAR LA TABLA USUARIOS EN ENTREGA Y RECIBE)
            $query = DB::table('prestamos')
                ->join('usuarios', 'prestamos.Usuario_ID', '=', 'usuarios.Usuario_ID')
                ->join('usuarios as p_entrega', 'prestamos.PersonalEntrega_ID', '=', 'p_entrega.Usuario_ID')
                ->leftJoin('usuarios as p_recibe', 'prestamos.PersonalRecibe_ID', '=', 'p_recibe.Usuario_ID')
                ->leftJoin('detalles_prestamo', 'prestamos.Prestamo_ID', '=', 'detalles_prestamo.Prestamo_ID')
                ->leftJoin('inventario_unidades', 'detalles_prestamo.Unidad_ID', '=', 'inventario_unidades.Unidad_ID')
                ->leftJoin('recursos_catalogo', 'inventario_unidades.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
                ->select(
                    'prestamos.*',
                    'usuarios.Matricula',
                    DB::raw("CONCAT(usuarios.NombreUsuario, ' ', IFNULL(usuarios.ApellidoPaterno, '')) AS NombreEstudiante"),
                    // Se usa NombreUsuario en lugar de NombrePersonal
                    DB::raw("CONCAT(p_entrega.NombreUsuario, ' ', IFNULL(p_entrega.ApellidoPaterno, '')) AS NombrePersonalEntrega"),
                    DB::raw("IFNULL(CONCAT(p_recibe.NombreUsuario, ' ', IFNULL(p_recibe.ApellidoPaterno, '')), 'Pendiente') AS NombrePersonalRecibe"),
                    DB::raw("IFNULL(GROUP_CONCAT(CONCAT(inventario_unidades.Unidad_ID, ' - ', recursos_catalogo.Titulo) SEPARATOR ', '), 'Sin unidades') as RecursosPrestados")
                )
                ->groupBy(
                    'prestamos.Prestamo_ID',
                    'prestamos.Usuario_ID',
                    'prestamos.PersonalEntrega_ID',
                    'prestamos.PersonalRecibe_ID',
                    'prestamos.FechaSalida',
                    'prestamos.FechaDevolucionEstablecida',
                    'prestamos.EstadoPrestamo',
                    'prestamos.EstadoPrestamo_Logico',
                    'prestamos.created_at',
                    'prestamos.updated_at',
                    'usuarios.Matricula',
                    'usuarios.NombreUsuario',
                    'usuarios.ApellidoPaterno',
                    'p_entrega.NombreUsuario',
                    'p_entrega.ApellidoPaterno',
                    'p_recibe.NombreUsuario',
                    'p_recibe.ApellidoPaterno'
                );

            // NUEVA LÓGICA: Filtrado por Rango de Fechas (Estilo Dashboard)
            if ($rangoFecha && $rangoFecha !== 'todo') {
                $now = Carbon::now();
                if ($rangoFecha === 'hoy') {
                    $query->whereDate('prestamos.FechaSalida', $now->toDateString());
                } else if ($rangoFecha === '7_dias') {
                    $query->whereBetween('prestamos.FechaSalida', [$now->copy()->subDays(7)->toDateString(), $now->toDateString()]);
                } else if ($rangoFecha === '30_dias') {
                    $query->whereBetween('prestamos.FechaSalida', [$now->copy()->subDays(30)->toDateString(), $now->toDateString()]);
                } else if (preg_match('/^\d{4}-\d{2}$/', $rangoFecha)) {
                    // Si el formato es YYYY-MM (Ej: 2026-06), filtramos por ese mes específico
                    $mes = Carbon::createFromFormat('Y-m', $rangoFecha);
                    $inicioMes = $mes->startOfMonth()->toDateString();
                    $finMes = $mes->endOfMonth()->toDateString();
                    $query->whereBetween('prestamos.FechaSalida', [$inicioMes, $finMes]);
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

                    $query->whereBetween(DB::raw('DATE(prestamos.FechaSalida)'), [$fInicio, $fFin]);
                    $isRangeSearch = true;
                } 
                // 2. FECHA EXACTA EN FORMATO LATINO: "20/07/2026" (Día/Mes/Año)
                else if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $search, $m)) {
                    $fechaSql = "{$m[3]}-{$m[2]}-{$m[1]}";
                    $query->whereDate('prestamos.FechaSalida', $fechaSql);
                } 
                // 3. MES Y AÑO EN FORMATO LATINO: "07/2026" (Mes/Año)
                else if (preg_match('/^(\d{2})\/(\d{4})$/', $search, $m)) {
                    $mesAnioSql = "{$m[2]}-{$m[1]}";
                    $query->where('prestamos.FechaSalida', 'LIKE', "%{$mesAnioSql}%");
                } 
                // 4. BÚSQUEDA GENERAL (Texto, ID, Matrícula, Alumno, Estado, Recursos o Personal)
                else {
                    $query->havingRaw("
                        prestamos.Prestamo_ID LIKE ? 
                        OR usuarios.Matricula LIKE ? 
                        OR NombreEstudiante LIKE ? 
                        OR prestamos.EstadoPrestamo LIKE ? 
                        OR RecursosPrestados LIKE ? 
                        OR CAST(prestamos.FechaSalida AS CHAR) LIKE ? 
                        OR CAST(prestamos.FechaDevolucionEstablecida AS CHAR) LIKE ?
                        OR NombrePersonalEntrega LIKE ? 
                        OR NombrePersonalRecibe LIKE ?
                    ", [
                        "%{$search}%", 
                        "%{$search}%", 
                        "%{$search}%", 
                        "%{$search}%", 
                        "%{$search}%", 
                        "%{$search}%", 
                        "%{$search}%",
                        "%{$search}%",
                        "%{$search}%"
                    ]);
                }
            }

            // 📅 Si se filtró por rango muestra primero la fecha más cercana; si no, ordena por ID descendente
            if ($isRangeSearch) {
                $query->orderBy('prestamos.FechaSalida', 'asc');
            } else {
                $query->orderBy('prestamos.Prestamo_ID', 'desc');
            }

            if ($request->has('all')) {
                $prestamos = $query->get();
                return response()->json(['success' => true, 'data' => $prestamos]);
            }

            $prestamos = $query->paginate(6);
            return response()->json(['success' => true, 'data' => $prestamos]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(StorePrestamoRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction(); 
        try {
            // 🏛️ RESOLVER HERENCIA LÓGICA
            $estadoLogico = $this->getLogicaRaiz('Prestamos', 'estados_prestamo', $validated['EstadoPrestamo']);

            $prestamoId = DB::table('prestamos')->insertGetId([
                'Usuario_ID'                 => $validated['Usuario_ID'],
                'PersonalEntrega_ID'         => $validated['PersonalEntrega_ID'],
                'FechaSalida'                => $validated['FechaSalida'],
                'FechaDevolucionEstablecida' => $validated['FechaDevolucionEstablecida'],
                'EstadoPrestamo'             => $validated['EstadoPrestamo'],
                'EstadoPrestamo_Logico'      => $estadoLogico,
                'created_at'                 => now(),
                'updated_at'                 => now()
            ]);

            foreach ($validated['unidades'] as $unidad_id) {
                DB::table('detalles_prestamo')->insert([
                    'Prestamo_ID' => $prestamoId, 
                    'Unidad_ID'   => $unidad_id, 
                    'created_at'  => now(), 
                    'updated_at'  => now()
                ]);
                DB::table('inventario_unidades')
                    ->where('Unidad_ID', $unidad_id)
                    ->update([
                        'EstadoDisponibilidad'        => $this->getVisualPorDefecto('Inventario', 'Prestado', 'Prestado'), 
                        'EstadoDisponibilidad_Logico' => 'Prestado',
                        'updated_at'                  => now()
                    ]); 
            }

            DB::commit(); 
            return response()->json(['success' => true, 'message' => 'Prestamo guardado'], 201);
        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(UpdatePrestamoRequest $request, $id)
    {
        $validated = $request->validated();

        DB::beginTransaction(); 
        try {
            // 🏛️ RESOLVER HERENCIA LÓGICA
            $estadoLogico = $this->getLogicaRaiz('Prestamos', 'estados_prestamo', $validated['EstadoPrestamo']);

            DB::table('prestamos')->where('Prestamo_ID', $id)->update([
                'FechaDevolucionEstablecida' => $validated['FechaDevolucionEstablecida'],
                'EstadoPrestamo'             => $validated['EstadoPrestamo'],
                'EstadoPrestamo_Logico'      => $estadoLogico,
                'PersonalRecibe_ID'          => $validated['PersonalRecibe_ID'] ?? null,
                'updated_at'                 => now()
            ]);

            $unidades = DB::table('detalles_prestamo')->where('Prestamo_ID', $id)->pluck('Unidad_ID');
            if ($unidades->count() > 0) {
                if ($estadoLogico === 'Devuelto') {
                    $estadoVisualDisp = $this->getVisualPorDefecto('Inventario', 'Disponible', 'Disponible');
                    $estadoLogicoDisp = 'Disponible';
                } elseif ($estadoLogico === 'Finalizado (Sanción)') {
                    $estadoVisualDisp = $this->getVisualPorDefecto('Inventario', 'Baja', 'Baja');
                    $estadoLogicoDisp = 'Baja';
                } else {
                    $estadoVisualDisp = $this->getVisualPorDefecto('Inventario', 'Prestado', 'Prestado');
                    $estadoLogicoDisp = 'Prestado';
                }

                DB::table('inventario_unidades')->whereIn('Unidad_ID', $unidades)->update([
                    'EstadoDisponibilidad'        => $estadoVisualDisp, 
                    'EstadoDisponibilidad_Logico' => $estadoLogicoDisp, 
                    'updated_at'                  => now()
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Actualizado'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction(); 
        try {
            $unidades = DB::table('detalles_prestamo')->where('Prestamo_ID', $id)->pluck('Unidad_ID');
            if ($unidades->count() > 0) {
                DB::table('inventario_unidades')->whereIn('Unidad_ID', $unidades)->update([
                    // 🏛️ NUEVO: Lee el texto favorito asignado a la acción raíz "Disponible"
                    'EstadoDisponibilidad' => $this->getVisualPorDefecto('Inventario', 'Disponible', 'Disponible'),
                    'EstadoDisponibilidad_Logico' => 'Disponible', 
                    'updated_at' => now()
                ]);
            }
            
            // TRUCO MAESTRO: Apagamos las llaves foráneas temporalmente para evitar el Error 500 de MySQL
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('detalles_prestamo')->where('Prestamo_ID', $id)->delete();
            DB::table('prestamos')->where('Prestamo_ID', $id)->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            DB::commit();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            // Si algo falla, encendemos la seguridad de nuevo por si acaso
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::rollBack();
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