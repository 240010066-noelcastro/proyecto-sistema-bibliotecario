<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Hacemos LEFT JOIN, pero FILTRAMOS estrictamente al Rol 2 (Usuarios/Alumnos)
            $query = DB::table('usuarios')
                ->join('roles', 'usuarios.Rol_ID', '=', 'roles.Rol_ID')
                ->leftJoin('grupos', 'usuarios.Grupo_ID', '=', 'grupos.Grupo_ID')
                ->leftJoin('carreras', 'grupos.Carrera_ID', '=', 'carreras.Carrera_ID')
                ->select('usuarios.*', 'grupos.NombreGrupo', 'carreras.NombreCarrera')
                ->where('usuarios.Rol_ID', '=', 2); // <--- CANDADO DE SEGURIDAD

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

                      if (strtolower(trim($search)) === 'activo') {
                          $q->orWhere('usuarios.EstadoCuenta', '=', 'Activo');
                      } else {
                          $q->orWhere('usuarios.EstadoCuenta', 'LIKE', "%{$search}%");
                      }
                });
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

    public function store(Request $request)
    {
        $request->validate([
            // La matrícula vuelve a ser obligatoria siempre porque aquí solo entran alumnos
            'Matricula' => 'required|string|max:30|unique:usuarios,Matricula',
            'NombreUsuario' => 'required|string|max:50',
            'ApellidoPaterno' => 'required|string|max:50',
            'ApellidoMaterno' => 'nullable|string|max:50',
            'CorreoElectronico' => 'required|email|max:100|unique:usuarios,CorreoElectronico',
            'Telefono' => 'required|string|max:20',
            'Grupo_ID' => 'nullable|integer|exists:grupos,Grupo_ID',
            'EstadoCuenta' => 'required|string|max:20',
        ]);

        $data = $request->all();
        
        // 🔥 BLOQUEO DE SEGURIDAD: Forzamos que siempre sea Rol 2
        $data['Rol_ID'] = 2; 
        // Su contraseña inicial por defecto será su matrícula encriptada
        $data['password'] = Hash::make($request->Matricula);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado con éxito',
            'data' => Usuario::create($data)
        ], 201);
    }

    public function update(Request $request, $id)
    {
        // 🔥 BLOQUEO DE SEGURIDAD: Solo puedes editar si es Rol 2
        $usuario = Usuario::where('Rol_ID', 2)->findOrFail($id);
        
        $request->validate([
            'Matricula' => 'required|string|max:30|unique:usuarios,Matricula,'.$id.',Usuario_ID',
            'NombreUsuario' => 'required|string|max:50',
            'ApellidoPaterno' => 'required|string|max:50',
            'ApellidoMaterno' => 'nullable|string|max:50',
            'CorreoElectronico' => 'required|email|max:100|unique:usuarios,CorreoElectronico,'.$id.',Usuario_ID',
            'Telefono' => 'required|string|max:20',
            'Grupo_ID' => 'nullable|integer|exists:grupos,Grupo_ID',
            'EstadoCuenta' => 'required|string|max:20',
        ]);

        $data = $request->all();
        
        // Quitamos el Rol_ID y el password por si intentan inyectarlos en la petición
        unset($data['Rol_ID']);
        unset($data['password']);

        $usuario->update($data);

        return response()->json(['success' => true, 'data' => $usuario], 200);
    }

    public function destroy($id)
    {
        try {
            // 🔥 BLOQUEO DE SEGURIDAD: Solo permite borrar usuarios Rol 2
            Usuario::where('Rol_ID', 2)->findOrFail($id)->delete();
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
                ->where('Rol_ID', '=', 1) // Filtra estrictamente al Personal Administrativo (Rol 1)
                ->get();

            return response()->json(['success' => true, 'data' => $personal]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}