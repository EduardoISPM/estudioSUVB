<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\ResumenSede;
use App\Models\Registro;
use App\Models\RemuneracionCabecera;
use App\Models\RemuneracionTrabajador;
use App\Models\RemuneracionDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================
 * DASHBOARD CONTROLLER
 * ============================================================
 * Controlador principal del dashboard que maneja:
 * - Estadísticas generales (tarjetas)
 * - Selector de mes/año
 * - Tabla de resumen por sede
 * - Gráficos (evolución mensual y comparativo)
 * - Comparación Subvención vs Remuneraciones
 * 
 * @author Estudiante de Programación
 * @version 2.0
 */
class DashboardController extends Controller
{
    /**
     * ============================================================
     * MÉTODO: INDEX
     * ============================================================
     * Función principal que carga el Dashboard con todos los datos
     * necesarios para la visualización.
     * 
     * @param Request $request - Contiene los parámetros mes y anio
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ============================================================
        // SECCIÓN 1: OBTENER PARÁMETROS DE FILTRO
        // ============================================================
        // Se obtienen los valores de mes y año desde la URL
        // Ejemplo: /dashboard?mes=AGOSTO&anio=2026
        $mes = $request->input('mes');
        $anio = $request->input('anio');

        // ============================================================
        // SECCIÓN 2: OBTENER LISTA DE MESES DISPONIBLES
        // ============================================================
        // Se consultan todos los meses y años que tienen datos
        // en la tabla 'archivos', se ordenan cronológicamente
        $archivosDisponibles = Archivo::select('mes_pago', 'anio_pago')
            ->distinct()
            ->get()
            ->sortBy(function($item) {
                // Mapeo de nombres de meses a números para ordenamiento
                $ordenMeses = [
                    'ENERO' => 1, 'FEBRERO' => 2, 'MARZO' => 3, 'ABRIL' => 4,
                    'MAYO' => 5, 'JUNIO' => 6, 'JULIO' => 7, 'AGOSTO' => 8,
                    'SEPTIEMBRE' => 9, 'OCTUBRE' => 10, 'NOVIEMBRE' => 11, 'DICIEMBRE' => 12
                ];
                $mesNumero = $ordenMeses[$item->mes_pago] ?? 0;
                // Ordenar primero por año, luego por número de mes
                return [$item->anio_pago, $mesNumero];
            }, SORT_REGULAR, true)
            ->values();

        // ============================================================
        // SECCIÓN 3: OBTENER AÑOS DISPONIBLES
        // ============================================================
        // Lista de años que tienen datos, ordenados del más reciente al más antiguo
        $aniosDisponibles = Archivo::select('anio_pago')
            ->distinct()
            ->orderBy('anio_pago', 'desc')
            ->pluck('anio_pago')
            ->toArray();

        // Si no se seleccionó un año, tomar el año más reciente
        if (!$anio && !empty($aniosDisponibles)) {
            $anio = $aniosDisponibles[0];
        }

        // Si no se seleccionó un mes, tomar el último mes del año seleccionado
        if (!$mes && $anio) {
            $ultimoMes = Archivo::where('anio_pago', $anio)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($ultimoMes) {
                $mes = $ultimoMes->mes_pago;
            }
        }

        // ============================================================
        // SECCIÓN 4: BUSCAR EL ARCHIVO SELECCIONADO
        // ============================================================
        // Obtener el archivo correspondiente al mes y año seleccionados
        $archivoSeleccionado = Archivo::where('mes_pago', $mes)
            ->where('anio_pago', $anio)
            ->latest()
            ->first();

        // ============================================================
        // SECCIÓN 5: OBTENER EL RESUMEN POR SEDES
        // ============================================================
        // Si existe el archivo, obtener el resumen de todas las sedes
        // Excluir registros con sede 'Sin Sede'
        $resumenSedes = collect();
        if ($archivoSeleccionado) {
            $resumenSedes = ResumenSede::where('archivo_id', $archivoSeleccionado->id)
                ->where('sede', '!=', 'Sin Sede')
                ->orderBy('sede')
                ->get();
        }

        // ============================================================
        // SECCIÓN 6: ESTADÍSTICAS GENERALES
        // ============================================================
        
        // 6.1: Total de archivos importados en el sistema
        $totalArchivos = Archivo::count();

        // 6.2: Total de cursos del mes seleccionado
        // Se cuentan los cursos distintos (curso_id) que tienen datos en el mes
        if ($archivoSeleccionado) {
            $totalCursos = Registro::where('archivo_id', $archivoSeleccionado->id)
                ->whereNotNull('curso_id')
                ->distinct()
                ->count('curso_id');
        } else {
            $totalCursos = Registro::select('curso_id')
                ->whereNotNull('curso_id')
                ->distinct()
                ->count('curso_id');
        }

        /**
         * ============================================================
         * 6.3: CURSOS PIE DEL MES SELECCIONADO - CORREGIDO
         * ============================================================
         * 🔥 IMPORTANTE: Los cursos PIE se identifican por el campo
         * 'tipo_subvencion' = 'CURSO_PIE'
         * 
         * NO se filtran por códigos específicos (1009, 1010, 1110, 1310)
         * porque esos códigos NO existen en la base de datos.
         * 
         * Los códigos reales en curso_id tienen formato:
         * - 10-4-A, 10-4-B, 10-4-C, 10-4-D (10 = enseñanza básica)
         * - 110-1-A, 110-1-B, 110-1-C, 110-1-D (110 = enseñanza media)
         * - 310-2-A, 310-2-B, 310-2-C, 310-2-D (310 = otra categoría)
         * 
         * La forma correcta de contar cursos PIE es por tipo_subvencion
         */
        if ($archivoSeleccionado) {
            $cursosPIE = Registro::where('archivo_id', $archivoSeleccionado->id)
                ->where('tipo_subvencion', 'CURSO_PIE')  // 🔥 CLAVE CORRECTA
                ->whereNotNull('curso_id')
                ->distinct()
                ->count('curso_id');
        } else {
            $cursosPIE = Registro::where('tipo_subvencion', 'CURSO_PIE')  // 🔥 CLAVE CORRECTA
                ->whereNotNull('curso_id')
                ->distinct()
                ->count('curso_id');
        }

