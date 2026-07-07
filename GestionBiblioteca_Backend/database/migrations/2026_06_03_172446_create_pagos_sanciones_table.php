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
        Schema::create('pagos_sanciones', function (Blueprint $table) {
            // PK personalizada
            $table->id('PagoID');
            
            // Llave foránea hacia sanciones
            $table->unsignedBigInteger('Sancion_ID');
            $table->foreign('Sancion_ID')->references('Sancion_ID')->on('sanciones');
            
            // Detalles económicos
            $table->decimal('MontoPagado', 10, 2);
            $table->dateTime('FechaPago');
            $table->string('MetodoPago', 50);
            $table->string('FolioRecibo', 50)->unique();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_sanciones');
    }
};