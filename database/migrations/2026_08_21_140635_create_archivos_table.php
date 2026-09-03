<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_archivo');
            $table->string('rbd')->nullable();
            $table->string('establecimiento')->nullable();
            $table->string('mes_pago')->nullable();
            $table->string('anio_pago')->nullable();
            $table->date('fecha_reporte')->nullable();
            $table->json('columnas_orden')->nullable();
            $table->json('posiciones_columnas')->nullable();
            $table->decimal('total_general', 15, 0)->default(0);
            $table->decimal('total_ley_19933', 15, 0)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};