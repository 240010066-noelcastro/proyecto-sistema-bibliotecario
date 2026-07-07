<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoRecurso extends Model
{
    protected $table = 'tipos_recursos';
    protected $primaryKey = 'TipoRecurso_ID';
    public $incrementing = true;
    
    protected $fillable = ['NombreTipo', 'Descripcion']; 
}