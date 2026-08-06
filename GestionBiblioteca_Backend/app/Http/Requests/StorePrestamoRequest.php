<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class StorePrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        return [
            'Usuario_ID'                 => 'required|integer|exists:usuarios,Usuario_ID',
            'PersonalEntrega_ID'         => 'required|integer|exists:usuarios,Usuario_ID',
            'FechaSalida'                => 'required|date',
            'FechaDevolucionEstablecida' => 'required|date',
            'EstadoPrestamo'             => 'required|string|max:30',
            'unidades'                   => 'required|array',
            'unidades.*'                 => 'required|string|exists:inventario_unidades,Unidad_ID',
        ];
    }
}
