<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id('Prestamo_ID');
            
            // 1. El usuario que pide el libro (Alumno o Maestro)
            $table->unsignedBigInteger('Usuario_ID');
            $table->foreign('Usuario_ID')->references('Usuario_ID')->on('usuarios');
            
            // 2. El bibliotecario (Admin) que entrega (¡AHORA APUNTA A USUARIOS!)
            $table->unsignedBigInteger('PersonalEntrega_ID');
            $table->foreign('PersonalEntrega_ID')->references('Usuario_ID')->on('usuarios');
            
            // 3. El bibliotecario (Admin) que recibe (¡AHORA APUNTA A USUARIOS!)
            $table->unsignedBigInteger('PersonalRecibe_ID')->nullable();
            $table->foreign('PersonalRecibe_ID')->references('Usuario_ID')->on('usuarios');
            
            $table->dateTime('FechaSalida');
            $table->dateTime('FechaDevolucionEstablecida');
            $table->string('EstadoPrestamo', 30);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};