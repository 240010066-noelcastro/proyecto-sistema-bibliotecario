<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InicioController extends Controller
{
    public function getStats(Request $request)
    {
        try {
            $usuarioId = $request->user()->Usuario_ID; 

            // 1. 🏛️ CORRECCIÓN: Cuenta tanto los préstamos 'Activos' como los 'Atrasados'
            $prestamosActivos = DB::table('prestamos')
                ->where('Usuario_ID', $usuarioId)
                ->whereIn(DB::raw('LOWER(EstadoPrestamo)'), ['activo', 'atrasado'])
                ->count();

            // 2. 🏛️ CORRECCIÓN: Cuenta libros vencidos ya sea porque dice 'Atrasado' o porque venció hoy
            $atrasosCount = DB::table('prestamos')
                ->where('Usuario_ID', $usuarioId)
                ->where(function($q) {
                    $q->where(DB::raw('LOWER(EstadoPrestamo)'), 'atrasado')
                      ->orWhere(function($sub) {
                          $sub->where(DB::raw('LOWER(EstadoPrestamo)'), 'activo')
                              ->whereDate('FechaDevolucionEstablecida', '<', Carbon::today());
                      });
                })
                ->count();

            // 3. Suma de multas pendientes
            $multasPendientes = DB::table('sanciones')
                ->where('Usuario_ID', $usuarioId)
                ->where('EstadoSancion', 'Pendiente')
                ->sum('MontoPago');

            // 4. Catálogo de Recursos Intercalados de Biblioteca con Escalado HD
            $novedades = DB::table('recursos_catalogo')
                ->select('Recurso_ID as id', 'Titulo', 'TipoRecurso_ID', 'Imagen_path')
                ->whereIn('TipoRecurso_ID', [1, 2, 4]) 
                ->inRandomOrder() 
                ->take(4) 
                ->get();

            // ESCALADO HD: Forzamos el cambio de zoom=1 a zoom=2 para descargar la portada nítida
            foreach ($novedades as $item) {
                if (!empty($item->Imagen_path)) {
                    $imgHD = str_replace('zoom=1', 'zoom=2', $item->Imagen_path);
                    $item->Imagen = str_starts_with($item->Imagen_path, 'http') ? $imgHD : url('storage/' . $item->Imagen_path);
                } else {
                    $item->Imagen = null;
                }
            }

            return response()->json([
                'success' => true,
                'prestamos_activos' => $prestamosActivos,
                'atrasos'           => $atrasosCount,
                'multas_pendientes' => number_format($multasPendientes, 2),
                'novedades'         => $novedades
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error en inicio'], 500);
        }
    }
}