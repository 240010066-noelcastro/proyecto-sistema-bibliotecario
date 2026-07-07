<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Revista extends Model {
    protected $table = 'revistas';
    protected $primaryKey = 'Recurso_ID';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['Recurso_ID', 'EdicionVolumen', 'ClasificacionISSN'];
}