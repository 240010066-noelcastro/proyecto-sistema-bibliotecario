<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Editorial extends Model
{
    protected $table = 'editoriales';
    protected $primaryKey = 'Editorial_ID';
    public $incrementing = true;
    
    protected $fillable = [
        'NombreEditorial', 'RazonSocial', 'ISBN_Editorial', 
        'Email', 'DatosContacto', 'PaisEditorial', 
        'DireccionEditorial', 'Observaciones'
    ]; 
}