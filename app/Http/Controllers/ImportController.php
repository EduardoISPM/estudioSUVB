<?php

namespace App\Http\Controllers;

use App\Services\ImportService;
use App\Models\CursoPersonalizado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * ============================================================
     * MOSTRAR FORMULARIO DE IMPORTACIÓN
     * ============================================================
     */
    public function index()
    {
        return view('importar');
    }

    /**
     * ============================================================
     * PROCESAR IMPORTACIÓN DE ARCHIVO
     * ============================================================
     * 1. Valida el archivo
     * 2. Llama al servicio para procesar
     * 3. Maneja diferentes respuestas:
     *    - Éxito: muestra resultado
     *    - Duplicado: pregunta si reemplazar
     *    - Cursos nuevos: muestra formulario de clasificación
     *    - Error: muestra mensaje de error
     */
    public function importar(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|max:10240'
            ]);

            $archivo = $request->file('archivo');
            $resultado = $this->importService->procesarArchivo($archivo);
            
            // 🔥 Verificar si hay cursos nuevos
            if (isset($resultado['cursos_nuevos']) && $resultado['cursos_nuevos'] === true) {
                return response()->json([
                    'success' => false,
                    'cursos_nuevos' => true,
                    'mensaje' => $resultado['mensaje'],
                    'cursos' => $resultado['cursos']
                ]);
            }
            
            // Verificar si es un duplicado
            if (isset($resultado['duplicado']) && $resultado['duplicado'] === true) {
                return response()->json([
                    'success' => false,
                    'duplicado' => true,
                    'mensaje' => $resultado['mensaje'],
                    'archivo_existente' => $resultado['archivo_existente']
                ]);
            }
            
            return response()->json([
                'success' => true,
                'mensaje' => '✅ Archivo importado correctamente',
                'data' => $resultado
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en importación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================================
     * REEMPLAZAR ARCHIVO DUPLICADO
     * ============================================================
     * Cuando el usuario confirma que quiere reemplazar un archivo
     * que ya existe para el mismo mes/año.
     */
    public function reemplazar(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|max:10240',
                'mes' => 'required|string',
                'anio' => 'required|string'
            ]);

            $archivo = $request->file('archivo');
            $mes = $request->input('mes');
            $anio = $request->input('anio');
            
            $resultado = $this->importService->reemplazarArchivo($archivo, $mes, $anio);
            
            return response()->json([
                'success' => true,
                'mensaje' => $resultado['mensaje'],
                'data' => $resultado
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al reemplazar archivo: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================================
     * CLASIFICAR CURSO NUEVO
     * ============================================================
     * 🔥 NUEVO MÉTODO
     * Recibe la clasificación de un curso nuevo y lo guarda en
     * la tabla cursos_personalizados para futuras importaciones.
     */
    public function clasificarCurso(Request $request)
    {
        try {
            $request->validate([
                'curso_id' => 'required|string',
                'sede' => 'required|string',
                'cod_ens' => 'required|string',
                'grado' => 'required|string',
                'letra' => 'required|string',
                'ens' => 'required|string'
            ]);

            // Verificar si ya existe (por si acaso)
            $existente = CursoPersonalizado::where('curso_id', $request->curso_id)->first();
            if ($existente) {
                return response()->json([
                    'success' => false,
                    'error' => 'Este curso ya está clasificado'
                ], 422);
            }

            $curso = CursoPersonalizado::create([
                'curso_id' => $request->curso_id,
                'cod_ens' => $request->cod_ens,
                'grado' => $request->grado,
                'letra' => $request->letra,
                'ens' => $request->ens,
                'sede' => $request->sede,
                'nombre_curso' => $request->nombre_curso ?? null
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => "✅ Curso {$curso->nombre_curso} clasificado en {$curso->sede}",
                'curso' => $curso
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al clasificar curso: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}