<?php

namespace App\Services;

use App\Models\RemuneracionCabecera;
use App\Models\RemuneracionTrabajador;
use App\Models\RemuneracionDetalle;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================
 * SERVICIO DE IMPORTACIÓN DE REMUNERACIONES
 * ============================================================
 * 
 * Este servicio procesa archivos Excel con estructura variable,
 * donde las columnas pueden cambiar según el mes.
 * 
 * Características principales:
 * - Lee 2 hojas: "Worksheet" (haberes) y "Worksheet 1" (descuentos)
 * - Detecta columnas clave dinámicamente:
 *   - "Total Haberes" (por nombre o última columna)
 *   - 🔥 "Tot. Desc." (BUSCA EXACTAMENTE "Tot. Desc." con prioridad)
 *   - "Total Desc. Leyes Sociales" (leyes sociales, guardadas aparte)
 * - 🔥 GUARDA SIEMPRE el JSON de haberes (incluso si está vacío)
 * - 🔥 GUARDA SIEMPRE el JSON de descuentos (incluso si está vacío)
 * - 🔥 NO calcula descuentos cuando "Tot. Desc." es 0 (mantiene 0)
 * - Guarda TODOS los conceptos en JSON (estructura flexible)
 * - Los campos fijos (RUT, empleado, etc.) van en columnas normales
 * - 🔥 GUARDA "Tot. Desc." y "Total Desc. Leyes Sociales" en el JSON
 * 
 * @author Estudiante de Programación
 * @version 7.0
 */
