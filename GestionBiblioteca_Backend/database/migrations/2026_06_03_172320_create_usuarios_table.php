<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('Usuario_ID');
            
            // Llave foránea del Rol (1 = Admin, 2 = Alumno)
            $table->unsignedBigInteger('Rol_ID');
            $table->foreign('Rol_ID')->references('Rol_ID')->on('roles');

            $table->string('NombreUsuario', 50);
            $table->string('ApellidoPaterno', 50)->nullable(); // Nullable para admins
            $table->string('ApellidoMaterno', 50)->nullable();
            
            $table->string('CorreoElectronico', 100)->unique();
            $table->string('password', 255); // Obligatorio para todos
            
            $table->string('Matricula', 30)->unique()->nullable(); // Nullable para admins
            $table->string('Telefono', 20)->nullable();
            $table->text('Direccion')->nullable(); // Solo para admins si quieren
            
            $table->unsignedBigInteger('Grupo_ID')->nullable();
            $table->foreign('Grupo_ID')->references('Grupo_ID')->on('grupos');
            
            $table->string('EstadoCuenta', 20)->default('Activo');
            
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('usuarios');
    }
};