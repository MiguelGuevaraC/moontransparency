<?php

use App\Http\Controllers\RolController;

use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('rol', [RolController::class, 'index']);
    Route::post('rol', [RolController::class, 'store']);
    Route::get('rol/{id}', [RolController::class, 'show']);
    Route::put('rol/{id}', [RolController::class, 'update']);
    Route::put('rol/{id}/setaccess', [RolController::class, 'setAccess']);
    Route::delete('rol/{id}', [RolController::class, 'destroy']);

});
