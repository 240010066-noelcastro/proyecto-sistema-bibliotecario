<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RecursoCatalogo;
use App\Models\DispositivoConectividad;

class ConectividadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
        'Titulo' => 'required|string|max:150|unique:recursos_catalogo,Titulo',
        'NumSerie' => 'nullable|string|unique:dispositivos_conectividad,NumSerie',
    ], [
        'Titulo.unique' => 'Este título ya se encuentra registrado.',
        'NumSerie.unique' => 'Ya existe un dispositivo con este Número de Serie.'
    ]);

        DB::beginTransaction();
        try {
            $path = $request->hasFile('imagen') ? $request->file('imagen')->store('portadas', 'public') : $request->input('Imagen_path');
            $tipo = DB::table('tipos_recursos')->where('NombreTipo', 'Dispositivo de Conectividad')->first();

            $catalogo = RecursoCatalogo::create([
                'Titulo' => $request->input('Titulo', 'Sin Título'),
                // 🏛️ REMOVIDO
                'AnioPublicacion' => $request->input('AnioPublicacion'),
                'Imagen_path' => $path,
                'Observaciones' => $request->input('Observaciones'),
                'TipoRecurso_ID' => $tipo ? $tipo->TipoRecurso_ID : 8,
                'TipoRecurso' => 'Dispositivo de Conectividad'
            ]);

            DispositivoConectividad::create([
                'Recurso_ID' => $catalogo->Recurso_ID,
                'Marca' => $request->input('Marca'),
                'NumSerie' => $request->input('NumSerie')
            ]);

            DB::commit();
            return response()->json(['success' => true], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
        'Titulo' => 'required|string|max:150|unique:recursos_catalogo,Titulo,' . $id . ',Recurso_ID',
        'NumSerie' => 'nullable|string|unique:dispositivos_conectividad,NumSerie,' . $id . ',Recurso_ID',
    ], [
        'Titulo.unique' => 'Este título ya se encuentra registrado.',
        'NumSerie.unique' => 'Este Número de Serie ya pertenece a otro dispositivo.'
    ]);

        DB::beginTransaction();
        try {
            $catalogo = RecursoCatalogo::findOrFail($id);
            $path = $catalogo->Imagen_path;
            
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('portadas', 'public');
            } elseif ($request->filled('Imagen_path') === false && $request->has('Imagen_path')) {
                $path = null;
            } elseif ($request->has('Imagen_path') && str_starts_with($request->input('Imagen_path'), 'http')) {
                $path = $request->input('Imagen_path');
            }

            $catalogo->update([
                'Titulo' => $request->input('Titulo', 'Sin Título'),
                // 🏛️ REMOVIDO
                'AnioPublicacion' => $request->input('AnioPublicacion'),
                'Imagen_path' => $path,
                'Observaciones' => $request->input('Observaciones'),
            ]);

            DispositivoConectividad::updateOrCreate(
                ['Recurso_ID' => $id],
                ['Marca' => $request->input('Marca'), 'NumSerie' => $request->input('NumSerie')]
            );

            DB::commit();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}