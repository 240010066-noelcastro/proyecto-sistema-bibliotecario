<?php

namespace App\Http\Controllers;

use App\Models\InventarioUnidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreInventarioRequest;
use App\Http\Requests\UpdateInventarioRequest;

class InventarioUnidadController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            $filtroBaja = $request->input('filtroBaja');

            $query = DB::table('inventario_unidades')
                ->join('recursos_catalogo', 'inventario_unidades.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
                ->select(
                    'inventario_unidades.Unidad_ID',
                    'inventario_unidades.Recurso_ID',
                    'inventario_unidades.EstadoFisicoInicial',
                    'inventario_unidades.EstadoDisponibilidad',
                    'inventario_unidades.EstadoDisponibilidad_Logico', 
                    'inventario_unidades.created_at',
                    'recursos_catalogo.Titulo',
                    'recursos_catalogo.TipoRecurso'
                );

            // LOGICA BLINDADA: Filtro explícito de Bajas guiado por la columna lógica interna
            if ($filtroBaja && $filtroBaja !== 'Todos') {
                if ($filtroBaja === 'Si') {
                    $query->where('inventario_unidades.EstadoDisponibilidad_Logico', 'Baja');
                } else if ($filtroBaja === 'No') {
                    $query->where('inventario_unidades.EstadoDisponibilidad_Logico', '!=', 'Baja');
                }
            } else if (!$filtroBaja) {
                $query->where('inventario_unidades.EstadoDisponibilidad_Logico', '!=', 'Baja');
            }

            // BUSCADOR UNIFICADO INTELIGENTE (LATINO DD/MM/YYYY)
            if ($search) {
                $search = trim($search);

                // 1. RANGO DE FECHAS: "13/07/2026 a 17/07/2026"
                if (preg_match('/^(\d{2}\/\d{2}\/\d{4})\s+a\s+(\d{2}\/\d{2}\/\d{4})$/i', $search, $matches)) {
                    $fInicio = \Carbon\Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
                    $fFin    = \Carbon\Carbon::createFromFormat('d/m/Y', $matches[2])->format('Y-m-d');

                    $query->whereBetween(DB::raw('DATE(inventario_unidades.created_at)'), [$fInicio, $fFin])
                          ->orderBy('inventario_unidades.created_at', 'asc'); // 📅 Fecha más próxima primero
                } 
                // 2. FECHA EXACTA EN FORMATO LATINO: "20/07/2026" (Día/Mes/Año)
                else if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $search, $m)) {
                    $fechaSql = "{$m[3]}-{$m[2]}-{$m[1]}"; // Se convierte a YYYY-MM-DD para MySQL
                    $query->whereDate('inventario_unidades.created_at', $fechaSql);
                } 
                // 3. MES Y AÑO EN FORMATO LATINO: "07/2026" (Mes/Año)
                else if (preg_match('/^(\d{2})\/(\d{4})$/', $search, $m)) {
                    $mesAnioSql = "{$m[2]}-{$m[1]}"; // Se convierte a YYYY-MM para MySQL
                    $query->where('inventario_unidades.created_at', 'LIKE', "%{$mesAnioSql}%");
                } 
                // 4. BÚSQUEDA GENERAL (Texto, Código, Título o Año solo "2026")
                else {
                    $query->where(function($q) use ($search) {
                        $q->where('inventario_unidades.Unidad_ID', 'LIKE', "%{$search}%")
                          ->orWhere('recursos_catalogo.Titulo', 'LIKE', "%{$search}%")
                          ->orWhere('recursos_catalogo.TipoRecurso', 'LIKE', "%{$search}%")
                          ->orWhere('inventario_unidades.EstadoFisicoInicial', 'LIKE', "%{$search}%")
                          ->orWhere('inventario_unidades.EstadoDisponibilidad', 'LIKE', "%{$search}%")
                          ->orWhere('inventario_unidades.created_at', 'LIKE', "%{$search}%");
                    });
                }
            }

            if ($request->has('all')) {
                return response()->json(['success' => true, 'data' => $query->get()]);
            }

            return response()->json(['success' => true, 'data' => $query->paginate(6)]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(StoreInventarioRequest $request)
    {
        try {
            $validated = $request->validated();

            // 🏛️ MAPEAR ACCIÓN RAÍZ HEREDADA
            $estadoLogico = $this->getLogicaRaiz('Inventario', 'disponibilidades', $validated['EstadoDisponibilidad']);

            $validated['EstadoDisponibilidad_Logico'] = $estadoLogico;

            $unidad = InventarioUnidad::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Unidad registrada en inventario',
                'data'    => $unidad
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateInventarioRequest $request, $id)
    {
        try {
            $unidad = InventarioUnidad::findOrFail($id);
            $validated = $request->validated();

            // 🏛️ MAPEAR ACCIÓN RAÍZ HEREDADA
            $estadoLogico = $this->getLogicaRaiz('Inventario', 'disponibilidades', $validated['EstadoDisponibilidad']);

            $validated['EstadoDisponibilidad_Logico'] = $estadoLogico;

            $unidad->update($validated);

            return response()->json(['success' => true, 'data' => $unidad], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            InventarioUnidad::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Unidad eliminada'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function buscarVivo(Request $request)
    {
        $term = $request->input('term');
        if (!$term) return response()->json(['data' => []]);

        // Evitar sugerir libros dados de baja en el buscador en vivo (Préstamos no lo verá)
        $resultados = DB::table('inventario_unidades')
            ->join('recursos_catalogo', 'inventario_unidades.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
            /* 🏛️ NUEVO: Añadida la columna EstadoDisponibilidad_Logico a la consulta */
            ->select('inventario_unidades.Unidad_ID', 'inventario_unidades.EstadoDisponibilidad', 'inventario_unidades.EstadoDisponibilidad_Logico', 'inventario_unidades.Recurso_ID', 'recursos_catalogo.Titulo')
            ->where('inventario_unidades.EstadoDisponibilidad_Logico', '!=', 'Baja')
            ->where(function($q) use ($term) {
                $q->where('inventario_unidades.Unidad_ID', 'LIKE', "%{$term}%")
                  ->orWhere('recursos_catalogo.Titulo', 'LIKE', "%{$term}%");
            })
            ->limit(10)
            ->get();

        return response()->json(['data' => $resultados]);
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
}