<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemuneracionDetalle extends Model
{
    /**
     * ============================================================
     * TABLA ASOCIADA
     * ============================================================
     */
    protected $table = 'remuneraciones_detalle';

    /**
     * ============================================================
     * CAMPOS QUE SE PUEDEN LLENAR
     * ============================================================
     */
    protected $fillable = [
        'trabajador_id',
        'tipo',
        'datos'
    ];

    /**
     * ============================================================
     * CASTS - CONVIERTE JSON A ARRAY AUTOMÁTICAMENTE
     * ============================================================
     */
    protected $casts = [
        'datos' => 'array'
    ];

    /**
     * ============================================================
     * RELACIONES
     * ============================================================
     * Un detalle pertenece a un trabajador
     */
    public function trabajador()
    {
        return $this->belongsTo(RemuneracionTrabajador::class, 'trabajador_id');
    }

    /**
     * ============================================================
     * OBTENER EL TOTAL DEL DETALLE (suma de todos los montos)
     * ============================================================
     */
    public function getTotalAttribute()
    {
        if (!$this->datos) {
            return 0;
        }
        return array_sum($this->datos);
    }

    /**
     * ============================================================
     * OBTENER UN ÍTEM ESPECÍFICO POR CÓDIGO
     * ============================================================
     */
    public function getItem($codigo)
    {
        if (!$this->datos) {
            return 0;
        }
        return $this->datos[$codigo] ?? 0;
    }

    /**
     * ============================================================
     * OBTENER TODOS LOS CÓDIGOS (keys)
     * ============================================================
     */
    public function getCodigosAttribute()
    {
        if (!$this->datos) {
            return [];
        }
        return array_keys($this->datos);
    }
}