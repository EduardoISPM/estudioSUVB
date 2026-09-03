{{-- 
    ============================================================
    VISTA: DASHBOARD PRINCIPAL
    ============================================================
    Esta vista muestra:
    1. Tarjetas de estadísticas (Archivos, Sedes, Cursos, Cursos PIE, Subvención)
    2. Selector de mes
    3. Tabla de resumen por sede
    4. Gráficos (evolución mensual y subvención por sede)
    5. Tabla comparativa: Subvención vs Remuneraciones por Sede
    6. NUEVO: Gráfico comparativo de 2 meses
--}}

@extends('layouts.app')

@section('content')
<style>
    /* ============================================================
       ESTILOS PROFESIONALES - DASHBOARD
       ============================================================ */
    
    /* Tarjetas de estadísticas */
    .stat-card {
        border: none;
        border-radius: 16px;
        padding: 20px 24px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        min-height: 100px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.08);
    }
    .stat-card .stat-icon {
        font-size: 32px;
        opacity: 0.15;
        position: absolute;
        right: 20px;
        top: 20px;
    }
    .stat-card .stat-number {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 2px;
        letter-spacing: -0.5px;
    }
    .stat-card .stat-label {
        font-size: 13px;
        opacity: 0.8;
        margin-bottom: 0;
        font-weight: 500;
    }
    .stat-card .stat-change {
        font-size: 11px;
        opacity: 0.8;
        margin-top: 2px;
    }

    /* Selector */
    .selector-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        background: #ffffff;
    }
    .selector-card .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    .selector-card .form-select {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 10px 16px;
        font-size: 14px;
        background-color: #f8fafc;
        transition: all 0.3s ease;
    }
    .selector-card .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
    }
    .selector-card .btn-filter {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        width: 100%;
    }
    .selector-card .btn-filter:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59,130,246,0.3);
    }

    /* Grid */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }
    @media (min-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: 6fr 5fr;
        }
    }

    /* Tarjetas de gráficos */
    .chart-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        background: #ffffff;
        transition: all 0.3s ease;
    }
    .chart-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.06);
    }
    .chart-card .card-header {
        background: transparent;
        border-bottom: 1px solid #f1f5f9;
        padding: 16px 20px 12px 20px;
        font-weight: 600;
        font-size: 15px;
        color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    .chart-card .card-header .badge-date {
        background: #f1f5f9;
        color: #475569;
        font-weight: 500;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
    }
    .chart-card .card-body {
        padding: 16px 20px 20px;
    }
    .chart-container {
        position: relative;
        height: 280px;
        width: 100%;
    }
    .chart-container-sm {
        height: 240px;
    }

    /* Tabla compacta */
    .table-compact {
        font-size: 13px;
        margin-bottom: 0;
    }
    .table-compact th,
    .table-compact td {
        padding: 8px 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-compact thead th {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
    }
    .table-compact tbody tr:hover {
        background: #f8fafc;
    }
    .table-compact .badge-sede {
        background: #eef2ff;
        color: #4f46e5;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        white-space: nowrap;
    }
    .table-compact .badge-success-custom {
        background: #dcfce7;
        color: #16a34a;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
    }
    .table-compact .badge-warning-custom {
        background: #fef3c7;
        color: #d97706;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
    }
    .table-compact .badge-secondary-custom {
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
    }
    .text-purple { color: #8b5cf6; }
    .text-success-dark { color: #16a34a; }

    /* ============================================================
       ESTILOS PARA GRÁFICO COMPARATIVO (NUEVO)
       ============================================================ */
    
    /* Leyenda de comparación */
    .comparison-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        padding: 10px 16px;
        background: #f8fafc;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    .comparison-legend .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 500;
        color: #0f172a;
    }
    .comparison-legend .legend-color {
        width: 24px;
        height: 4px;
        border-radius: 3px;
    }
    .comparison-legend .legend-color.solid {
        opacity: 1;
    }
    .comparison-legend .legend-color.dashed {
        border: 2px dashed #3b82f6;
        background: transparent;
        height: 0;
    }
    .comparison-legend .badge {
        font-size: 10px;
        padding: 2px 8px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stat-card .stat-number { font-size: 22px; }
        .stat-card { padding: 16px 18px; min-height: 80px; }
        .chart-container { height: 200px; }
        .comparison-legend {
            gap: 8px;
            padding: 8px 12px;
        }
        .comparison-legend .legend-item {
            font-size: 10px;
        }
        .comparison-legend .legend-color {
            width: 18px;
            height: 3px;
        }
    }
</style>

{{-- ============================================================
     SECCIÓN 1: TARJETAS DE ESTADÍSTICAS (FLEXBOX)
     ============================================================ --}}
<div class="d-flex gap-2 mb-4 flex-wrap">
    {{-- Tarjeta 1: Archivos Importados --}}
    <div class="flex-fill" style="min-width: 120px;">
        <div class="stat-card text-white" style="background: linear-gradient(135deg, #6366f1, #4f46e5); padding: 10px 14px; min-height: 65px; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-number" style="font-size: 20px; line-height: 1.2;">{{ $totalArchivos ?? 0 }}</div>
                    <div class="stat-label" style="font-size: 10px; opacity: 0.8;">Archivos</div>
                </div>
                <i class="fas fa-file-alt" style="font-size: 22px; opacity: 0.25;"></i>
            </div>
        </div>
    </div>

    {{-- Tarjeta 2: Sedes Activas --}}
    <div class="flex-fill" style="min-width: 120px;">
        <div class="stat-card text-white" style="background: linear-gradient(135deg, #22c55e, #16a34a); padding: 10px 14px; min-height: 65px; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-number" style="font-size: 20px; line-height: 1.2;">{{ $totalSedes ?? 0 }}</div>
                    <div class="stat-label" style="font-size: 10px; opacity: 0.8;">Sedes</div>
                </div>
                <i class="fas fa-school" style="font-size: 22px; opacity: 0.25;"></i>
            </div>
        </div>
    </div>

    {{-- Tarjeta 3: Cursos --}}
    <div class="flex-fill" style="min-width: 120px;">
        <div class="stat-card text-white" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); padding: 10px 14px; min-height: 65px; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-number" style="font-size: 20px; line-height: 1.2;">{{ number_format($totalCursos ?? 0) }}</div>
                    <div class="stat-label" style="font-size: 10px; opacity: 0.8;">Cursos</div>
                </div>
                <i class="fas fa-users" style="font-size: 22px; opacity: 0.25;"></i>
            </div>
        </div>
    </div>

    {{-- Tarjeta 4: Cursos PIE --}}
    <div class="flex-fill" style="min-width: 120px;">
        <div class="stat-card text-white" style="background: linear-gradient(135deg, #ec4899, #db2777); padding: 10px 14px; min-height: 65px; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-number" style="font-size: 20px; line-height: 1.2;">{{ number_format($cursosPIE ?? 0) }}</div>
                    <div class="stat-label" style="font-size: 10px; opacity: 0.8;">Cursos PIE</div>
                </div>
                <i class="fas fa-hand-holding-heart" style="font-size: 22px; opacity: 0.25;"></i>
            </div>
        </div>
    </div>

    {{-- Tarjeta 5: Total Subvención --}}
    <div class="flex-fill" style="min-width: 140px;">
        <div class="stat-card text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 10px 14px; min-height: 65px; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-number" style="font-size: 16px; line-height: 1.2;">${{ number_format($archivoSeleccionado->total_general ?? 0) }}</div>
                    <div class="stat-label" style="font-size: 10px; opacity: 0.8;">Subvención</div>
                </div>
                <i class="fas fa-coins" style="font-size: 22px; opacity: 0.25;"></i>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     SECCIÓN 2: SELECTOR DE MES
     ============================================================ --}}
