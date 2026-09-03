<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursoPersonalizado extends Model
{
    /**
     * 🔥 Especificar el nombre correcto de la tabla
     * La tabla se llama 'cursos_personalizados' (con S al final)
     */
    protected $table = 'cursos_personalizados';

    protected $fillable = [
        'curso_id',
        'cod_ens',
        'grado',
        'letra',
        'ens',
        'sede',
        'nombre_curso'
    ];
}