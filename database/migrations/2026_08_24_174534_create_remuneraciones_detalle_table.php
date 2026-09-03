<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remuneraciones_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('remuneraciones_trabajadores')->onDelete('cascade');
            $table->string('tipo', 20); // 'haberes' o 'descuentos'
            $table->json('datos');      // Datos variables en JSON
            $table->timestamps();
            
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remuneraciones_detalle');
    }
};