@if($archivosDisponibles && count($archivosDisponibles) > 0)
    <div class="card selector-card mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" action="{{ route('dashboard') }}" class="row g-3 align-items-end" id="formSelector">
                <div class="col-md-8">
                    <label for="selectorMes" class="form-label mb-1">
                        <i class="fas fa-calendar-alt me-1"></i> Seleccionar Mes
                    </label>
                    <select name="mes" id="selectorMes" class="form-select" onchange="actualizarAnioYEnviar()">
                        @php
                            $mesActual = '';
                        @endphp
                        @foreach($archivosDisponibles as $archivo)
                            @if($mesActual != $archivo->anio_pago)
                                @if(!$loop->first)
                                    </optgroup>
                                @endif
                                <optgroup label="━━━ {{ $archivo->anio_pago }} ━━━">
                                @php
                                    $mesActual = $archivo->anio_pago;
                                @endphp
                            @endif
                            <option value="{{ $archivo->mes_pago }}" 
                                data-anio="{{ $archivo->anio_pago }}"
                                {{ $mes == $archivo->mes_pago && $anio == $archivo->anio_pago ? 'selected' : '' }}>
                                {{ ucfirst(strtolower($archivo->mes_pago)) }}
                            </option>
                        @endforeach
                        @if(!empty($archivosDisponibles))
                            </optgroup>
                        @endif
                    </select>
                    <input type="hidden" name="anio" id="hiddenAnio" value="{{ $anio }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-sliders-h me-2"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- ============================================================
     SECCIÓN 3: DASHBOARD GRID (TABLA + GRÁFICOS)
     ============================================================ --}}
