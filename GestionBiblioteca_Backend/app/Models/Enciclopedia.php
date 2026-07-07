<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Enciclopedia extends Model {
    protected $table = 'enciclopedias';
    protected $primaryKey = 'Recurso_ID';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['Recurso_ID', 'EdicionVolumen', 'ClasificacionISBN'];
}