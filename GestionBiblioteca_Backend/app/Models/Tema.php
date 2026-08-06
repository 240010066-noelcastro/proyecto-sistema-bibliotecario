<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tema extends Model
{
    use HasFactory;

    // Nombre exacto de la tabla en la migración
    protected $table = 'temas_catalogo';
    
    // Llave primaria personalizada
    protected $primaryKey = 'Tema_ID';

    protected $fillable = [
        'NombreTema'
    ];

    // Relación: Un tema puede pertenecer a muchos recursos
    public function recursos()
    {
        return $this->hasMany(RecursoCatalogo::class, 'Tema_ID', 'Tema_ID');
    }
}