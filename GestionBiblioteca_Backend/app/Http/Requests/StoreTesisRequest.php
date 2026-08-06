<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Rol;

class StoreTesisRequest extends FormRequest
{
    public function authorize(): bool
    {
    return $this->user() && $this->user()->Rol_ID === Rol::ADMIN;
    }

    public function rules(): array
    {
        return [
            'Titulo'           => 'required|string|max:150|unique:recursos_catalogo,Titulo',
            'Cantidad_Paginas' => 'nullable|integer|min:0',
            'AnioPublicacion'  => 'nullable|string|max:10',
            'Observaciones'    => 'nullable|string',
            'Mensaje_Legal'    => 'nullable|string',
            'Asesor'           => 'nullable|string|max:150',
            'Carrera'          => 'nullable|string|max:150',
            'Autor'            => 'nullable|string|max:150',
            'TemaRecurso'      => 'nullable|string',
            'Formato'          => 'nullable|string|max:50',
            'Idioma'           => 'nullable|string|max:50',
            'Genero'           => 'nullable|string|max:50',
            'Resumen'          => 'nullable|string',
            'imagen'           => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'documento_pdf'    => 'nullable|file|mimes:pdf|max:20480',
            'Imagen_path'      => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'Titulo.unique' => 'Este título ya se encuentra registrado.',
            'imagen.max'          => 'La portada no debe pesar más de 2 MB (2048 KB).',
            'imagen.mimes'        => 'La portada debe ser una imagen JPG, PNG o WEBP.',
            'documento_pdf.max'   => 'El archivo PDF de la tesis no debe superar los 20 MB.',
            'documento_pdf.mimes' => 'El documento adjunto debe ser en formato PDF.',
        ];
    }
}