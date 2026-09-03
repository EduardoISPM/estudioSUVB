<?php

namespace App\Http\Controllers;

use App\Services\RemuneracionImportService;
use App\Models\RemuneracionCabecera;
use App\Models\RemuneracionTrabajador;
use App\Models\RemuneracionDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================
 * REMUNERACION CONTROLLER
 * ============================================================
 * Controlador para la gestión de remuneraciones:
 * - Listado con filtros y paginación
 * - Importación de archivos Excel
 * - Reemplazo de datos existentes
 * - Detalle de remuneraciones por trabajador
 * - 🔥 NUEVO: Detalle agrupado por Centro de Costo (Sede)
 * - Eliminación de registros
 * - API para consultas
 * 
 * @author Estudiante de Programación
 * @version 3.0
 */
class RemuneracionController extends Controller
{
    protected $importService;

    public function __construct(RemuneracionImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * ============================================================
     * MÉTODO: INDEX
     * ============================================================
     * Muestra el listado de remuneraciones con filtros y paginación
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ============================================================
        // 🔥 LOG DE INICIO
        // ============================================================
        Log::info('📋 ===== ACCEDIENDO AL LISTADO DE REMUNERACIONES =====');
        Log::info('📋 Parámetros de filtro:', $request->all());

        // ============================================================
        // CONSULTA BASE
        // ============================================================
        $query = RemuneracionCabecera::withCount('trabajadores');

        // ============================================================
        // FILTROS
        // ============================================================
        
        // Filtro por mes
        if ($request->has('mes') && $request->mes) {
            $query->where('mes_pago', $request->mes);
            Log::info('📋 Aplicando filtro por mes: ' . $request->mes);
        }

        // Filtro por año
        if ($request->has('anio') && $request->anio) {
            $query->where('anio_pago', $request->anio);
            Log::info('📋 Aplicando filtro por año: ' . $request->anio);
        }

        // ============================================================
        // ORDENAMIENTO
        // ============================================================
        $query->orderBy('anio_pago', 'desc')
            ->orderByRaw("FIELD(mes_pago, 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE') desc");

        // ============================================================
        // PAGINACIÓN
        // ============================================================
        $remuneraciones = $query->paginate(15);
        Log::info('📋 Total de remuneraciones encontradas: ' . $remuneraciones->total());

        // ============================================================
        // DATOS PARA FILTROS
        // ============================================================
        $mesesDisponibles = RemuneracionCabecera::select('mes_pago')
            ->distinct()
            ->pluck('mes_pago')
            ->toArray();

        $aniosDisponibles = RemuneracionCabecera::select('anio_pago')
            ->distinct()
            ->orderBy('anio_pago', 'desc')
            ->pluck('anio_pago')
            ->toArray();

        Log::info('📋 Meses disponibles: ' . json_encode($mesesDisponibles));
        Log::info('📋 Años disponibles: ' . json_encode($aniosDisponibles));

        // ============================================================
        // CALCULAR TOTALES
        // ============================================================
        $totales = [
            'haberes' => $query->sum('total_haberes'),
            'leyes_sociales' => $query->sum('total_leyes_sociales'),
            'neto' => $query->sum('total_neto')
        ];

        Log::info('📊 TOTALES CALCULADOS:');
        Log::info('   Total Haberes: ' . number_format($totales['haberes']));
        Log::info('   Total Leyes Sociales: ' . number_format($totales['leyes_sociales']));
        Log::info('   Total Neto: ' . number_format($totales['neto']));

        // ============================================================
        // RETORNAR VISTA
        // ============================================================
        Log::info('✅ ===== FINALIZANDO LISTADO DE REMUNERACIONES =====');
        
        return view('remuneraciones.index', compact(
            'remuneraciones',
            'mesesDisponibles',
            'aniosDisponibles',
            'totales'
        ));
    }

