<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable { 
    use HasApiTokens, HasFactory, Notifiable;
    
    protected $table = 'usuarios';
    protected $primaryKey = 'Usuario_ID'; //
    
    protected $fillable = [
        'Rol_ID', 'NombreUsuario', 'ApellidoPaterno', 'ApellidoMaterno', 
        'CorreoElectronico', 'password', 'Matricula', 'Telefono', 
        'Direccion', 'Grupo_ID', 'FotoPerfil', 'EstadoCuenta' // 💡 Agregamos FotoPerfil aquí[cite: 1]
    ]; 

    protected $hidden = [
        'password', 
    ];

    // 🔗 PUENTE: Conecta este usuario con su Grupo correspondiente[cite: 1]
    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'Grupo_ID', 'Grupo_ID');
    }
}