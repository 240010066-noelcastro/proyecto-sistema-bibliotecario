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
        Schema::create('inventario_unidades', function (Blueprint $table) {
            // PK alfanumérica (código de barras/inventario)
            $table->string('Unidad_ID', 50)->primary();
            
            // Llave foránea hacia el catálogo (la ficha técnica)
            $table->unsignedBigInteger('Recurso_ID');
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo');
            
            // Estados físicos
            $table->string('EstadoFisicoInicial', 30);
            $table->string('EstadoDisponibilidad', 30);
            $table->string('EstadoDisponibilidad_Logico', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_unidades');
    }
};