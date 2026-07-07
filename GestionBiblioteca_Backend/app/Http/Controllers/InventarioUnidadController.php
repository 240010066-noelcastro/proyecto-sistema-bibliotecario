<?php

namespace App\Http\Controllers;

use App\Models\InventarioUnidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioUnidadController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            $filtroBaja = $request->input('filtroBaja'); // Se recibe la variable del React

            $query = DB::table('inventario_unidades')
                ->join('recursos_catalogo', 'inventario_unidades.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
                ->select(
                    'inventario_unidades.Unidad_ID',
                    'inventario_unidades.Recurso_ID',
                    'inventario_unidades.EstadoFisicoInicial',
                    'inventario_unidades.EstadoDisponibilidad',
                    'recursos_catalogo.Titulo',
                    'recursos_catalogo.TipoRecurso'
                );

            // LOGICA BLINDADA: Filtro explícito de Bajas (Sin choques con nombres)
            if ($filtroBaja && $filtroBaja !== 'Todos') {
                if ($filtroBaja === 'Si') {
                    $query->where('inventario_unidades.EstadoDisponibilidad', 'Baja');
                } else if ($filtroBaja === 'No') {
                    $query->where('inventario_unidades.EstadoDisponibilidad', '!=', 'Baja');
                }
            } else if (!$filtroBaja) {
                // Seguridad: si no manda nada, ocultamos las bajas
                $query->where('inventario_unidades.EstadoDisponibilidad', '!=', 'Baja');
            }

            // BUSCADOR LIBRE DE ERRORES (Busca texto, pero respeta el filtro anterior de bajas)
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('inventario_unidades.Unidad_ID', 'LIKE', "%{$search}%")
                      ->orWhere('recursos_catalogo.Titulo', 'LIKE', "%{$search}%")
                      ->orWhere('recursos_catalogo.TipoRecurso', 'LIKE', "%{$search}%")
                      ->orWhere('inventario_unidades.EstadoFisicoInicial', 'LIKE', "%{$search}%")
                      ->orWhere('inventario_unidades.EstadoDisponibilidad', 'LIKE', "%{$search}%");
                });
            }

            if ($request->has('all')) {
                return response()->json(['success' => true, 'data' => $query->get()]);
            }

            return response()->json(['success' => true, 'data' => $query->paginate(6)]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'Recurso_ID' => 'required|integer|exists:recursos_catalogo,Recurso_ID', 
                'EstadoFisicoInicial' => 'required|string|max:30',
                'EstadoDisponibilidad' => 'required|string|max:30',
            ]);

            $unidad = InventarioUnidad::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Unidad registrada en inventario',
                'data' => $unidad
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $unidad = InventarioUnidad::findOrFail($id);
            
            $request->validate([
                'Recurso_ID' => 'required|integer|exists:recursos_catalogo,Recurso_ID', 
                'EstadoFisicoInicial' => 'required|string|max:30',
                'EstadoDisponibilidad' => 'required|string|max:30',
            ]);

            $unidad->update($request->except('Unidad_ID'));

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
            ->select('inventario_unidades.Unidad_ID', 'inventario_unidades.EstadoDisponibilidad', 'inventario_unidades.Recurso_ID', 'recursos_catalogo.Titulo')
            ->where('inventario_unidades.EstadoDisponibilidad', '!=', 'Baja')
            ->where(function($q) use ($term) {
                $q->where('inventario_unidades.Unidad_ID', 'LIKE', "%{$term}%")
                  ->orWhere('recursos_catalogo.Titulo', 'LIKE', "%{$term}%");
            })
            ->limit(10)
            ->get();

        return response()->json(['data' => $resultados]);
    }
}