        /**
         * 6.4: Detalle de cursos PIE (para verificación)
         * ============================================================
         * Se obtienen los datos completos de los cursos PIE para
         * poder verificarlos en la vista si es necesario.
         * Útil para depuración y transparencia.
         */
        $cursosPIEDetalle = [];
        if ($archivoSeleccionado) {
            $cursosPIEDetalle = Registro::where('archivo_id', $archivoSeleccionado->id)
                ->where('tipo_subvencion', 'CURSO_PIE')
                ->whereNotNull('curso_id')
                ->select('curso_id', 'cod_ens', 'grado', 'letra', 'ens')
                ->distinct()
                ->limit(10)
                ->get()
                ->toArray();
        }

        // 6.5: Total de sedes activas
        // Se cuentan las sedes distintas que tienen datos registrados
        $totalSedes = ResumenSede::distinct('sede')
            ->where('sede', '!=', 'Sin Sede')
            ->count('sede');

        // ============================================================
        // SECCIÓN 7: DATOS PARA GRÁFICO DE EVOLUCIÓN MENSUAL
        // ============================================================
        // Este gráfico muestra 4 líneas: Total, General, Curso PIE, Alumnos PIE
        // a lo largo de todos los meses disponibles
        
        $datosEvolucionTotal = [];
        $datosEvolucionGeneral = [];
        $datosEvolucionCursoPie = [];
        $datosEvolucionAlumnosPie = [];
        $mesesEvolucion = [];

        // Obtener todos los archivos ordenados cronológicamente
        $archivosOrdenados = Archivo::orderBy('anio_pago', 'asc')
            ->orderByRaw("FIELD(mes_pago, 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE')")
            ->get();

        // Recorrer cada archivo para calcular los datos de evolución
        foreach ($archivosOrdenados as $archivo) {
            // Formato de etiqueta: "AGO 2026"
            $mesesEvolucion[] = substr($archivo->mes_pago, 0, 3) . ' ' . $archivo->anio_pago;

            // Calcular subvención general
            $totalGeneral = ResumenSede::where('archivo_id', $archivo->id)
                ->where('sede', '!=', 'Sin Sede')
                ->sum('subvencion_general');

            // Calcular subvención curso PIE
            $totalCursoPie = ResumenSede::where('archivo_id', $archivo->id)
                ->where('sede', '!=', 'Sin Sede')
                ->sum('subvencion_curso_pie');

            // Calcular subvención alumnos PIE
            $totalAlumnosPie = ResumenSede::where('archivo_id', $archivo->id)
                ->where('sede', '!=', 'Sin Sede')
                ->sum('subvencion_alumnos_pie');

            // Calcular total de subvención
            $totalSubvencion = $totalGeneral + $totalCursoPie + $totalAlumnosPie;

            // Almacenar datos en millones (para visualización en el gráfico)
            $datosEvolucionTotal[] = $totalSubvencion > 0 ? $totalSubvencion / 1000000 : 0;
            $datosEvolucionGeneral[] = $totalGeneral > 0 ? $totalGeneral / 1000000 : 0;
            $datosEvolucionCursoPie[] = $totalCursoPie > 0 ? $totalCursoPie / 1000000 : 0;
            $datosEvolucionAlumnosPie[] = $totalAlumnosPie > 0 ? $totalAlumnosPie / 1000000 : 0;
        }

