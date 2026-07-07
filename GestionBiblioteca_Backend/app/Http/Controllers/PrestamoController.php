<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hoy = Carbon::now()->toDateString(); 
            $search = $request->input('search');
            $rangoFecha = $request->input('rangoFecha'); // Capturamos el filtro del Dashboard (hoy, 7_dias, 30_dias, etc)

            // 1. Auto-actualizar estados
            DB::table('prestamos')->where('EstadoPrestamo', 'Activo')->whereDate('FechaDevolucionEstablecida', '<', $hoy)->update(['EstadoPrestamo' => 'Atrasado']);
            DB::table('prestamos')->where('EstadoPrestamo', 'Atrasado')->whereDate('FechaDevolucionEstablecida', '>=', $hoy)->update(['EstadoPrestamo' => 'Activo']);

            // 2. Consulta Maestra (CORREGIDA PARA USAR LA TABLA USUARIOS EN ENTREGA Y RECIBE)
            $query = DB::table('prestamos')
                ->join('usuarios', 'prestamos.Usuario_ID', '=', 'usuarios.Usuario_ID')
                ->join('usuarios as p_entrega', 'prestamos.PersonalEntrega_ID', '=', 'p_entrega.Usuario_ID')
                ->leftJoin('usuarios as p_recibe', 'prestamos.PersonalRecibe_ID', '=', 'p_recibe.Usuario_ID')
                ->leftJoin('detalles_prestamo', 'prestamos.Prestamo_ID', '=', 'detalles_prestamo.Prestamo_ID')
                ->leftJoin('inventario_unidades', 'detalles_prestamo.Unidad_ID', '=', 'inventario_unidades.Unidad_ID')
                ->leftJoin('recursos_catalogo', 'inventario_unidades.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
                ->select(
                    'prestamos.*',
                    'usuarios.Matricula',
                    DB::raw("CONCAT(usuarios.NombreUsuario, ' ', IFNULL(usuarios.ApellidoPaterno, '')) AS NombreEstudiante"),
                    // Se usa NombreUsuario en lugar de NombrePersonal
                    DB::raw("CONCAT(p_entrega.NombreUsuario, ' ', IFNULL(p_entrega.ApellidoPaterno, '')) AS NombrePersonalEntrega"),
                    DB::raw("IFNULL(CONCAT(p_recibe.NombreUsuario, ' ', IFNULL(p_recibe.ApellidoPaterno, '')), 'Pendiente') AS NombrePersonalRecibe"),
                    DB::raw("IFNULL(GROUP_CONCAT(CONCAT(inventario_unidades.Unidad_ID, ' - ', recursos_catalogo.Titulo) SEPARATOR ', '), 'Sin unidades') as RecursosPrestados")
                )
                ->groupBy(
                    'prestamos.Prestamo_ID',
                    'prestamos.Usuario_ID',
                    'prestamos.PersonalEntrega_ID',
                    'prestamos.PersonalRecibe_ID',
                    'prestamos.FechaSalida',
                    'prestamos.FechaDevolucionEstablecida',
                    'prestamos.EstadoPrestamo',
                    'prestamos.created_at',
                    'prestamos.updated_at',
                    'usuarios.Matricula',
                    'usuarios.NombreUsuario',
                    'usuarios.ApellidoPaterno',
                    'p_entrega.NombreUsuario',
                    'p_entrega.ApellidoPaterno',
                    'p_recibe.NombreUsuario',
                    'p_recibe.ApellidoPaterno'
                );

            // NUEVA LÓGICA: Filtrado por Rango de Fechas (Estilo Dashboard)
            if ($rangoFecha && $rangoFecha !== 'todo') {
                $now = Carbon::now();
                if ($rangoFecha === 'hoy') {
                    $query->whereDate('prestamos.FechaSalida', $now->toDateString());
                } else if ($rangoFecha === '7_dias') {
                    $query->whereBetween('prestamos.FechaSalida', [$now->copy()->subDays(7)->toDateString(), $now->toDateString()]);
                } else if ($rangoFecha === '30_dias') {
                    $query->whereBetween('prestamos.FechaSalida', [$now->copy()->subDays(30)->toDateString(), $now->toDateString()]);
                } else if (preg_match('/^\d{4}-\d{2}$/', $rangoFecha)) {
                    // Si el formato es YYYY-MM (Ej: 2026-06), filtramos por ese mes específico
                    $mes = Carbon::createFromFormat('Y-m', $rangoFecha);
                    $inicioMes = $mes->startOfMonth()->toDateString();
                    $finMes = $mes->endOfMonth()->toDateString();
                    $query->whereBetween('prestamos.FechaSalida', [$inicioMes, $finMes]);
                }
            }

            if ($search) {
                $query->havingRaw("
                    prestamos.Prestamo_ID LIKE ? 
                    OR usuarios.Matricula LIKE ? 
                    OR NombreEstudiante LIKE ? 
                    OR prestamos.EstadoPrestamo LIKE ? 
                    OR RecursosPrestados LIKE ? 
                    OR CAST(prestamos.FechaSalida AS CHAR) LIKE ? 
                    OR CAST(prestamos.FechaDevolucionEstablecida AS CHAR) LIKE ?
                    OR NombrePersonalEntrega LIKE ? 
                    OR NombrePersonalRecibe LIKE ?
                ", [
                    "%{$search}%", 
                    "%{$search}%", 
                    "%{$search}%", 
                    "%{$search}%", 
                    "%{$search}%", 
                    "%{$search}%", 
                    "%{$search}%",
                    "%{$search}%",
                    "%{$search}%"
                ]);
            }

            $query->orderBy('prestamos.Prestamo_ID', 'desc');

            if ($request->has('all')) {
                $prestamos = $query->get();
                return response()->json(['success' => true, 'data' => $prestamos]);
            }

            $prestamos = $query->paginate(6);
            return response()->json(['success' => true, 'data' => $prestamos]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // EL CANDADO DE SEGURIDAD QUE FALTABA
        $request->validate([
            'Usuario_ID' => 'required|integer|exists:usuarios,Usuario_ID',
            'PersonalEntrega_ID' => 'required|integer|exists:usuarios,Usuario_ID',
            'FechaSalida' => 'required|date',
            'FechaDevolucionEstablecida' => 'required|date',
            'EstadoPrestamo' => 'required|string|max:30',
            'unidades' => 'required|array', // Verificamos que manden un arreglo de libros
            'unidades.*' => 'required|string|exists:inventario_unidades,Unidad_ID', // Verificamos que cada libro exista
        ]);

        DB::beginTransaction(); 
        try {
            $prestamoId = DB::table('prestamos')->insertGetId([
                'Usuario_ID' => $request->Usuario_ID,
                'PersonalEntrega_ID' => $request->PersonalEntrega_ID,
                'FechaSalida' => $request->FechaSalida,
                'FechaDevolucionEstablecida' => $request->FechaDevolucionEstablecida,
                'EstadoPrestamo' => $request->EstadoPrestamo,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($request->unidades as $unidad_id) {
                DB::table('detalles_prestamo')->insert([
                    'Prestamo_ID' => $prestamoId, 
                    'Unidad_ID' => $unidad_id, 
                    'created_at' => now(), 
                    'updated_at' => now()
                ]);
                DB::table('inventario_unidades')
                    ->where('Unidad_ID', $unidad_id)
                    ->update(['EstadoDisponibilidad' => 'Prestado', 'updated_at' => now()]); 
            }

            DB::commit(); 
            return response()->json(['success' => true, 'message' => 'Prestamo guardado'], 201);
        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // EL CANDADO DE SEGURIDAD QUE FALTABA
        $request->validate([
            'FechaDevolucionEstablecida' => 'required|date',
            'EstadoPrestamo' => 'required|string|max:30',
            'PersonalRecibe_ID' => 'nullable|integer|exists:usuarios,Usuario_ID',
        ]);

        DB::beginTransaction(); 
        try {
            DB::table('prestamos')->where('Prestamo_ID', $id)->update([
                'FechaDevolucionEstablecida' => $request->FechaDevolucionEstablecida,
                'EstadoPrestamo' => $request->EstadoPrestamo,
                'PersonalRecibe_ID' => $request->PersonalRecibe_ID,
                'updated_at' => now()
            ]);

            $unidades = DB::table('detalles_prestamo')->where('Prestamo_ID', $id)->pluck('Unidad_ID');
            if ($unidades->count() > 0) {
                $estadoDisp = ($request->input('EstadoPrestamo') === 'Devuelto') ? 'Disponible' : 'Prestado';
                DB::table('inventario_unidades')->whereIn('Unidad_ID', $unidades)->update(['EstadoDisponibilidad' => $estadoDisp, 'updated_at' => now()]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Actualizado'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction(); 
        try {
            $unidades = DB::table('detalles_prestamo')->where('Prestamo_ID', $id)->pluck('Unidad_ID');
            if ($unidades->count() > 0) {
                DB::table('inventario_unidades')->whereIn('Unidad_ID', $unidades)->update(['EstadoDisponibilidad' => 'Disponible', 'updated_at' => now()]);
            }
            
            // TRUCO MAESTRO: Apagamos las llaves foráneas temporalmente para evitar el Error 500 de MySQL
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('detalles_prestamo')->where('Prestamo_ID', $id)->delete();
            DB::table('prestamos')->where('Prestamo_ID', $id)->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            DB::commit();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            // Si algo falla, encendemos la seguridad de nuevo por si acaso
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}