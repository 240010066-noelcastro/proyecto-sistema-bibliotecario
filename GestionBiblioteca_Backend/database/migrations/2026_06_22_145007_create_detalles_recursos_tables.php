<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Libros
        Schema::create('libros', function (Blueprint $table) {
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
            $table->string('EdicionVolumen', 50)->nullable();
            $table->string('ClasificacionISBN', 30)->nullable();
        });

        // 2. Tesis (Obligado a singular)
        Schema::create('tesis', function (Blueprint $table) {
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
            $table->string('Asesor', 150)->nullable();
            $table->string('GradoCarrera', 100)->nullable();
            $table->string('AutorTexto', 150)->nullable();
        });

        // 3. Revistas
        Schema::create('revistas', function (Blueprint $table) {
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
            $table->string('EdicionVolumen', 50)->nullable();
            $table->string('ClasificacionISSN', 30)->nullable();
        });

        // 4. Audiovisuales
        Schema::create('audiovisuales', function (Blueprint $table) {
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
            $table->string('Formato', 50)->nullable(); // Ej. DVD, CD, Blu-Ray
            $table->string('Duracion', 50)->nullable();
        });

        // 5. Enciclopedias
        Schema::create('enciclopedias', function (Blueprint $table) {
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
            $table->string('EdicionVolumen', 50)->nullable();
            $table->string('ClasificacionISBN', 30)->nullable();
        });

        // 6. Mobiliario Didáctico
        Schema::create('mobiliario_didactico', function (Blueprint $table) {
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
            $table->string('Marca', 100)->nullable();
            $table->string('Material', 100)->nullable();
            $table->string('EstadoFisico', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobiliario_didactico');
        Schema::dropIfExists('enciclopedias');
        Schema::dropIfExists('audiovisuales');
        Schema::dropIfExists('revistas');
        Schema::dropIfExists('tesis');
        Schema::dropIfExists('libros');
    }
};