        // ============================================================
        // SECCIÓN 8: GRÁFICO COMPARATIVO DE 2 MESES
        // ============================================================
        // Este gráfico compara los datos del mes actual con el mes anterior
        // Mostrando barras agrupadas para cada sede
        
        // 8.1: Definir el orden de los meses
        $mesesOrden = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 
                       'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        
        // 8.2: Configurar mes actual
        $mesActual = $mes;
        $anioActual = $anio;
        
        // 8.3: Calcular mes anterior
        // Si es ENERO, el mes anterior es DICIEMBRE del año anterior
        $indiceActual = array_search($mesActual, $mesesOrden);
        $indiceAnterior = $indiceActual - 1;
        $anioAnterior = $anioActual;
        
        if ($indiceAnterior < 0) {
            $indiceAnterior = 11; // DICIEMBRE
            $anioAnterior = $anioActual - 1;
        }
        
        $mesAnterior = $mesesOrden[$indiceAnterior];
        
        // 8.4: Buscar archivo del mes anterior
        $archivoAnterior = Archivo::where('mes_pago', $mesAnterior)
            ->where('anio_pago', $anioAnterior)
            ->latest()
            ->first();
        
        // 8.5: Preparar datos comparativos
        $sedesComparativas = [];
        
        if ($archivoSeleccionado) {
            // Obtener sedes del mes actual
            $sedesActual = ResumenSede::where('archivo_id', $archivoSeleccionado->id)
                ->where('sede', '!=', 'Sin Sede')
                ->orderBy('sede')
                ->get();
            
            // Obtener sedes del mes anterior (si existe)
            $sedesAnterior = collect();
            if ($archivoAnterior) {
                $sedesAnterior = ResumenSede::where('archivo_id', $archivoAnterior->id)
                    ->where('sede', '!=', 'Sin Sede')
                    ->orderBy('sede')
                    ->get();
            }
            
            // Combinar datos de ambos meses para cada sede
            foreach ($sedesActual as $sede) {
                // Buscar la misma sede en el mes anterior
                $sedeAnterior = $sedesAnterior->firstWhere('sede', $sede->sede);
                
                // Calcular total actual
                $totalActual = $sede->subvencion_general + 
                              $sede->subvencion_curso_pie + 
                              $sede->subvencion_alumnos_pie;
                
                // Calcular total anterior (0 si no existe)
                $totalAnterior = 0;
                if ($sedeAnterior) {
                    $totalAnterior = $sedeAnterior->subvencion_general + 
                                    $sedeAnterior->subvencion_curso_pie + 
                                    $sedeAnterior->subvencion_alumnos_pie;
                }
                
                // Calcular el porcentaje de cambio
                $cambioPorcentual = $totalAnterior > 0 
                    ? round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 2)
                    : 0;
                
                // Almacenar todos los datos en un array estructurado
                $sedesComparativas[] = [
                    'sede' => $sede->sede,
                    'actual_general' => $sede->subvencion_general / 1000000,
                    'actual_curso_pie' => $sede->subvencion_curso_pie / 1000000,
                    'actual_alumnos_pie' => $sede->subvencion_alumnos_pie / 1000000,
                    'actual_total' => $totalActual / 1000000,
                    'anterior_general' => $sedeAnterior ? $sedeAnterior->subvencion_general / 1000000 : 0,
                    'anterior_curso_pie' => $sedeAnterior ? $sedeAnterior->subvencion_curso_pie / 1000000 : 0,
                    'anterior_alumnos_pie' => $sedeAnterior ? $sedeAnterior->subvencion_alumnos_pie / 1000000 : 0,
                    'anterior_total' => $totalAnterior / 1000000,
                    'cambio_porcentual' => $cambioPorcentual
                ];
            }
        }

        // ============================================================
        // SECCIÓN 9: COMPARACIÓN SUBVENCIÓN VS REMUNERACIONES
        // ============================================================
        // Esta sección compara las subvenciones con las remuneraciones
        // para cada sede, calculando la cobertura y diferencia
        
        $datosComparacion = null;
        $remuneracionMes = null;

        if ($archivoSeleccionado) {
            // Buscar registro de remuneraciones para el mismo mes/año
            $remuneracionMes = RemuneracionCabecera::where('mes_pago', $mes)
                ->where('anio_pago', $anio)
                ->first();

            // Si existen remuneraciones, generar la comparación
            if ($remuneracionMes) {
                $sedesSubvencion = ResumenSede::where('archivo_id', $archivoSeleccionado->id)
                    ->where('sede', '!=', 'Sin Sede')
                    ->get();

                $datosComparacion = $this->generarDatosComparacion($sedesSubvencion, $remuneracionMes);
            }
        }

        // ============================================================
        // SECCIÓN 10: RETORNAR LA VISTA CON TODOS LOS DATOS
        // ============================================================
        // Se pasan todas las variables a la vista 'dashboard'
        // para que puedan ser utilizadas en el HTML y JavaScript
        return view('dashboard', compact(
            // Datos del archivo y selección
            'archivoSeleccionado',
            'resumenSedes',
            'archivosDisponibles',
            'aniosDisponibles',
            'mes',
            'anio',
            
            // Estadísticas generales
            'totalArchivos',
            'totalCursos',
            'cursosPIE',                // 🔥 CORREGIDO: usa tipo_subvencion = 'CURSO_PIE'
            'cursosPIEDetalle',         // Detalle para verificación
            'totalSedes',
            
            // Datos para gráfico de evolución mensual
            'mesesEvolucion',
            'datosEvolucionTotal',
            'datosEvolucionGeneral',
            'datosEvolucionCursoPie',
            'datosEvolucionAlumnosPie',
            
            // Datos para gráfico comparativo de 2 meses
            'sedesComparativas',
            'mesActual',
            'mesAnterior',
            'anioActual',
            'anioAnterior',
            
            // Datos para tabla comparativa
            'datosComparacion',
            'remuneracionMes'
        ));
    }

    /**
     * ============================================================
     * MÉTODO: generarDatosComparacion
     * ============================================================
     * Genera la comparación entre subvenciones y remuneraciones
     * para cada sede, mapeando los nombres de sedes a centros de costo.
     * 
     * @param \Illuminate\Support\Collection $sedesSubvencion - Colección de sedes con subvenciones
     * @param \App\Models\RemuneracionCabecera $remuneracionMes - Datos de remuneraciones del mes
     * @return array - Datos estructurados para la tabla comparativa
     */
    protected function generarDatosComparacion($sedesSubvencion, $remuneracionMes)
    {
        // Array para almacenar los resultados
        $resultado = [];
        $totales = ['remuneraciones' => 0, 'subvenciones' => 0];
        
        /**
         * Mapeo de nombres de sedes a centros de costo
         * ============================================================
         * Cada sede puede tener uno o más centros de costo asociados
         * en el sistema de remuneraciones.
         */
        $mapeoSedes = [
            'Sede Jardín' => ['02 COLEGIO JARDIN INFANT'],
            'Sede 1 a 4 Básico' => ['03 COLEGIO 1° A 4°'],
            'Sede 5 a 6 Básico' => ['04 COLEGIO 5° A 6°'],
            'Sede 7 a 8 Básico' => ['05 COLEGIO 7° A 8°'],
            'Ed. Media' => ['01 CENTRAL', '06 COLEGIO ENSEÑANZA'],
            'Sede PIE' => ['07 INTEGRACIÓN - PIE']
        ];
        
        // Procesar cada sede
        foreach ($sedesSubvencion as $sede) {
            $nombreSede = $sede->sede;
            
            /**
             * Calcular subvención según el tipo de sede
             * ============================================================
             * - Sede PIE: suma subvención curso PIE + alumnos PIE
             * - Otras sedes: solo subvención general
             */
            if ($nombreSede === 'Sede PIE') {
                $subvencion = $sede->subvencion_curso_pie + $sede->subvencion_alumnos_pie;
            } else {
                $subvencion = $sede->subvencion_general;
            }
            
            // Calcular remuneraciones para esta sede
            $centrosCosto = $mapeoSedes[$nombreSede] ?? [];
            $remuneracionSede = $this->calcularRemuneracionPorSede($remuneracionMes->id, $centrosCosto);
            
            // Almacenar los datos de la sede
            $resultado[] = [
                'sede' => $nombreSede,
                'remuneraciones' => $remuneracionSede,
                'subvenciones' => $subvencion,
                'diferencia' => $subvencion - $remuneracionSede,
                'porcentaje' => $remuneracionSede > 0 ? round(($subvencion / $remuneracionSede) * 100, 2) : 0
            ];
            
            // Acumular totales
            $totales['remuneraciones'] += $remuneracionSede;
            $totales['subvenciones'] += $subvencion;
        }
        
        // Retornar datos completos incluyendo totales
        return [
            'detalle' => $resultado,
            'totales' => $totales,
            'diferencia_total' => $totales['subvenciones'] - $totales['remuneraciones'],
            'porcentaje_total' => $totales['remuneraciones'] > 0 
                ? round(($totales['subvenciones'] / $totales['remuneraciones']) * 100, 2) 
                : 0
        ];
    }

    /**
     * ============================================================
     * MÉTODO: calcularRemuneracionPorSede
     * ============================================================
     * Calcula el total de remuneraciones para una sede específica
     * sumando Total Haberes + Total Leyes Sociales de los trabajadores
     * que pertenecen a los centros de costo de la sede.
     * 
     * @param int $remuneracionId - ID del registro de remuneraciones
     * @param array $centrosCosto - Array de centros de costo de la sede
     * @return float - Total de remuneraciones de la sede
     */
    protected function calcularRemuneracionPorSede($remuneracionId, $centrosCosto)
    {
        // Si no hay centros de costo, retornar 0
        if (empty($centrosCosto)) {
            return 0;
        }
        
        // Obtener trabajadores de la sede según los centros de costo
        $trabajadores = RemuneracionTrabajador::where('cabecera_id', $remuneracionId)
            ->whereIn('centro_costo', $centrosCosto)
            ->get();
        
        $total = 0;
        
        // Calcular total para cada trabajador
        foreach ($trabajadores as $trabajador) {
            // Obtener el detalle de descuentos del trabajador
            $detalle = RemuneracionDetalle::where('trabajador_id', $trabajador->id)
                ->where('tipo', 'descuentos')
                ->first();
            
            // Extraer las leyes sociales del detalle
            $leyesSociales = 0;
            if ($detalle && isset($detalle->datos['Total Desc. Leyes Sociales'])) {
                $leyesSociales = $detalle->datos['Total Desc. Leyes Sociales'];
            }
            
            // Sumar haberes + leyes sociales
            $total += $trabajador->total_haberes + $leyesSociales;
        }
        
        return $total;
    }

    /**
     * ============================================================
     * MÉTODO: datos
     * ============================================================
     * Carga la vista de listado de registros con paginación y filtros.
     * Permite filtrar por mes, año, sede y tipo de subvención.
     * 
     * @param Request $request - Contiene parámetros de búsqueda y filtros
     * @return \Illuminate\View\View
     */
    public function datos(Request $request)
    {
        // Consulta base con relaciones y ordenamiento
        $query = Registro::with('archivo')->orderBy('created_at', 'desc');

        // ============================================================
        // FILTROS DISPONIBLES
        // ============================================================
        
        // Filtro por mes
        if ($request->has('mes') && $request->mes) {
            $query->whereHas('archivo', function($q) use ($request) {
                $q->where('mes_pago', $request->mes);
            });
        }

        // Filtro por año
        if ($request->has('anio') && $request->anio) {
            $query->whereHas('archivo', function($q) use ($request) {
                $q->where('anio_pago', $request->anio);
            });
        }

        // Filtro por sede
        if ($request->has('sede') && $request->sede) {
            $query->where('sede', $request->sede);
        }

        // Filtro por tipo de subvención
        if ($request->has('tipo') && $request->tipo) {
            $query->where('tipo_subvencion', $request->tipo);
        }

        // Búsqueda por texto (curso_id, cod_ens, grado, letra, ens)
        if ($request->has('buscar') && $request->buscar) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('curso_id', 'LIKE', "%{$buscar}%")
                  ->orWhere('cod_ens', 'LIKE', "%{$buscar}%")
                  ->orWhere('grado', 'LIKE', "%{$buscar}%")
                  ->orWhere('letra', 'LIKE', "%{$buscar}%")
                  ->orWhere('ens', 'LIKE', "%{$buscar}%");
            });
        }

        // ============================================================
        // PAGINACIÓN Y DATOS PARA FILTROS
        // ============================================================
        
        // Obtener registros paginados (20 por página)
        $registros = $query->paginate(20);

        // Obtener listas para los filtros desplegables
        $mesesDisponibles = Archivo::select('mes_pago')->distinct()->pluck('mes_pago')->toArray();
        $aniosDisponibles = Archivo::select('anio_pago')->distinct()->orderBy('anio_pago', 'desc')->pluck('anio_pago')->toArray();
        $sedesDisponibles = ResumenSede::select('sede')->distinct()->where('sede', '!=', 'Sin Sede')->orderBy('sede')->pluck('sede')->toArray();

        // Retornar vista con datos
        return view('datos', compact(
            'registros',
            'mesesDisponibles',
            'aniosDisponibles',
            'sedesDisponibles'
        ));
    }
}