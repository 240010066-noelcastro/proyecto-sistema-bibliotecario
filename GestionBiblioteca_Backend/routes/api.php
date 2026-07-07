<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutorController;
use App\Http\Controllers\BitacoraBajaInventarioController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\ConstanciaNoAdeudoController;
use App\Http\Controllers\DetallePrestamoController;
use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\EditorialController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\InventarioUnidadController;
use App\Http\Controllers\PagoSancionController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\RecursoCatalogoController;
use App\Http\Controllers\SancionController;
use App\Http\Controllers\TipoRecursoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LibroController;
use App\Http\Controllers\TesisController;
use App\Http\Controllers\RevistaController;
use App\Http\Controllers\AudiovisualController;
use App\Http\Controllers\EnciclopediaController;
use App\Http\Controllers\MobiliarioDidacticoController;
use App\Http\Controllers\ConectividadController;
use App\Http\Controllers\ConfiguracionSistemaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\PerfilController;

// ==========================================================
// 1. RUTAS DE AUTENTICACIÓN (Abiertas al público)
// ==========================================================

Route::post('/login', [AuthController::class, 'loginUnificado']);
Route::post('/login-google', [AuthController::class, 'loginGoogle']); 
Route::post('/completar-registro', [AuthController::class, 'completarRegistro']); 
Route::post('/solicitar-recuperacion', [AuthController::class, 'solicitarRecuperacion']);
Route::post('/restablecer-password', [AuthController::class, 'restablecerPassword']);

// ==========================================================
// 2. RUTAS DE LA API (Módulos del Sistema)
// ==========================================================

// RUTAS ESPECÍFICAS DEBEN IR ANTES DE LOS API RESOURCE
Route::get('/inventario/buscar', [InventarioUnidadController::class, 'buscarVivo']);
Route::get('/sanciones/candidatos', [SancionController::class, 'getCandidatos']);
Route::get('/dashboard-stats', [DashboardController::class, 'index']);
Route::get('/personal', [UsuarioController::class, 'getPersonal']); 

// RUTAS ESPECÍFICAS DE AUTOCUMPLETADO Y ALTA RÁPIDA DE TEMAS
Route::get('/temas/buscar', [App\Http\Controllers\TemaController::class, 'buscar']);
Route::post('/temas', [App\Http\Controllers\TemaController::class, 'store']);

Route::apiResource('autores', AutorController::class);
Route::apiResource('bitacoras-bajas', BitacoraBajaInventarioController::class);
Route::apiResource('carreras', CarreraController::class);
Route::apiResource('constancias', ConstanciaNoAdeudoController::class);
Route::apiResource('detalles-prestamo', DetallePrestamoController::class);
Route::apiResource('devoluciones', DevolucionController::class);
Route::apiResource('editoriales', EditorialController::class);
Route::apiResource('grupos', GrupoController::class);
Route::apiResource('inventario', InventarioUnidadController::class);
Route::apiResource('pagos-sanciones', PagoSancionController::class);
Route::apiResource('prestamos', PrestamoController::class);
Route::apiResource('recursos', RecursoCatalogoController::class);
Route::apiResource('sanciones', SancionController::class);
Route::apiResource('tipos-recursos', TipoRecursoController::class);
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('catalogo', RecursoCatalogoController::class);
Route::apiResource('libros', LibroController::class);
Route::apiResource('tesis', TesisController::class);
Route::apiResource('revistas', RevistaController::class);
Route::apiResource('audiovisuales', AudiovisualController::class);
Route::apiResource('enciclopedias', EnciclopediaController::class);
Route::apiResource('mobiliario-didactico', MobiliarioDidacticoController::class);
Route::apiResource('dispositivos-conectividad', ConectividadController::class);

// RUTAS DE CONFIGURACIÓN DEL SISTEMA
Route::get('/configuraciones/{modulo}', [ConfiguracionSistemaController::class, 'getByModulo']);
Route::post('/configuraciones', [ConfiguracionSistemaController::class, 'storeOrUpdate']);


// ==========================================================
// 3. RUTAS PRIVADAS DEL PORTAL DE USUARIO
// ==========================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('usuario/dashboard-stats', [InicioController::class, 'getStats']);
    Route::put('usuario/update-perfil', [PerfilController::class, 'update']);
    Route::get('usuario/grupos-carrera', [PerfilController::class, 'getGruposPorCarrera']);
    Route::get('/usuario/recurso/{id}', [RecursoCatalogoController::class, 'getRecursoDetalle']);
});