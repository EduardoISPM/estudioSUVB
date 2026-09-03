<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Archivo extends Model
{
    protected $fillable = [
        'nombre_archivo',
        'rbd',
        'establecimiento',
        'mes_pago',
        'anio_pago',
        'fecha_reporte',
        'columnas_orden',
        'posiciones_columnas',
        'total_general',
        'total_ley_19933'
    ];

    protected $casts = [
        'columnas_orden' => 'array',
        'posiciones_columnas' => 'array'
    ];

    public function registros(): HasMany
    {
        return $this->hasMany(Registro::class);
    }

    public function resumenSedes(): HasMany
    {
        return $this->hasMany(ResumenSede::class);
    }
}