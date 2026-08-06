<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorito extends Model
{
    // Vinculamos el modelo a tu tabla de Laragon
    protected $table = 'favoritos';

    // Desactivamos el incremento automático ya que es una llave compuesta
    public $incrementing = false;

    // Campos habilitados para llenado rápido
    protected $fillable = [
        'Usuario_ID',
        'Recurso_ID'
    ];
}