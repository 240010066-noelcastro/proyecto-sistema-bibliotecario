<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaNoAdeudo;
use Illuminate\Http\Request;

class ConstanciaNoAdeudoController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => ConstanciaNoAdeudo::all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Usuario_ID' => 'required|integer',
            'Personal_ID' => 'required|integer',
            'FechaEmision' => 'required|date', 
            'FolioDigital' => 'required|string|max:50|unique:constancias_no_adeudo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Constancia registrada con éxito',
            'data' => ConstanciaNoAdeudo::create($request->all())
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $constancia = ConstanciaNoAdeudo::findOrFail($id);

        $request->validate([
            'Usuario_ID' => 'required|integer',
            'Personal_ID' => 'required|integer',
            'FechaEmision' => 'required|date',
            // El truco experto: unique:tabla,columna,id_a_ignorar,nombre_llave_primaria
            'FolioDigital' => 'required|string|max:50|unique:constancias_no_adeudo,FolioDigital,' . $id . ',ConstanciaID',
        ]);

        $constancia->update($request->all());

        return response()->json(['success' => true, 'data' => $constancia], 200);
    }

    public function destroy($id)
    {
        ConstanciaNoAdeudo::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Constancia eliminada'], 200);
    }
}