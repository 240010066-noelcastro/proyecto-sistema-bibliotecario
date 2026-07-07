<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePrestamo extends Model
{
    protected $table = 'detalles_prestamo';
    protected $primaryKey = 'DetallesPrestamo_ID';
    public $incrementing = true;
    
    // Solo necesitamos los campos que son para las relaciones
    protected $fillable = ['Prestamo_ID', 'Unidad_ID']; 
}