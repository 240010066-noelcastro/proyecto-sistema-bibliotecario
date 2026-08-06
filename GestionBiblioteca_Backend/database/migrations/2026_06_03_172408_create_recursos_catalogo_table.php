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
            
            // Relación con Temas (Llama a la tabla temas_catalogo)
            $table->unsignedInteger('Tema_ID')->nullable();
            
            $table->integer('AnioPublicacion');
            $table->string('Imagen_path', 255)->nullable();
            $table->text('Observaciones')->nullable();
            
            // Campos para enlaces, avisos legales y PDFs
            $table->string('URL_Externa', 255)->nullable();
            $table->text('Mensaje_Legal')->nullable();
            $table->string('Archivo_PDF', 255)->nullable();

            // Metadatos bibliográficos
            $table->string('Formato', 50)->nullable();
            $table->integer('Cantidad_Paginas')->nullable();
            $table->string('Idioma', 50)->nullable();
            $table->string('Genero', 100)->nullable();
            $table->text('Resumen')->nullable();

            // Llaves foráneas
            $table->unsignedBigInteger('Autor_ID')->nullable();
            $table->foreign('Autor_ID')->references('Autor_ID')->on('autores');

            $table->unsignedBigInteger('Editorial_ID')->nullable();
            $table->foreign('Editorial_ID')->references('Editorial_ID')->on('editoriales');

            $table->unsignedBigInteger('TipoRecurso_ID');
            $table->foreign('TipoRecurso_ID')->references('TipoRecurso_ID')->on('tipos_recursos');

            $table->string('TipoRecurso', 50)->nullable();

            $table->timestamps();

            // Llave foránea de Tema (Se vincula con temas_catalogo)
            $table->foreign('Tema_ID', 'fk_recursos_temas')
                  ->references('Tema_ID')
                  ->on('temas_catalogo')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos_catalogo');
    }
};