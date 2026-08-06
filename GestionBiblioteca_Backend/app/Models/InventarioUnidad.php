<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioUnidad extends Model
{
    protected $table = 'inventario_unidades';
    protected $primaryKey = 'Unidad_ID';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'Unidad_ID', 
        'Recurso_ID', 
        'EstadoFisicoInicial', 
        'EstadoDisponibilidad',
        'EstadoDisponibilidad_Logico'
    ];
}