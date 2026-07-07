<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audiovisual extends Model
{
    protected $table = 'audiovisuales';
    protected $primaryKey = 'Recurso_ID';
    public $timestamps = false;

    // ESTO ES LO QUE HAY QUE CAMBIAR AQUÍ ADENTRO:
    protected $fillable = [
        'Recurso_ID',
        'Marca',     // <--- Antes decía Formato
        'NumSerie'   // <--- Antes decía Duracion
    ];
}