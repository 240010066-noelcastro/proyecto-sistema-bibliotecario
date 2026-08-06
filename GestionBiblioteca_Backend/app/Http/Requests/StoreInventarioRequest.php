<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class StoreInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        return [
            'Unidad_ID'            => 'nullable|string|max:50|unique:inventario_unidades,Unidad_ID',
            'Recurso_ID'           => 'required|integer|exists:recursos_catalogo,Recurso_ID',
            'EstadoFisicoInicial'  => 'required|string|max:30',
            'EstadoDisponibilidad' => 'required|string|max:30',
        ];
    }
}