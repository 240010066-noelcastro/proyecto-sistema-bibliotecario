<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispositivoConectividad extends Model
{
    protected $table = 'dispositivos_conectividad';
    protected $primaryKey = 'Recurso_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'Recurso_ID',
        'Marca',
        'NumSerie'
    ];
}