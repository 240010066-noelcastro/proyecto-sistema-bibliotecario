<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'NombreUsuario'     => 'required|string|max:50',
            'ApellidoPaterno'   => 'nullable|string|max:50',
            'ApellidoMaterno'   => 'nullable|string|max:50',
            'CorreoElectronico' => 'required|email|max:100|unique:usuarios,CorreoElectronico',
            'Matricula'         => 'nullable|string|max:30',
            'Telefono'          => 'nullable|string|max:20',
            'Direccion'         => 'nullable|string',
            'Grupo_ID'          => 'nullable|exists:grupos,Grupo_ID',
            'Rol_ID'            => 'required|exists:roles,Rol_ID',
        ];
    }
}
