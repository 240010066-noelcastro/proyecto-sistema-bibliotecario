<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carreras';
    protected $primaryKey = 'Carrera_ID';
    public $incrementing = true;
    protected $fillable = ['NombreCarrera', 'Siglas']; // Agregué 'Siglas' por si también la quieres guardar
}