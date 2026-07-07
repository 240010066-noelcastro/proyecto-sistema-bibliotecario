<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{
    protected $table = 'devoluciones';
    protected $primaryKey = 'Devolucion_ID';
    public $incrementing = true;
    
    protected $fillable = ['DetallesPrestamo_ID', 'Personal_ID', 'FechaDevolucionReal', 'EstadoFisicoDevolucion']; 
}