<?php

namespace App\Http\Controllers;

use App\Models\DetallePrestamo;
use Illuminate\Http\Request;

class DetallePrestamoController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => DetallePrestamo::all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Prestamo_ID' => 'required|integer|exists:prestamos,Prestamo_ID',
            'Unidad_ID' => 'required|string|max:50|exists:inventario_unidades,Unidad_ID',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detalle de préstamo creado',
            'data' => DetallePrestamo::create($request->all())
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $detalle = DetallePrestamo::findOrFail($id);

        $request->validate([
            'Prestamo_ID' => 'required|integer|exists:prestamos,Prestamo_ID',
            'Unidad_ID' => 'required|string|max:50|exists:inventario_unidades,Unidad_ID',
        ]);

        $detalle->update($request->all());

        return response()->json(['success' => true, 'data' => $detalle], 200);
    }

    public function destroy($id)
    {
        DetallePrestamo::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Detalle eliminado'], 200);
    }
}