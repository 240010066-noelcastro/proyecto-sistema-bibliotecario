<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfiguracionSistema;

class ConfiguracionSistemaController extends Controller
{
    // ==========================================================
    // 1. Obtener todas las configuraciones de un módulo (Ej: 'Sanciones')
    // ==========================================================
    public function getByModulo($modulo)
    {
        try {
            $configuraciones = ConfiguracionSistema::where('Modulo', $modulo)->get();
            
            // Formateamos la respuesta para que a React le llegue un objeto limpio y fácil de leer
            // Ejemplo: { "tipos_sancion": ["Dañado", "Extraviado"], "estados_pago": ["Pendiente", "Pagado"] }
            $formatoReact = [];
            foreach ($configuraciones as $config) {
                $formatoReact[$config->Clave] = $config->Valor;
            }

            return response()->json(['success' => true, 'data' => $formatoReact], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================================
    // 2. Guardar o Actualizar una configuración (Desde la Tuerquita)
    // ==========================================================
    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'Modulo' => 'required|string|max:50',
            'Clave' => 'required|string|max:100',
            'Valor' => 'required' // Puede recibir arrays o strings
        ]);

        try {
            // updateOrCreate busca si ya existe esa Clave en ese Modulo. 
            // Si existe, la actualiza. Si no, la crea nueva.
            $configuracion = ConfiguracionSistema::updateOrCreate(
                ['Modulo' => $request->input('Modulo'), 'Clave' => $request->input('Clave')],
                ['Valor' => $request->input('Valor')]
            );

            return response()->json(['success' => true, 'data' => $configuracion, 'message' => 'Configuración guardada'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}