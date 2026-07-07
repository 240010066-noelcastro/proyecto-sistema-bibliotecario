<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Tesis extends Model {
    protected $table = 'tesis'; // Obligado a singular
    protected $primaryKey = 'Recurso_ID';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['Recurso_ID', 'Asesor', 'GradoCarrera', 'AutorTexto'];
}