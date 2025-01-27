<?php

use App\Http\Controllers\ProyectController;

use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('proyect', [ProyectController::class, 'index']);
    Route::post('proyect', [ProyectController::class, 'store']);
    Route::get('proyect/{id}', [ProyectController::class, 'show']);
    Route::put('proyect/{id}', [ProyectController::class, 'update']);
    Route::delete('proyect/{id}', [ProyectController::class, 'destroy']);

});
