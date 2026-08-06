<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class UpdatePrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        return [
            'FechaDevolucionEstablecida' => 'required|date',
            'EstadoPrestamo'             => 'required|string|max:30',
            'PersonalRecibe_ID'          => 'nullable|integer|exists:usuarios,Usuario_ID',
        ];
    }
}