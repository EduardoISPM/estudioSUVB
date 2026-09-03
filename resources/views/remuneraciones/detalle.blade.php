@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        {{-- ============================================================
             CABECERA
             ============================================================ --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">
                            <i class="fas fa-file-invoice text-primary me-2"></i>
                            Remuneraciones - {{ $remuneracion->mes_pago }} {{ $remuneracion->anio_pago }}
                        </h4>
                        <p class="text-muted mb-0">
                            <strong>Empresa:</strong> {{ $remuneracion->empresa }} |
                            <strong>RUT:</strong> {{ $remuneracion->rut_empresa }} |
                            <strong>RBD:</strong> {{ $remuneracion->rbd }}
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('remuneraciones.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             ESTADÍSTICAS RÁPIDAS
             ============================================================ --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Trabajadores</h6>
                        <h2 class="mb-0">{{ $remuneracion->total_trabajadores }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Haberes</h6>
                        <h2 class="mb-0 text-primary">${{ number_format($remuneracion->total_haberes) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Descuentos</h6>
                        <h2 class="mb-0 text-danger">${{ number_format($remuneracion->total_descuentos) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Neto</h6>
                        <h2 class="mb-0 text-success">${{ number_format($remuneracion->total_neto) }}</h2>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             LISTADO DE TRABAJADORES
             ============================================================ --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">
                    <i class="fas fa-users me-2 text-primary"></i>
                    Detalle de Trabajadores
                    <span class="badge bg-secondary ms-2">{{ $remuneracion->trabajadores->count() }}</span>
                </h5>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaTrabajadores">
                        <thead class="table-dark">
                            <tr>
                                <th>RUT</th>
                                <th>Empleado</th>
                                <th>Cargo</th>
                                <th>Centro Costo</th>
                                <th>Sueldo Base</th>
                                <th class="text-end">Total Haberes</th>
                                <th class="text-end">Total Descuentos</th>
                                <th class="text-end">Total Neto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($remuneracion->trabajadores as $trabajador)
                                <tr>
                                    <td><strong>{{ $trabajador->rut }}</strong></td>
                                    <td>{{ $trabajador->empleado }}</td>
                                    <td>{{ $trabajador->tipo }}</td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $trabajador->centro_costo ?? 'Sin sede' }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($trabajador->sueldo_base) }}</td>
                                    <td class="text-end">${{ number_format($trabajador->total_haberes) }}</td>
                                    <td class="text-end">${{ number_format($trabajador->total_descuentos) }}</td>
                                    <td class="text-end fw-bold text-success">
                                        ${{ number_format($trabajador->total_neto) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="4"><strong>TOTAL</strong></td>
                                <td>${{ number_format($remuneracion->trabajadores->sum('sueldo_base')) }}</td>
                                <td class="text-end">${{ number_format($remuneracion->trabajadores->sum('total_haberes')) }}</td>
                                <td class="text-end">${{ number_format($remuneracion->trabajadores->sum('total_descuentos')) }}</td>
                                <td class="text-end text-success">
                                    ${{ number_format($remuneracion->trabajadores->sum('total_neto')) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .table th, .table td {
        white-space: nowrap;
        vertical-align: middle;
    }
    .badge.bg-info {
        background: #8b5cf6 !important;
    }
</style>

<script>
    // Agregar búsqueda en la tabla de trabajadores
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.createElement('div');
        input.className = 'mb-3';
        input.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="buscarTrabajador" placeholder="Buscar por RUT o nombre...">
                    </div>
                </div>
            </div>
        `;
        
        const tabla = document.querySelector('.table-responsive');
        tabla.parentNode.insertBefore(input, tabla);
        
        document.getElementById('buscarTrabajador').addEventListener('keyup', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaTrabajadores tbody tr');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(filtro) ? '' : 'none';
            });
        });
    });
</script>
@endsection