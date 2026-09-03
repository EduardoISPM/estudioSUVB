<?php

namespace App\Services;

use App\Models\Archivo;
use App\Models\Registro;
use App\Models\ResumenSede;
use App\Models\CursoPersonalizado;  // ← 🔥 NUEVO: Para manejar cursos personalizados
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportService
{
    /**
     * @var string|null Curso ID actual para asignar sede a Alumnos PIE
     */
    protected $cursoIdActual;

    /**
     * ============================================================
     * PROCESAR ARCHIVO PRINCIPAL
     * ============================================================
     * 1. Lee el archivo (HTML o Excel)
     * 2. 🔥 DETECTA cursos nuevos en el archivo
     * 3. Extrae mes/año
     * 4. Verifica duplicados
     * 5. Procesa los datos y guarda en BD
     */
    public function procesarArchivo($archivo)
    {
        DB::beginTransaction();
        
        try {
            // 1. Leer el contenido del archivo
            $contenido = file_get_contents($archivo->getRealPath());
            $esHtml = $this->esArchivoHtml($contenido);
            
            // 🔥 2. DETECTAR CURSOS NUEVOS (solo si es HTML)
            if ($esHtml) {
                $cursosNuevos = $this->detectarCursosNuevos($contenido);
                
                if (!empty($cursosNuevos)) {
                    // Si hay cursos nuevos, detener el proceso y pedir clasificación
                    DB::rollBack();
                    return [
                        'success' => false,
                        'cursos_nuevos' => true,
                        'mensaje' => '📋 Se encontraron cursos nuevos en el archivo. Por favor, clasifícalos.',
                        'cursos' => $cursosNuevos
                    ];
                }
            }
            
            // 3. Extraer mes y año del contenido o del nombre del archivo
            $mes = $this->extraerMesDelContenido($contenido) ?? $this->extraerMes($archivo);
            $anio = $this->extraerAnioDelContenido($contenido) ?? $this->extraerAnio($archivo);
            
            Log::info('📅 Mes extraído: ' . ($mes ?? 'N/A'));
            Log::info('📅 Año extraído: ' . ($anio ?? 'N/A'));
            
            // 4. Verificar si ya existe un archivo con el mismo mes y año
            $archivoExistente = Archivo::where('mes_pago', $mes)
                                        ->where('anio_pago', $anio)
                                        ->first();
            
            if ($archivoExistente) {
                DB::rollBack();
                return [
                    'success' => false,
                    'duplicado' => true,
                    'mensaje' => "⚠️ Ya existe un archivo para {$mes} {$anio}.",
                    'archivo_existente' => [
                        'id' => $archivoExistente->id,
                        'nombre' => $archivoExistente->nombre_archivo,
                        'fecha' => $archivoExistente->created_at->format('d/m/Y H:i')
                    ]
                ];
            }
            
            // 5. Crear registro del archivo
            $archivoModel = Archivo::create([
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'mes_pago' => $mes,
                'anio_pago' => $anio,
                'fecha_reporte' => now(),
                'total_general' => 0,
                'total_ley_19933' => 0
            ]);
            
            Log::info('📄 ¿Es HTML? ' . ($esHtml ? 'SÍ' : 'NO'));
            Log::info('📄 Tamaño del archivo: ' . strlen($contenido) . ' bytes');
            
            // 6. Procesar según el tipo de archivo
            if ($esHtml) {
                Log::info('🔄 Procesando archivo como HTML...');
                $this->procesarArchivoHtml($contenido, $archivoModel->id);
            } else {
                Log::info('🔄 Procesando archivo como Excel normal...');
                $import = new ColegiosImport($archivoModel->id);
                Excel::import($import, $archivo);
            }
            
            // 7. Calcular totales
            $totales = $this->calcularTotales($archivoModel->id);
            $archivoModel->total_general = $totales['total_general'];
            $archivoModel->total_ley_19933 = $totales['total_ley_19933'];
            $archivoModel->save();
            
            // 8. Generar resumen por sedes
            $this->generarResumenSedes($archivoModel->id);
            
            DB::commit();
            
            $totalRegistros = Registro::where('archivo_id', $archivoModel->id)->count();
            
            Log::info('✅ Importación completada. Registros: ' . $totalRegistros);
            
            return [
                'success' => true,
                'archivo_id' => $archivoModel->id,
                'registros' => $totalRegistros,
                'posiciones' => ['HTML' => 'Archivo procesado como HTML'],
                'totales' => $totales
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en importación: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * ============================================================
     * REEMPLAZAR ARCHIVO (CUANDO EL USUARIO CONFIRMA DUPLICADO)
     * ============================================================
     * Elimina los datos existentes para un mes/año y guarda los nuevos.
     */
    public function reemplazarArchivo($archivo, $mes, $anio)
    {
        DB::beginTransaction();
        
        try {
            // 1. Eliminar datos existentes para este mes/año
            $archivoExistente = Archivo::where('mes_pago', $mes)
                                        ->where('anio_pago', $anio)
                                        ->first();
            
            if ($archivoExistente) {
                $archivoExistente->delete();
                Log::info("🗑️ Datos eliminados para {$mes} {$anio}");
            }
            
            // 2. Procesar el nuevo archivo
            $contenido = file_get_contents($archivo->getRealPath());
            $esHtml = $this->esArchivoHtml($contenido);
            
            $archivoModel = Archivo::create([
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'mes_pago' => $mes,
                'anio_pago' => $anio,
                'fecha_reporte' => now(),
                'total_general' => 0,
                'total_ley_19933' => 0
            ]);
            
            Log::info('🔄 Reemplazando archivo para ' . $mes . ' ' . $anio);
            
            if ($esHtml) {
                $this->procesarArchivoHtml($contenido, $archivoModel->id);
            } else {
                $import = new ColegiosImport($archivoModel->id);
                Excel::import($import, $archivo);
            }
            
            // 3. Calcular totales
            $totales = $this->calcularTotales($archivoModel->id);
            $archivoModel->total_general = $totales['total_general'];
            $archivoModel->total_ley_19933 = $totales['total_ley_19933'];
            $archivoModel->save();
            
            // 4. Generar resumen por sedes
            $this->generarResumenSedes($archivoModel->id);
            
            DB::commit();
            
            $totalRegistros = Registro::where('archivo_id', $archivoModel->id)->count();
            
            return [
                'success' => true,
                'archivo_id' => $archivoModel->id,
                'registros' => $totalRegistros,
                'mensaje' => "✅ Archivo de {$mes} {$anio} reemplazado correctamente",
                'totales' => $totales
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al reemplazar archivo: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * ============================================================
     * DETECTAR CURSOS NUEVOS EN EL ARCHIVO
     * ============================================================
     * 🔥 NUEVO MÉTODO
     * Compara los cursos del archivo con los ya registrados en:
     * - Tabla cursos_personalizados
     * - Tabla registros (importaciones anteriores)
     * Devuelve los cursos que NO existen.
     */
    protected function detectarCursosNuevos($contenido)
    {
        // Extraer todas las filas de la tabla HTML
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $contenido, $filas);
        
        if (empty($filas[1])) {
            return [];
        }
        
        // 🔥 Obtener cursos ya registrados en cursos_personalizados
        $cursosExistentes = CursoPersonalizado::pluck('curso_id')->toArray();
        
        // 🔥 También obtener cursos que ya están en la tabla registros
        $cursosEnRegistros = Registro::select('curso_id')
            ->whereNotNull('curso_id')
            ->distinct()
            ->pluck('curso_id')
            ->toArray();
        
        // Combinar ambos arrays
        $todosLosCursosExistentes = array_unique(array_merge($cursosExistentes, $cursosEnRegistros));
        
        $cursosNuevos = [];
        $encabezados = [];
        $encontreEncabezados = false;
        
        foreach ($filas[1] as $filaHtml) {
            preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $filaHtml, $celdas);
            
            if (empty($celdas[1])) {
                continue;
            }
            
            $datos = array_map(function($celda) {
                return trim(strip_tags($celda));
            }, $celdas[1]);
            
            // Buscar encabezados (fila con "Cod. Ens.")
            if (!$encontreEncabezados) {
                foreach ($datos as $idx => $valor) {
                    if (strpos($valor, 'Cod. Ens.') !== false || strpos($valor, 'Cod. Ens') !== false) {
                        $encabezados = $datos;
                        $encontreEncabezados = true;
                        break;
                    }
                }
                continue;
            }
            
            // Saltar filas vacías o de totales
            if (empty($datos[0]) || $datos[0] === '' || strpos($datos[0], 'Total') !== false) {
                continue;
            }
            
            if (count($datos) < 5) {
                continue;
            }
            
            // Construir el array con los encabezados
            $row = [];
            foreach ($encabezados as $idx => $nombre) {
                $nombreLimpio = trim($nombre);
                $row[$nombreLimpio] = $datos[$idx] ?? '';
            }
            
            $codEns = $row['Cod. Ens.'] ?? $row['Cod. Ens'] ?? null;
            $grado = $row['Grado'] ?? null;
            $letra = $row['LETRA'] ?? null;
            $ens = $row['ENS'] ?? null;
            
            // Saltar si no tiene datos válidos
            if (empty($codEns) || empty($grado) || empty($letra) || empty($ens)) {
                continue;
            }
            
            // 🔥 Solo detectar cursos principales (no Alumnos PIE)
            if (!in_array($ens, [9, 10, 110, 310, 1009, 1010, 1110, 1310])) {
                continue;
            }
            
            $cursoId = $codEns . '-' . $grado . '-' . $letra;
            
            // 🔥 Verificar si es un curso nuevo
            if (!in_array($cursoId, $todosLosCursosExistentes)) {
                $cursosNuevos[] = [
                    'curso_id' => $cursoId,
                    'cod_ens' => $codEns,
                    'grado' => $grado,
                    'letra' => $letra,
                    'ens' => $ens,
                    'nombre_curso' => $this->generarNombreCurso($codEns, $grado, $letra, $ens),
                    'sede_sugerida' => $this->sugerirSede($grado, $ens)
                ];
                
                // Agregar a la lista para evitar duplicados en el mismo archivo
                $todosLosCursosExistentes[] = $cursoId;
            }
        }
        
        return $cursosNuevos;
    }

    /**
     * ============================================================
     * GENERAR NOMBRE DEL CURSO
     * ============================================================
     * 🔥 NUEVO MÉTODO
     * Convierte los datos del curso en un nombre legible.
     * Ej: 110-8-E → "8° Básico E"
     */
    protected function generarNombreCurso($codEns, $grado, $letra, $ens)
    {
        $nombres = [
            9 => 'Pre Kinder',
            10 => 'Kinder',
            1009 => 'Pre Kinder PIE',
            1010 => 'Kinder PIE',
            110 => $grado . '° Básico',
            1110 => $grado . '° Básico PIE',
            310 => $grado . '° Medio',
            1310 => $grado . '° Medio PIE'
        ];
        
        $nombre = $nombres[$ens] ?? $codEns . '-' . $grado . $letra;
        
        return $nombre . ' ' . $letra;
    }

    /**
     * ============================================================
     * SUGERIR SEDE SEGÚN GRADO Y ENS
     * ============================================================
     * 🔥 NUEVO MÉTODO
     * Propone una sede automática para el curso nuevo
     * basado en su grado y tipo de enseñanza.
     */
    protected function sugerirSede($grado, $ens)
    {
        // Sede Jardín: Pre Kinder y Kinder
        if (in_array($ens, [9, 10, 1009, 1010])) {
            return 'Sede Jardín';
        }
        
        // Ed. Media
        if (in_array($ens, [310, 1310])) {
            return 'Ed. Media';
        }
        
        // Enseñanza Básica: clasificar por rango de grado
        if (in_array($ens, [110, 1110])) {
            if ($grado <= 4) {
                return 'Sede 1 a 4 Básico';
            } elseif ($grado <= 6) {
                return 'Sede 5 a 6 Básico';
            } elseif ($grado <= 8) {
                return 'Sede 7 a 8 Básico';
            } else {
                // Grados > 8 (ej: 9° Básico) → sede genérica
                return 'Sede Básica ' . $grado . '°';
            }
        }
        
        return 'Sin Sede';
    }

    /**
     * ============================================================
     * DETECTAR SI EL ARCHIVO ES HTML
     * ============================================================
     * Busca etiquetas HTML en el contenido.
     */
    protected function esArchivoHtml($contenido)
    {
        return strpos($contenido, '<html') !== false || 
               strpos($contenido, '<table') !== false ||
               strpos($contenido, '<tr') !== false ||
               strpos($contenido, '<td') !== false;
    }

    /**
     * ============================================================
     * EXTRAER MES DEL CONTENIDO HTML
     * ============================================================
     * Busca "MES PAGO:" en el HTML y extrae el mes.
     * Ejemplo: <strong>MES PAGO:</strong> DICIEMBRE 2025
     */
    protected function extraerMesDelContenido($contenido)
    {
        if (preg_match('/MES\s*PAGO\s*[:]\s*(?:<[^>]*>)*\s*([A-ZÁÉÍÓÚÑa-záéíóúñ]+)\s*(\d{4})/i', $contenido, $matches)) {
            Log::info('✅ Mes encontrado en contenido: ' . $matches[1] . ' ' . $matches[2]);
            return trim($matches[1]);
        }
        
        if (preg_match('/MES\s*PAGO\s*[:]\s*(?:<[^>]*>)*\s*([A-ZÁÉÍÓÚÑa-záéíóúñ]+)/i', $contenido, $matches)) {
            Log::info('✅ Mes encontrado (sin año): ' . $matches[1]);
            return trim($matches[1]);
        }
        
        $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 
                  'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        foreach ($meses as $mes) {
            if (stripos($contenido, $mes) !== false) {
                Log::info('✅ Mes encontrado por lista: ' . $mes);
                return $mes;
            }
        }
        
        Log::warning('❌ No se pudo extraer el mes del contenido');
        return null;
    }

    /**
     * ============================================================
     * EXTRAER AÑO DEL CONTENIDO HTML
     * ============================================================
     * Busca "MES PAGO:" en el HTML y extrae el año.
     */
    protected function extraerAnioDelContenido($contenido)
    {
        if (preg_match('/MES\s*PAGO\s*[:]\s*(?:<[^>]*>)*\s*[A-ZÁÉÍÓÚÑa-záéíóúñ]+\s*(\d{4})/i', $contenido, $matches)) {
            Log::info('✅ Año encontrado en contenido: ' . $matches[1]);
            return trim($matches[1]);
        }
        
        if (preg_match('/\b(20\d{2})\b/', $contenido, $matches)) {
            Log::info('✅ Año encontrado (cualquier): ' . $matches[1]);
            return $matches[1];
        }
        
        Log::warning('❌ No se pudo extraer el año del contenido');
        return null;
    }

    /**
     * ============================================================
     * PROCESAR ARCHIVO HTML
     * ============================================================
     * Extrae la tabla HTML, detecta encabezados y procesa cada fila.
     */
    protected function procesarArchivoHtml($contenido, $archivoId)
    {
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $contenido, $filas);
        
        if (empty($filas[1])) {
            Log::warning('⚠️ No se encontraron filas en el HTML');
            return;
        }
        
        Log::info('📊 Filas encontradas: ' . count($filas[1]));
        
        $encabezados = [];
        $filaNumero = 0;
        $encontreEncabezados = false;
        $totalFilasProcesadas = 0;
        
        foreach ($filas[1] as $filaHtml) {
            preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $filaHtml, $celdas);
            
            if (empty($celdas[1])) {
                continue;
            }
            
            $datos = array_map(function($celda) {
                return trim(strip_tags($celda));
            }, $celdas[1]);
            
            if (!$encontreEncabezados) {
                foreach ($datos as $idx => $valor) {
                    if (strpos($valor, 'Cod. Ens.') !== false || strpos($valor, 'Cod. Ens') !== false) {
                        $encabezados = $datos;
                        $encontreEncabezados = true;
                        Log::info('📋 Encabezados encontrados:', $encabezados);
                        break;
                    }
                }
                continue;
            }
            
            if (empty($datos[0]) || $datos[0] === '' || strpos($datos[0], 'Total') !== false) {
                continue;
            }
            
            if (count($datos) < 5) {
                continue;
            }
            
            $row = [];
            foreach ($encabezados as $idx => $nombre) {
                $nombreLimpio = trim($nombre);
                $row[$nombreLimpio] = $datos[$idx] ?? '';
            }
            
            $codEns = $row['Cod. Ens.'] ?? $row['Cod. Ens'] ?? null;
            if (empty($codEns) || $codEns === 'Total' || $codEns === '') {
                continue;
            }
            
            if (isset($row['Cod. Ens']) && !isset($row['Cod. Ens.'])) {
                $row['Cod. Ens.'] = $row['Cod. Ens'];
            }
            
            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $row[$key] = trim($value);
                }
            }
            
            $this->guardarRegistro($row, $archivoId, $filaNumero);
            $totalFilasProcesadas++;
            $filaNumero++;
        }
        
        Log::info('✅ Total filas procesadas: ' . $totalFilasProcesadas);
    }

    /**
     * ============================================================
     * GUARDAR REGISTRO EN BASE DE DATOS
     * ============================================================
     * Crea un registro en la tabla 'registros' con todos los datos.
     * Los Alumnos PIE heredan la sede del curso padre.
     */
    protected function guardarRegistro($row, $archivoId, $filaNumero)
    {
        $codEns = $row['Cod. Ens.'] ?? $row['Cod. Ens'] ?? null;
        $ens = $row['ENS'] ?? null;
        $tipoSubvencion = $this->determinarTipoSubvencion($ens);
        
        $cursoId = ($codEns ?? '') . '-' . ($row['Grado'] ?? '') . '-' . ($row['LETRA'] ?? '');
        $this->cursoIdActual = $cursoId;
        
        // 🔥 Verificar si el curso está en cursos_personalizados
        $cursoPersonalizado = CursoPersonalizado::where('curso_id', $cursoId)->first();
        
        if ($cursoPersonalizado) {
            // Si el curso está personalizado, usar su sede
            $sede = $cursoPersonalizado->sede;
        } else {
            // Si no, calcular la sede normalmente
            $sede = $this->calcularSede($row['Grado'] ?? null, $ens);
        }
        
        $subvencionBase = $this->parseNumber($row['Subvención Base'] ?? 0);
        $ley19933 = $this->parseNumber($row['Subvención Ley 19.933'] ?? 0);
        $ley19933Incremento = $this->parseNumber($row['Subvención Ley 19.933 Incremento Zona'] ?? 0);
        $ruralidad = $this->parseNumber($row['Subvención Ruralidad Ley 19.933'] ?? 0);
        $totalLey = $this->parseNumber($row['Total Ley 19.933'] ?? 0);
        $promedioAsistencia = $this->parseNumber($row['Promedio Asistencia'] ?? null);
        $factorUse = $this->parseNumber($row['Factor USE'] ?? null);
        
        Registro::create([
            'archivo_id' => $archivoId,
            'fila_numero' => $filaNumero + 1,
            'cod_ens' => $codEns,
            'grado' => $row['Grado'] ?? null,
            'letra' => $row['LETRA'] ?? null,
            'ens' => $ens,
            'jec' => $row['JEC'] ?? null,
            'nivel' => $row['NIVEL'] ?? null,
            'glosa_subvencion' => $row['GLOSA SUBVENCIÓN'] ?? null,
            'promedio_asistencia' => $promedioAsistencia,
            'factor_use' => $factorUse,
            'subvencion_base' => $subvencionBase,
            'curso_id' => $cursoId,
            'sede' => $sede,
            'tipo_subvencion' => $tipoSubvencion,
            'subvencion_ley_19933' => $ley19933,
            'subvencion_ley_19933_incremento' => $ley19933Incremento,
            'subvencion_ruralidad' => $ruralidad,
            'total_ley_19933' => $totalLey,
            'datos_completos' => json_encode($row)
        ]);
    }

    /**
     * ============================================================
     * DETERMINAR TIPO DE SUBVENCIÓN SEGÚN ENS
     * ============================================================
     * ENS = 9, 10, 110, 310        → GENERAL
     * ENS = 1009, 1010, 1110, 1310 → CURSO_PIE
     * Cualquier otro ENS           → ALUMNOS_PIE
     */
    protected function determinarTipoSubvencion($ens)
    {
        if (in_array($ens, [9, 10, 110, 310])) {
            return 'GENERAL';
        } elseif (in_array($ens, [1009, 1010, 1110, 1310])) {
            return 'CURSO_PIE';
        } else {
            return 'ALUMNOS_PIE';
        }
    }

    /**
     * ============================================================
     * CALCULAR SEDE SEGÚN GRADO Y ENS
     * ============================================================
     * Los Alumnos PIE heredan la sede del curso padre.
     */
    protected function calcularSede($grado, $ens)
    {
        // 1. CÓDIGOS ENS PRINCIPALES (DEFINEN LA SEDE)
        if (in_array($ens, [9, 10, 1009, 1010])) {
            return 'Sede Jardín';
        }
        
        if (in_array($ens, [110, 1110]) && in_array($grado, [1, 2, 3, 4])) {
            return 'Sede 1 a 4 Básico';
        }
        
        if (in_array($ens, [110, 1110]) && in_array($grado, [5, 6])) {
            return 'Sede 5 a 6 Básico';
        }
        
        if (in_array($ens, [110, 1110]) && in_array($grado, [7, 8])) {
            return 'Sede 7 a 8 Básico';
        }
        
        if (in_array($ens, [310, 1310])) {
            return 'Ed. Media';
        }
        
        // 2. ALUMNOS PIE - HEREDAN LA SEDE DEL CURSO PADRE
        if ($this->cursoIdActual) {
            $cursoPadre = Registro::where('curso_id', $this->cursoIdActual)
                                  ->whereIn('ens', [9, 10, 110, 310, 1009, 1010, 1110, 1310])
                                  ->first();
            
            if ($cursoPadre) {
                return $cursoPadre->sede;
            }
        }
        
        return 'Sin Sede';
    }

    /**
     * ============================================================
     * PARSEAR NÚMEROS (LIMPIAR FORMATO)
     * ============================================================
     * Convierte valores como "$ 1.095.040" o "12,9485" a números.
     */
    protected function parseNumber($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }
        
        if (is_string($value)) {
            $value = str_replace('$', '', $value);
            $value = str_replace(' ', '', $value);
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            $value = preg_replace('/[^0-9\.\-]/', '', $value);
        }
        
        return floatval($value);
    }

    /**
     * ============================================================
     * CALCULAR TOTALES DEL ARCHIVO
     * ============================================================
     */
    protected function calcularTotales($archivoId)
    {
        $registros = Registro::where('archivo_id', $archivoId)->get();
        
        return [
            'total_general' => $registros->sum('subvencion_base'),
            'total_ley_19933' => $registros->sum('total_ley_19933')
        ];
    }

    /**
     * ============================================================
     * GENERAR RESUMEN POR SEDES
     * ============================================================
     */
    protected function generarResumenSedes($archivoId)
    {
        $registros = Registro::where('archivo_id', $archivoId)->get();
        $sedes = $registros->groupBy('sede');
        
        foreach ($sedes as $sede => $registrosSede) {
            $cursos = $registrosSede->pluck('curso_id')->unique();
            $generales = $registrosSede->where('tipo_subvencion', 'GENERAL');
            $cursoPie = $registrosSede->where('tipo_subvencion', 'CURSO_PIE');
            $alumnosPie = $registrosSede->where('tipo_subvencion', 'ALUMNOS_PIE');
            
            $promedioAsistencia = 0;
            $promedioFactorUse = 0;
            if ($generales->count() > 0) {
                $promedioAsistencia = $generales->avg('promedio_asistencia');
                $promedioFactorUse = $generales->avg('factor_use');
            }
            
            ResumenSede::create([
                'archivo_id' => $archivoId,
                'sede' => $sede,
                'subvencion_general' => $generales->sum('subvencion_base'),
                'subvencion_curso_pie' => $cursoPie->sum('subvencion_base'),
                'subvencion_alumnos_pie' => $alumnosPie->sum('subvencion_base'),
                'total_subvencion' => $registrosSede->sum('subvencion_base'),
                'subvencion_ley_19933' => $registrosSede->sum('subvencion_ley_19933'),
                'total_ley_19933' => $registrosSede->sum('total_ley_19933'),
                'promedio_asistencia' => $promedioAsistencia,
                'promedio_factor_use' => $promedioFactorUse,
                'cantidad_cursos' => $cursos->count()
            ]);
        }
    }

    /**
     * ============================================================
     * EXTRAER MES DEL NOMBRE DEL ARCHIVO
     * ============================================================
     */
    protected function extraerMes($archivo)
    {
        $nombre = $archivo->getClientOriginalName();
        
        $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 
                  'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        
        foreach ($meses as $mes) {
            if (stripos($nombre, $mes) !== false) {
                return $mes;
            }
        }
        
        return null;
    }

    /**
     * ============================================================
     * EXTRAER AÑO DEL NOMBRE DEL ARCHIVO
     * ============================================================
     */
    protected function extraerAnio($archivo)
    {
        $nombre = $archivo->getClientOriginalName();
        
        if (preg_match('/\b(20\d{2})\b/', $nombre, $matches)) {
            return $matches[1];
        }
        
        return date('Y');
    }
}