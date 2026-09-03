<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemuneracionTrabajador extends Model
{
    /**
     * ============================================================
     * TABLA ASOCIADA
     * ============================================================
     */
    protected $table = 'remuneraciones_trabajadores';

    /**
     * ============================================================
     * CAMPOS QUE SE PUEDEN LLENAR
     * ============================================================
     */
    protected $fillable = [
        'cabecera_id',
        'rut',
        'empleado',
        'periodo',
        'tipo',
        'centro_costo',
        'dt',
        'carga_horaria',
        'sueldo_base',
        'total_haberes',
        'total_descuentos',
        'total_neto'
    ];

    /**
     * ============================================================
     * RELACIONES
     * ============================================================
     * Un trabajador pertenece a una cabecera
     */
    public function cabecera()
    {
        return $this->belongsTo(RemuneracionCabecera::class, 'cabecera_id');
    }

    /**
     * Un trabajador tiene muchos detalles (haberes y descuentos)
     */
    public function detalles()
    {
        return $this->hasMany(RemuneracionDetalle::class, 'trabajador_id');
    }

    /**
     * ============================================================
     * OBTENER DETALLES POR TIPO
     * ============================================================
     */
    public function getDetallesPorTipo($tipo)
    {
        return $this->detalles()->where('tipo', $tipo)->first();
    }

    /**
     * ============================================================
     * OBTENER HABERES (JSON)
     * ============================================================
     */
    public function getHaberesAttribute()
    {
        $detalle = $this->getDetallesPorTipo('haberes');
        return $detalle ? $detalle->datos : [];
    }

    /**
     * ============================================================
     * OBTENER DESCUENTOS (JSON)
     * ============================================================
     */
    public function getDescuentosAttribute()
    {
        $detalle = $this->getDetallesPorTipo('descuentos');
        return $detalle ? $detalle->datos : [];
    }

    /**
     * ============================================================
     * OBTENER UN ÍTEM ESPECÍFICO DE HABERES
     * ============================================================
     */
    public function getHaberesItem($codigo)
    {
        $haberes = $this->haberes;
        return $haberes[$codigo] ?? 0;
    }

    /**
     * ============================================================
     * OBTENER UN ÍTEM ESPECÍFICO DE DESCUENTOS
     * ============================================================
     */
    public function getDescuentosItem($codigo)
    {
        $descuentos = $this->descuentos;
        return $descuentos[$codigo] ?? 0;
    }
}