<div class="dashboard-grid">
    {{-- COLUMNA IZQUIERDA --}}
    <div>
        {{-- Detalle del archivo --}}
        @if($archivoSeleccionado)
            <div class="card chart-card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block" style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Archivo</small>
                        <span class="badge-file">{{ $archivoSeleccionado->nombre_archivo }}</span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block" style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Importado</small>
                        <span style="font-weight: 600; font-size: 14px; color: #0f172a;">{{ $archivoSeleccionado->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Resumen por Sede --}}
        <div class="card chart-card">
            <div class="card-header">
                <span><i class="fas fa-table me-2 text-primary"></i> Resumen por Sede</span>
                <span class="badge-date">{{ $mes ?? '' }} {{ $anio ?? '' }}</span>
            </div>
            <div class="card-body">
                @if(isset($resumenSedes) && count($resumenSedes) > 0)
                    <div class="table-responsive">
                        <table class="table table-compact">
                            <thead>
                                <tr>
                                    <th>Sede</th>
                                    <th class="text-end">General</th>
                                    <th class="text-end">Curso PIE</th>
                                    <th class="text-end">Alumnos PIE</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">% Asist.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalGeneral = 0;
                                    $totalCursoPie = 0;
                                    $totalAlumnosPie = 0;
                                    $totalSubvencion = 0;
                                    $totalAsistencia = 0;
                                    $countSedes = 0;
                                @endphp
                                @foreach($resumenSedes as $sede)
                                    @php
                                        $totalGeneral += $sede->subvencion_general;
                                        $totalCursoPie += $sede->subvencion_curso_pie;
                                        $totalAlumnosPie += $sede->subvencion_alumnos_pie;
                                        $totalSubvencion += $sede->total_subvencion;
                                        if ($sede->promedio_asistencia > 0) {
                                            $totalAsistencia += $sede->promedio_asistencia;
                                            $countSedes++;
                                        }
                                    @endphp
                                    <tr>
                                        <td><span class="badge-sede">{{ $sede->sede }}</span></td>
                                        <td class="text-end">${{ number_format($sede->subvencion_general) }}</td>
                                        <td class="text-end">${{ number_format($sede->subvencion_curso_pie) }}</td>
                                        <td class="text-end">${{ number_format($sede->subvencion_alumnos_pie) }}</td>
                                        <td class="text-end fw-bold text-primary">${{ number_format($sede->total_subvencion) }}</td>
                                        <td class="text-end">{{ number_format($sede->promedio_asistencia, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-end fw-bold">${{ number_format($totalGeneral) }}</td>
                                    <td class="text-end fw-bold">${{ number_format($totalCursoPie) }}</td>
                                    <td class="text-end fw-bold">${{ number_format($totalAlumnosPie) }}</td>
                                    <td class="text-end fw-bold text-success-dark">${{ number_format($totalSubvencion) }}</td>
                                    <td class="text-end fw-bold">{{ $countSedes > 0 ? number_format($totalAsistencia / $countSedes, 2) : 0 }}%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-info-circle fa-3x text-muted mb-2"></i>
                        <p class="text-muted">No hay datos para {{ $mes ?? '' }} {{ $anio ?? '' }}</p>
                        <a href="{{ route('importar.form') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-upload me-2"></i> Importar Archivo
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- COLUMNA DERECHA --}}
    <div>
        {{-- Gráfico 1: Evolución Mensual --}}
        <div class="card chart-card mb-3">
            <div class="card-header">
                <span><i class="fas fa-chart-line me-2 text-success"></i> Evolución Mensual</span>
                <span class="badge-date">Millones $</span>
            </div>
            <div class="card-body">
                <div class="chart-container chart-container-sm">
                    <canvas id="chartEvolucion"></canvas>
                </div>
            </div>
        </div>

        {{-- ============================================================
             GRÁFICO 2: COMPARACIÓN SUBVENCIÓN POR SEDE (2 MESES) - NUEVO
             ============================================================ --}}
        <div class="card chart-card">
            <div class="card-header">
                <span>
                    <i class="fas fa-chart-bar me-2 text-primary"></i> 
                    Comparación Subvención por Sede
                </span>
                <span class="badge-date">
                    {{ $mesActual ?? '' }} {{ $anioActual ?? '' }} vs {{ $mesAnterior ?? '' }} {{ $anioAnterior ?? '' }}
                </span>
            </div>
            <div class="card-body">
                {{-- Leyenda de colores --}}
                <div class="comparison-legend mb-3">
                    <div class="legend-item">
                        <span class="legend-color solid" style="background: #3b82f6;"></span>
                        <span>{{ $mesActual ?? 'Actual' }} (Sólido)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color dashed" style="border: 2px dashed #3b82f6;"></span>
                        <span>{{ $mesAnterior ?? 'Anterior' }} (Rayado)</span>
                    </div>
                    <div class="legend-item ms-3">
                        <span class="badge bg-success">▲</span>
                        <span>Incremento</span>
                    </div>
                    <div class="legend-item">
                        <span class="badge bg-danger">▼</span>
                        <span>Decremento</span>
                    </div>
                </div>

                {{-- Contenedor del gráfico --}}
                <div class="chart-container chart-container-sm">
                    <canvas id="chartComparativo"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     SECCIÓN 4: TABLA COMPARATIVA - SUBVENCIÓN VS REMUNERACIONES
     ============================================================ --}}
