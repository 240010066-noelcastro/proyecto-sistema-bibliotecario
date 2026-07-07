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
        Schema::create('grupos', function (Blueprint $table) {
            // ID personalizado según tu diseño técnico
            $table->id('Grupo_ID'); 
            $table->string('NombreGrupo', 20);
            
            // Llave foránea para la relación con Carreras
            $table->unsignedBigInteger('Carrera_ID');
            $table->foreign('Carrera_ID')->references('Carrera_ID')->on('carreras');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};