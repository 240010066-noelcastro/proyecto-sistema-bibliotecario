<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dispositivos_conectividad', function (Blueprint $table) {
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->string('Marca', 100)->nullable();
            $table->string('NumSerie', 100)->nullable();
            
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dispositivos_conectividad');
    }
};