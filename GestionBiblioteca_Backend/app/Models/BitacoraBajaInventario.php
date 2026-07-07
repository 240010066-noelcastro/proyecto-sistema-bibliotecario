<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraBajaInventario extends Model
{
    protected $table = 'bitacora_bajas_inventario';
    protected $primaryKey = 'Baja_ID';
    public $incrementing = true;
    
    protected $fillable = ['Unidad_ID', 'Personal_ID', 'MotivoBaja', 'Comentarios', 'FechaBaja']; 
}