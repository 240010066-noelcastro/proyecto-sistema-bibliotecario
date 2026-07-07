<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Autor extends Model
{
    protected $table = 'autores';
    protected $primaryKey = 'Autor_ID';
    public $incrementing = true;
    
    protected $fillable = [
        'NombreAutor', 
        'ApellidosAutor', 
        'Seudonimo', 
        'TipoAutor', 
        'Nacionalidad', 
        'Bibliografia', 
        'Email', 
        'Telefono'
    ]; 
}