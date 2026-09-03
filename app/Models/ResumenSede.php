<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumenSede extends Model
{
    protected $fillable = [
        'archivo_id',
        'sede',
        'subvencion_general',
        'subvencion_curso_pie',
        'subvencion_alumnos_pie',
        'total_subvencion',
        'subvencion_ley_19933',
        'total_ley_19933',
        'promedio_asistencia',
        'promedio_factor_use',
        'cantidad_cursos'
    ];

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class);
    }
}