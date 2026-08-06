<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Models\Usuario;
use App\Enums\Rol;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Garantizamos que los roles existan cifrados en la BD sin duplicar registros
        DB::table('roles')->updateOrInsert(
            ['Rol_ID' => Rol::ADMIN->value],
            [
                'NombreRol' => Crypt::encryptString('Administrador'),
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        DB::table('roles')->updateOrInsert(
            ['Rol_ID' => Rol::USUARIO->value],
            [
                'NombreRol' => Crypt::encryptString('Usuario'),
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // 2. Creamos el Administrador Inicial vinculado a tu cuenta de Google
        Usuario::firstOrCreate(
            [
                // ⚠️ IMPORTANTE: Cambia este correo por tu correo institucional REAL de Google
                'CorreoElectronico' => '240010066@upve.edu.mx' 
            ],
            [
                'Rol_ID' => Rol::ADMIN->value,
                'NombreUsuario' => 'Admin Principal',
                'ApellidoPaterno' => 'UPVE',
                'ApellidoMaterno' => 'Biblioteca',
                'Matricula' => 'ADM-001',
                'Telefono' => '0000000000',
                'EstadoCuenta' => 'Activo',
                'Grupo_ID' => null
            ]
        );
    }
}