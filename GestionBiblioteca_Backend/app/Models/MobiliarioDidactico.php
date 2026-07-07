<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MobiliarioDidactico extends Model {
    protected $table = 'mobiliario_didactico';
    protected $primaryKey = 'Recurso_ID';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['Recurso_ID', 'Marca', 'Material', 'EstadoFisico'];
}