    /**
     * ============================================================
     * MÉTODO: IMPORTAR
     * ============================================================
     * Importa un archivo de remuneraciones
     * El mes/año se extrae AUTOMÁTICAMENTE del archivo (fila 8)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importar(Request $request)
    {
        // ============================================================
        // 🔥 LOG DE INICIO DE IMPORTACIÓN
        // ============================================================
        Log::info('📥 ===== INICIANDO PROCESO DE IMPORTACIÓN =====');
        Log::info('📥 Timestamp: ' . now()->toDateTimeString());

        try {
            // ============================================================
            // VALIDACIÓN
            // ============================================================
            Log::info('🔍 Validando datos de entrada...');
            
            $request->validate([
                'archivo' => 'required|file|max:51200|mimes:xlsx,xls',
                'mes' => 'required|string',
                'anio' => 'required|string'
            ]);

            Log::info('✅ Validación superada correctamente');

            $mes = $request->mes;
            $anio = $request->anio;

            // ============================================================
            // VERIFICAR DUPLICADO
            // ============================================================
            Log::info('🔍 Verificando si ya existen datos para ' . $mes . ' ' . $anio);
            
            $existe = RemuneracionCabecera::where('mes_pago', $mes)
                ->where('anio_pago', $anio)
                ->exists();

            if ($existe) {
                Log::info('⚠️ DUPLICADO DETECTADO: Ya existen datos para ' . $mes . ' ' . $anio);
                
                return response()->json([
                    'success' => false,
                    'duplicado' => true,
                    'mensaje' => "⚠️ Ya existen remuneraciones para {$mes} {$anio}.",
                    'mes' => $mes,
                    'anio' => $anio
                ]);
            }

            Log::info('✅ No se encontraron datos previos para ' . $mes . ' ' . $anio);

            // ============================================================
            // 🔥 PROCESAR ARCHIVO
            // ============================================================
            Log::info('🔄 Llamando al servicio de importación...');
            
            $archivo = $request->file('archivo');
            $resultado = $this->importService->procesarArchivo($archivo);
            
            // ============================================================
            // 🔥 LOG DE RESULTADO
            // ============================================================
            Log::info('📊 RESULTADO DE LA IMPORTACIÓN:');
            Log::info('   Success: ' . ($resultado['success'] ? 'SÍ' : 'NO'));
            Log::info('   Total Haberes: ' . number_format($resultado['total_haberes'] ?? 0));
            Log::info('   Total Descuentos: ' . number_format($resultado['total_descuentos'] ?? 0));

            Log::info('✅ ===== IMPORTACIÓN COMPLETADA EXITOSAMENTE =====');

            return response()->json([
                'success' => true,
                'mensaje' => "✅ Remuneraciones de {$mes} {$anio} importadas correctamente",
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            // ============================================================
            // 🔥 LOG DE ERROR
            // ============================================================
            Log::error('❌ ===== ERROR EN IMPORTACIÓN =====');
            Log::error('❌ Mensaje: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================================
     * MÉTODO: REEMPLAZAR
     * ============================================================
     * Reemplaza remuneraciones existentes (cuando el usuario confirma)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reemplazar(Request $request)
    {
        // ============================================================
        // 🔥 LOG DE INICIO DE REEMPLAZO
        // ============================================================
        Log::info('🔄 ===== INICIANDO PROCESO DE REEMPLAZO =====');
        Log::info('🔄 Mes a reemplazar: ' . $request->mes);
        Log::info('🔄 Año a reemplazar: ' . $request->anio);

        try {
            // ============================================================
            // VALIDACIÓN
            // ============================================================
            $request->validate([
                'archivo' => 'required|file|max:51200|mimes:xlsx,xls',
                'mes' => 'required|string',
                'anio' => 'required|string'
            ]);

            $mes = $request->mes;
            $anio = $request->anio;

            Log::info('🔄 Validación superada. Procediendo con reemplazo...');

            // ============================================================
            // ELIMINAR DATOS ANTIGUOS
            // ============================================================
            Log::info('🔄 Eliminando datos antiguos para ' . $mes . ' ' . $anio);
            
            \DB::transaction(function() use ($mes, $anio) {
                $cabecera = RemuneracionCabecera::where('mes_pago', $mes)
                    ->where('anio_pago', $anio)
                    ->first();
                
                if ($cabecera) {
                    Log::info('🔄 Eliminando cabecera ID: ' . $cabecera->id);
                    $cabecera->delete();
                    Log::info('✅ Datos eliminados correctamente');
                } else {
                    Log::info('ℹ️ No se encontraron datos previos para eliminar');
                }
            });

            // ============================================================
            // 🔥 PROCESAR NUEVO ARCHIVO
            // ============================================================
            Log::info('🔄 Procesando nuevo archivo para reemplazo...');
            
            $archivo = $request->file('archivo');
            $resultado = $this->importService->procesarArchivo($archivo);
            
            Log::info('✅ ===== REEMPLAZO COMPLETADO EXITOSAMENTE =====');

            return response()->json([
                'success' => true,
                'mensaje' => "✅ Remuneraciones de {$mes} {$anio} reemplazadas correctamente",
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ===== ERROR EN REEMPLAZO =====');
            Log::error('❌ Mensaje: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================================
     * MÉTODO: DETALLE
     * ============================================================
     * Muestra el detalle de una remuneración con todos sus trabajadores
     * 
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function detalle($id)
    {
        // ============================================================
        // 🔥 LOG DE ACCESO AL DETALLE
        // ============================================================
        Log::info('📋 Accediendo al detalle de remuneración ID: ' . $id);

        // ============================================================
        // OBTENER REMUNERACIÓN CON RELACIONES
        // ============================================================
        $remuneracion = RemuneracionCabecera::with(['trabajadores.detalles'])
            ->findOrFail($id);
        
        Log::info('📋 Remuneración: ' . $remuneracion->mes_pago . ' ' . $remuneracion->anio_pago);
        Log::info('📋 Total trabajadores: ' . $remuneracion->trabajadores->count());

        // ============================================================
        // CALCULAR ESTADÍSTICAS
        // ============================================================
        $totalTrabajadores = $remuneracion->trabajadores->count();
        
        $totalHaberes = $remuneracion->trabajadores->sum('total_haberes');
        
        $totalLeyesSociales = 0;
        foreach ($remuneracion->trabajadores as $trabajador) {
            if ($trabajador->detalles) {
                $detalle = $trabajador->detalles->firstWhere('tipo', 'descuentos');
                if ($detalle && isset($detalle->datos['Total Desc. Leyes Sociales'])) {
                    $totalLeyesSociales += $detalle->datos['Total Desc. Leyes Sociales'];
                }
            }
        }

        $totalNeto = $totalHaberes + $totalLeyesSociales;

        // ============================================================
        // AGRUPAR POR CENTRO DE COSTO
        // ============================================================
        $centrosCosto = $remuneracion->trabajadores
            ->groupBy('centro_costo')
            ->map(function($grupo) {
                return [
                    'cantidad' => $grupo->count(),
                    'total_haberes' => $grupo->sum('total_haberes')
                ];
            });

        Log::info('📊 Totales calculados:');
        Log::info('   Total Haberes: ' . number_format($totalHaberes));
        Log::info('   Total Leyes Sociales: ' . number_format($totalLeyesSociales));
        Log::info('   Total Neto: ' . number_format($totalNeto));

        // ============================================================
        // RETORNAR VISTA
        // ============================================================
        return view('remuneraciones.detalle', compact(
            'remuneracion',
            'totalTrabajadores',
            'totalHaberes',
            'totalLeyesSociales',
            'totalNeto',
            'centrosCosto'
        ));
    }

    /**
     * ============================================================
     * 🔥 MÉTODO: DETALLE POR CENTRO DE COSTO (SEDE) - NUEVO
     * ============================================================
     * Muestra el detalle de una remuneración agrupada por Centro de Costo
     * 
     * @param int $id - ID de la cabecera
     * @return \Illuminate\View\View
     */
    public function detallePorCentroCosto($id)
    {
        // ============================================================
        // 🔥 LOG DE ACCESO
        // ============================================================
        Log::info('📋 Accediendo al detalle por Centro de Costo ID: ' . $id);

        // ============================================================
        // 1. OBTENER LA REMUNERACIÓN
        // ============================================================
        $remuneracion = RemuneracionCabecera::findOrFail($id);
        
        Log::info('📋 Remuneración: ' . $remuneracion->mes_pago . ' ' . $remuneracion->anio_pago);

        // ============================================================
        // 2. OBTENER TRABAJADORES
        // ============================================================
        $trabajadores = RemuneracionTrabajador::where('cabecera_id', $id)->get();
        
        Log::info('📋 Total trabajadores: ' . $trabajadores->count());

        // ============================================================
        // 3. AGRUPAR POR CENTRO DE COSTO
        // ============================================================
        $centrosCosto = $trabajadores->groupBy('centro_costo')->map(function($grupo) {
            return [
                'cantidad' => $grupo->count(),
                'total_haberes' => $grupo->sum('total_haberes'),
                'total_descuentos' => $grupo->sum('total_descuentos'),
                'total_neto' => $grupo->sum('total_neto')
            ];
        });
        
        Log::info('📋 Centros de costo encontrados: ' . $centrosCosto->count());

        // ============================================================
        // 4. CALCULAR TOTALES
        // ============================================================
        $totales = [
            'trabajadores' => $trabajadores->count(),
            'haberes' => $trabajadores->sum('total_haberes'),
            'descuentos' => $trabajadores->sum('total_descuentos'),
            'neto' => $trabajadores->sum('total_neto')
        ];

        // ============================================================
        // 5. OBTENER LEYES SOCIALES POR CENTRO DE COSTO
        // ============================================================
        $centrosCostoConLeyes = [];
        foreach ($centrosCosto as $centro => $datos) {
            // Obtener trabajadores de este centro
            $ids = $trabajadores->where('centro_costo', $centro)->pluck('id');
            
            // Sumar leyes sociales desde los detalles
            $leyesSociales = RemuneracionDetalle::whereIn('trabajador_id', $ids)
                ->where('tipo', 'descuentos')
                ->get()
                ->sum(function($detalle) {
                    return $detalle->datos['Total Desc. Leyes Sociales'] ?? 0;
                });
            
            $centrosCostoConLeyes[$centro] = [
                'cantidad' => $datos['cantidad'],
                'total_haberes' => $datos['total_haberes'],
                'total_descuentos' => $datos['total_descuentos'],
                'total_neto' => $datos['total_neto'],
                'leyes_sociales' => $leyesSociales
            ];
        }
        
        Log::info('📊 Leyes Sociales calculadas para ' . count($centrosCostoConLeyes) . ' centros');

        // ============================================================
        // 6. ORDENAR POR TOTAL DE HABERES (DESCENDENTE)
        // ============================================================
        $centrosCostoOrdenados = collect($centrosCostoConLeyes)
            ->sortByDesc('total_haberes');
        
        // ============================================================
        // 7. RETORNAR VISTA
        // ============================================================
        Log::info('✅ ===== FINALIZANDO DETALLE POR CENTRO DE COSTO =====');
        
        return view('remuneraciones.detalle-centro-costo', compact(
            'remuneracion',
            'centrosCostoOrdenados',
            'totales'
        ));
    }

