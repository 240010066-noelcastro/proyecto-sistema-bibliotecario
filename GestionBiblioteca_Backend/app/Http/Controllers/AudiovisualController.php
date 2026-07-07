<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\RecursoCatalogo;
use App\Models\Audiovisual;

class AudiovisualController extends Controller
{
    public function store(Request $request)
    {
        // En public function store:
    $request->validate([
        'Titulo' => 'required|string|max:150|unique:recursos_catalogo,Titulo',
        'NumSerie' => 'nullable|string|unique:audiovisuales,NumSerie',
    ], [
        'Titulo.unique' => 'Este título ya se encuentra registrado.',
        'NumSerie.unique' => 'Ya existe un equipo registrado con este número de serie.'
    ]);

        DB::beginTransaction();
        try {
            $path = $request->hasFile('imagen') ? $request->file('imagen')->store('portadas', 'public') : $request->input('Imagen_path');
            $tipo = DB::table('tipos_recursos')->where('NombreTipo', 'Equipo Audiovisual')->first();

            $catalogo = RecursoCatalogo::create([
                'Titulo' => $request->input('Titulo', 'Sin Título'),
                'AnioPublicacion' => $request->input('AnioPublicacion'),
                'Imagen_path' => $path,
                'Observaciones' => $request->input('Observaciones'),
                'TipoRecurso_ID' => $tipo ? $tipo->TipoRecurso_ID : 5,
                'TipoRecurso' => 'Equipo Audiovisual'
            ]);

            Audiovisual::create([
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
        'NumSerie' => 'nullable|string|unique:audiovisuales,NumSerie,' . $id . ',Recurso_ID',
    ], [
        'Titulo.unique' => 'Este título ya se encuentra registrado.',
        'NumSerie.unique' => 'Este número de serie ya pertenece a otro equipo.'
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
                // 🏛️ REMOVIDO: Heredará automáticamente NULL en la base de datos
                'AnioPublicacion' => $request->input('AnioPublicacion'),
                'Imagen_path' => $path,
                'Observaciones' => $request->input('Observaciones'),
            ]);

            Audiovisual::updateOrCreate(
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