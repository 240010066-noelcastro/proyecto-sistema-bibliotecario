<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupos';
    protected $primaryKey = 'Grupo_ID'; //[cite: 1]
    public $incrementing = true;
    
    protected $fillable = ['NombreGrupo', 'Carrera_ID']; 

    // 🔗 PUENTE: Conecta este grupo con su Carrera correspondiente[cite: 1]
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'Carrera_ID', 'Carrera_ID');
    }
}