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
        Schema::create('editoriales', function (Blueprint $table) {
            // PK personalizada
            $table->id('Editorial_ID');
            
            // Datos de la editorial
            $table->string('NombreEditorial', 150);
            $table->string('RazonSocial', 150)->nullable(); 
            $table->string('ISBN_Editorial', 30)->nullable();
            $table->string('Email', 100)->nullable(); 
            $table->string('DatosContacto', 250)->nullable();
            $table->string('PaisEditorial', 100)->nullable();
            $table->string('DireccionEditorial', 250)->nullable();
            $table->string('Observaciones', 250)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editoriales');
    }
};