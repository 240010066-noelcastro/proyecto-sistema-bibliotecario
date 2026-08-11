<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipos_recursos', function (Blueprint $table) {
            // PK personalizada
            $table->id('TipoRecurso_ID');
            
            // Datos de clasificación
            $table->string('NombreTipo', 50);
            $table->string('Descripcion', 250)->nullable();
            
            $table->timestamps();
        });
        // Inserción automática al migrar
        DB::table('tipos_recursos')->insert([
            ['TipoRecurso_ID' => 1, 'NombreTipo' => 'Libro', 'Descripcion' => 'Material bibliográfico impreso o digital', 'created_at' => now(), 'updated_at' => now()],
            ['TipoRecurso_ID' => 2, 'NombreTipo' => 'Equipo Audiovisual', 'Descripcion' => 'Proyectores, pantallas y equipos multimedia', 'created_at' => now(), 'updated_at' => now()],
            ['TipoRecurso_ID' => 3, 'NombreTipo' => 'Revista / Artículo Científico', 'Descripcion' => 'Publicaciones periódicas y de investigación', 'created_at' => now(), 'updated_at' => now()],
            ['TipoRecurso_ID' => 4, 'NombreTipo' => 'Tesis', 'Descripcion' => 'Trabajos de titulación y memorias de estadía', 'created_at' => now(), 'updated_at' => now()],
            ['TipoRecurso_ID' => 5, 'NombreTipo' => 'Enciclopedia / Diccionario', 'Descripcion' => 'Obras de consulta y referencia general', 'created_at' => now(), 'updated_at' => now()],
            ['TipoRecurso_ID' => 6, 'NombreTipo' => 'Mobiliario Didáctico', 'Descripcion' => 'Materiales y mobiliario de apoyo académico', 'created_at' => now(), 'updated_at' => now()],
            ['TipoRecurso_ID' => 7, 'NombreTipo' => 'Dispositivo de Conectividad', 'Descripcion' => 'Equipos de cómputo y redes', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_recursos');
    }
};
