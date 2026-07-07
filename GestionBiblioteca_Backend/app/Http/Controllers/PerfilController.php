<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Grupo;

class PerfilController extends Controller
{
    // 1. GUARDAR CAMBIOS (Teléfono, Grupo y Foto)
    public function update(Request $request)
    {
        try {
            // Tomamos el usuario directo de la sesión autenticada
            $usuario = $request->user();

            if (!$usuario) {
                return response()->json(['success' => false, 'message' => 'Sesión no válida.'], 401);
            }

            // 👇 CORRECCIÓN AQUÍ: Cambiamos 'grupos,id' por 'grupos,Grupo_ID'
            $request->validate([
                'telefono' => 'required|string|max:15',
                'grupo_id' => 'required|exists:grupos,Grupo_ID', 
                'foto'     => 'nullable|string' 
            ]);

            // Asignamos los cambios directo al modelo de la sesión
            $usuario->Telefono = $request->telefono;
            $usuario->Grupo_ID = $request->grupo_id;
            
            // Si viene una foto en Base64, la guardamos
            if ($request->has('foto') && !empty($request->foto)) {
                $usuario->FotoPerfil = $request->foto;
            }

            $usuario->save();

            // Recargamos el modelo con sus relaciones actualizadas
            $usuario->load(['grupo.carrera']);

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado con éxito.',
                'usuario' => $usuario
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    // 2. OBTENER LOS GRUPOS EXCLUSIVOS DE LA CARRERA DEL ALUMNO
    public function getGruposPorCarrera(Request $request)
    {
        try {
            $usuario = $request->user()->load('grupo');
            
            if (!$usuario->grupo) {
                $todosLosGrupos = Grupo::all();
                return response()->json($todosLosGrupos);
            }

            $carreraId = $usuario->grupo->Carrera_ID;
            $grupos = Grupo::where('Carrera_ID', $carreraId)->get();

            return response()->json($grupos);
        } catch (\Throwable $e) {
            return response()->json([]);
        }
    }
}