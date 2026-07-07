<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recursos_catalogo', function (Blueprint $table) {
            $table->id('Recurso_ID');
            $table->string('Titulo', 250);
            $table->string('TemaRecurso', 100);
            $table->integer('AnioPublicacion');
            $table->string('Imagen_path', 255)->nullable();
            $table->string('Observaciones', 250)->nullable();
            
            // Llaves foráneas
            $table->unsignedBigInteger('Autor_ID')->nullable();
            $table->foreign('Autor_ID')->references('Autor_ID')->on('autores');
            
            $table->unsignedBigInteger('Editorial_ID')->nullable();
            $table->foreign('Editorial_ID')->references('Editorial_ID')->on('editoriales');
            
            $table->unsignedBigInteger('TipoRecurso_ID');
            $table->foreign('TipoRecurso_ID')->references('TipoRecurso_ID')->on('tipos_recursos');
            
            // Este campo nos dirá a qué tabla hija ir a buscar los detalles
            $table->string('TipoRecurso', 50)->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos_catalogo');
    }
};