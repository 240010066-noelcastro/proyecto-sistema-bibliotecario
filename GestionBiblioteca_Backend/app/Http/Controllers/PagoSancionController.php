<?php

namespace App\Http\Controllers;

use App\Models\PagoSancion;
use Illuminate\Http\Request;

class PagoSancionController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => PagoSancion::all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Sancion_ID' => 'required|integer|exists:sanciones,Sancion_ID',
            'MontoPagado' => 'required|numeric|min:0',
            'FechaPago' => 'required|date',
            'MetodoPago' => 'required|string|max:50',
            'FolioRecibo' => 'required|string|max:50|unique:pagos_sanciones',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado correctamente',
            'data' => PagoSancion::create($request->all())
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $pago = PagoSancion::findOrFail($id);

        $request->validate([
            'Sancion_ID' => 'required|integer|exists:sanciones,Sancion_ID',
            'MontoPagado' => 'required|numeric|min:0',
            'FechaPago' => 'required|date',
            'MetodoPago' => 'required|string|max:50',
            // El truco para ignorar la fila actual al actualizar un campo único
            'FolioRecibo' => 'required|string|max:50|unique:pagos_sanciones,FolioRecibo,' . $id . ',PagoID',
        ]);

        $pago->update($request->all());

        return response()->json(['success' => true, 'data' => $pago], 200);
    }

    public function destroy($id)
    {
        PagoSancion::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Registro de pago eliminado'], 200);
    }
}