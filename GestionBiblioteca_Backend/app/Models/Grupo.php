<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupos';
    protected $primaryKey = 'Grupo_ID';
    public $incrementing = true;
    
    // Agregamos 'Estado' al arreglo fillable
    protected $fillable = ['NombreGrupo', 'Carrera_ID', 'Estado']; 

    // Relación con el modelo Carrera
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'Carrera_ID', 'Carrera_ID');
    }
}