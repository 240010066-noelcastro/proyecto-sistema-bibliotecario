<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tesis', function (Blueprint $table) { // Estrictamente singular
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
            $table->string('Asesor', 150)->nullable();
            $table->string('GradoCarrera', 100)->nullable();
            $table->string('AutorTexto', 150)->nullable();
        });
    }
    public function down(): void {
        Schema::dropIfExists('tesis');
    }
};