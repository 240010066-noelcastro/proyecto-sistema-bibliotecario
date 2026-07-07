<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecursoCatalogo extends Model
{
    protected $table = 'recursos_catalogo';
    protected $primaryKey = 'Recurso_ID';
    public $incrementing = true;
    
    protected $fillable = [
        'Titulo', 'Tema_ID', 'AnioPublicacion', 'Imagen_path', 'Observaciones', 
        'URL_Externa', 'Mensaje_Legal', 'Archivo_PDF', 'Autor_ID', 'Editorial_ID', 
        'TipoRecurso_ID', 'TipoRecurso',
        // NUEVOS CAMPOS CENTRALIZADOS LIMPIOS
        'Formato', 'Cantidad_Paginas', 'Idioma', 'Genero', 'Resumen'
    ];
}