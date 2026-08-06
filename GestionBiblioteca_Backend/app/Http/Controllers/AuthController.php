<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Enums\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    // ================================================================
    // PROCESAMIENTO INICIAL CON GOOGLE (Alumnos)
    // ================================================================
    public function loginGoogle(Request $request)
    {
    try {
        $request->validate([
            'correo' => 'required|email',
            'nombre' => 'required'
        ]);

        $usuarioBuscado = Usuario::where('CorreoElectronico', $request->correo)->first();

        if (!$usuarioBuscado) {
                return response()->json([
                    'success' => true,
                    'es_nuevo' => true, 
                    'message' => 'Correo institucional válido. Redirigiendo a completar registro.',
                    'datos_google' => [
                        'correo' => $request->correo,
                        'nombre' => $request->nombre,
                        'apellido_paterno' => $request->apellido_paterno,
                        'apellido_materno' => $request->apellido_materno
                    ]
                ]);
            }

            $token = $usuarioBuscado->createToken('usuario_token')->plainTextToken;
            $usuarioBuscado->load(['grupo.carrera']);

            $rolTexto = ($usuarioBuscado->Rol_ID === Rol::ADMIN) ? 'admin' : 'usuario';

                return response()->json([
                    'success' => true,
                    'es_nuevo' => false,
                    'message' => 'Bienvenido al Sistema Bibliotecario, ' . $usuarioBuscado->NombreUsuario,
                        'token' => $token,
                        'rol' => Crypt::encryptString($rolTexto), // Rol cifrado simétricamente
                        'usuario' => $usuarioBuscado
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error en el servidor de Google Auth: ' . $e->getMessage()], 500);
        }
    }

    // ================================================================
    // COMPLETAR EL REGISTRO DE UN NUEVO USUARIO
    // ================================================================
    public function completarRegistro(Request $request)
    {
        try {
            $request->validate([
                'correo' => 'required|email|unique:usuarios,CorreoElectronico',
                'nombre' => 'required',
                'matricula' => 'required|unique:usuarios,Matricula',
                'telefono' => 'required',
                'grupo_id' => 'nullable'
            ]);

            $nuevoUsuario = new Usuario();
            $nuevoUsuario->Rol_ID = Rol::USUARIO;
            $nuevoUsuario->CorreoElectronico = $request->correo;
            $nuevoUsuario->NombreUsuario = $request->nombre;
            $nuevoUsuario->ApellidoPaterno = $request->apellido_paterno ?? '';
            $nuevoUsuario->ApellidoMaterno = $request->apellido_materno ?? '';
            $nuevoUsuario->Matricula = $request->matricula;
            $nuevoUsuario->Telefono = $request->telefono;
            $nuevoUsuario->Grupo_ID = $request->grupo_id ?: null; 
            $nuevoUsuario->EstadoCuenta = 'Activo'; 
            $nuevoUsuario->save();

            $token = $nuevoUsuario->createToken('usuario_token')->plainTextToken;
            $nuevoUsuario->load(['grupo.carrera']);

            return response()->json([
                'success' => true,
                'message' => 'Registro completado exitosamente.',
                'token' => $token,
                'rol' => Crypt::encryptString('usuario'), 
                'usuario' => $nuevoUsuario
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()], 500);
        }
    }

    // ================================================================
    // CIERRE DE SESIÓN SEGURO (Revocación de Token Sanctum)
    // ================================================================
    public function logout(Request $request)
    {
        try {
            // Elimina el token actual usado en la petición de la base de datos
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión: ' . $e->getMessage()
            ], 500);
        }
    }
}
