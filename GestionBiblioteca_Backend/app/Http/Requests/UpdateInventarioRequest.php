<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class UpdateInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        return [
            'Recurso_ID'           => 'required|integer|exists:recursos_catalogo,Recurso_ID',
            'EstadoFisicoInicial'  => 'required|string|max:30',
            'EstadoDisponibilidad' => 'required|string|max:30',
        ];
    }
}