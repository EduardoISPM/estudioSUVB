<?php

namespace App\Imports;

use App\Models\Registro;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;

class ColegiosImport implements ToModel, WithHeadingRow, WithChunkReading
{
    public $posiciones = [];
    public $totalRegistros = 0;
    public $archivoId = null;
    
    protected $columnasFijas = [
        'Cod. Ens.',
        'Grado',
        'JEC',
        'LETRA',
        'ENS',
        'NIVEL',
        'GLOSA SUBVENCIÓN'
    ];
    
    protected $primeraFila = true;

    public function __construct($archivoId = null)
    {
        $this->archivoId = $archivoId;
    }

    public function model(array $row)
    {
        $this->totalRegistros++;
        
        // Detectar posiciones en la primera fila
        if ($this->primeraFila) {
            $this->detectarPosiciones($row);
            $this->primeraFila = false;
        }

        // Validar columnas fijas
        foreach ($this->columnasFijas as $columna) {
            if (!isset($row[$columna])) {
                throw new \Exception("Error: No se encontró la columna '{$columna}' en el archivo");
            }
        }

        // Determinar tipo de subvención según ENS
        $ens = $row['ENS'] ?? null;
        $tipoSubvencion = $this->determinarTipoSubvencion($ens);
        
        // Calcular sede
        $sede = $this->calcularSede($row['Grado'] ?? null, $ens);
        
        // Crear ID del curso
        $cursoId = ($row['Cod. Ens.'] ?? '') . '-' . ($row['Grado'] ?? '') . '-' . ($row['LETRA'] ?? '');

        // Construir datos del registro
        $registroData = [
            'archivo_id' => $this->archivoId,
            'fila_numero' => $this->totalRegistros,
            'cod_ens' => $row['Cod. Ens.'] ?? null,
            'grado' => $row['Grado'] ?? null,
            'letra' => $row['LETRA'] ?? null,
            'ens' => $ens,
            'jec' => $row['JEC'] ?? null,
            'nivel' => $row['NIVEL'] ?? null,
            'glosa_subvencion' => $row['GLOSA SUBVENCIÓN'] ?? null,
            'promedio_asistencia' => $this->parseNumber($row['Promedio Asistencia'] ?? null),
            'factor_use' => $this->parseNumber($row['Factor USE'] ?? null),
            'subvencion_base' => $this->parseNumber($row['Subvención Base'] ?? 0),
            'curso_id' => $cursoId,
            'sede' => $sede,
            'tipo_subvencion' => $tipoSubvencion,
            'subvencion_ley_19933' => $this->parseNumber($row['Subvención Ley 19.933'] ?? 0),
            'subvencion_ley_19933_incremento' => $this->parseNumber($row['Subvención Ley 19.933 Incremento Zona'] ?? 0),
            'subvencion_ruralidad' => $this->parseNumber($row['Subvención Ruralidad Ley 19.933'] ?? 0),
            'total_ley_19933' => $this->parseNumber($row['Total Ley 19.933'] ?? 0),
            'datos_completos' => json_encode($row)
        ];

        // Guardar registro
        return new Registro($registroData);
    }

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

    protected function calcularSede($grado, $ens)
    {
        // Sede Jardín
        if (in_array($ens, [9, 10, 1009, 1010])) {
            return 'Sede Jardín';
        }
        
        // Sede 1 a 4 Básico
        if (in_array($ens, [110, 1110]) && in_array($grado, [1, 2, 3, 4])) {
            return 'Sede 1 a 4 Básico';
        }
        
        // Sede 5 a 6 Básico
        if (in_array($ens, [110, 1110]) && in_array($grado, [5, 6])) {
            return 'Sede 5 a 6 Básico';
        }
        
        // Sede 7 a 8 Básico
        if (in_array($ens, [110, 1110]) && in_array($grado, [7, 8])) {
            return 'Sede 7 a 8 Básico';
        }
        
        // Ed. Media
        if (in_array($ens, [310, 1310])) {
            return 'Ed. Media';
        }
        
        return 'Sin Sede';
    }

    protected function detectarPosiciones($row)
    {
        $columnasExcel = array_keys($row);
        
        Log::info('Columnas detectadas:', $columnasExcel);
        
        // Buscar Factor USE
        $posUSE = array_search('Factor USE', $columnasExcel);
        if ($posUSE !== false) {
            $this->posiciones['Factor USE'] = $posUSE + 1;
            Log::info("✅ Factor USE encontrado en posición: " . ($posUSE + 1));
        }
        
        // Buscar Subvención Base
        $posSub = array_search('Subvención Base', $columnasExcel);
        if ($posSub !== false) {
            $this->posiciones['Subvención Base'] = $posSub + 1;
            Log::info("✅ Subvención Base encontrado en posición: " . ($posSub + 1));
        }
        
        // Buscar Subvención Ley 19.933
        $posLey = array_search('Subvención Ley 19.933', $columnasExcel);
        if ($posLey !== false) {
            $this->posiciones['Subvención Ley 19.933'] = $posLey + 1;
            Log::info("✅ Subvención Ley 19.933 encontrado en posición: " . ($posLey + 1));
        }
    }

    protected function parseNumber($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }
        
        if (is_string($value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            $value = preg_replace('/[^0-9\.\-]/', '', $value);
        }
        
        return floatval($value);
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}