class RemuneracionImportService
{
    /**
     * ============================================================
     * PROCESAR ARCHIVO DE REMUNERACIONES
     * ============================================================
     * Punto de entrada principal del servicio.
     * 
     * @param \Illuminate\Http\UploadedFile $archivo
     * @return array
     * @throws \Exception
     */
    public function procesarArchivo($archivo)
    {
        DB::beginTransaction();
        
        try {
            // ============================================================
            // 1. CARGAR EL ARCHIVO EXCEL
            // ============================================================
            $spreadsheet = IOFactory::load($archivo->getRealPath());
            
            // Log de las hojas disponibles (depuración)
            Log::info('📋 Hojas disponibles en el archivo:');
            foreach ($spreadsheet->getSheetNames() as $index => $nombre) {
                Log::info("   Hoja {$index}: '{$nombre}'");
            }
            
            // ============================================================
            // 2. OBTENER LAS HOJAS POR SU NOMBRE
            // ============================================================
            $hojaHaberes = $spreadsheet->getSheetByName('Worksheet');
            $hojaDescuentos = $spreadsheet->getSheetByName('Worksheet 1');
            
            if (!$hojaHaberes || !$hojaDescuentos) {
                throw new \Exception('El archivo debe tener las hojas "Worksheet" (haberes) y "Worksheet 1" (descuentos)');
            }
            
            // ============================================================
            // 3. EXTRAER INFORMACIÓN DEL ENCABEZADO (Filas 1-9)
            // ============================================================
            $cabecera = $this->extraerCabecera($hojaHaberes);
            
            Log::info('📄 ===== INICIANDO IMPORTACIÓN =====');
            Log::info("📅 Periodo: {$cabecera['periodo']}");
            Log::info("📅 Mes: {$cabecera['mes']} {$cabecera['anio']}");
            Log::info("🏫 Institución: {$cabecera['institucion']}");
            
            // ============================================================
            // 4. OBTENER TÍTULOS DE LAS COLUMNAS (Fila 10)
            // ============================================================
            $titulosHaberes = $this->obtenerTitulos($hojaHaberes);
            $titulosDescuentos = $this->obtenerTitulos($hojaDescuentos);
            
            // ============================================================
            // 5. DETECTAR COLUMNAS CLAVE DINÁMICAMENTE
            // ============================================================
            
            // 5.1: Buscar "Total Haberes" en la hoja de haberes
            $indiceTotalHaberes = $this->buscarColumna($titulosHaberes, [
                'Total Haberes',
                'TOTAL HABERES',
                'Total Haber',
                'TOTAL HABER',
                'Total Remuneraciones',
                'TOTAL REMUNERACIONES'
            ]);
            
            // Si no se encuentra por nombre, usar la última columna
            if ($indiceTotalHaberes === null) {
                $indiceTotalHaberes = count($titulosHaberes) - 1;
                Log::info("⚠️ 'Total Haberes' por posición: índice {$indiceTotalHaberes}");
            } else {
                Log::info("✅ 'Total Haberes' encontrado en índice: {$indiceTotalHaberes}");
            }
            
            // ============================================================
            // 🔥 5.2: BUSCAR "Tot. Desc." - VERSIÓN DEFINITIVA
            // ============================================================
            // 🔥 IMPORTANTE: Priorizamos EXACTAMENTE "Tot. Desc." para NO
            // confundir con "Total descuento legales" u otras columnas
            $indiceTotalDescuentos = null;
            
            Log::info("📋 Buscando 'Tot. Desc.' en títulos de descuentos:");
            
            // 🔥 PASO 1: Buscar EXACTAMENTE "Tot. Desc." (con punto y espacio)
            foreach ($titulosDescuentos as $idx => $nombre) {
                if (!is_string($nombre)) continue;
                $nombreLimpio = trim($nombre);
                // Coincidencia EXACTA (prioridad máxima)
                if ($nombreLimpio === 'Tot. Desc.' || $nombreLimpio === 'Tot. Desc') {
                    $indiceTotalDescuentos = $idx;
                    Log::info("✅ 'Tot. Desc.' EXACTO encontrado en índice: {$idx} - Nombre: '{$nombre}'");
                    break;
                }
            }
            
            // 🔥 PASO 2: Buscar "Tot. Dctos." (abreviatura)
            if ($indiceTotalDescuentos === null) {
                foreach ($titulosDescuentos as $idx => $nombre) {
                    if (!is_string($nombre)) continue;
                    $nombreLimpio = strtoupper(trim($nombre));
                    if (strpos($nombreLimpio, 'TOT. DCTOS.') !== false || 
                        strpos($nombreLimpio, 'TOT. DCTOS') !== false) {
                        $indiceTotalDescuentos = $idx;
                        Log::info("✅ 'Tot. Dctos.' encontrado en índice: {$idx} - Nombre: '{$nombre}'");
                        break;
                    }
                }
            }
            
            // 🔥 PASO 3: Buscar "Total Descuentos" (solo si no se encontró antes)
            if ($indiceTotalDescuentos === null) {
                $indiceTotalDescuentos = $this->buscarColumna($titulosDescuentos, [
                    'Total Descuentos',
                    'TOTAL DESCUENTOS'
                ]);
            }
            
            // 🔥 PASO 4: Si no se encuentra, buscar cualquier columna que contenga "Tot. Desc"
            if ($indiceTotalDescuentos === null) {
                $indiceTotalDescuentos = $this->buscarColumna($titulosDescuentos, [
                    'Tot. Desc.',
                    'Tot. Desc',
                    'Tot. Dctos.'
                ]);
            }
            
            if ($indiceTotalDescuentos !== null) {
                $nombreColumna = $titulosDescuentos[$indiceTotalDescuentos] ?? 'N/A';
                Log::info("✅ 'Tot. Desc.' usando índice: {$indiceTotalDescuentos} - Nombre: '{$nombreColumna}'");
            } else {
                Log::warning("⚠️ 'Tot. Desc.' NO encontrado en la hoja de descuentos");
            }
            
            // 5.3: Buscar "Total Desc. Leyes Sociales" en la hoja de descuentos
            $indiceLeyesSociales = $this->buscarColumna($titulosDescuentos, [
                'Total Desc. Leyes Sociales',
                'Total Leyes Sociales',
                'TOTAL DESC. LEYES SOCIALES',
                'TOTAL LEYES SOCIALES'
            ]);
            
            if ($indiceLeyesSociales !== null) {
                Log::info("✅ 'Leyes Sociales' encontrado en índice: {$indiceLeyesSociales}");
            } else {
                Log::warning("⚠️ 'Leyes Sociales' NO encontrado en la hoja de descuentos");
            }
            
            // ============================================================
            // 6. OBTENER DATOS DE TRABAJADORES (Fila 11 en adelante)
            // ============================================================
            $datosHaberes = $this->obtenerDatos($hojaHaberes);
            $datosDescuentos = $this->obtenerDatos($hojaDescuentos);
            
            Log::info('📊 Trabajadores en haberes: ' . count($datosHaberes));
            Log::info('📊 Trabajadores en descuentos: ' . count($datosDescuentos));
            
            // ============================================================
            // 7. VERIFICAR MES/AÑO
            // ============================================================
            $mes = $cabecera['mes'];
            $anio = $cabecera['anio'];
            
            if (!$mes || !$anio) {
                throw new \Exception('No se pudo extraer el mes/año del periodo: ' . ($cabecera['periodo'] ?? 'N/A'));
            }
            
            // ============================================================
            // 8. GUARDAR CABECERA
            // ============================================================
            $cabeceraId = $this->guardarCabecera($cabecera, $archivo);
            
            // ============================================================
            // 9. PROCESAR TRABAJADORES Y CALCULAR TOTALES
            // ============================================================
            $totales = $this->procesarTrabajadores(
                $cabeceraId,
                $datosHaberes,
                $datosDescuentos,
                $titulosHaberes,
                $titulosDescuentos,
                $indiceTotalHaberes,
                $indiceTotalDescuentos,
                $indiceLeyesSociales
            );
            
            // ============================================================
            // 10. ACTUALIZAR CABECERA CON TOTALES
            // ============================================================
            RemuneracionCabecera::where('id', $cabeceraId)->update([
                'mes_pago' => $mes,
                'anio_pago' => $anio,
                'total_trabajadores' => $totales['total_trabajadores'],
                'total_haberes' => $totales['total_haberes'],
                'total_descuentos' => $totales['total_descuentos'],
                'total_neto' => $totales['total_neto'],
                'total_leyes_sociales' => $totales['total_leyes_sociales'] ?? 0
            ]);
            
            DB::commit();
            
            // ============================================================
            // 11. LOGS FINALES
            // ============================================================
            Log::info('📊 ===== TOTALES FINALES =====');
            Log::info("   ✅ Trabajadores: " . number_format($totales['total_trabajadores']));
            Log::info("   ✅ Total Haberes: $" . number_format($totales['total_haberes']));
            Log::info("   ✅ Total Descuentos: $" . number_format($totales['total_descuentos']));
            Log::info("   ✅ Total Leyes Sociales: $" . number_format($totales['total_leyes_sociales']));
            Log::info("   ✅ Total Neto: $" . number_format($totales['total_neto']));
            Log::info('✅ ===== IMPORTACIÓN COMPLETADA =====');
            
            // ============================================================
            // 12. RETORNAR RESULTADO
            // ============================================================
            return [
                'success' => true,
                'mensaje' => "✅ Remuneraciones de {$mes} {$anio} importadas correctamente",
                'total_trabajadores' => $totales['total_trabajadores'],
                'total_haberes' => $totales['total_haberes'],
                'total_descuentos' => $totales['total_descuentos'],
                'total_neto' => $totales['total_neto'],
                'total_leyes_sociales' => $totales['total_leyes_sociales'] ?? 0,
                'mes' => $mes,
                'anio' => $anio
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al importar: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * ============================================================
     * BUSCAR COLUMNA POR NOMBRE
     * ============================================================
     * Busca un índice de columna por su nombre (case-insensitive)
     * y permite múltiples nombres posibles.
     * 
     * @param array $titulos - Array de nombres de columnas
     * @param array $posiblesNombres - Lista de nombres a buscar
     * @return int|null - Índice de la columna o null si no se encuentra
     */
    protected function buscarColumna($titulos, $posiblesNombres)
    {
        foreach ($titulos as $idx => $nombre) {
            if (!is_string($nombre)) continue;
            $nombreLimpio = strtoupper(trim($nombre));
            foreach ($posiblesNombres as $posible) {
                if (strpos($nombreLimpio, strtoupper($posible)) !== false) {
                    return $idx;
                }
            }
        }
        return null;
    }

    /**
     * ============================================================
     * EXTRAER INFORMACIÓN DEL ENCABEZADO (Filas 1-9)
     * ============================================================
     * 🔥 IMPORTANTE: El periodo está en la CELDA A8
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja
     * @return array
     */
    protected function extraerCabecera($hoja)
    {
        // Datos de la empresa (columna B)
        $empresa = $this->limpiarTexto($hoja->getCell('B2')->getValue());
        $rutEmpresa = $this->limpiarTexto($hoja->getCell('B3')->getValue());
        $institucion = $this->limpiarTexto($hoja->getCell('B4')->getValue());
        $rbd = $this->limpiarTexto($hoja->getCell('B5')->getValue());
        
        // 🔥 EL PERIODO ESTÁ EN LA CELDA A8 (NO en B8)
        // Tu Excel tiene: A8 = "Periodo: 01-07-2026 al 31-07-2026"
        $periodoTexto = $this->limpiarTexto($hoja->getCell('A8')->getValue());
        
        Log::info('📅 Periodo extraído (A8): ' . ($periodoTexto ?? 'NULL'));
        
        // Extraer mes y año del periodo "01-07-2026 al 31-07-2026"
        $mesAnio = $this->extraerMesAnio($periodoTexto);
        $fechas = $this->extraerFechas($periodoTexto);
        
        return [
            'empresa' => $empresa,
            'rut_empresa' => $rutEmpresa,
            'institucion' => $institucion,
            'rbd' => $rbd,
            'periodo' => $periodoTexto,
            'mes' => $mesAnio['mes'],
            'anio' => $mesAnio['anio'],
            'fecha_inicio' => $fechas['inicio'],
            'fecha_fin' => $fechas['fin']
        ];
    }

    /**
     * ============================================================
     * EXTRAER MES Y AÑO DEL PERIODO
     * ============================================================
     * "01-07-2026 al 31-07-2026" → MES: JULIO, ANIO: 2026
     * 
     * @param string $periodoTexto
     * @return array
     */
    protected function extraerMesAnio($periodoTexto)
    {
        preg_match('/(\d{2})[-\/](\d{2})[-\/](\d{4})/', $periodoTexto, $matches);
        if (empty($matches)) {
            return ['mes' => null, 'anio' => null];
        }
        
        $mesNumero = intval($matches[2]);
        $anio = $matches[3];
        
        $meses = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
        ];
        
        return ['mes' => $meses[$mesNumero] ?? null, 'anio' => $anio];
    }

    /**
     * ============================================================
     * EXTRAER FECHAS DE INICIO Y FIN
     * ============================================================
     * 
     * @param string $periodoTexto
     * @return array
     */
    protected function extraerFechas($periodoTexto)
    {
        preg_match_all('/(\d{2})[-\/](\d{2})[-\/](\d{4})/', $periodoTexto, $matches);
        if (empty($matches[0]) || count($matches[0]) < 2) {
            return ['inicio' => null, 'fin' => null];
        }
        
        $fechaInicio = $matches[0][0] ?? null;
        $fechaFin = $matches[0][1] ?? null;
        
        return [
            'inicio' => $fechaInicio ? date('Y-m-d', strtotime(str_replace('-', '/', $fechaInicio))) : null,
            'fin' => $fechaFin ? date('Y-m-d', strtotime(str_replace('-', '/', $fechaFin))) : null
        ];
    }

    /**
     * ============================================================
     * OBTENER TÍTULOS (Fila 10)
     * ============================================================
     * Mantiene TODAS las columnas (incluyendo vacías)
     * para mantener la correspondencia con los datos.
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja
     * @return array
     */
    protected function obtenerTitulos($hoja)
    {
        $titulos = $hoja->rangeToArray('A10:ZZ10')[0];
        $titulos = array_map(function($valor) { 
            return $this->limpiarTexto($valor); 
        }, $titulos);
        return $titulos;
    }

    /**
     * ============================================================
     * OBTENER DATOS (Fila 11 en adelante)
     * ============================================================
     * Mantiene TODAS las columnas (incluyendo vacías)
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja
     * @return array
     */
    protected function obtenerDatos($hoja)
    {
        $datos = [];
        $ultimaFila = $hoja->getHighestRow();
        
        for ($i = 11; $i <= $ultimaFila; $i++) {
            $fila = $hoja->rangeToArray('A' . $i . ':ZZ' . $i)[0];
            $fila = array_map(function($valor) { 
                return $this->limpiarValor($valor); 
            }, $fila);
            
            // Verificar si la fila tiene algún dato (no está vacía)
            $tieneDatos = false;
            foreach ($fila as $celda) {
                if (!empty($celda) && $celda !== null) {
                    $tieneDatos = true;
                    break;
                }
            }
            
            if ($tieneDatos) {
                $datos[] = $fila;
            }
        }
        return $datos;
    }

    /**
     * ============================================================
     * GUARDAR CABECERA
     * ============================================================
     * 
     * @param array $cabecera
     * @param \Illuminate\Http\UploadedFile $archivo
     * @return int
     */
    protected function guardarCabecera($cabecera, $archivo)
    {
        return RemuneracionCabecera::create([
            'mes_pago' => $cabecera['mes'],
            'anio_pago' => $cabecera['anio'],
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'empresa' => $cabecera['empresa'],
            'rut_empresa' => $cabecera['rut_empresa'],
            'institucion' => $cabecera['institucion'],
            'rbd' => $cabecera['rbd'],
            'periodo_inicio' => $cabecera['fecha_inicio'],
            'periodo_fin' => $cabecera['fecha_fin'],
            'fecha_importacion' => now()
        ])->id;
    }

    /**
     * ============================================================
     * 🔥 PROCESAR TRABAJADORES - VERSIÓN FINAL CORREGIDA
     * ============================================================
     * 
     * Características:
     * - Las primeras 8 columnas son FIJAS (N°, RUT, Empleado, PERIODO, 
     *   Tipo, Centro de Costo, DT, Carga Horaria)
     * - "Total Haberes" es la última columna en la hoja de haberes
     * - 🔥 "Tot. Desc." se busca EXACTAMENTE primero (prioridad máxima)
     * - 🔥 SI "Tot. Desc." es 0, se MANTIENE 0 (NO se calcula)
     * - "Total Desc. Leyes Sociales" se detecta por nombre y se guarda aparte
     * - Guarda TODOS los conceptos en JSON (estructura flexible)
     * - 🔥 GUARDA SIEMPRE el JSON de haberes (incluso si está vacío)
     * - 🔥 GUARDA SIEMPRE el JSON de descuentos (incluso si está vacío)
     * - 🔥 GUARDA "Tot. Desc." en el JSON para referencia
     * 
     * @param int $cabeceraId
     * @param array $datosHaberes
     * @param array $datosDescuentos
     * @param array $titulosHaberes
     * @param array $titulosDescuentos
     * @param int $indiceTotalHaberes
     * @param int|null $indiceTotalDescuentos
     * @param int|null $indiceLeyesSociales
     * @return array
     */
    protected function procesarTrabajadores(
        $cabeceraId,
        $datosHaberes,
        $datosDescuentos,
        $titulosHaberes,
        $titulosDescuentos,
        $indiceTotalHaberes,
        $indiceTotalDescuentos,
        $indiceLeyesSociales
    ) {
        $totalHaberes = 0;
        $totalDescuentos = 0;
        $totalNeto = 0;
        $totalLeyesSociales = 0;
        $contador = 0;
        
        // ============================================================
        // COLUMNAS FIJAS (SIEMPRE SON LAS PRIMERAS 8)
        // ============================================================
        $COLUMNAS_FIJAS = 8;
        
        Log::info("📋 Índice de 'Total Haberes': {$indiceTotalHaberes}");
        Log::info("📋 Índice de 'Tot. Desc.': " . ($indiceTotalDescuentos ?? 'NO ENCONTRADO'));
        Log::info("📋 Índice de 'Leyes Sociales': " . ($indiceLeyesSociales ?? 'NO ENCONTRADO'));
        
        // ============================================================
        // DEPURACIÓN: MOSTRAR TÍTULOS DE DESCUENTOS
        // ============================================================
        Log::info("📋 ===== TÍTULOS DE DESCUENTOS =====");
        foreach ($titulosDescuentos as $idx => $nombre) {
            if (!empty($nombre)) {
                Log::info("   Índice {$idx}: '{$nombre}'");
            }
        }
        
        // ============================================================
        // CREAR ÍNDICE DE DESCUENTOS POR RUT (búsqueda rápida)
        // ============================================================
        $descuentosPorRut = [];
        foreach ($datosDescuentos as $filaDescuentos) {
            $rut = $filaDescuentos[1] ?? null; // Columna B (RUT)
            if ($rut) {
                $descuentosPorRut[$rut] = $filaDescuentos;
            }
        }
        
        Log::info("📊 Total descuentos por RUT: " . count($descuentosPorRut));
        
        // ============================================================
        // RECORRER CADA TRABAJADOR
        // ============================================================
        foreach ($datosHaberes as $indice => $filaHaberes) {
            // ============================================================
            // EXTRAER COLUMNAS FIJAS (SIEMPRE EN MISMA POSICIÓN)
            // ============================================================
            $rut = $filaHaberes[1] ?? null;          // Columna B
            $empleado = $filaHaberes[2] ?? null;     // Columna C
            $periodo = $filaHaberes[3] ?? null;      // Columna D
            $tipo = $filaHaberes[4] ?? null;         // Columna E
            $centroCosto = $filaHaberes[5] ?? null;  // Columna F
            $dt = $filaHaberes[6] ?? null;           // Columna G
            $cargaHoraria = $filaHaberes[7] ?? null; // Columna H
            
            // Saltar si no hay RUT
            if (empty($rut)) { 
                continue; 
            }
            
            // ============================================================
            // 🔥 TOTAL HABERES (ÚLTIMA COLUMNA)
            // ============================================================
            $totalHaberesTrabajador = 0;
            if (isset($filaHaberes[$indiceTotalHaberes])) {
                $totalHaberesTrabajador = $this->limpiarValorNumerico($filaHaberes[$indiceTotalHaberes]);
            }
            
            // ============================================================
            // 🔥 TOTAL DESCUENTOS Y LEYES SOCIALES
            // ============================================================
            $totalDescuentoTrabajador = 0;
            $totalLeyesSocialesTrabajador = 0;
            
            if (isset($descuentosPorRut[$rut])) {
                $filaDescuentos = $descuentosPorRut[$rut];
                
                // 🔥 1. USAR "Tot. Desc." DIRECTAMENTE DEL EXCEL
                if ($indiceTotalDescuentos !== null && isset($filaDescuentos[$indiceTotalDescuentos])) {
                    $totalDescuentoTrabajador = $this->limpiarValorNumerico($filaDescuentos[$indiceTotalDescuentos]);
                    if ($contador < 5) {
                        Log::info("✅ 'Tot. Desc.' para {$rut}: " . number_format($totalDescuentoTrabajador));
                    }
                }
                
                // 🔥 2. SI "Tot. Desc." ES 0, MANTENER 0 (NO CALCULAR)
                // 🔥 IMPORTANTE: No calcular sumando, porque incluiría "Leyes Sociales"
                if ($totalDescuentoTrabajador == 0) {
                    Log::debug("✅ 'Tot. Desc.' es 0 para {$rut}, manteniendo 0");
                    $totalDescuentoTrabajador = 0;
                }
                
                // 🔥 3. EXTRAER LEYES SOCIALES (APARTE, NO ES DESCUENTO)
                if ($indiceLeyesSociales !== null && isset($filaDescuentos[$indiceLeyesSociales])) {
                    $totalLeyesSocialesTrabajador = $this->limpiarValorNumerico($filaDescuentos[$indiceLeyesSociales]);
                    if ($contador < 5) {
                        Log::debug("💰 Leyes Sociales para {$rut}: " . number_format($totalLeyesSocialesTrabajador));
                    }
                }
            }
            
            // ============================================================
            // CALCULAR NETO
            // ============================================================
            $totalNetoTrabajador = $totalHaberesTrabajador - $totalDescuentoTrabajador;
            
            // ============================================================
            // 🔥 PREPARAR JSON PARA DESCUENTOS (CON "Tot. Desc.")
            // ============================================================
            $descuentosVariables = [];
            
            if (isset($descuentosPorRut[$rut])) {
                $filaDescuentos = $descuentosPorRut[$rut];
                $columnasDescuentos = count($filaDescuentos);
                
                // Guardar TODOS los descuentos variables (incluyendo valores 0)
                for ($j = $COLUMNAS_FIJAS; $j < $columnasDescuentos; $j++) {
                    // Saltar "Total Desc. Leyes Sociales" (se guarda al final)
                    if ($j == $indiceLeyesSociales) { 
                        continue; 
                    }
                    
                    // Saltar "Tot. Desc." (también se guarda al final)
                    if ($j == $indiceTotalDescuentos) {
                        continue;
                    }
                    
                    $codigo = $titulosDescuentos[$j] ?? "Descuento_{$j}";
                    $valor = $this->limpiarValorNumerico($filaDescuentos[$j] ?? 0);
                    
                    // Guardar TODOS los valores (incluyendo 0 para mantener estructura)
                    $descuentosVariables[$codigo] = $valor;
                }
            }
            
            // 🔥 GUARDAR "Tot. Desc." EN EL JSON (¡IMPORTANTE!)
            $descuentosVariables['Tot. Desc.'] = $totalDescuentoTrabajador;
            
            // 🔥 GUARDAR "Total Desc. Leyes Sociales" EN EL JSON
            $descuentosVariables['Total Desc. Leyes Sociales'] = $totalLeyesSocialesTrabajador;
            
            // ============================================================
            // 🔥 PREPARAR JSON PARA HABERES
            // ============================================================
            $haberesVariables = [];
            for ($j = $COLUMNAS_FIJAS; $j < $indiceTotalHaberes; $j++) {
                $codigo = $titulosHaberes[$j] ?? "Concepto_{$j}";
                $valor = $this->limpiarValorNumerico($filaHaberes[$j] ?? 0);
                // Guardar TODOS los valores (incluyendo 0)
                $haberesVariables[$codigo] = $valor;
            }
            
            // ============================================================
            // MOSTRAR PRIMEROS 5 TRABAJADORES (DEPURACIÓN)
            // ============================================================
            if ($contador < 5) {
                $numeroTrabajador = $contador + 1;
                Log::info("🔍 TRABAJADOR {$numeroTrabajador}: {$rut} - {$empleado}");
                Log::info("   Centro Costo: {$centroCosto}");
                Log::info("   Total Haberes: " . number_format($totalHaberesTrabajador));
                Log::info("   Total Descuentos: " . number_format($totalDescuentoTrabajador));
                Log::info("   Leyes Sociales: " . number_format($totalLeyesSocialesTrabajador));
                Log::info("   Total Neto: " . number_format($totalNetoTrabajador));
                Log::info("   Descuentos JSON: " . json_encode($descuentosVariables));
                Log::info("   ---");
            }
            
            // ============================================================
            // GUARDAR TRABAJADOR EN BASE DE DATOS
            // ============================================================
            $trabajador = RemuneracionTrabajador::create([
                'cabecera_id' => $cabeceraId,
                'rut' => $rut,
                'empleado' => $empleado,
                'periodo' => $periodo,
                'tipo' => $tipo,
                'centro_costo' => $centroCosto,
                'dt' => $dt,
                'carga_horaria' => $cargaHoraria,
                'sueldo_base' => $this->limpiarValorNumerico($filaHaberes[$COLUMNAS_FIJAS] ?? 0),
                'total_haberes' => $totalHaberesTrabajador,
                'total_descuentos' => $totalDescuentoTrabajador,
                'total_neto' => $totalNetoTrabajador
            ]);
            
            // ============================================================
            // 🔥 GUARDAR HABERES (JSON) - SIEMPRE
            // ============================================================
            // 🔥 IMPORTANTE: Se guarda SIEMPRE, incluso si está vacío
            RemuneracionDetalle::create([
                'trabajador_id' => $trabajador->id,
                'tipo' => 'haberes',
                'datos' => $haberesVariables
            ]);
            
            // ============================================================
            // 🔥 GUARDAR DESCUENTOS (JSON) - SIEMPRE
            // ============================================================
            // 🔥 IMPORTANTE: Se guarda SIEMPRE, incluso si está vacío
            // Esto asegura que el JSON exista para consultas posteriores
            RemuneracionDetalle::create([
                'trabajador_id' => $trabajador->id,
                'tipo' => 'descuentos',
                'datos' => $descuentosVariables
            ]);
            
            // ============================================================
            // ACUMULAR TOTALES GENERALES
            // ============================================================
            $totalHaberes += $totalHaberesTrabajador;
            $totalDescuentos += $totalDescuentoTrabajador;
            $totalNeto += $totalNetoTrabajador;
            $totalLeyesSociales += $totalLeyesSocialesTrabajador;
            $contador++;
        }
        
        // ============================================================
        // LOGS FINALES
        // ============================================================
        Log::info('📊 ===== TOTALES =====');
        Log::info("   ✅ Trabajadores: {$contador}");
        Log::info("   ✅ Total Haberes: " . number_format($totalHaberes));
        Log::info("   ✅ Total Descuentos: " . number_format($totalDescuentos));
        Log::info("   ✅ Total Leyes Sociales: " . number_format($totalLeyesSociales));
        Log::info("   ✅ Total Neto: " . number_format($totalNeto));
        
        // ============================================================
        // RETORNAR RESULTADOS
        // ============================================================
        return [
            'total_trabajadores' => $contador,
            'total_haberes' => $totalHaberes,
            'total_descuentos' => $totalDescuentos,
            'total_neto' => $totalNeto,
            'total_leyes_sociales' => $totalLeyesSociales
        ];
    }

    /**
     * ============================================================
     * LIMPIAR TEXTO
     * ============================================================
     * Elimina espacios extras y caracteres especiales
     * 
     * @param mixed $texto
     * @return string|null
     */
    protected function limpiarTexto($texto)
    {
        if ($texto === null || $texto === '') {
            return null;
        }
        return trim($texto);
    }

    /**
     * ============================================================
     * LIMPIAR VALOR NUMÉRICO
     * ============================================================
     * Convierte strings como "$ 1.095.040" o "12,9485" a número
     * 
     * @param mixed $valor
     * @return float
     */
    protected function limpiarValorNumerico($valor)
    {
        if ($valor === null || $valor === '') {
            return 0;
        }
        if (is_numeric($valor)) {
            return floatval($valor);
        }
        if (is_string($valor)) {
            // Remover símbolos de moneda y espacios
            $valor = str_replace('$', '', $valor);
            $valor = str_replace(' ', '', $valor);
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
            $valor = preg_replace('/[^0-9\.\-]/', '', $valor);
        }
        return floatval($valor);
    }

    /**
     * ============================================================
     * LIMPIAR VALOR (para cualquier tipo de dato)
     * ============================================================
     * 
     * @param mixed $valor
     * @return mixed
     */
    protected function limpiarValor($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_string($valor)) {
            return trim($valor);
        }
        return $valor;
    }
}