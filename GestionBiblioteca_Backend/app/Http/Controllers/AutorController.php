<?php

namespace App\Http\Controllers;

use App\Models\Autor;
use Illuminate\Http\Request;

class AutorController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Autor::query();

            // 1. Lógica para la barra de búsqueda en tiempo real (Nombres completos y Tipo)
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('NombreAutor', 'LIKE', "%{$search}%")
                      ->orWhere('ApellidosAutor', 'LIKE', "%{$search}%")
                      // Este CONCAT pega Nombre y Apellido para que busque la cadena completa:
                      ->orWhereRaw("TRIM(CONCAT(IFNULL(NombreAutor,''), ' ', IFNULL(ApellidosAutor,''))) LIKE ?", ["%{$search}%"])
                      ->orWhere('Seudonimo', 'LIKE', "%{$search}%")
                      ->orWhere('TipoAutor', 'LIKE', "%{$search}%") // <--- Ya filtra por Personal o Corporativo
                      ->orWhere('Nacionalidad', 'LIKE', "%{$search}%")
                      ->orWhere('Email', 'LIKE', "%{$search}%")
                      ->orWhere('Telefono', 'LIKE', "%{$search}%");
                });
            }

            // 2. Si React pide todos los datos (para Excel/PDF)
            if ($request->has('all')) {
                return response()->json(['success' => true, 'data' => $query->get()]);
            }

            // 3. Paginación de 6 en 6
            $autores = $query->paginate(6);
            return response()->json(['success' => true, 'data' => $autores]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // VALIDACIONES COMPLETAS (Protege tu base de datos de textos muy largos)
        $request->validate([
            'NombreAutor' => 'required|string|max:100',
            'ApellidosAutor' => 'nullable|string|max:100',
            'Seudonimo' => 'nullable|string|max:100',
            'TipoAutor' => 'required|string|max:30',
            'Nacionalidad' => 'nullable|string|max:50',
            'Email' => 'nullable|email|max:100',
            'Telefono' => 'nullable|regex:/^[\d\+\-\s\(\)]+$/|max:20',
            'Bibliografia' => 'nullable|string' // En React debes mandar 'Bibliografia' en lugar de 'NotasBiograficas'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Autor creado con éxito',
            'data' => Autor::create($request->all())
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $autor = Autor::where('Autor_ID', $id)->firstOrFail();
        
        $request->validate([
            'NombreAutor' => 'required|string|max:100',
            'ApellidosAutor' => 'nullable|string|max:100',
            'Seudonimo' => 'nullable|string|max:100',
            'TipoAutor' => 'required|string|max:30',
            'Nacionalidad' => 'nullable|string|max:50',
            'Email' => 'nullable|email|max:100',
            'Telefono' => 'nullable|regex:/^[\d\+\-\s\(\)]+$/|max:20',
            'Bibliografia' => 'nullable|string'
        ]);

        $autor->update($request->all());

        return response()->json(['success' => true, 'data' => $autor], 200);
    }

    public function destroy($id)
    {
        Autor::where('Autor_ID', $id)->firstOrFail()->delete();
        return response()->json(['success' => true, 'message' => 'Autor eliminado'], 200);
    }
}