<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use Illuminate\Http\Request;

class CarreraController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Carrera::query();

            // 1. Lógica para la barra de búsqueda en tiempo real
            if ($request->has('search') && !empty($request->search)) {
                $search = trim($request->search);

                // Verificamos si lo que escribió el usuario es EXACTAMENTE una sigla (ej. "AD" o "TIID")
                $esSiglaExacta = \App\Models\Carrera::where('Siglas', strtoupper($search))->exists();

                if ($esSiglaExacta) {
                    // Si encontró la sigla exacta, obligamos a que solo devuelva esa
                    $query->where('Siglas', strtoupper($search));
                } else {
                    // Si no es una sigla (ej. escribió "Ingeniería"), hace la búsqueda normal por similitud
                    $query->where(function($q) use ($search) {
                        $q->where('NombreCarrera', 'LIKE', "%{$search}%")
                          ->orWhere('Siglas', 'LIKE', "%{$search}%");
                    });
                }
            }

            // 2. Si React pide todos los datos (para Excel/PDF)
            if ($request->has('all')) {
                return response()->json(['success' => true, 'data' => $query->get()]);
            }

            // 3. Paginación de 6 en 6
            $carreras = $query->paginate(6);
            return response()->json(['success' => true, 'data' => $carreras]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'NombreCarrera' => 'required|string|max:150', 
            'Siglas' => 'required|string|max:10',
        ]);

        $carrera = Carrera::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Carrera creada con éxito',
            'data' => $carrera
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $carrera = Carrera::findOrFail($id);

        $request->validate([
            'NombreCarrera' => 'required|string|max:150', // <--- Ya corregido a 150
            'Siglas' => 'required|string|max:10',
        ]);

        $carrera->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Carrera actualizada con éxito',
            'data' => $carrera
        ], 200);
    }

    public function destroy($id)
    {
        $carrera = Carrera::findOrFail($id);
        $carrera->delete();

        return response()->json([
            'success' => true,
            'message' => 'Carrera eliminada con éxito'
        ], 200);
    }
}