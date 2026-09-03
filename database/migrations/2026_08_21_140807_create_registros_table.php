<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archivo_id')->constrained()->onDelete('cascade');
            $table->integer('fila_numero')->nullable();
            
            // Datos del curso
            $table->string('cod_ens')->nullable();
            $table->string('grado')->nullable();
            $table->string('letra')->nullable();
            $table->string('ens')->nullable();
            
            // Datos principales
            $table->decimal('promedio_asistencia', 10, 4)->nullable();
            $table->decimal('factor_use', 10, 5)->nullable();
            $table->decimal('subvencion_base', 15, 0)->nullable();
            $table->string('glosa_subvencion')->nullable();
            
            // Datos calculados
            $table->string('curso_id')->nullable();
            $table->string('sede')->nullable();
            $table->string('tipo_subvencion')->nullable();
            
            // Datos informativos
            $table->string('jec')->nullable();
            $table->string('nivel')->nullable();
            
            // Nuevas columnas (Ley 19.933)
            $table->decimal('subvencion_ley_19933', 15, 0)->default(0);
            $table->decimal('subvencion_ley_19933_incremento', 15, 0)->default(0);
            $table->decimal('subvencion_ruralidad', 15, 0)->default(0);
            $table->decimal('total_ley_19933', 15, 0)->default(0);
            
            // Flexibilidad
            $table->json('datos_completos')->nullable();
            
            $table->timestamps();
            
            $table->index('curso_id');
            $table->index('sede');
            $table->index('ens');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros');
    }
};