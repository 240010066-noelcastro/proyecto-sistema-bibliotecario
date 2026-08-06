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
        Schema::create('favoritos', function (Blueprint $table) {
            $table->unsignedBigInteger('Usuario_ID');
            $table->unsignedBigInteger('Recurso_ID');
            $table->timestamps();

            // Definimos una llave primaria compuesta para evitar registros duplicados
            $table->primary(['Usuario_ID', 'Recurso_ID']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};