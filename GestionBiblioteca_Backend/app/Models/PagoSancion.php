<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoSancion extends Model
{
    protected $table = 'pagos_sanciones';
    protected $primaryKey = 'PagoID';
    public $incrementing = true;
    
    protected $fillable = ['Sancion_ID', 'MontoPagado', 'FechaPago', 'MetodoPago', 'FolioRecibo']; 
}