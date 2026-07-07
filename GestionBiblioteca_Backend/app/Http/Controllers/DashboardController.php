<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Recibimos las variables. Si vienen palabras como "hoy" o "7", son periodos rápidos.
            // Si vienen fechas YYYY-MM-DD, son rangos personalizados.
            $prestamosInicio = $request->input('prestamos_inicio');
            $prestamosFin = $request->input('prestamos_fin');
            
            $sancionesInicio = $request->input('sanciones_inicio');
            $sancionesFin = $request->input('sanciones_fin');

            // ==========================================
            // 1. KPIs GLOBALES (Tarjetas superiores)
            // ==========================================
            $totalUsuarios = DB::table('usuarios')->count();
            $totalPersonal = DB::table('usuarios')->where('Rol_ID', 1)->count();
            $totalAutores = DB::table('autores')->count();
            $totalEditoriales = DB::table('editoriales')->count();

            $totalCatalogo = DB::table('recursos_catalogo')->count();
            $totalLibros = DB::table('recursos_catalogo')->where('TipoRecurso', 'Libro')->count();
            $totalTesis = DB::table('recursos_catalogo')->where('TipoRecurso', 'Tesis')->count();
            $totalAudiovisual = DB::table('recursos_catalogo')->where('TipoRecurso', 'Equipo Audiovisual')->count();
            $totalRevistas = DB::table('recursos_catalogo')->where('TipoRecurso', 'like', '%Revista%')->count();
            
            $prestamosPeriodo = DB::table('prestamos')->count();
            $multasRecaudadas = DB::table('sanciones')->where('EstadoSancion', 'Pagado')->sum('MontoPago');

            // ==========================================
            // HELPER PARA APLICAR FECHAS DINÁMICAMENTE
            // ==========================================
            $aplicarFiltro = function($query, $columna, $inicio, $fin) {
                if ($inicio === 'hoy') {
                    $query->whereDate($columna, Carbon::today());
                } elseif (in_array($inicio, ['7', '30'])) {
                    $query->whereDate($columna, '>=', Carbon::now()->subDays((int)$inicio)->startOfDay());
                } elseif ($inicio && $inicio !== 'siempre') {
                    $query->whereDate($columna, '>=', $inicio);
                }
                
                // El campo "Fin" solo viene lleno cuando el usuario usa calendarios
                if ($fin) {
                    $query->whereDate($columna, '<=', $fin);
                }
            };

            // ==========================================
            // 2. GRÁFICAS
            // ==========================================

            // GRÁFICA 1: Pastel (Global)
            $recursosPorTipo = DB::table('recursos_catalogo')
                ->select('TipoRecurso as name', DB::raw('count(*) as value'))
                ->groupBy('TipoRecurso')
                ->get();

            // GRÁFICA 2: Tendencia Préstamos
            $queryTendenciaP = DB::table('prestamos');
            $aplicarFiltro($queryTendenciaP, 'FechaSalida', $prestamosInicio, $prestamosFin);
            
            $tendenciaPrestamos = $queryTendenciaP->select('FechaSalida')
                ->orderBy('FechaSalida', 'asc')
                ->get()
                ->groupBy(function($item) {
                    return Carbon::parse($item->FechaSalida)->format('Y-m-d'); 
                })
                ->sortKeys()
                ->map(function ($group, $key) {
                    return [
                        'fecha' => Carbon::parse($key)->translatedFormat('d M Y'), 
                        'cantidad' => $group->count()
                    ];
                })->values()->toArray();

            // GRÁFICA 3: Tendencia Sanciones
            $queryTendenciaS = DB::table('sanciones');
            $aplicarFiltro($queryTendenciaS, 'FechaGeneracion', $sancionesInicio, $sancionesFin);
            
            $tendenciaSanciones = $queryTendenciaS->select('FechaGeneracion')
                ->orderBy('FechaGeneracion', 'asc')
                ->get()
                ->groupBy(function($item) {
                    return Carbon::parse($item->FechaGeneracion)->format('Y-m-d'); 
                })
                ->sortKeys()
                ->map(function ($group, $key) {
                    return [
                        'fecha' => Carbon::parse($key)->translatedFormat('d M Y'), 
                        'cantidad' => $group->count()
                    ];
                })->values()->toArray();

            // GRÁFICA 4: Estado de Préstamos (Global)
            $prestamosPorEstado = DB::table('prestamos')
                ->select('EstadoPrestamo as name', DB::raw('count(*) as value'))
                ->groupBy('EstadoPrestamo')
                ->get();

            // ==========================================
            // RETORNO DE LA RESPUESTA
            // ==========================================
            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'usuarios' => $totalUsuarios,
                        'personal' => $totalPersonal,
                        'autores' => $totalAutores,
                        'editoriales' => $totalEditoriales,
                        'total_catalogo' => $totalCatalogo,
                        'libros' => $totalLibros,
                        'tesis' => $totalTesis,
                        'audiovisual' => $totalAudiovisual,
                        'revistas' => $totalRevistas,
                        'prestamos_periodo' => $prestamosPeriodo,
                        'multas_recaudadas' => number_format($multasRecaudadas, 2)
                    ],
                    'charts' => [
                        'recursosPorTipo' => $recursosPorTipo,
                        'tendenciaPrestamos' => $tendenciaPrestamos,
                        'tendenciaSanciones' => $tendenciaSanciones,
                        'prestamosPorEstado' => $prestamosPorEstado
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}