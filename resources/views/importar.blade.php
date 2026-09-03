{{-- 
    ============================================================
    VISTA: IMPORTACIÓN DE ARCHIVOS
    ============================================================
    Esta vista permite al usuario:
    1. Subir archivos Excel/HTML del MINEDUC
    2. Ver el progreso de la importación
    3. Clasificar cursos nuevos (si se detectan)
    4. Reemplazar archivos duplicados
--}}

@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                {{-- Título --}}
                <h5 class="mb-3"><i class="fas fa-upload text-primary me-2"></i> Importar Archivo MINEDUC</h5>
                <p class="text-muted">Sube el archivo Excel con los datos de subvención</p>
                
                {{-- ============================================================
                     FORMULARIO DE SUBIDA
                     ============================================================ --}}
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    
                    {{-- Área de Drag & Drop --}}
                    <div class="upload-area" id="dropArea" style="border: 3px dashed #cbd5e0; border-radius: 15px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.3s;">
                        <div class="upload-icon">
                            <i class="fas fa-file-excel fa-4x text-primary"></i>
                        </div>
                        <p style="font-size: 18px; font-weight: 500; color: #4a5568; margin-top: 15px;">
                            📂 Arrastra tu archivo aquí o haz clic para seleccionar
                        </p>
                        <p style="color: #a0aec0; font-size: 14px;">
                            Formatos permitidos: .xlsx, .xls (Máx. 10MB)
                        </p>
                        <input type="file" name="archivo" id="archivo" accept=".xlsx,.xls,.html" style="display: none;">
                    </div>

                    {{-- Botón de envío --}}
                    <div class="text-center mt-4">
                        <button type="submit" class="btn-importar" id="btnSubmit">
                            <i class="fas fa-rocket me-2"></i> Importar Datos
                        </button>
                    </div>
                </form>

                {{-- ============================================================
                     ESTADO DE CARGA
                     ============================================================ --}}
                <div id="cargando" style="display: none; text-align: center; margin-top: 30px;">
                    <div class="spinner-border text-primary" role="status" style="width: 50px; height: 50px;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Procesando archivo... Por favor espera</p>
                    <div class="progress" style="height: 8px; margin-top: 15px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" style="width: 0%;"></div>
                    </div>
                    <p class="mt-2 text-muted small" id="progressText">0%</p>
                </div>

                {{-- ============================================================
                     ÁREA DE RESULTADOS (mensajes, errores, clasificación)
                     ============================================================ --}}
                <div id="resultado" style="display: none; margin-top: 30px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     ESTILOS CSS
     ============================================================ --}}
