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
        Schema::create('bitacora_bajas_inventario', function (Blueprint $table) {
            // PK personalizada
            $table->id('Baja_ID');
            
            // Llaves foráneas
            $table->string('Unidad_ID', 50);
            $table->foreign('Unidad_ID')->references('Unidad_ID')->on('inventario_unidades');
            
            // ¡CORREGIDO! Ahora apunta a la tabla unificada de 'usuarios'
            $table->unsignedBigInteger('Personal_ID');
            $table->foreign('Personal_ID')->references('Usuario_ID')->on('usuarios');
            
            // Datos de la baja
            $table->string('MotivoBaja', 50);
            $table->string('Comentarios', 250)->nullable();
            $table->dateTime('FechaBaja');
            
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_bajas_inventario');
    }
};