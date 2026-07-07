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
        Schema::create('devoluciones', function (Blueprint $table) {
            // PK personalizada
            $table->id('Devolucion_ID');
            
            // Llaves foráneas
            $table->unsignedBigInteger('DetallesPrestamo_ID');
            $table->foreign('DetallesPrestamo_ID')->references('DetallesPrestamo_ID')->on('detalles_prestamo');
            
            $table->unsignedBigInteger('Personal_ID');
            $table->foreign('Personal_ID')->references('Usuario_ID')->on('usuarios');
            
            // Datos de la devolución
            $table->dateTime('FechaDevolucionReal');
            $table->string('EstadoFisicoDevolucion', 30);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devoluciones');
    }
};