    /**
     * ============================================================
     * MÉTODO: ELIMINAR
     * ============================================================
     * Elimina una remuneración y todos sus datos relacionados
     * 
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function eliminar($id)
    {
        // ============================================================
        // 🔥 LOG DE ELIMINACIÓN
        // ============================================================
        Log::info('🗑️ Eliminando remuneración ID: ' . $id);

        $remuneracion = RemuneracionCabecera::findOrFail($id);
        $mes = $remuneracion->mes_pago;
        $anio = $remuneracion->anio_pago;
        
        $trabajadoresCount = $remuneracion->trabajadores()->count();
        Log::info('🗑️ Remuneración: ' . $mes . ' ' . $anio);
        Log::info('🗑️ Trabajadores a eliminar: ' . $trabajadoresCount);
        
        // Eliminar en cascada
        $remuneracion->delete();
        
        Log::info('✅ Remuneración eliminada correctamente');

        return redirect()->route('remuneraciones.index')
            ->with('success', "✅ Remuneraciones de {$mes} {$anio} eliminadas correctamente");
    }

    /**
     * ============================================================
     * MÉTODO: API GET BY MES/AÑO
     * ============================================================
     * API para obtener remuneraciones por mes y año
     * 
     * @param string $mes
     * @param string $anio
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiGetByMesAnio($mes, $anio)
    {
        // ============================================================
        // 🔥 LOG DE CONSULTA API
        // ============================================================
        Log::info('📡 API - Consultando remuneraciones para ' . $mes . ' ' . $anio);

        // ============================================================
        // BUSCAR REMUNERACIÓN
        // ============================================================
        $remuneracion = RemuneracionCabecera::with(['trabajadores.detalles'])
            ->where('mes_pago', $mes)
            ->where('anio_pago', $anio)
            ->first();

        if (!$remuneracion) {
            Log::info('📡 API - No se encontraron datos para ' . $mes . ' ' . $anio);
            
            return response()->json([
                'success' => false,
                'message' => "No hay remuneraciones para {$mes} {$anio}"
            ], 404);
        }

        Log::info('📡 API - Remuneración encontrada, ID: ' . $remuneracion->id);

        // ============================================================
        // CALCULAR TOTALES
        // ============================================================
        $totalTrabajadores = $remuneracion->trabajadores->count();
        $totalHaberes = $remuneracion->trabajadores->sum('total_haberes');
        
        $totalLeyesSociales = 0;
        foreach ($remuneracion->trabajadores as $trabajador) {
            if ($trabajador->detalles) {
                $detalle = $trabajador->detalles->firstWhere('tipo', 'descuentos');
                if ($detalle && isset($detalle->datos['Total Desc. Leyes Sociales'])) {
                    $totalLeyesSociales += $detalle->datos['Total Desc. Leyes Sociales'];
                }
            }
        }

        Log::info('📡 API - Datos calculados:');
        Log::info('   Total Haberes: ' . number_format($totalHaberes));
        Log::info('   Total Leyes Sociales: ' . number_format($totalLeyesSociales));

        // ============================================================
        // RETORNAR RESPUESTA
        // ============================================================
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $remuneracion->id,
                'mes_pago' => $remuneracion->mes_pago,
                'anio_pago' => $remuneracion->anio_pago,
                'total_trabajadores' => $totalTrabajadores,
                'total_haberes' => $totalHaberes,
                'total_leyes_sociales' => $totalLeyesSociales,
                'total_neto' => $totalHaberes + $totalLeyesSociales,
                'centros_costo' => $remuneracion->trabajadores
                    ->groupBy('centro_costo')
                    ->map(function($grupo) {
                        return [
                            'cantidad' => $grupo->count(),
                            'total_haberes' => $grupo->sum('total_haberes')
                        ];
                    })
            ]
        ]);
    }
}