<style>
    .upload-area:hover {
        border-color: #3b82f6 !important;
        background: #f7fafc;
    }
    .upload-area.dragover {
        border-color: #3b82f6 !important;
        background: #ebf4ff;
    }
    .btn-importar {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    .btn-importar:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        color: white;
    }
    .btn-importar:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    #resultado .alert-success {
        background: #f0fff4;
        border: 1px solid #9ae6b4;
        color: #22543d;
        padding: 20px;
        border-radius: 10px;
    }
    #resultado .alert-danger {
        background: #fff5f5;
        border: 1px solid #feb2b2;
        color: #742a2a;
        padding: 20px;
        border-radius: 10px;
    }
    #resultado .alert-warning {
        background: #fffbeb;
        border: 1px solid #fcd34d;
        color: #92400e;
        padding: 20px;
        border-radius: 10px;
    }
    #resultado .alert-info {
        background: #eff6ff;
        border: 1px solid #93c5fd;
        color: #1e40af;
        padding: 20px;
        border-radius: 10px;
    }
    .detalle-posiciones {
        background: #f7fafc;
        padding: 15px;
        border-radius: 10px;
        margin-top: 15px;
    }
    .detalle-posiciones ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .detalle-posiciones li {
        padding: 5px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .detalle-posiciones li:last-child {
        border-bottom: none;
    }
</style>

{{-- ============================================================
     JAVASCRIPT
     ============================================================ --}}
<script>
    // ============================================================
    // 1. DRAG AND DROP
    // ============================================================
    const dropArea = document.getElementById('dropArea');
    const fileInput = document.getElementById('archivo');
    
    // Al hacer clic en el área, abrir selector de archivos
    dropArea.addEventListener('click', () => fileInput.click());
    
    // Prevenir comportamiento por defecto del navegador
    dropArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropArea.classList.add('dragover');
    });
    
    dropArea.addEventListener('dragleave', () => {
        dropArea.classList.remove('dragover');
    });
    
    // Cuando se suelta un archivo
    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        dropArea.classList.remove('dragover');
        fileInput.files = e.dataTransfer.files;
        updateFileLabel();
    });
    
    // Cuando se selecciona un archivo manualmente
    fileInput.addEventListener('change', updateFileLabel);
    
    // Actualizar el label con el nombre del archivo
    function updateFileLabel() {
        if (fileInput.files.length > 0) {
            const fileName = fileInput.files[0].name;
            dropArea.querySelector('p:first-of-type').textContent = '📄 ' + fileName;
            dropArea.style.borderColor = '#48bb78';
            dropArea.style.background = '#f0fff4';
        }
    }

    // ============================================================
    // 2. FUNCIONES DE RESULTADOS
    // ============================================================

    /**
     * Muestra el mensaje de éxito después de una importación
     */
    function mostrarExito(data) {
        const resultado = document.getElementById('resultado');
        let html = `
            <div class="alert-success">
                <h4><i class="fas fa-check-circle me-2"></i> ¡Importación Exitosa!</h4>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Registros importados</small>
                        <strong>${data.data.registros}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Archivo ID</small>
                        <strong>#${data.data.archivo_id}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Total Subvención</small>
                        <strong>$${new Intl.NumberFormat('es-CL').format(data.data.totales.total_general)}</strong>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('dashboard') }}" class="btn-importar" style="font-size: 14px; padding: 8px 20px;">
                        <i class="fas fa-home me-2"></i> Ir al Dashboard
                    </a>
                </div>
            </div>
        `;
        resultado.innerHTML = html;
    }

    /**
     * Muestra un mensaje de error
     */
    function mostrarError(data) {
        const resultado = document.getElementById('resultado');
        resultado.innerHTML = `
            <div class="alert-danger">
                <h4><i class="fas fa-exclamation-circle me-2"></i> Error</h4>
                <p>${data.error || 'Error desconocido'}</p>
                <ul class="mt-2" style="font-size: 14px;">
                    <li>Verifica que el archivo tenga el formato correcto</li>
                    <li>Las columnas deben estar presentes</li>
                    <li>El archivo no debe estar corrupto</li>
                </ul>
            </div>
        `;
    }

    // ============================================================
    // 3. 🔥 FUNCIONES PARA CURSOS NUEVOS
    // ============================================================

    /**
     * Muestra la tabla de cursos nuevos con opciones de clasificación
     * 🔥 Los botones se activan automáticamente si hay sede sugerida
     */
    function mostrarCursosNuevos(data) {
        const resultado = document.getElementById('resultado');
        
        // Construir el HTML de la tabla
        let html = `
            <div class="alert-warning">
                <h4><i class="fas fa-flag text-warning me-2"></i> ${data.mensaje}</h4>
                <p class="mt-3">Los siguientes cursos no están registrados en el sistema. Por favor, asigna una sede a cada uno:</p>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Cód. Ens</th>
                                <th>Grado</th>
                                <th>Letra</th>
                                <th>Sede sugerida</th>
                                <th>Sede asignada</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        // Iterar sobre cada curso nuevo
        data.cursos.forEach(curso => {
            // Determinar si la sede sugerida debe estar seleccionada
            const hasSugerida = curso.sede_sugerida ? true : false;
            html += `
                <tr data-curso-id="${curso.curso_id}">
                    <td><strong>${curso.nombre_curso}</strong></td>
                    <td>${curso.cod_ens}</td>
                    <td>${curso.grado}</td>
                    <td>${curso.letra}</td>
                    <td><span class="badge bg-info">${curso.sede_sugerida || 'Sin sugerencia'}</span></td>
                    <td>
                        <select class="form-select form-select-sm sede-select" 
                                data-curso-id="${curso.curso_id}" 
                                data-cod-ens="${curso.cod_ens}" 
                                data-grado="${curso.grado}" 
                                data-letra="${curso.letra}" 
                                data-ens="${curso.ens}" 
                                data-nombre="${curso.nombre_curso}">
                            <option value="">Seleccionar sede...</option>
                            <option value="Sede Jardín" ${curso.sede_sugerida === 'Sede Jardín' ? 'selected' : ''}>Sede Jardín</option>
                            <option value="Sede 1 a 4 Básico" ${curso.sede_sugerida === 'Sede 1 a 4 Básico' ? 'selected' : ''}>Sede 1 a 4 Básico</option>
                            <option value="Sede 5 a 6 Básico" ${curso.sede_sugerida === 'Sede 5 a 6 Básico' ? 'selected' : ''}>Sede 5 a 6 Básico</option>
                            <option value="Sede 7 a 8 Básico" ${curso.sede_sugerida === 'Sede 7 a 8 Básico' ? 'selected' : ''}>Sede 7 a 8 Básico</option>
                            <option value="Ed. Media" ${curso.sede_sugerida === 'Ed. Media' ? 'selected' : ''}>Ed. Media</option>
                        </select>
                    </td>
                    <td>
                        {{-- 🔥 El botón se habilita SOLO si hay sede sugerida --}}
                        <button class="btn btn-sm btn-success btn-clasificar" 
                                data-curso-id="${curso.curso_id}" 
                                ${hasSugerida ? '' : 'disabled'}>
                            <i class="fas fa-check me-1"></i> Clasificar
                        </button>
                    </td>
                </tr>
            `;
        });
        
        // Verificar si todos los cursos tienen sede sugerida
        const todosTienenSede = data.cursos.every(c => c.sede_sugerida);
        
        html += `
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex gap-3 flex-wrap">
                    {{-- 🔥 Botón "Clasificar Todos" se habilita si todos tienen sede sugerida --}}
                    <button class="btn btn-primary" id="btnClasificarTodos" ${todosTienenSede ? '' : 'disabled'}>
                        <i class="fas fa-save me-2"></i> Clasificar Todos
                    </button>
                    <button class="btn btn-secondary" onclick="cancelarCursosNuevos()">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </button>
                </div>
                <div id="progresoClasificacion" style="display: none; margin-top: 15px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Clasificando cursos...</p>
                </div>
            </div>
        `;
        
        resultado.innerHTML = html;
        
        // ============================================================
        // 🔥 CONFIGURACIÓN DE EVENTOS PARA BOTONES
        // ============================================================
        
        // 1. Cuando cambia el select, habilitar/deshabilitar el botón de esa fila
        document.querySelectorAll('.sede-select').forEach(select => {
            select.addEventListener('change', function() {
                const row = this.closest('tr');
                const btn = row.querySelector('.btn-clasificar');
                // 🔥 Si el select tiene un valor, habilitar el botón
                btn.disabled = this.value === '';
                
                // Actualizar el botón "Clasificar Todos"
                actualizarBotonTodos();
            });
        });
        
        // 2. Función para actualizar el estado del botón "Clasificar Todos"
        function actualizarBotonTodos() {
            const allSelected = document.querySelectorAll('.sede-select');
            const allHaveSede = Array.from(allSelected).every(s => s.value !== '');
            const btnTodos = document.getElementById('btnClasificarTodos');
            if (btnTodos) {
                btnTodos.disabled = !allHaveSede;
            }
        }
        
        // 3. Evento para clasificar un curso individual
        document.querySelectorAll('.btn-clasificar').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const select = row.querySelector('.sede-select');
                const cursoId = select.dataset.cursoId;
                const sede = select.value;
                
                if (sede) {
                    clasificarCurso(cursoId, sede, select);
                }
            });
        });
        
        // 4. Evento para clasificar todos los cursos
        const btnTodos = document.getElementById('btnClasificarTodos');
        if (btnTodos) {
            btnTodos.addEventListener('click', function() {
                const selects = document.querySelectorAll('.sede-select');
                selects.forEach(select => {
                    if (select.value) {
                        clasificarCurso(select.dataset.cursoId, select.value, select);
                    }
                });
            });
        }
    }

    /**
     * Clasifica un curso individual enviando la sede al servidor
     */
    function clasificarCurso(cursoId, sede, selectElement) {
        const row = selectElement.closest('tr');
        const btn = row.querySelector('.btn-clasificar');
        const originalText = btn.innerHTML;
        
        // Deshabilitar botón y mostrar spinner
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Clasificando...';
        
        // Preparar datos para enviar al servidor
        const data = {
            curso_id: cursoId,
            sede: sede,
            cod_ens: selectElement.dataset.codEns,
            grado: selectElement.dataset.grado,
            letra: selectElement.dataset.letra,
            ens: selectElement.dataset.ens,
            nombre_curso: selectElement.dataset.nombre
        };
        
        // Enviar petición al servidor
        fetch('{{ route("importar.clasificar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Marcar como clasificado
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Clasificado ✅';
                btn.className = 'btn btn-sm btn-success btn-clasificar';
                selectElement.disabled = true;
                
                // Mostrar badge de éxito en la columna sede
                const sedeCell = row.querySelector('td:nth-child(6)');
                sedeCell.innerHTML = `<span class="badge bg-success">${sede}</span>`;
                
                // Verificar si todos los cursos están clasificados
                const allButtons = document.querySelectorAll('.btn-clasificar');
                const allDone = Array.from(allButtons).every(b => b.disabled === true && b.innerHTML.includes('Clasificado'));
                const btnTodos = document.getElementById('btnClasificarTodos');
                if (btnTodos && allDone) {
                    btnTodos.innerHTML = '<i class="fas fa-check me-2"></i> ¡Todos clasificados!';
                    btnTodos.className = 'btn btn-success';
                    btnTodos.disabled = true;
                    
                    // Mostrar opción para continuar
                    const progreso = document.getElementById('progresoClasificacion');
                    if (progreso) {
                        progreso.style.display = 'block';
                        progreso.innerHTML = `
                            <div class="alert-success mt-3">
                                <h5><i class="fas fa-check-circle me-2"></i> ¡Todos los cursos clasificados!</h5>
                                <p>Ahora puedes continuar con la importación.</p>
                                <button class="btn btn-primary" onclick="continuarImportacion()">
                                    <i class="fas fa-arrow-right me-2"></i> Continuar con la importación
                                </button>
                            </div>
                        `;
                    }
                }
            } else {
                // Error al clasificar
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('Error de conexión: ' + error.message);
        });
    }

    /**
     * Cancela la clasificación de cursos nuevos
     */
    function cancelarCursosNuevos() {
        const resultado = document.getElementById('resultado');
        resultado.innerHTML = `
            <div class="alert-info">
                <h4><i class="fas fa-info-circle text-primary me-2"></i> Operación cancelada</h4>
                <p>No se realizaron cambios en la base de datos.</p>
                <div class="mt-3 d-flex gap-3 flex-wrap">
                    <a href="{{ route('importar.form') }}" class="btn-importar" style="font-size: 14px; padding: 8px 20px;">
                        <i class="fas fa-upload me-2"></i> Volver a importar
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="padding: 8px 20px;">
                        <i class="fas fa-home me-2"></i> Ir al Dashboard
                    </a>
                </div>
            </div>
        `;
    }

    /**
     * Recarga la página para continuar con la importación
     * después de clasificar todos los cursos
     */
    function continuarImportacion() {
        location.reload();
    }

    // ============================================================
    // 4. 🔥 FUNCIONES PARA DUPLICADOS
    // ============================================================

    /**
     * Muestra el modal de confirmación para reemplazar un archivo duplicado
     */
    function mostrarModalConfirmacion(data, formData) {
        const resultado = document.getElementById('resultado');
        
        resultado.innerHTML = `
            <div class="alert-warning">
                <h4><i class="fas fa-exclamation-triangle text-warning me-2"></i> ⚠️ Archivo duplicado</h4>
                <p><strong>${data.mensaje}</strong></p>
                <div class="mt-3 p-3 bg-light rounded">
                    <p><strong>Archivo existente:</strong> ${data.archivo_existente.nombre}</p>
                    <p><strong>Fecha de importación:</strong> ${data.archivo_existente.fecha}</p>
                </div>
                <p class="mt-3">¿Deseas <strong>REEMPLAZAR</strong> los datos existentes por los nuevos?</p>
                <div class="mt-3 d-flex gap-3 flex-wrap">
                    <button class="btn btn-danger" onclick="reemplazarArchivo()" id="btnReemplazar">
                        <i class="fas fa-exchange-alt me-2"></i> Sí, Reemplazar
                    </button>
                    <button class="btn btn-secondary" onclick="cancelarReemplazo()">
                        <i class="fas fa-times me-2"></i> No, Cancelar
                    </button>
                </div>
                <div id="progresoReemplazo" style="display: none; margin-top: 15px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Reemplazando datos...</p>
                </div>
            </div>
        `;
        
        // Guardar datos para usar después
        window._formDataReemplazo = formData;
        window._mensajeDuplicado = data.mensaje;
    }

    /**
     * Ejecuta el reemplazo del archivo duplicado
     */
    async function reemplazarArchivo() {
        const resultado = document.getElementById('resultado');
        const btnReemplazar = document.getElementById('btnReemplazar');
        const progreso = document.getElementById('progresoReemplazo');
        
        btnReemplazar.disabled = true;
        progreso.style.display = 'block';
        
        try {
            const formData = window._formDataReemplazo;
            const mensaje = window._mensajeDuplicado || '';
            const partes = mensaje.match(/([A-ZÁÉÍÓÚÑ]+)\s*(\d{4})/);
            const mes = partes ? partes[1] : '';
            const anio = partes ? partes[2] : '';
            
            formData.append('mes', mes);
            formData.append('anio', anio);
            
            const response = await fetch('{{ route("importar.reemplazar") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            const data = await response.json();
            
            progreso.style.display = 'none';
            
            if (data.success) {
                resultado.innerHTML = `
                    <div class="alert-success">
                        <h4><i class="fas fa-check-circle me-2"></i> ¡Reemplazo Exitoso!</h4>
                        <p><strong>${data.mensaje}</strong></p>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Registros importados</small>
                                <strong>${data.data.registros}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Archivo ID</small>
                                <strong>#${data.data.archivo_id}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Total Subvención</small>
                                <strong>$${new Intl.NumberFormat('es-CL').format(data.data.totales.total_general)}</strong>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('dashboard') }}" class="btn-importar" style="font-size: 14px; padding: 8px 20px;">
                                <i class="fas fa-home me-2"></i> Ir al Dashboard
                            </a>
                        </div>
                    </div>
                `;
            } else {
                resultado.innerHTML = `
                    <div class="alert-danger">
                        <h4><i class="fas fa-exclamation-circle me-2"></i> Error al reemplazar</h4>
                        <p>${data.error}</p>
                    </div>
                `;
            }
            
        } catch (error) {
            progreso.style.display = 'none';
            resultado.innerHTML = `
                <div class="alert-danger">
                    <h4><i class="fas fa-exclamation-triangle me-2"></i> Error</h4>
                    <p>${error.message}</p>
                </div>
            `;
        }
    }

    /**
     * Cancela la operación de reemplazo
     */
    function cancelarReemplazo() {
        const resultado = document.getElementById('resultado');
        resultado.innerHTML = `
            <div class="alert-info">
                <h4><i class="fas fa-info-circle text-primary me-2"></i> Operación cancelada</h4>
                <p>No se realizaron cambios en la base de datos.</p>
                <div class="mt-3 d-flex gap-3 flex-wrap">
                    <a href="{{ route('importar.form') }}" class="btn-importar" style="font-size: 14px; padding: 8px 20px;">
                        <i class="fas fa-upload me-2"></i> Volver a importar
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="padding: 8px 20px;">
                        <i class="fas fa-home me-2"></i> Ir al Dashboard
                    </a>
                </div>
            </div>
        `;
    }

    // ============================================================
    // 5. SUBMIT DEL FORMULARIO (MANEJO PRINCIPAL)
    // ============================================================

    document.getElementById('uploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validar que hay un archivo seleccionado
        if (!fileInput.files.length) {
            alert('Por favor selecciona un archivo');
            return;
        }
        
        // Preparar elementos
        const formData = new FormData(this);
        const resultado = document.getElementById('resultado');
        const cargando = document.getElementById('cargando');
        const btnSubmit = document.getElementById('btnSubmit');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        
        // Mostrar estado de carga
        cargando.style.display = 'block';
        resultado.style.display = 'none';
        btnSubmit.disabled = true;
        progressBar.style.width = '0%';
        progressText.textContent = '0%';
        
        try {
            // Enviar archivo al servidor
            const response = await fetch('{{ route("importar.procesar") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            const data = await response.json();
            
            cargando.style.display = 'none';
            resultado.style.display = 'block';
            
            // ============================================================
            // 🔥 MANEJO DE RESPUESTAS DEL SERVIDOR
            // ============================================================
            
            // Caso 1: Éxito
            if (data.success) {
                mostrarExito(data);
            } 
            // Caso 2: Cursos nuevos detectados
            else if (data.cursos_nuevos === true) {
                mostrarCursosNuevos(data);
            } 
            // Caso 3: Archivo duplicado
            else if (data.duplicado === true) {
                mostrarModalConfirmacion(data, formData);
            } 
            // Caso 4: Error genérico
            else {
                mostrarError(data);
            }
            
        } catch (error) {
            // Error de conexión
            cargando.style.display = 'none';
            resultado.style.display = 'block';
            resultado.innerHTML = `
                <div class="alert-danger">
                    <h4><i class="fas fa-exclamation-triangle me-2"></i> Error de conexión</h4>
                    <p>${error.message}</p>
                </div>
            `;
        } finally {
            btnSubmit.disabled = false;
        }
    });
</script>
@endsection