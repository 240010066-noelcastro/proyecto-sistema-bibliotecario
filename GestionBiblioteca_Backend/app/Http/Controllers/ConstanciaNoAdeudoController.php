<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaNoAdeudo;
use App\Models\Usuario;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ConstanciaNoAdeudoController extends Controller
{
    // 1. Listar historial de constancias emitidas
    public function index()
    {
        $constancias = ConstanciaNoAdeudo::with(['usuario', 'personal'])
            ->orderBy('ConstanciaID', 'DESC')
            ->get();

        return response()->json(['success' => true, 'data' => $constancias]);
    }

    // 2. Verificar si el usuario está libre de adeudos
    public function verificarEstado($usuarioId)
    {
        $prestamosActivos = DB::table('prestamos')
            ->where('Usuario_ID', $usuarioId)
            ->whereIn('EstadoPrestamo_Logico', ['Activo', 'Atrasado'])
            ->count();

        $sancionesPendientes = DB::table('sanciones')
            ->where('Usuario_ID', $usuarioId)
            ->where('EstadoSancion_Logico', 'Pendiente')
            ->count();

        $limpio = ($prestamosActivos === 0 && $sancionesPendientes === 0);

        return response()->json([
            'success' => true,
            'limpio'  => $limpio,
            'prestamos_activos'   => $prestamosActivos,
            'sanciones_pendientes' => $sancionesPendientes
        ]);
    }

    // 3. Evaluar, guardar en BD y generar PDF
    public function generarPdf(Request $request)
    {
        $request->validate([
            'Usuario_ID'  => 'required|integer|exists:usuarios,Usuario_ID',
            'Personal_ID' => 'required|integer',
        ]);

        $usuarioId  = $request->Usuario_ID;
        $personalId = $request->Personal_ID;

        // VERIFICACIÓN ESTRICTA DE ADEUDOS
        $prestamosActivos = DB::table('prestamos')
            ->where('Usuario_ID', $usuarioId)
            ->whereIn('EstadoPrestamo_Logico', ['Activo', 'Atrasado'])
            ->count();

        $sancionesPendientes = DB::table('sanciones')
            ->where('Usuario_ID', $usuarioId)
            ->where('EstadoSancion_Logico', 'Pendiente')
            ->count();

        if ($prestamosActivos > 0 || $sancionesPendientes > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede emitir la constancia. El usuario cuenta con {$prestamosActivos} préstamo(s) activo(s) y {$sancionesPendientes} sanción(es) pendiente(s)."
            ], 422);
        }

        // Obtener datos para la constancia desde la tabla 'usuarios'
        $usuario = Usuario::findOrFail($usuarioId);
        $personal = DB::table('usuarios')->where('Usuario_ID', $personalId)->first();

        $nombreFirmante = 'Encargado(a) de Biblioteca';
        if ($personal) {
            $nombreFirmante = trim("{$personal->NombreUsuario} {$personal->ApellidoPaterno} {$personal->ApellidoMaterno}");
        }

        // 1. REGISTRAR PRIMERO EN LA BASE DE DATOS (obtiene el ID autoincrementable)
        $constancia = ConstanciaNoAdeudo::create([
            'Usuario_ID'   => $usuarioId,
            'Personal_ID'  => $personalId,
            'FechaEmision' => now(),
            'FolioDigital' => 'PENDIENTE',
        ]);

        // 2. Generar Folio Digital Secuencial (Ej. CNA-2026-0024-0001)
        $idConstancia = $constancia->getKey(); // Obtiene automáticamente ConstanciaID
        $folioDigital = 'CNA-' . date('Y') . '-' . str_pad($usuarioId, 4, '0', STR_PAD_LEFT) . '-' . str_pad($idConstancia, 4, '0', STR_PAD_LEFT);

        // 3. Actualizar la constancia con su folio definitivo
        $constancia->update([
            'FolioDigital' => $folioDigital,
        ]);

        // Formatear Fecha en Español
        Carbon::setLocale('es');
        $fechaFormateada = Carbon::now()->isoFormat('D \d\e MMMM \d\e YYYY');

        // Renderizar vista PDF con DomPDF
        $pdf = Pdf::loadView('pdf.constancia_no_adeudo', [
            'usuario'        => $usuario,
            'personal'       => $personal,
            'nombreFirmante' => $nombreFirmante,
            'fecha'          => $fechaFormateada,
            'folioDigital'   => $folioDigital
        ]);

        return $pdf->stream("Constancia_NoAdeudo_{$usuario->Matricula}.pdf");
    }

    // 4. Reimprimir una constancia ya emitida por su ID
    public function reimprimir($id)
    {
        $constancia = ConstanciaNoAdeudo::with(['usuario'])->findOrFail($id);
        $personal   = DB::table('usuarios')->where('Usuario_ID', $constancia->Personal_ID)->first();

        $nombreFirmante = 'Encargado(a) de Biblioteca';
        if ($personal) {
            $nombreFirmante = trim("{$personal->NombreUsuario} {$personal->ApellidoPaterno} {$personal->ApellidoMaterno}");
        }

        Carbon::setLocale('es');
        $fechaFormateada = Carbon::parse($constancia->FechaEmision)->isoFormat('D \d\e MMMM \d\e YYYY');

        $pdf = Pdf::loadView('pdf.constancia_no_adeudo', [
            'usuario'      => $constancia->usuario,
            'personal'     => $personal,
            'fecha'        => $fechaFormateada,
            'folioDigital' => $constancia->FolioDigital
        ]);

        return $pdf->stream("Constancia_{$constancia->FolioDigital}.pdf");
    }
}