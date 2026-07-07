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
        Schema::create('tipos_recursos', function (Blueprint $table) {
            // PK personalizada
            $table->id('TipoRecurso_ID');
            
            // Datos de clasificación
            $table->string('NombreTipo', 50);
            $table->string('Descripcion', 250)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_recursos');
    }
};
