<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\RecursoCatalogo;
use App\Models\MobiliarioDidactico;
use App\Http\Requests\StoreMobiliarioRequest;
use App\Http\Requests\UpdateMobiliarioRequest;

class MobiliarioDidacticoController extends Controller
{
    public function store(StoreMobiliarioRequest $request)
    {
        DB::beginTransaction();
        try {
            $path = $request->hasFile('imagen') ? $request->file('imagen')->store('portadas', 'public') : $request->input('Imagen_path');
            $tipo = DB::table('tipos_recursos')->where('NombreTipo', 'Mobiliario Didáctico')->first();

            $catalogo = RecursoCatalogo::create([
                'Titulo' => $request->input('Titulo', 'Sin Título'),
                // 🏛️ REMOVIDO
                'AnioPublicacion' => $request->input('AnioPublicacion'),
                'Imagen_path' => $path,
                'Observaciones' => $request->input('Observaciones'),
                'TipoRecurso_ID' => $tipo ? $tipo->TipoRecurso_ID : 8,
                'TipoRecurso' => 'Mobiliario Didáctico'
            ]);

            MobiliarioDidactico::create([
                'Recurso_ID' => $catalogo->Recurso_ID,
                'Marca' => $request->input('Marca'),
                'Material' => $request->input('Material')
            ]);

            DB::commit();
            return response()->json(['success' => true], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateMobiliarioRequest $request, $id)
    {
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

            MobiliarioDidactico::updateOrCreate(
                ['Recurso_ID' => $id],
                ['Marca' => $request->input('Marca'), 'Material' => $request->input('Material')]
            );

            DB::commit();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $recurso = RecursoCatalogo::findOrFail($id);
            if (!empty($recurso->Imagen_path) && !str_starts_with($recurso->Imagen_path, 'http')) {
                Storage::disk('public')->delete($recurso->Imagen_path);
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('inventario_unidades')->where('Recurso_ID', $id)->delete();
            $recurso->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::commit();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}