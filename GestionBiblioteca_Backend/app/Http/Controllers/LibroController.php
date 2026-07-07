<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\RecursoCatalogo;
use App\Models\Libro;

class LibroController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'Titulo' => 'required|string|max:150|unique:recursos_catalogo,Titulo',
            'ClasificacionISBN' => 'nullable|string|unique:libros,ClasificacionISBN',
            'Cantidad_Paginas' => 'nullable|integer|min:0',
        ], [
            'Titulo.unique' => 'Este título ya se encuentra registrado.',
            'ClasificacionISBN.unique' => 'Este ISBN ya se encuentra registrado.'
        ]);

        DB::beginTransaction();
        try {
            $path = $request->hasFile('imagen') ? $request->file('imagen')->store('portadas', 'public') : $request->input('Imagen_path');
            
            $autor = DB::table('autores')->whereRaw("TRIM(CONCAT(IFNULL(NombreAutor,''), ' ', IFNULL(ApellidosAutor,''))) = ?", [trim($request->input('Autor'))])->first();
            $editorial = DB::table('editoriales')->where('NombreEditorial', trim($request->input('Editorial')))->first();
            $tipo = DB::table('tipos_recursos')->where('NombreTipo', 'Libro')->first();

            // 🏛️ NUEVO PROCESAMIENTO: Buscamos el Tema_ID correspondiente al string enviado por React
            $temaTexto = trim($request->input('TemaRecurso'));
            $temaRow = DB::table('temas_catalogo')->where('NombreTema', $temaTexto)->first();
            $temaId = $temaRow ? $temaRow->Tema_ID : null;

            $catalogo = RecursoCatalogo::create([
                'Titulo' => $request->input('Titulo', 'Sin Título'),
                'Tema_ID' => $temaId, // 🏛️ CORRECCIÓN: Guardamos la FK oficial indexada
                'AnioPublicacion' => $request->input('AnioPublicacion'),
                'Imagen_path' => $path,
                'Observaciones' => $request->input('Observaciones'),
                'URL_Externa' => $request->input('URL_Externa'),
                'Mensaje_Legal' => $request->input('Mensaje_Legal'),
                'Autor_ID' => $autor ? $autor->Autor_ID : null,
                'Editorial_ID' => $editorial ? $editorial->Editorial_ID : null,
                'TipoRecurso_ID' => $tipo ? $tipo->TipoRecurso_ID : 1,
                'TipoRecurso' => 'Libro',
                'Formato' => $request->input('Formato'),
                'Cantidad_Paginas' => $request->input('Cantidad_Paginas'),
                'Idioma' => $request->input('Idioma'),
                'Genero' => $request->input('Genero'),
                'Resumen' => $request->input('Resumen')
            ]);

            Libro::create([
                'Recurso_ID' => $catalogo->Recurso_ID,
                'EdicionVolumen' => $request->input('EdicionVolumen'),
                'ClasificacionISBN' => $request->input('ClasificacionISBN')
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
            'ClasificacionISBN' => 'nullable|string|unique:libros,ClasificacionISBN,' . $id . ',Recurso_ID',
            'Cantidad_Paginas' => 'nullable|integer|min:0',
        ], [
            'Titulo.unique' => 'Este título ya se encuentra registrado.',
            'ClasificacionISBN.unique' => 'Este ISBN ya pertenece a otro libro.'
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

            $autor = DB::table('autores')->whereRaw("TRIM(CONCAT(IFNULL(NombreAutor,''), ' ', IFNULL(ApellidosAutor,''))) = ?", [trim($request->input('Autor'))])->first();
            $editorial = DB::table('editoriales')->where('NombreEditorial', trim($request->input('Editorial')))->first();

            // 🏛️ NUEVO PROCESAMIENTO RELACIONAL PARA LA ACTUALIZACIÓN
            $temaTexto = trim($request->input('TemaRecurso'));
            $temaRow = DB::table('temas_catalogo')->where('NombreTema', $temaTexto)->first();
            $temaId = $temaRow ? $temaRow->Tema_ID : null;

            $catalogo->update([
                'Titulo' => $request->input('Titulo', 'Sin Título'),
                'Tema_ID' => $temaId, // 🏛️ CORREGIDO
                'AnioPublicacion' => $request->input('AnioPublicacion'),
                'Imagen_path' => $path,
                'Observaciones' => $request->input('Observaciones'),
                'URL_Externa' => $request->input('URL_Externa'),
                'Mensaje_Legal' => $request->input('Mensaje_Legal'),
                'Autor_ID' => $autor ? $autor->Autor_ID : null,
                'Editorial_ID' => $editorial ? $editorial->Editorial_ID : null,
                'Formato' => $request->input('Formato'),
                'Cantidad_Paginas' => $request->input('Cantidad_Paginas'),
                'Idioma' => $request->input('Idioma'),
                'Genero' => $request->input('Genero'),
                'Resumen' => $request->input('Resumen')
            ]);

            Libro::updateOrCreate(
                ['Recurso_ID' => $id],
                ['EdicionVolumen' => $request->input('EdicionVolumen'), 'ClasificacionISBN' => $request->input('ClasificacionISBN')]
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