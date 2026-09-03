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
        Schema::create('remuneraciones_cabecera', function (Blueprint $table) {
            $table->id();
            $table->string('mes_pago', 20);
            $table->string('anio_pago', 4);
            $table->string('nombre_archivo')->nullable();
            $table->string('empresa')->nullable();
            $table->string('rut_empresa', 20)->nullable();
            $table->string('institucion')->nullable();
            $table->string('rbd', 20)->nullable();
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fin')->nullable();
            $table->integer('total_trabajadores')->default(0);
            $table->decimal('total_haberes', 15, 0)->default(0);
            $table->decimal('total_descuentos', 15, 0)->default(0);
            $table->decimal('total_neto', 15, 0)->default(0);
            $table->timestamp('fecha_importacion')->useCurrent();
            $table->timestamps();
            
            $table->unique(['mes_pago', 'anio_pago']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remuneraciones_cabecera');
    }
};