<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remuneraciones_trabajadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabecera_id')->constrained('remuneraciones_cabecera')->onDelete('cascade');
            $table->string('rut', 20)->nullable();
            $table->string('empleado')->nullable();
            $table->string('periodo', 20)->nullable();
            $table->string('tipo', 50)->nullable();
            $table->string('centro_costo')->nullable();
            $table->integer('dt')->nullable();
            $table->decimal('carga_horaria', 10, 2)->nullable();
            $table->decimal('sueldo_base', 15, 0)->default(0);
            $table->decimal('total_haberes', 15, 0)->default(0);
            $table->decimal('total_descuentos', 15, 0)->default(0);
            $table->decimal('total_neto', 15, 0)->default(0);
            $table->timestamps();
            
            $table->index('rut');
            $table->index('centro_costo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remuneraciones_trabajadores');
    }
};