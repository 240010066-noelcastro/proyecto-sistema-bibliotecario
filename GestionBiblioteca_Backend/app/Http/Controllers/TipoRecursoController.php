<?php

namespace App\Http\Controllers;

use App\Models\TipoRecurso;
use Illuminate\Http\Request;

class TipoRecursoController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => TipoRecurso::all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'NombreTipo' => 'required|string|max:50',
            'Descripcion' => 'nullable|string|max:250',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de recurso registrado exitosamente',
            'data' => TipoRecurso::create($request->all())
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $tipo = TipoRecurso::findOrFail($id);

        // El candado de validación idéntico al store
        $request->validate([
            'NombreTipo' => 'required|string|max:50',
            'Descripcion' => 'nullable|string|max:250',
        ]);

        $tipo->update($request->all());

        return response()->json(['success' => true, 'data' => $tipo], 200);
    }

    public function destroy($id)
    {
        TipoRecurso::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Tipo de recurso eliminado'], 200);
    }
}