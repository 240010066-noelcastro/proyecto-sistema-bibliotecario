<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class UpdateRevistaRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'Titulo'            => 'required|string|max:150|unique:recursos_catalogo,Titulo,' . $id . ',Recurso_ID',
            'ClasificacionISSN' => 'nullable|string|unique:revistas,ClasificacionISSN,' . $id . ',Recurso_ID',
            'Cantidad_Paginas'  => 'nullable|integer|min:0',
            'AnioPublicacion'   => 'nullable|string|max:10',
            'Observaciones'     => 'nullable|string',
            'URL_Externa'       => 'nullable|string|max:255',
            'Mensaje_Legal'     => 'nullable|string',
            'Autor'             => 'nullable|string',
            'Editorial'         => 'nullable|string',
            'TemaRecurso'       => 'nullable|string',
            'Formato'           => 'nullable|string|max:50',
            'Idioma'            => 'nullable|string|max:50',
            'Genero'            => 'nullable|string|max:50',
            'Resumen'           => 'nullable|string',
            'EdicionVolumen'    => 'nullable|string|max:50',
            'imagen'            => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'Imagen_path'       => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'Titulo.unique'            => 'Este título ya se encuentra registrado.',
            'ClasificacionISSN.unique' => 'Este ISSN ya se encuentra registrado en otra revista.',
            'imagen.max'          => 'La portada no debe pesar más de 2 MB (2048 KB).',
            'imagen.mimes'        => 'La portada debe ser una imagen JPG, PNG o WEBP.',
        ];
    }
}