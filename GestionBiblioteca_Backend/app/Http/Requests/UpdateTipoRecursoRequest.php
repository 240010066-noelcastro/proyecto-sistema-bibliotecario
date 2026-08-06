<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class UpdateTipoRecursoRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'NombreTipo'  => 'required|string|max:50|unique:tipos_recursos,NombreTipo,' . $id . ',TipoRecurso_ID',
            'Descripcion' => 'nullable|string|max:250',
        ];
    }
}