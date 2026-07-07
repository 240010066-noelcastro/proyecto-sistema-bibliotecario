<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sancion extends Model
{
    protected $table = 'sanciones';
    protected $primaryKey = 'Sancion_ID';
    public $incrementing = true;
    
    // Corregido según tu BD
    protected $fillable = [
        'Usuario_ID', 'DetallesPrestamo_ID', 'TipoSancion', 
        'MontoPago', 'EstadoSancion', 'FechaGeneracion', 'FechaPago', 'Observaciones'
    ]; 
}