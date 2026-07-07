<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_sistema', function (Blueprint $table) {
            $table->id('Config_ID');
            $table->string('Modulo', 50)->comment('Ej: Sanciones, Catalogo');
            $table->string('Clave', 100)->comment('Ej: tipos_sancion, mensaje_legal');
            $table->json('Valor')->comment('Arreglo de opciones o texto');
            $table->timestamps();

            // Evitar duplicados exactos
            $table->unique(['Modulo', 'Clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_sistema');
    }
};