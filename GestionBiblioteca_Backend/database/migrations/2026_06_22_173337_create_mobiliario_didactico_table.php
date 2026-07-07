<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mobiliario_didactico', function (Blueprint $table) {
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
            $table->string('Marca', 100)->nullable();
            $table->string('Material', 100)->nullable();
            $table->string('EstadoFisico', 50)->nullable();
        });
    }
    public function down(): void {
        Schema::dropIfExists('mobiliario_didactico');
    }
};