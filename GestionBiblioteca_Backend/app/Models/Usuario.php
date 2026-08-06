<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\Rol;

class Usuario extends Authenticatable { 
    use HasApiTokens, HasFactory, Notifiable;
    
    protected $table = 'usuarios';
    protected $primaryKey = 'Usuario_ID'; //
    
    protected $fillable = [
        'Rol_ID', 'NombreUsuario', 'ApellidoPaterno', 'ApellidoMaterno', 
        'CorreoElectronico', 'Matricula', 'Telefono', 
        'Direccion', 'Grupo_ID', 'FotoPerfil', 'EstadoCuenta'
    ]; 

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'Rol_ID' => Rol::class,
    ];
    
    // 🔗 PUENTE: Conecta este usuario con su Grupo correspondiente[cite: 1]
    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'Grupo_ID', 'Grupo_ID');
    }
}