@extends('layouts.app')

@section('content')
{{-- ============================================================
     SECCIÓN 1: FORMULARIO DE IMPORTACIÓN
     ============================================================ --}}
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">
                    <i class="fas fa-money-bill-wave text-success me-2"></i> 
                    Importar Remuneraciones
                </h5>
                <p class="text-muted">
                    Sube el archivo Excel con dos hojas: 
                    <strong>Worksheet</strong> (haberes) y <strong>Worksheet 1</strong> (descuentos)
                </p>
                
                <form id="uploadForm" enctype="multipart/form-data" class="mb-4">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Mes</label>
                            <select name="mes" class="form-select" required>
                                <option value="">Seleccionar</option>
                                @foreach(['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'] as $mes)
                                    <option value="{{ $mes }}">{{ $mes }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Año</label>
                            <select name="anio" class="form-select" required>
                                <option value="">Año</option>
                                @for($i = 2024; $i <= date('Y'); $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Archivo Excel</label>
                            <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls" required>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Hoja 1: Worksheet (haberes) | Hoja 2: Worksheet 1 (descuentos)
                            </small>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success w-100" id="btnImportar">
                                <i class="fas fa-upload me-2"></i> Importar
                            </button>
                        </div>
                    </div>
                </form>

                <div id="resultado" style="display: none; margin-top: 15px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     SECCIÓN 2: LISTADO DE REMUNERACIONES IMPORTADAS
     ============================================================ --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2 text-primary"></i> 
                        Remuneraciones Importadas
                    </h5>
                    <span class="badge bg-secondary">{{ $remuneraciones->count() }} registros</span>
                </div>
                
                @if($remuneraciones->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Mes</th>
                                    <th>Año</th>
                                    <th class="text-center">Trabajadores</th>
                                    <th class="text-end">Total Haberes</th>
                                    <th class="text-end">Total Descuentos</th>
                                    <th class="text-end">Total Leyes Sociales</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($remuneraciones as $rem)
                                    <tr>
                                        <td><strong>{{ $rem->mes_pago ?? 'N/A' }}</strong></td>
                                        <td>{{ $rem->anio_pago ?? 'N/A' }}</td>
                                        <td class="text-center">{{ $rem->total_trabajadores }}</td>
                                        <td class="text-end">${{ number_format($rem->total_haberes) }}</td>
                                        <td class="text-end">${{ number_format($rem->total_descuentos) }}</td>
                                        <td class="text-end">${{ number_format($rem->total_leyes_sociales ?? 0) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                {{-- Botón: Ver detalle por trabajador --}}
                                                <a href="{{ route('remuneraciones.detalle', $rem->id) }}" 
                                                   class="btn btn-outline-primary" 
                                                   title="Ver detalle por trabajador">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                
                                                {{-- 🔥 NUEVO: Botón: Ver detalle por Centro de Costo --}}
                                                <a href="{{ route('remuneraciones.detalle.centro-costo', $rem->id) }}" 
                                                   class="btn btn-outline-success" 
                                                   title="Ver detalle por Centro de Costo (Sede)">
                                                    <i class="fas fa-building"></i>
                                                </a>
                                                
                                                {{-- Botón: Eliminar --}}
                                                <button class="btn btn-outline-danger" 
                                                        onclick="confirmarEliminar({{ $rem->id }})" 
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <p class="text-muted fs-5">No hay remuneraciones importadas aún</p>
                        <p class="text-muted">Sube tu primer archivo usando el formulario de arriba</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     JAVASCRIPT - MANEJO DE IMPORTACIÓN
     ============================================================ --}}
@push('scripts')
<script>
    /**
     * ============================================================
     * SUBIR ARCHIVO
     * ============================================================
     */
    document.getElementById('uploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const resultado = document.getElementById('resultado');
        const btnImportar = document.getElementById('btnImportar');
        
        resultado.style.display = 'block';
        resultado.innerHTML = `
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <div class="spinner-border text-primary me-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <div>
                        <strong>Procesando archivo...</strong>
                        <p class="mb-0 text-muted small">Por favor espera, esto puede tomar unos segundos.</p>
                    </div>
                </div>
            </div>
        `;
        btnImportar.disabled = true;
        
        try {
            const response = await fetch('{{ route("remuneraciones.importar") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            const data = await response.json();
            
            // 🔥 Verificar si es un duplicado
            if (data.duplicado === true) {
                mostrarConfirmacionDuplicado(data);
                return;
            }
            
            // 🔥 Verificar si fue exitoso
            if (data.success) {
                resultado.innerHTML = `
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle me-2"></i> ${data.mensaje}</h5>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <small class="text-muted d-block">Trabajadores</small>
                                <strong>${data.data.total_trabajadores}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Total Haberes</small>
                                <strong>$${new Intl.NumberFormat('es-CL').format(data.data.total_haberes)}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Total Descuentos</small>
                                <strong>$${new Intl.NumberFormat('es-CL').format(data.data.total_descuentos)}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Total Leyes Sociales</small>
                                <strong>$${new Intl.NumberFormat('es-CL').format(data.data.total_leyes_sociales)}</strong>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-success" onclick="location.reload()">
                                <i class="fas fa-sync me-2"></i> Recargar
                            </button>
                        </div>
                    </div>
                `;
                setTimeout(() => location.reload(), 3000);
            } else {
                mostrarError(data);
            }
            
        } catch (error) {
            mostrarError({ error: error.message });
        } finally {
            btnImportar.disabled = false;
        }
    });

    function mostrarError(data) {
        const resultado = document.getElementById('resultado');
        resultado.innerHTML = `
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-circle me-2"></i> Error</h5>
                <p>${data.error || 'Error desconocido'}</p>
                <ul class="mt-2 small">
                    <li>Verifica que el archivo tenga las hojas "Worksheet" y "Worksheet 1"</li>
                    <li>El archivo debe ser .xlsx o .xls</li>
                    <li>El archivo no debe estar corrupto</li>
                </ul>
            </div>
        `;
    }

    /**
     * ============================================================
     * CONFIRMACIÓN DE DUPLICADO
     * ============================================================
     */
    function mostrarConfirmacionDuplicado(data) {
        const resultado = document.getElementById('resultado');
        resultado.innerHTML = `
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle text-warning me-2"></i> ${data.mensaje}</h5>
                <p class="mt-2">¿Deseas <strong>REEMPLAZAR</strong> los datos existentes por los nuevos?</p>
                <div class="mt-3 d-flex gap-3">
                    <button class="btn btn-danger" onclick="reemplazarRemuneracion()" id="btnReemplazar">
                        <i class="fas fa-exchange-alt me-2"></i> Sí, Reemplazar
                    </button>
                    <button class="btn btn-secondary" onclick="cancelarReemplazo()">
                        <i class="fas fa-times me-2"></i> No, Cancelar
                    </button>
                </div>
                <div id="progresoReemplazo" style="display: none; margin-top: 15px;">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <div>
                            <strong>Reemplazando datos...</strong>
                            <p class="mb-0 text-muted small">Eliminando datos antiguos y guardando nuevos.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        window._formData = new FormData(document.getElementById('uploadForm'));
        window._mes = data.mes;
        window._anio = data.anio;
    }

    async function reemplazarRemuneracion() {
        const formData = window._formData;
        const mes = window._mes;
        const anio = window._anio;
        const resultado = document.getElementById('resultado');
        const btnReemplazar = document.getElementById('btnReemplazar');
        const progreso = document.getElementById('progresoReemplazo');
        
        btnReemplazar.disabled = true;
        progreso.style.display = 'block';
        formData.append('mes', mes);
        formData.append('anio', anio);
        
        try {
            const response = await fetch('{{ route("remuneraciones.reemplazar") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await response.json();
            progreso.style.display = 'none';
            if (data.success) {
                resultado.innerHTML = `<div class="alert alert-success"><h5><i class="fas fa-check-circle me-2"></i> ${data.mensaje}</h5></div>`;
                setTimeout(() => location.reload(), 2000);
            } else {
                resultado.innerHTML = `<div class="alert alert-danger"><h5><i class="fas fa-exclamation-circle me-2"></i> Error</h5><p>${data.error}</p></div>`;
            }
        } catch (error) {
            progreso.style.display = 'none';
            resultado.innerHTML = `<div class="alert alert-danger"><h5><i class="fas fa-exclamation-triangle me-2"></i> Error</h5><p>${error.message}</p></div>`;
        }
    }

    function cancelarReemplazo() {
        const resultado = document.getElementById('resultado');
        resultado.innerHTML = `<div class="alert alert-info"><h5><i class="fas fa-info-circle me-2"></i> Operación cancelada</h5><p>No se realizaron cambios en la base de datos.</p></div>`;
    }

    /**
     * ============================================================
     * ELIMINAR REMUNERACIÓN
     * ============================================================
     */
    function confirmarEliminar(id) {
        if (confirm('¿Estás seguro de eliminar esta remuneración?')) {
            fetch(`/remuneraciones/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(response => {
                if (response.ok) location.reload();
                else alert('Error al eliminar');
            });
        }
    }
</script>
@endpush
@endsection