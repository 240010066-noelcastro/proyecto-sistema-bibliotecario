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
        Schema::create('constancias_no_adeudo', function (Blueprint $table) {
            // Tu PK personalizada se queda igual
            $table->id('ConstanciaID');
            
            // Llave foránea del alumno
            $table->unsignedBigInteger('Usuario_ID');
            $table->foreign('Usuario_ID')->references('Usuario_ID')->on('usuarios');
            
            // ¡CORREGIDO! Ahora apunta a la tabla unificada de 'usuarios'
            $table->unsignedBigInteger('Personal_ID');
            $table->foreign('Personal_ID')->references('Usuario_ID')->on('usuarios');
            
            // Tus datos de constancia se quedan exactamente igual
            $table->dateTime('FechaEmision');
            $table->string('FolioDigital', 50)->unique();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('constancias_no_adeudo');
    }
};