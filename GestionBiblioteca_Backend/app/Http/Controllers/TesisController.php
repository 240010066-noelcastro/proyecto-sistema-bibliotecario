<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\RecursoCatalogo;
use App\Models\Tesis;
use App\Http\Requests\StoreTesisRequest;
use App\Http\Requests\UpdateTesisRequest;

class TesisController extends Controller
{
   public function store(StoreTesisRequest $request)
    {
        DB::beginTransaction();
        try {
            $path = $request->hasFile('imagen') ? $request->file('imagen')->store('portadas', 'public') : $request->input('Imagen_path');
            $pathPdf = $request->hasFile('documento_pdf') ? $request->file('documento_pdf')->store('tesis_pdfs', 'public') : null;
            $tipo = DB::table('tipos_recursos')->where('NombreTipo', 'Tesis')->first();

            // 🏛️ MAPEO RELACIONAL EN CREACIÓN
            $temaTexto = trim($request->input('TemaRecurso'));
            $temaRow = DB::table('temas_catalogo')->where('NombreTema', $temaTexto)->first();
            $temaId = $temaRow ? $temaRow->Tema_ID : null;

            $catalogo = RecursoCatalogo::create([
                'Titulo' => $request->input('Titulo', 'Sin Título'),
                'Tema_ID' => $temaId, // 🏛️ CORREGIDO
                'AnioPublicacion' => $request->input('AnioPublicacion'),
                'Imagen_path' => $path,
                'Archivo_PDF' => $pathPdf,
                'Mensaje_Legal' => $request->input('Mensaje_Legal'),
                'Observaciones' => $request->input('Observaciones'),
                'TipoRecurso_ID' => $tipo ? $tipo->TipoRecurso_ID : 4,
                'TipoRecurso' => 'Tesis',
                'Formato' => $request->input('Formato'),
                'Cantidad_Paginas' => $request->input('Cantidad_Paginas'),
                'Idioma' => $request->input('Idioma'),
                'Genero' => $request->input('Genero'),
                'Resumen' => $request->input('Resumen')
            ]);

            Tesis::create([
                'Recurso_ID' => $catalogo->Recurso_ID,
                'Asesor' => $request->input('Asesor'),
                'GradoCarrera' => $request->input('Carrera'),
                'AutorTexto' => $request->input('Autor')
            ]);

            DB::commit();
            return response()->json(['success' => true], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateTesisRequest $request, $id)
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

            $pathPdf = $catalogo->Archivo_PDF;
            if ($request->hasFile('documento_pdf')) {
                if ($pathPdf) Storage::disk('public')->delete($pathPdf);
                $pathPdf = $request->file('documento_pdf')->store('tesis_pdfs', 'public');
            }

            // 🏛️ MAPEO RELACIONAL EN ACTUALIZACIÓN
            $temaTexto = trim($request->input('TemaRecurso'));
            $temaRow = DB::table('temas_catalogo')->where('NombreTema', $temaTexto)->first();
            $temaId = $temaRow ? $temaRow->Tema_ID : null;

            $catalogo->update([
                'Titulo' => $request->input('Titulo', 'Sin Título'),
                'Tema_ID' => $temaId, // 🏛️ CORREGIDO
                'AnioPublicacion' => $request->input('AnioPublicacion'),
                'Imagen_path' => $path,
                'Archivo_PDF' => $pathPdf,
                'Mensaje_Legal' => $request->input('Mensaje_Legal'),
                'Observaciones' => $request->input('Observaciones'),
                'Formato' => $request->input('Formato'),
                'Cantidad_Paginas' => $request->input('Cantidad_Paginas'),
                'Idioma' => $request->input('Idioma'),
                'Genero' => $request->input('Genero'),
                'Resumen' => $request->input('Resumen')
            ]);

            Tesis::updateOrCreate(
                ['Recurso_ID' => $id],
                ['Asesor' => $request->input('Asesor'), 'GradoCarrera' => $request->input('Carrera'), 'AutorTexto' => $request->input('Autor')]
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
            
            if (!empty($recurso->Archivo_PDF)) {
                Storage::disk('public')->delete($recurso->Archivo_PDF);
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