<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumen_sedes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archivo_id')->constrained()->onDelete('cascade');
            $table->string('sede');
            
            // Subvenciones
            $table->decimal('subvencion_general', 15, 0)->default(0);
            $table->decimal('subvencion_curso_pie', 15, 0)->default(0);
            $table->decimal('subvencion_alumnos_pie', 15, 0)->default(0);
            $table->decimal('total_subvencion', 15, 0)->default(0);
            
            // Ley 19.933
            $table->decimal('subvencion_ley_19933', 15, 0)->default(0);
            $table->decimal('total_ley_19933', 15, 0)->default(0);
            
            // Promedios
            $table->decimal('promedio_asistencia', 10, 4)->default(0);
            $table->decimal('promedio_factor_use', 10, 5)->default(0);
            $table->integer('cantidad_cursos')->default(0);
            
            $table->timestamps();
            
            $table->index('sede');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumen_sedes');
    }
};