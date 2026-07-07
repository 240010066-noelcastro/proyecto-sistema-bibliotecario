<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'Rol_ID';
    public $incrementing = true;
    
    protected $fillable = ['NombreRol']; 
}