<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemaController extends Controller
{
    // Realiza búsquedas predictivas en vivo mientras el bibliotecario escribe
    public function buscar(Request $request)
    {
        $term = $request->input('term');
        
        // 🏛️ NUEVO: Sincronizador de lista blanca para el Frontend
        if ($request->has('all')) {
            $todos = DB::table('temas_catalogo')->pluck('NombreTema')->toArray();
            return response()->json(['success' => true, 'data' => $todos]);
        }

        if (!$term || trim($term) === '') {
            return response()->json(['data' => []]);
        }

        $resultados = DB::table('temas_catalogo')
            ->where('NombreTema', 'LIKE', "%{$term}%")
            ->limit(8)
            ->get();

        return response()->json(['data' => $resultados]);
    }

    // Procesa el alta rápida en caliente si el tema ingresado no existe aún
    public function store(Request $request)
    {
        $request->validate([
            'NombreTema' => 'required|string|max:100'
        ]);

        $nombreNuevo = trim($request->input('NombreTema'));

        // Evitamos duplicidad validando en minúsculas de forma segura
        $temaExistente = DB::table('temas_catalogo')
            ->where(DB::raw('LOWER(NombreTema)'), strtolower($nombreNuevo))
            ->first();

        if ($temaExistente) {
            return response()->json([
                'success' => true,
                'message' => 'El tema ya se encuentra registrado.',
                'data' => $temaExistente
            ], 200);
        }

        $id = DB::table('temas_catalogo')->insertGetId([
            'NombreTema' => $nombreNuevo,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Área / Tema registrado con éxito.',
            'data' => [
                'Tema_ID' => $id,
                'NombreTema' => $nombreNuevo
            ]
        ], 201);
    }
}