@if(isset($datosComparacion) && $datosComparacion && count($datosComparacion['detalle']) > 0)
    <div class="card chart-card mt-3">
        <div class="card-header">
            <span>
                <i class="fas fa-balance-scale me-2 text-purple"></i>
                Comparación Subvención vs Remuneraciones por Sede
            </span>
            <span class="badge-date">{{ $mes ?? '' }} {{ $anio ?? '' }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th>Sede</th>
                            <th class="text-end">Remuneraciones</th>
                            <th class="text-end">Subvenciones</th>
                            <th class="text-end">Diferencia</th>
                            <th class="text-end">% Cobertura</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalRemuneraciones = 0;
                            $totalSubvenciones = 0;
                        @endphp
                        @foreach($datosComparacion['detalle'] as $item)
                            @php
                                $totalRemuneraciones += $item['remuneraciones'];
                                $totalSubvenciones += $item['subvenciones'];
                            @endphp
                            <tr>
                                <td><span class="badge-sede">{{ $item['sede'] }}</span></td>
                                <td class="text-end">${{ number_format($item['remuneraciones']) }}</td>
                                <td class="text-end">${{ number_format($item['subvenciones']) }}</td>
                                <td class="text-end fw-bold {{ $item['diferencia'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    ${{ number_format($item['diferencia']) }}
                                </td>
                                <td class="text-end">
                                    @if($item['remuneraciones'] > 0)
                                        <span class="badge {{ $item['porcentaje'] >= 100 ? 'badge-success-custom' : 'badge-warning-custom' }}">
                                            {{ number_format($item['porcentaje'], 2) }}%
                                        </span>
                                    @else
                                        <span class="badge-secondary-custom">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td><strong>TOTAL</strong></td>
                            <td class="text-end">${{ number_format($totalRemuneraciones) }}</td>
                            <td class="text-end">${{ number_format($totalSubvenciones) }}</td>
                            <td class="text-end {{ ($totalSubvenciones - $totalRemuneraciones) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($totalSubvenciones - $totalRemuneraciones) }}
                            </td>
                            <td class="text-end">
                                @if($totalRemuneraciones > 0)
                                    <span class="badge {{ ($totalSubvenciones / $totalRemuneraciones) >= 1 ? 'badge-success-custom' : 'badge-warning-custom' }}">
                                        {{ number_format(($totalSubvenciones / $totalRemuneraciones) * 100, 2) }}%
                                    </span>
                                @else
                                    <span class="badge-secondary-custom">N/A</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>Remuneraciones</strong> = Total Haberes + Total Leyes Sociales &nbsp;|&nbsp;
                    <strong>% Cobertura</strong> = (Subvenciones / Remuneraciones) × 100
                </small>
            </div>
        </div>
    </div>
@elseif(isset($remuneracionMes) && !$remuneracionMes)
    <div class="card chart-card mt-3">
        <div class="card-body text-center py-4">
            <i class="fas fa-info-circle fa-3x text-muted mb-2"></i>
            <p class="text-muted">
                No hay datos de remuneraciones para {{ $mes ?? '' }} {{ $anio ?? '' }}
            </p>
            <small class="text-muted">
                Importa un archivo de remuneraciones para ver la comparación
            </small>
            <div class="mt-3">
                <a href="{{ route('remuneraciones.index') }}" class="btn btn-sm btn-success">
                    <i class="fas fa-money-bill-wave me-2"></i> Importar Remuneraciones
                </a>
            </div>
        </div>
    </div>
@endif

{{-- ============================================================
     JAVASCRIPT - GRÁFICOS
     ============================================================ --}}
@push('scripts')
<script>
    /**
     * ============================================================
     * FUNCIÓN: actualizarAnioYEnviar()
     * ============================================================
     * Actualiza el año oculto y envía el formulario del selector
     */
    function actualizarAnioYEnviar() {
        const select = document.getElementById('selectorMes');
        if (!select) return;
        const selectedOption = select.options[select.selectedIndex];
        const anio = selectedOption.getAttribute('data-anio');
        document.getElementById('hiddenAnio').value = anio;
        document.getElementById('formSelector').submit();
    }

    /**
     * ============================================================
     * GRÁFICO 1: EVOLUCIÓN MENSUAL (4 LÍNEAS CON PUNTOS)
     * ============================================================
     * Muestra: Total, General, Curso PIE, Alumnos PIE
     */
    document.addEventListener('DOMContentLoaded', function() {
        const ctxEvolucion = document.getElementById('chartEvolucion');
        if (ctxEvolucion) {
            new Chart(ctxEvolucion, {
                type: 'line',
                data: {
                    labels: {!! json_encode($mesesEvolucion ?? []) !!},
                    datasets: [
                        {
                            label: 'Total',
                            data: {!! json_encode($datosEvolucionTotal ?? []) !!},
                            borderColor: '#1e293b',
                            backgroundColor: 'rgba(30, 41, 59, 0.05)',
                            borderWidth: 3,
                            pointBackgroundColor: '#1e293b',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 9,
                            fill: false,
                            tension: 0.3,
                            order: 0
                        },
                        {
                            label: 'General',
                            data: {!! json_encode($datosEvolucionGeneral ?? []) !!},
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.05)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            fill: false,
                            tension: 0.3,
                            order: 1
                        },
                        {
                            label: 'Curso PIE',
                            data: {!! json_encode($datosEvolucionCursoPie ?? []) !!},
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.05)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#22c55e',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            fill: false,
                            tension: 0.3,
                            order: 2
                        },
                        {
                            label: 'Alumnos PIE',
                            data: {!! json_encode($datosEvolucionAlumnosPie ?? []) !!},
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.05)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            fill: false,
                            tension: 0.3,
                            order: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 16,
                                padding: 15,
                                font: { size: 12, weight: 'bold' },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.parsed.y.toFixed(2) + 'M';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) { return '$' + value.toFixed(0) + 'M'; },
                                font: { size: 10 }
                            },
                            grid: { color: 'rgba(0,0,0,0.04)' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 }, maxTicksLimit: 12 }
                        }
                    }
                }
            });
        }

        /**
         * ============================================================
         * GRÁFICO COMPARATIVO: 2 MESES (NUEVO)
         * ============================================================
         * Este gráfico muestra barras agrupadas comparando los datos
         * de subvención entre el mes actual y el mes anterior.
         * 
         * Características:
         * - Barras sólidas = mes actual
         * - Barras rayadas = mes anterior
         * - 3 categorías por sede: General, Curso PIE, Alumnos PIE
         * - Tooltip con valores en millones y porcentaje de cambio
         * - Etiquetas de cambio (▲/▼ + %) sobre las barras
         */
        const ctxComparativo = document.getElementById('chartComparativo');
        if (ctxComparativo) {
            // Obtener datos del controlador
            const sedesData = {!! json_encode($sedesComparativas ?? []) !!};
            
            // Verificar si hay datos
            if (!sedesData || sedesData.length === 0) {
                ctxComparativo.parentElement.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay datos para comparar</p>
                        <small class="text-muted">
                            Importa archivos de ${'{'}{{ $mesAnterior ?? '' }} ${'{'}{{ $anioAnterior ?? '' }} y 
                            {{ $mesActual ?? '' }} {{ $anioActual ?? '' }}
                        </small>
                    </div>
                `;
                return;
            }

            // Preparar etiquetas (nombres de sedes)
            const labels = sedesData.map(item => item.sede);
            
            // Preparar datos del mes ACTUAL
            const actualGeneral = sedesData.map(item => item.actual_general);
            const actualCursoPie = sedesData.map(item => item.actual_curso_pie);
            const actualAlumnosPie = sedesData.map(item => item.actual_alumnos_pie);
            
            // Preparar datos del mes ANTERIOR
            const anteriorGeneral = sedesData.map(item => item.anterior_general);
            const anteriorCursoPie = sedesData.map(item => item.anterior_curso_pie);
            const anteriorAlumnosPie = sedesData.map(item => item.anterior_alumnos_pie);
            
            // Crear el gráfico
            new Chart(ctxComparativo, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        // ================================================
                        // GRUPO 1: MES ACTUAL (SÓLIDO)
                        // ================================================
                        {
                            label: '{{ $mesActual }} General',
                            data: actualGeneral,
                            backgroundColor: 'rgba(59, 130, 246, 0.85)',
                            borderColor: '#3b82f6',
                            borderWidth: 1,
                            borderRadius: 4,
                            order: 0,
                            barPercentage: 0.9,
                            categoryPercentage: 0.7
                        },
                        {
                            label: '{{ $mesActual }} Curso PIE',
                            data: actualCursoPie,
                            backgroundColor: 'rgba(34, 197, 94, 0.85)',
                            borderColor: '#22c55e',
                            borderWidth: 1,
                            borderRadius: 4,
                            order: 1,
                            barPercentage: 0.9,
                            categoryPercentage: 0.7
                        },
                        {
                            label: '{{ $mesActual }} Alumnos PIE',
                            data: actualAlumnosPie,
                            backgroundColor: 'rgba(245, 158, 11, 0.85)',
                            borderColor: '#f59e0b',
                            borderWidth: 1,
                            borderRadius: 4,
                            order: 2,
                            barPercentage: 0.9,
                            categoryPercentage: 0.7
                        },
                        
                        // ================================================
                        // GRUPO 2: MES ANTERIOR (RAYADO)
                        // ================================================
                        {
                            label: '{{ $mesAnterior }} General',
                            data: anteriorGeneral,
                            backgroundColor: 'rgba(59, 130, 246, 0.25)',
                            borderColor: '#3b82f6',
                            borderWidth: 2,
                            borderDash: [4, 4],
                            borderRadius: 4,
                            order: 3,
                            barPercentage: 0.9,
                            categoryPercentage: 0.7
                        },
                        {
                            label: '{{ $mesAnterior }} Curso PIE',
                            data: anteriorCursoPie,
                            backgroundColor: 'rgba(34, 197, 94, 0.25)',
                            borderColor: '#22c55e',
                            borderWidth: 2,
                            borderDash: [4, 4],
                            borderRadius: 4,
                            order: 4,
                            barPercentage: 0.9,
                            categoryPercentage: 0.7
                        },
                        {
                            label: '{{ $mesAnterior }} Alumnos PIE',
                            data: anteriorAlumnosPie,
                            backgroundColor: 'rgba(245, 158, 11, 0.25)',
                            borderColor: '#f59e0b',
                            borderWidth: 2,
                            borderDash: [4, 4],
                            borderRadius: 4,
                            order: 5,
                            barPercentage: 0.9,
                            categoryPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        // ================================================
                        // CONFIGURACIÓN DE LA LEYENDA
                        // ================================================
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 14,
                                padding: 10,
                                font: { 
                                    size: 10, 
                                    weight: '600' 
                                },
                                usePointStyle: true,
                                pointStyle: 'rectRounded'
                            }
                        },
                        
                        // ================================================
                        // CONFIGURACIÓN DEL TOOLTIP
                        // ================================================
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 11 },
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    // Mostrar el valor en millones con formato
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y;
                                    return label + ': $' + value.toFixed(2) + 'M';
                                },
                                afterBody: function(tooltipItems) {
                                    // Mostrar cambio porcentual en la sede
                                    const dataIndex = tooltipItems[0].dataIndex;
                                    const item = sedesData[dataIndex];
                                    if (item && item.cambio_porcentual !== 0) {
                                        const cambio = item.cambio_porcentual;
                                        const icono = cambio > 0 ? '📈' : '📉';
                                        const color = cambio > 0 ? '#22c55e' : '#ef4444';
                                        return `$'{'}'{icono} Cambio: $'{'}'<span style="color:${color};font-weight:bold;">${cambio > 0 ? '+' : ''}${cambio}%</span>`;
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    scales: {
                        // ================================================
                        // EJES X (Sedes)
                        // ================================================
                        x: {
                            grid: { 
                                display: false 
                            },
                            ticks: { 
                                font: { 
                                    size: 9, 
                                    weight: '500' 
                                },
                                maxRotation: 25,
                                minRotation: 0
                            }
                        },
                        
                        // ================================================
                        // EJES Y (Valores en millones)
                        // ================================================
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) { 
                                    return '$' + value.toFixed(0) + 'M'; 
                                },
                                font: { 
                                    size: 9 
                                }
                            },
                            grid: { 
                                color: 'rgba(0,0,0,0.05)',
                                drawBorder: false
                            }
                        }
                    }
                },
                
                // ========================================================
                // PLUGIN: Mostrar etiquetas de cambio sobre las barras
                // ========================================================
                plugins: [{
                    id: 'customLabels',
                    afterDraw: function(chart) {
                        const ctx = chart.ctx;
                        const meta = chart.getDatasetMeta(0); // Primer dataset
                        const data = chart.data.datasets[0].data;
                        
                        // Solo dibujar si hay datos
                        if (!meta.data || meta.data.length === 0) return;
                        
                        // Para cada barra del primer dataset (mes actual)
                        meta.data.forEach((bar, index) => {
                            const item = sedesData[index];
                            if (!item || item.cambio_porcentual === 0) return;
                            
                            const cambio = item.cambio_porcentual;
                            const esPositivo = cambio > 0;
                            
                            // Configurar estilo del texto
                            ctx.save();
                            ctx.font = 'bold 10px system-ui';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            
                            // Color según tendencia
                            ctx.fillStyle = esPositivo ? '#22c55e' : '#ef4444';
                            
                            // Posición: encima de la barra más alta
                            const maxValue = Math.max(
                                item.actual_total,
                                item.anterior_total
                            );
                            
                            // Calcular posición Y
                            const yPos = chart.scales.y.getPixelForValue(maxValue) - 8;
                            const xPos = bar.x;
                            
                            // Dibujar el texto con icono
                            const icono = esPositivo ? '▲' : '▼';
                            const texto = `${icono} ${Math.abs(cambio)}%`;
                            
                            // Fondo semitransparente para mejor legibilidad
                            const metrics = ctx.measureText(texto);
                            const padding = 4;
                            const rectX = xPos - (metrics.width / 2) - padding;
                            const rectY = yPos - 14;
                            const rectW = metrics.width + (padding * 2);
                            const rectH = 16;
                            
                            ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
                            ctx.shadowColor = 'rgba(0,0,0,0.1)';
                            ctx.shadowBlur = 6;
                            ctx.shadowOffsetX = 0;
                            ctx.shadowOffsetY = 2;
                            ctx.beginPath();
                            ctx.roundRect(rectX, rectY, rectW, rectH, 4);
                            ctx.fill();
                            
                            // Restaurar sombra
                            ctx.shadowColor = 'transparent';
                            ctx.shadowBlur = 0;
                            
                            // Dibujar el texto
                            ctx.fillStyle = esPositivo ? '#16a34a' : '#dc2626';
                            ctx.font = 'bold 9px system-ui';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.fillText(texto, xPos, yPos + 12);
                            
                            ctx.restore();
                        });
                    }
                }]
            });
        }
    });

    // Polyfill para roundRect en navegadores antiguos
    if (!CanvasRenderingContext2D.prototype.roundRect) {
        CanvasRenderingContext2D.prototype.roundRect = function(x, y, w, h, r) {
            if (r > w/2) r = w/2;
            if (r > h/2) r = h/2;
            this.moveTo(x + r, y);
            this.arcTo(x + w, y, x + w, y + h, r);
            this.arcTo(x + w, y + h, x, y + h, r);
            this.arcTo(x, y + h, x, y, r);
            this.arcTo(x, y, x + w, y, r);
            return this;
        };
    }
</script>
@endpush
@endsection