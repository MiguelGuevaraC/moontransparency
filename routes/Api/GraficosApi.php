<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\GraficosApiController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('grafico-01', [GraficosApiController::class, 'avanceProyecto']);
    Route::get('grafico-03', [GraficosApiController::class, 'donacionesPorTipo']);
    Route::get('grafico-02', [GraficosApiController::class, 'estadoFinanciero']);
    Route::get('dashboard', [GraficosApiController::class, 'dashboard_resumen']);
    Route::get('dashboard-by-project/{id}', [GraficosApiController::class, 'dashboard_by_project']);
});
