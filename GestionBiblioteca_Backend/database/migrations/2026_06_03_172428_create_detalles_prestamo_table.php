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
        Schema::create('detalles_prestamo', function (Blueprint $table) {
            // PK personalizada
            $table->id('DetallesPrestamo_ID');
            
            // Llaves foráneas
            $table->unsignedBigInteger('Prestamo_ID');
            $table->foreign('Prestamo_ID')->references('Prestamo_ID')->on('prestamos');
            
            // Unidad_ID es string porque viene de inventario_unidades
            $table->string('Unidad_ID', 50);
            $table->foreign('Unidad_ID')->references('Unidad_ID')->on('inventario_unidades');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_prestamo');
    }
};