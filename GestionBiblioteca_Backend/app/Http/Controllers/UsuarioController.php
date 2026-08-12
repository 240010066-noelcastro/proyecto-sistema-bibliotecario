<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Enums\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Http\Requests\RegisterAdminRequest;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        // 🛡️ BLOQUEO DE SEGURIDAD: Solo el Administrador puede listar usuarios
        if (!$request->user() || $request->user()->Rol_ID !== Rol::ADMIN) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado. Requiere permisos de Administrador.'], 403);
        }

        try {
            // Hacemos LEFT JOIN, pero FILTRAMOS estrictamente al Rol (Usuarios/Alumnos)
            $query = DB::table('usuarios')
                ->join('roles', 'usuarios.Rol_ID', '=', 'roles.Rol_ID')
                ->leftJoin('grupos', 'usuarios.Grupo_ID', '=', 'grupos.Grupo_ID')
                ->leftJoin('carreras', 'grupos.Carrera_ID', '=', 'carreras.Carrera_ID')
                ->select('usuarios.*', 'grupos.NombreGrupo', 'carreras.NombreCarrera')
                ->where('usuarios.Rol_ID', '=', Rol::USUARIO->value);

            // 1. Motor de búsqueda en tiempo real
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('usuarios.Matricula', 'LIKE', "%{$search}%")
                      ->orWhereRaw("CONCAT(usuarios.NombreUsuario, ' ', IFNULL(usuarios.ApellidoPaterno, ''), ' ', IFNULL(usuarios.ApellidoMaterno, '')) LIKE ?", ["%{$search}%"])
                      ->orWhere('usuarios.NombreUsuario', 'LIKE', "%{$search}%")
                      ->orWhere('usuarios.ApellidoPaterno', 'LIKE', "%{$search}%")
                      ->orWhere('usuarios.CorreoElectronico', 'LIKE', "%{$search}%")
                      ->orWhere('grupos.NombreGrupo', 'LIKE', "%{$search}%")
                      ->orWhere('carreras.NombreCarrera', 'LIKE', "%{$search}%")
                      ->orWhere('usuarios.Telefono', 'LIKE', "%{$search}%");
                });
            }

            // Filtro desplegable por Estado (Solo aplica si no es 'Todos')
                      if ($request->has('estado') && !empty($request->estado) && $request->estado !== 'Todos') {
                          $query->where('usuarios.EstadoCuenta', '=', $request->estado);
                     }

            // 2. Exportar todos los datos (Excel/PDF)
            if ($request->has('all')) {
                return response()->json(['success' => true, 'data' => $query->get()]);
            }

            // 3. Paginación
            $usuarios = $query->paginate(6);
            return response()->json(['success' => true, 'data' => $usuarios]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();
        $data['Rol_ID'] = Rol::USUARIO->value; 

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado con éxito',
            'data' => Usuario::create($data)
        ], 201);
    }

    public function update(Request $request, $id)
    {
    $usuario = Usuario::where('Rol_ID', Rol::USUARIO->value)->findOrFail($id);
    
    $data = $request->all();
    unset($data['Rol_ID']);

    $usuario->update($data);

    return response()->json(['success' => true, 'data' => $usuario], 200);
    
    }
    public function destroy(Request $request, $id)
    {
        if (!$request->user() || $request->user()->Rol_ID !== Rol::ADMIN) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado.'], 403);
        }

        try {
            // BLOQUEO DE SEGURIDAD: Solo permite borrar usuarios
            Usuario::where('Rol_ID', Rol::USUARIO->value)->findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Usuario eliminado'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar porque tiene préstamos o sanciones asociadas.'], 500);
        }
    }

    // ---> NUEVO MÉTODO PARA CARGAR EL PERSONAL EN PRESTAMOS <---
    public function getPersonal(Request $request)
    {
        try {
            $personal = DB::table('usuarios')
                ->select('Usuario_ID as Personal_ID', 'NombreUsuario as NombrePersonal', 'ApellidoPaterno')
                ->where('Rol_ID', '=', Rol::ADMIN->value)
                ->get();

            return response()->json(['success' => true, 'data' => $personal]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function registrarAdmin(RegisterAdminRequest $request)
    {
        try {
            $data = $request->validated();

            if (!Hash::check($data['llave_infraestructura'], env('MASTER_ADMIN_KEY'))) {
                return response()->json(['success' => false, 'message' => 'Acción denegada. La llave de infraestructura es incorrecta.'], 403);
            }

            $admin = Usuario::create([
                'Rol_ID'            => Rol::ADMIN->value,
                'NombreUsuario'     => $data['NombreUsuario'],
                'ApellidoPaterno'   => $data['ApellidoPaterno'],
                'ApellidoMaterno'   => $data['ApellidoMaterno'] ?? null,
                'CorreoElectronico' => $data['CorreoElectronico'],
                'Telefono'          => $data['Telefono'],
                'EstadoCuenta'      => 'Activo'
            ]);

            return response()->json(['success' => true, 'message' => 'Administrador de infraestructura registrado con éxito.', 'data' => $admin], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()], 500);
        }
    }
}