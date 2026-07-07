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
        Schema::create('sanciones', function (Blueprint $table) {
            // PK personalizada
            $table->id('Sancion_ID');
            
            // Llaves foráneas
            $table->unsignedBigInteger('Usuario_ID');
            $table->foreign('Usuario_ID')->references('Usuario_ID')->on('usuarios');
            
            $table->unsignedBigInteger('DetallesPrestamo_ID')->nullable(); // <-- Agregado el nullable
            $table->foreign('DetallesPrestamo_ID')->references('DetallesPrestamo_ID')->on('detalles_prestamo');
            
            // Detalles de la sanción
            $table->string('TipoSancion', 50); // "Mora", "Daño Material", "Pérdida"
            $table->decimal('MontoPago', 10, 2);
            $table->string('EstadoSancion', 30); // "Pendiente", "Pagada", "Condonada"
            $table->dateTime('FechaGeneracion');
            $table->string('Observaciones', 250)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanciones');
    }
};