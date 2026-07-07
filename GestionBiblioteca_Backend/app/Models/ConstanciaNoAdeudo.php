<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaNoAdeudo extends Model
{
    protected $table = 'constancias_no_adeudo';
    protected $primaryKey = 'ConstanciaID';
    public $incrementing = true;
    
    // Incluimos las llaves foráneas y el folio
    protected $fillable = ['Usuario_ID', 'Personal_ID', 'FechaEmision', 'FolioDigital']; 
}