{{-- 
    ============================================================
    VISTA: DETALLE POR CENTRO DE COSTO (SEDE)
    ============================================================
    Esta vista muestra:
    1. Cabecera con información del mes/año
    2. Tarjetas de resumen (Trabajadores, Haberes, Leyes Sociales, Neto)
    3. Tabla agrupada por Centro de Costo con:
       - Número
       - Centro de Costo
       - Cantidad de Trabajadores
       - Total Haberes
       - Leyes Sociales
       - % Participación
    4. Gráficos (Pie y Barras)
--}}

@extends('layouts.app')

@section('content')
<div class="container-fluid">
    
    {{-- ============================================================
         SECCIÓN 1: CABECERA
         ============================================================ --}}
    <div class="row mb-4">
        <div class="col">
            <h2>
                <i class="fas fa-building me-2 text-primary"></i>
                Detalle por Centro de Costo
            </h2>
            <p class="text-muted">
                <strong>{{ $remuneracion->mes_pago }} {{ $remuneracion->anio_pago }}</strong>
                - {{ $remuneracion->institucion ?? 'Sin institución' }}
            </p>
        </div>
        <div class="col text-end">
            <a href="{{ route('remuneraciones.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
            <a href="{{ route('remuneraciones.detalle', $remuneracion->id) }}" class="btn btn-info">
                <i class="fas fa-users me-2"></i> Ver por Trabajador
            </a>
        </div>
    </div>

    {{-- ============================================================
         SECCIÓN 2: TARJETAS DE RESUMEN
         ============================================================ --}}
    <div class="row g-3 mb-4">
        {{-- Tarjeta 1: Total Trabajadores --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Total Trabajadores</h6>
                    <h2 class="mb-0">{{ number_format($totales['trabajadores']) }}</h2>
                </div>
            </div>
        </div>

        {{-- Tarjeta 2: Total Haberes --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Total Haberes</h6>
                    <h4 class="mb-0">${{ number_format($totales['haberes']) }}</h4>
                </div>
            </div>
        </div>

        {{-- Tarjeta 3: Total Leyes Sociales --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-white">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Total Leyes Sociales</h6>
                    <h4 class="mb-0">${{ number_format($totales['leyes_sociales'] ?? 0) }}</h4>
                </div>
            </div>
        </div>

        {{-- Tarjeta 4: Total Neto --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Total Neto</h6>
                    <h4 class="mb-0">${{ number_format($totales['neto']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         SECCIÓN 3: TABLA POR CENTRO DE COSTO
         ============================================================ --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-building me-2 text-primary"></i>
                    Resumen por Centro de Costo
                </h5>
                <span class="badge bg-secondary">{{ $centrosCostoOrdenados->count() }} centros</span>
            </div>
        </div>
        <div class="card-body p-0">
            @if($centrosCostoOrdenados->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-compact mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Centro de Costo</th>
                                <th class="text-center">Trabajadores</th>
                                <th class="text-end">Total Haberes</th>
                                <th class="text-end">Leyes Sociales</th>
                                <th class="text-center">% Participación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $contador = 0;
                            @endphp
                            @foreach($centrosCostoOrdenados as $centro => $datos)
                                @php
                                    $contador++;
                                    // Calcular porcentaje de participación
                                    $porcentaje = $totales['haberes'] > 0 
                                        ? ($datos['total_haberes'] / $totales['haberes']) * 100 
                                        : 0;
                                @endphp
                                <tr>
                                    <td>{{ $contador }}</td>
                                    <td>
                                        <strong>{{ $centro }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $datos['cantidad'] }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        ${{ number_format($datos['total_haberes']) }}
                                    </td>
                                    <td class="text-end text-warning">
                                        ${{ number_format($datos['leyes_sociales'] ?? 0) }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <span class="me-2">{{ number_format($porcentaje, 1) }}%</span>
                                            <div class="progress" style="width: 60px; height: 6px;">
                                                <div class="progress-bar" 
                                                     role="progressbar" 
                                                     style="width: {{ $porcentaje }}%; background: #3b82f6;"
                                                     aria-valuenow="{{ $porcentaje }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="2"><strong>TOTALES</strong></td>
                                <td class="text-center">{{ number_format($totales['trabajadores']) }}</td>
                                <td class="text-end text-success">
                                    ${{ number_format($totales['haberes']) }}
                                </td>
                                <td class="text-end text-warning">
                                    ${{ number_format($totales['leyes_sociales'] ?? 0) }}
                                </td>
                                <td class="text-center">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-building fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No hay centros de costo registrados</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
         SECCIÓN 4: GRÁFICOS
         ============================================================ --}}
    <div class="row mt-4">
        {{-- Gráfico 1: Pie - Distribución de Haberes --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>
                        Distribución de Haberes por Centro
                    </h6>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="chartCentros"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráfico 2: Barras - Comparación Haberes vs Leyes Sociales --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2 text-success"></i>
                        Comparación Haberes vs Leyes Sociales
                    </h6>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="chartComparacion"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     SECCIÓN 5: JAVASCRIPT - GRÁFICOS
     ============================================================ --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================================
    // DATOS PARA GRÁFICOS
    // ============================================================
    const labels = {!! json_encode($centrosCostoOrdenados->keys()) !!};
    const haberes = {!! json_encode($centrosCostoOrdenados->pluck('total_haberes')) !!};
    const leyessociales = {!! json_encode($centrosCostoOrdenados->pluck('leyes_sociales')) !!};
    const colores = [
        '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6',
        '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#06b6d4'
    ];

    // ============================================================
    // GRÁFICO 1: PIE - Distribución de Haberes
    // ============================================================
    const ctxPie = document.getElementById('chartCentros');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: haberes,
                    backgroundColor: colores.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 10 },
                            boxWidth: 12,
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const porcentaje = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': $' + context.parsed.toLocaleString() + ' (' + porcentaje + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================================================
    // GRÁFICO 2: BARRAS - Comparación Haberes vs Leyes Sociales
    // ============================================================
    const ctxBarras = document.getElementById('chartComparacion');
    if (ctxBarras) {
        new Chart(ctxBarras, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Haberes',
                        data: haberes,
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: '#22c55e',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Leyes Sociales',
                        data: leyessociales,
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: '#f59e0b',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { size: 11 },
                            boxWidth: 14,
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 } }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + (value / 1000000).toFixed(1) + 'M';
                            },
                            font: { size: 9 }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    }
                }
            }
        });
    }
});
</script>
@endpush

{{-- ============================================================
     SECCIÓN 6: ESTILOS ADICIONALES
     ============================================================ --}}
<style>
    /* Estilos para la tabla compacta */
    .table-compact th, 
    .table-compact td {
        padding: 10px 14px;
        vertical-align: middle;
        font-size: 13px;
    }
    .table-compact thead th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-compact tbody tr:hover {
        background: #f8fafc;
    }
    
    /* Estilos para la barra de progreso */
    .progress {
        background: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }
    .progress-bar {
        transition: width 0.6s ease;
        border-radius: 10px;
    }
</style>
@endsection