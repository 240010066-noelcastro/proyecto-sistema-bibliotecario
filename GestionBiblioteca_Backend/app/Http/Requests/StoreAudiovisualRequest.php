<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class StoreAudiovisualRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        return [
            'Titulo'          => 'required|string|max:150|unique:recursos_catalogo,Titulo',
            'NumSerie'        => 'nullable|string|unique:audiovisuales,NumSerie',
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
            'NumSerie.unique' => 'Ya existe un equipo registrado con este número de serie.',
            'imagen.max'          => 'La portada no debe pesar más de 2 MB (2048 KB).',
            'imagen.mimes'        => 'La portada debe ser una imagen JPG, PNG o WEBP.',
        ];
    }
}