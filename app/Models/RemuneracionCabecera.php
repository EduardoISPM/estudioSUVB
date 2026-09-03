<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemuneracionCabecera extends Model
{
    /**
     * ============================================================
     * TABLA ASOCIADA
     * ============================================================
     */
    protected $table = 'remuneraciones_cabecera';

    /**
     * ============================================================
     * CAMPOS QUE SE PUEDEN LLENAR
     * ============================================================
     */
    protected $fillable = [
        'mes_pago',
        'anio_pago',
        'nombre_archivo',
        'empresa',
        'rut_empresa',
        'institucion',
        'rbd',
        'periodo_inicio',
        'periodo_fin',
        'total_trabajadores',
        'total_haberes',
        'total_descuentos',
        'total_neto',
        'fecha_importacion'
    ];

    /**
     * ============================================================
     * RELACIONES
     * ============================================================
     * Una cabecera tiene muchos trabajadores
     */
    public function trabajadores()
    {
        return $this->hasMany(RemuneracionTrabajador::class, 'cabecera_id');
    }

    /**
     * ============================================================
     * OBTENER EL NOMBRE COMPLETO DEL MES Y AÑO
     * ============================================================
     */
    public function getPeriodoAttribute()
    {
        return $this->mes_pago . ' ' . $this->anio_pago;
    }

    /**
     * ============================================================
     * OBTENER EL TOTAL DE REMUNERACIONES FORMATEADO
     * ============================================================
     */
    public function getTotalHaberesFormateadoAttribute()
    {
        return '$' . number_format($this->total_haberes, 0, ',', '.');
    }

    public function getTotalDescuentosFormateadoAttribute()
    {
        return '$' . number_format($this->total_descuentos, 0, ',', '.');
    }

    public function getTotalNetoFormateadoAttribute()
    {
        return '$' . number_format($this->total_neto, 0, ',', '.');
    }
}