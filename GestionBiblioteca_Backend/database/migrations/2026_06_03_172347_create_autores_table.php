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
        Schema::create('autores', function (Blueprint $table) {
            // PK personalizada con autoincremento
            $table->id('Autor_ID');
            
            // Datos del autor/entidad
            $table->string('NombreAutor', 100);
            $table->string('ApellidosAutor', 100)->nullable();
            $table->string('Seudonimo', 100)->nullable();
            $table->string('TipoAutor', 30);
            $table->string('Nacionalidad', 50)->nullable();
            $table->text('Bibliografia')->nullable();
            
            // Agregamos los dos campos faltantes de la captura
            $table->string('Email', 100)->nullable();
            $table->string('Telefono', 20)->nullable();
            
            // Esto genera los campos created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autores');
    }
};