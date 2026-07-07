<?php

namespace App\Http\Controllers;

use App\Models\BitacoraBajaInventario;
use Illuminate\Http\Request;

class BitacoraBajaInventarioController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => BitacoraBajaInventario::all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Unidad_ID' => 'required|string|max:50',
            'Personal_ID' => 'required|integer',
            'MotivoBaja' => 'required|string|max:50', 
            'Comentarios' => 'nullable|string|max:250',
            'FechaBaja' => 'required|date',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Baja registrada con éxito',
            'data' => BitacoraBajaInventario::create($request->all())
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $baja = BitacoraBajaInventario::findOrFail($id);
        
        $request->validate([
            'Unidad_ID' => 'required|string|max:50',
            'Personal_ID' => 'required|integer',
            'MotivoBaja' => 'required|string|max:50',
            'Comentarios' => 'nullable|string|max:250',
            'FechaBaja' => 'required|date',
        ]);

        $baja->update($request->all());

        return response()->json(['success' => true, 'data' => $baja], 200);
    }

    public function destroy($id)
    {
        BitacoraBajaInventario::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Registro eliminado'], 200);
    }
}