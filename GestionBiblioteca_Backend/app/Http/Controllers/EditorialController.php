<?php

namespace App\Http\Controllers;

use App\Models\Editorial;
use Illuminate\Http\Request;

class EditorialController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Editorial::query();

            // 1. Lógica para la barra de búsqueda en tiempo real
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('NombreEditorial', 'LIKE', "%{$search}%")
                      ->orWhere('RazonSocial', 'LIKE', "%{$search}%")
                      ->orWhere('ISBN_Editorial', 'LIKE', "%{$search}%")
                      ->orWhere('Email', 'LIKE', "%{$search}%") /* <--- ¡ESTA LÍNEA FALTABA! */
                      ->orWhere('PaisEditorial', 'LIKE', "%{$search}%");
                });
            }

            // 2. Si React pide todos los datos (para Excel/PDF)
            if ($request->has('all')) {
                return response()->json(['success' => true, 'data' => $query->get()]);
            }

            // 3. Paginación de 6 en 6
            $editoriales = $query->paginate(6);
            return response()->json(['success' => true, 'data' => $editoriales]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'NombreEditorial' => 'required|string|max:150',
            'RazonSocial' => 'nullable|string|max:150', 
            'Email' => 'nullable|email|max:100',
            'ISBN_Editorial' => 'nullable|string|max:30',
            'DatosContacto' => 'nullable|string|max:250',
            'PaisEditorial' => 'nullable|string|max:100',
            'DireccionEditorial' => 'nullable|string|max:250',
            'Observaciones' => 'nullable|string|max:250'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Editorial creada con éxito',
            'data' => Editorial::create($request->all())
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $editorial = Editorial::where('Editorial_ID', $id)->firstOrFail();
        
        $request->validate([
            'NombreEditorial' => 'required|string|max:150',
            'RazonSocial' => 'nullable|string|max:150',
            'Email' => 'nullable|email|max:100',
            'ISBN_Editorial' => 'nullable|string|max:30',
            'DatosContacto' => 'nullable|string|max:250',
            'PaisEditorial' => 'nullable|string|max:100',
            'DireccionEditorial' => 'nullable|string|max:250',
            'Observaciones' => 'nullable|string|max:250'
        ]);

        $editorial->update($request->all());

        return response()->json(['success' => true, 'data' => $editorial], 200);
    }

    public function destroy($id)
    {
        Editorial::where('Editorial_ID', $id)->firstOrFail()->delete();
        return response()->json(['success' => true, 'message' => 'Editorial eliminada'], 200);
    }
}