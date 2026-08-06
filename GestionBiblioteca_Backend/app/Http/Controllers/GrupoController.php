<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrupoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = DB::table('grupos')
                ->join('carreras', 'grupos.Carrera_ID', '=', 'carreras.Carrera_ID')
                ->select('grupos.*', 'carreras.NombreCarrera', 'carreras.Siglas');

            // 1. Lógica para la barra de búsqueda (Híbrida: exacta para siglas, flexible para nombres)
            if ($request->has('search') && !empty($request->search)) {
                $search = trim($request->search);

                // Verificamos si escribió EXACTAMENTE una sigla válida de alguna carrera
                $esSiglaExacta = \App\Models\Carrera::where('Siglas', strtoupper($search))->exists();

                if ($esSiglaExacta) {
                    // Si es una sigla exacta, obligamos a que solo traiga los grupos de esa carrera
                    $query->where('carreras.Siglas', strtoupper($search));
                } else {
                    // Si no es sigla (ej. escribió "1-1" o "Administración"), busca por coincidencia en todo
                    $query->where(function($q) use ($search) {
                        $q->where('grupos.NombreGrupo', 'LIKE', "%{$search}%")
                          ->orWhere('carreras.NombreCarrera', 'LIKE', "%{$search}%")
                          ->orWhere('carreras.Siglas', 'LIKE', "%{$search}%");
                    });
                }
            }

            // Filtro por Estado (Solo aplica si no es 'Todos')
            if ($request->has('estado') && !empty($request->estado) && $request->estado !== 'Todos') {
                $query->where('grupos.Estado', '=', $request->estado);
            }

            // 3. Si React pide todos los datos (para Excel/PDF)
            if ($request->has('all')) {
                return response()->json(['success' => true, 'data' => $query->get()]);
            }

            // 3. Paginación normal de 6 en 6
            $grupos = $query->paginate(6);
            return response()->json(['success' => true, 'data' => $grupos]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function gruposPublicos()
    {
        try {
            $grupos = DB::table('grupos')
                ->join('carreras', 'grupos.Carrera_ID', '=', 'carreras.Carrera_ID')
                ->select('grupos.*', 'carreras.NombreCarrera', 'carreras.Siglas')
                ->where('grupos.Estado', '=', 'Activo')
                ->get();

            return response()->json(['success' => true, 'data' => $grupos]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'NombreGrupo' => 'required|string|max:20',
                'Carrera_ID'  => 'required|integer|exists:carreras,Carrera_ID', 
                'Estado'      => 'required|string|in:Activo,Inactivo'
            ]);

            $grupo = Grupo::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Grupo creado con éxito',
                'data'    => $grupo
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $grupo = Grupo::findOrFail($id);
            
            $request->validate([
                'NombreGrupo' => 'required|string|max:20',
                'Carrera_ID'  => 'required|integer|exists:carreras,Carrera_ID', 
                'Estado'      => 'required|string|in:Activo,Inactivo'
            ]);

            $grupo->update($request->all());

            return response()->json(['success' => true, 'data' => $grupo], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Grupo::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Grupo eliminado'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}