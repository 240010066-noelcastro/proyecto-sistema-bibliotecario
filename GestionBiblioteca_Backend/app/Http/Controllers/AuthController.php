<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ================================================================
    // 1. LOGIN UNIFICADO (Sirve para ADMINS y para ALUMNOS)
    // ================================================================
    public function loginUnificado(Request $request)
    {
        try {
            $request->validate([
                'correo' => 'required|email',
                'password' => 'required'
            ]);

            // Buscamos al usuario en la nueva tabla unificada
            $usuario = Usuario::where('CorreoElectronico', $request->correo)->first();

            // Si no existe
            if (!$usuario) {
                return response()->json(['success' => false, 'message' => 'Credenciales incorrectas o usuario no encontrado.'], 401);
            }

            // Si está inactivo
            if ($usuario->EstadoCuenta !== 'Activo') {
                return response()->json(['success' => false, 'message' => 'Esta cuenta se encuentra bloqueada o inactiva.'], 403);
            }

            // Verificamos la contraseña
            if (!Hash::check($request->password, $usuario->password)) {
                return response()->json(['success' => false, 'message' => 'Credenciales incorrectas.'], 401);
            }

            // Detectamos si es Admin (Rol 1) o Usuario normal (Rol 2)
            $rolTexto = ($usuario->Rol_ID == 1) ? 'admin' : 'usuario';
            $tokenName = ($usuario->Rol_ID == 1) ? 'admin_token' : 'estudiante_token';

            $token = $usuario->createToken($tokenName)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Bienvenido al sistema, ' . $usuario->NombreUsuario,
                'token' => $token,
                'rol' => $rolTexto, // Mandamos el rol a React para que sepa a dónde enviarlo
                'usuario' => $usuario
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error Interno: ' . $e->getMessage()], 500);
        }
    }

    // ================================================================
    // 2. PROCESAMIENTO INICIAL CON GOOGLE (Alumnos)
    // ================================================================
    public function loginGoogle(Request $request)
    {
        try {
            $request->validate([
                'correo' => 'required|email',
                'nombre' => 'required'
            ]);

            $estudiante = Usuario::where('CorreoElectronico', $request->correo)->first();

            if (!$estudiante) {
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

            $token = $estudiante->createToken('estudiante_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'es_nuevo' => false,
                'message' => 'Bienvenido al Sistema Bibliotecario, ' . $estudiante->NombreUsuario,
                'token' => $token,
                'rol' => 'usuario',
                'usuario' => $estudiante
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error en el servidor de Google Auth: ' . $e->getMessage()], 500);
        }
    }

    // ================================================================
    // 3. COMPLETAR EL REGISTRO DE UN ALUMNO NUEVO
    // ================================================================
    public function completarRegistro(Request $request)
    {
        try {
            $request->validate([
                'correo' => 'required|email|unique:usuarios,CorreoElectronico',
                'nombre' => 'required',
                'matricula' => 'required|unique:usuarios,Matricula',
                'telefono' => 'required',
                'grupo_id' => 'required',
                'password' => 'required|string|min:6' // <--- AHORA VALIDAMOS LA CONTRASEÑA
            ]);

            $estudiante = new Usuario();
            $estudiante->Rol_ID = 2; // <--- LE ASIGNAMOS EL ROL 2 (USUARIO NORMAL)
            $estudiante->CorreoElectronico = $request->correo;
            
            // Guardamos la contraseña encriptada que escribió el usuario
            $estudiante->password = Hash::make($request->password); 
            
            $estudiante->NombreUsuario = $request->nombre;
            $estudiante->ApellidoPaterno = $request->apellido_paterno ?? '';
            $estudiante->ApellidoMaterno = $request->apellido_materno ?? '';
            $estudiante->Matricula = $request->matricula;
            $estudiante->Telefono = $request->telefono;
            $estudiante->Grupo_ID = $request->grupo_id; 
            $estudiante->EstadoCuenta = 'Activo'; 
            $estudiante->save();

            $token = $estudiante->createToken('estudiante_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registro completado exitosamente.',
                'token' => $token,
                'rol' => 'usuario',
                'usuario' => $estudiante
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()], 500);
        }
    }

    // ================================================================
// 4. SOLICITAR RECUPERACIÓN DE CONTRASEÑA (Envía Correo)
// ================================================================
public function solicitarRecuperacion(Request $request)
{
    $request->validate(['correo' => 'required|email']);

    $usuario = Usuario::where('CorreoElectronico', $request->correo)->first();

    // Por seguridad, si no existe, no le decimos al atacante que el correo no existe
    if (!$usuario) {
        return response()->json([
            'success' => true,
            'message' => 'Si el correo existe en nuestro sistema, se ha enviado un enlace de recuperación.'
        ]);
    }

    // Generamos un token temporal único de 60 caracteres
    $token = \Str::random(60);

    // Lo guardamos en la tabla nativa de Laravel para tokens de restablecimiento
    \DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->correo],
        [
            'token' => \Hash::make($token),
            'created_at' => now()
        ]
    );

    // Creamos el enlace que se enviará al correo (apuntando a tu frontend de React)
    $enlaceRecuperacion = "http://localhost:5173/restablecer-password?token=" . $token . "&correo=" . urlencode($request->correo);

    try {
        // Enviamos el correo usando una vista simple en línea
        \Mail::send([], [], function ($message) use ($request, $enlaceRecuperacion) {
            $message->to($request->correo)
                ->subject('Restablecer Contraseña - Biblioteca UPVE')
                ->html("
                    <div style='font-family: sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; border: 1px solid #d1d5db; border-radius: 8px;'>
                        <h2 style='color: #582c83;'>Restablecimiento de Contraseña</h2>
                        <p>Has solicitado restablecer tu contraseña para el Sistema Bibliotecario de la UPVE.</p>
                        <p>Haz clic en el siguiente botón para crear una nueva contraseña. Este enlace expira en 60 minutos.</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='{$enlaceRecuperacion}' style='background-color: #582c83; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Restablecer Contraseña</a>
                        </div>
                        <p style='color: #6b7280; font-size: 12px;'>Si tú no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
                    </div>
                ");
        });

        return response()->json([
            'success' => true,
            'message' => 'Si el correo existe en nuestro sistema, se ha enviado un enlace de recuperación.'
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error al enviar correo: ' . $e->getMessage()], 500);
    }
}

// ================================================================
// 5. RESTABLECER LA CONTRASEÑA CON EL TOKEN VALIDADO
// ================================================================
public function restablecerPassword(Request $request)
{
    $request->validate([
        'token' => 'required',
        'correo' => 'required|email',
        'password' => 'required|string|min:6'
    ]);

    // Buscamos el token en la tabla de reseteos
    $registroToken = \DB::table('password_reset_tokens')->where('email', $request->correo)->first();

    if (!$registroToken) {
        return response()->json(['success' => false, 'message' => 'Solicitud de restablecimiento inválida o vencida.'], 400);
    }

    // Validamos que el token coincida y no tenga más de 1 hora de antigüedad
    if (!\Hash::check($request->token, $registroToken->token) || \Carbon\Carbon::parse($registroToken->created_at)->addMinutes(60)->isPast()) {
        return response()->json(['success' => false, 'message' => 'El enlace ha expirado o es inválido.'], 400);
    }

    // Buscamos al usuario y actualizamos su contraseña
    $usuario = Usuario::where('CorreoElectronico', $request->correo)->first();
    if ($usuario) {
        $usuario->password = \Hash::make($request->password);
        $usuario->save();

        // Borramos el token para que no se pueda volver a usar
        \DB::table('password_reset_tokens')->where('email', $request->correo)->delete();

        return response()->json(['success' => true, 'message' => 'Tu contraseña ha sido actualizada con éxito.']);
    }

    return response()->json(['success' => false, 'message' => 'No se pudo encontrar al usuario.'], 444);
}
}