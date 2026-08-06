<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaNoAdeudo extends Model
{
    protected $table = 'constancias_no_adeudo';
    protected $primaryKey = 'ConstanciaID';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = ['Usuario_ID', 'Personal_ID', 'FechaEmision', 'FolioDigital'];

    // Relación con el alumno
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'Usuario_ID', 'Usuario_ID');
    }

    // Relación con el encargado de biblioteca
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'Personal_ID', 'Personal_ID');
    }
}