<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_sistema';
    protected $primaryKey = 'Config_ID';

    protected $fillable = [
        'Modulo',
        'Clave',
        'Valor'
    ];

    // MAGIA: Le dice a Laravel que maneje este campo JSON como un arreglo nativo
    protected $casts = [
        'Valor' => 'array'
    ];
}