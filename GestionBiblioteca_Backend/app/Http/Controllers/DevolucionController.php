<?php

namespace App\Http\Controllers;

use App\Models\Devolucion;
use Illuminate\Http\Request;

class DevolucionController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Devolucion::all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'DetallesPrestamo_ID' => 'required|integer|exists:detalles_prestamo,DetallesPrestamo_ID',
            // ¡CORREGIDO! Ahora verifica que el bibliotecario exista en la tabla 'usuarios'
            'Personal_ID' => 'required|integer|exists:usuarios,Usuario_ID',
            'FechaDevolucionReal' => 'required|date',
            'EstadoFisicoDevolucion' => 'required|string|max:30',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Devolución registrada con éxito',
            'data' => Devolucion::create($request->all())
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $devolucion = Devolucion::findOrFail($id);

        $request->validate([
            'DetallesPrestamo_ID' => 'required|integer|exists:detalles_prestamo,DetallesPrestamo_ID',
            // ¡CORREGIDO! Apunta a usuarios
            'Personal_ID' => 'required|integer|exists:usuarios,Usuario_ID',
            'FechaDevolucionReal' => 'required|date',
            'EstadoFisicoDevolucion' => 'required|string|max:30',
        ]);

        $devolucion->update($request->all());

        return response()->json(['success' => true, 'data' => $devolucion], 200);
    }

    public function destroy($id)
    {
        Devolucion::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Registro de devolución eliminado'], 200);
    }
}