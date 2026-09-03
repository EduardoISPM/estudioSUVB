<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos_personalizados', function (Blueprint $table) {
            $table->id();
            $table->string('curso_id');           // Ej: 110-8-E
            $table->string('cod_ens');            // Ej: 110
            $table->string('grado');              // Ej: 8
            $table->string('letra');              // Ej: E
            $table->string('ens');                // Ej: 110
            $table->string('sede');               // Sede asignada por el usuario
            $table->string('nombre_curso')->nullable(); // Ej: 8° Básico E
            $table->timestamps();
            
            $table->unique('curso_id');
            $table->index('sede');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos_personalizados');
    }
};