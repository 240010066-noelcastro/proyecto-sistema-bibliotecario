<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class UpdateAudiovisualRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'Titulo'          => 'required|string|max:150|unique:recursos_catalogo,Titulo,' . $id . ',Recurso_ID',
            'NumSerie'        => 'nullable|string|unique:audiovisuales,NumSerie,' . $id . ',Recurso_ID',
            'Marca'           => 'nullable|string|max:100',
            'AnioPublicacion' => 'nullable|string|max:10',
            'Observaciones'   => 'nullable|string',
            'imagen'          => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'Imagen_path'     => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'Titulo.unique'   => 'Este título ya se encuentra registrado.',
            'NumSerie.unique' => 'Este número de serie ya pertenece a otro equipo.',
            'imagen.max'          => 'La portada no debe pesar más de 2 MB (2048 KB).',
            'imagen.mimes'        => 'La portada debe ser una imagen JPG, PNG o WEBP.',
        ];
    }
}