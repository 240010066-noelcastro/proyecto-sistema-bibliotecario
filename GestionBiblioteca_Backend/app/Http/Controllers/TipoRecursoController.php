<?php

namespace App\Http\Controllers;

use App\Models\TipoRecurso;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTipoRecursoRequest;
use App\Http\Requests\UpdateTipoRecursoRequest;

class TipoRecursoController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => TipoRecurso::all()]);
    }

    public function store(StoreTipoRecursoRequest $request)
    {
        $validated = $request->validated();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de recurso registrado exitosamente',
            'data' => TipoRecurso::create($validated)
        ], 201);
    }

    public function update(UpdateTipoRecursoRequest $request, $id)
    {
        $tipo = TipoRecurso::findOrFail($id);
        $validated = $request->validated();

        $tipo->update($validated);

        return response()->json(['success' => true, 'data' => $tipo], 200);
    }

    public function destroy($id)
    {
        TipoRecurso::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Tipo de recurso eliminado'], 200);
    }
}