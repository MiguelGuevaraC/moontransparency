<?php

use App\Http\Controllers\AllyController;
use App\Http\Controllers\OdsController;

use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('ally', [AllyController::class, 'index']);
    Route::post('ally', [AllyController::class, 'store']);
    Route::get('ally/{id}', [AllyController::class, 'show']);
    Route::post('ally/{id}', [AllyController::class, 'update']);
    Route::delete('ally/{id}', [AllyController::class, 'destroy']);

});
