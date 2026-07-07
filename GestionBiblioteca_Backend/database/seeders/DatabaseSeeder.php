<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Creamos los roles (CORREGIDO con ::)
        $adminRol = Rol::create(['NombreRol' => 'Administrador']); // ID: 1
        $userRol = Rol::create(['NombreRol' => 'Usuario']);       // ID: 2

        // 2. Creamos el Administrador inicial
        Usuario::create([
            'Rol_ID' => 1, 
            'NombreUsuario' => 'Admin Principal',
            'ApellidoPaterno' => 'UPVE',
            'ApellidoMaterno' => 'Biblioteca',
            'CorreoElectronico' => 'admin@upve.edu.mx',
            'password' => Hash::make('admin123'), // Así se encripta
            'Telefono' => '0000000000',
            'EstadoCuenta' => 'Activo'
        ]);
    }
}