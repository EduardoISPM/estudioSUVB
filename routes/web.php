<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\RemuneracionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
| Aquí se definen todas las rutas web del sistema.
| 
| @author Estudiante de Programación
| @version 2.0
*/

// ============================================================
// DASHBOARD
// ============================================================
// Ruta principal del sistema - Muestra el dashboard con gráficos y estadísticas
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// ============================================================
// IMPORTACIÓN DE SUBVENCIONES
// ============================================================
// Gestión de archivos de subvenciones (módulo principal)
Route::get('/importar', [ImportController::class, 'index'])->name('importar.form');
Route::post('/importar', [ImportController::class, 'importar'])->name('importar.procesar');
Route::post('/importar/reemplazar', [ImportController::class, 'reemplazar'])->name('importar.reemplazar');
Route::post('/importar/clasificar', [ImportController::class, 'clasificarCurso'])->name('importar.clasificar');

// ============================================================
// DATOS
// ============================================================
// Listado y filtrado de registros de subvenciones
Route::get('/datos', [DashboardController::class, 'datos'])->name('datos');

// ============================================================
// 🔥 REMUNERACIONES
// ============================================================
// Gestión completa de remuneraciones:
// - Listado con filtros y paginación
// - Importación de archivos Excel
// - Reemplazo de datos existentes
// - Detalle por trabajador
// - 🔥 NUEVO: Detalle agrupado por Centro de Costo (Sede)
// - Eliminación de registros
// - API para consultas

// Listado principal
Route::get('/remuneraciones', [RemuneracionController::class, 'index'])
    ->name('remuneraciones.index');

// Importación
Route::post('/remuneraciones/importar', [RemuneracionController::class, 'importar'])
    ->name('remuneraciones.importar');

// Reemplazo (cuando el usuario confirma duplicado)
Route::post('/remuneraciones/reemplazar', [RemuneracionController::class, 'reemplazar'])
    ->name('remuneraciones.reemplazar');

// Detalle por trabajador
Route::get('/remuneraciones/{id}', [RemuneracionController::class, 'detalle'])
    ->name('remuneraciones.detalle');

// 🔥 NUEVO: Detalle agrupado por Centro de Costo (Sede)
Route::get('/remuneraciones/{id}/centro-costo', [RemuneracionController::class, 'detallePorCentroCosto'])
    ->name('remuneraciones.detalle.centro-costo');

// Eliminación
Route::delete('/remuneraciones/{id}', [RemuneracionController::class, 'eliminar'])
    ->name('remuneraciones.eliminar');

// API para el Dashboard (consulta por mes/año)
Route::get('/api/remuneraciones/{mes}/{anio}', [RemuneracionController::class, 'apiGetByMesAnio'])
    ->name('api.remuneraciones');