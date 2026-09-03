<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registro extends Model
{
    protected $fillable = [
        'archivo_id',
        'fila_numero',
        'cod_ens',
        'grado',
        'letra',
        'ens',
        'promedio_asistencia',
        'factor_use',
        'subvencion_base',
        'glosa_subvencion',
        'curso_id',
        'sede',
        'tipo_subvencion',
        'jec',
        'nivel',
        'subvencion_ley_19933',
        'subvencion_ley_19933_incremento',
        'subvencion_ruralidad',
        'total_ley_19933',
        'datos_completos'
    ];

    protected $casts = [
        'datos_completos' => 'array'
    ];

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class);
    }
}