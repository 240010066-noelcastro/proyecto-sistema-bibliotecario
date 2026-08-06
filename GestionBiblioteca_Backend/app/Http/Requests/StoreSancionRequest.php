<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class StoreSancionRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        return [
            'Usuario_ID'          => 'required|integer|exists:usuarios,Usuario_ID',
            'DetallesPrestamo_ID' => 'nullable|integer|exists:detalles_prestamo,DetallesPrestamo_ID',
            'TipoSancion'         => 'required|string|max:50',
            'MontoPago'           => 'required|numeric|min:0',
            'EstadoSancion'       => 'required|string|max:30',
            'FechaGeneracion'     => 'required|date',
            'FechaPago'           => 'nullable|date',
            'Observaciones'       => 'nullable|string|max:250',
            'DarDeBaja'           => 'nullable|boolean',
        ];
    }
}