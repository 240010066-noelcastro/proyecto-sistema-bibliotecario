<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    protected $table = 'prestamos';
    protected $primaryKey = 'Prestamo_ID';
    public $incrementing = true;
    
    // Corregido: Separamos Entrega y Recibe
    protected $fillable = [
        'Usuario_ID', 
        'PersonalEntrega_ID', 
        'PersonalRecibe_ID', 
        'FechaSalida', 
        'FechaDevolucionEstablecida', 
        'EstadoPrestamo',
        'EstadoPrestamo_Logico'
    ];
}