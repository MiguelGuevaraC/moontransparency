<?php

use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('indicator', [IndicatorController::class, 'index']);
    Route::post('indicator', [IndicatorController::class, 'store']);
    Route::get('indicator/{id}', [IndicatorController::class, 'show']);
    Route::put('indicator/{id}', [IndicatorController::class, 'update']);
    Route::delete('indicator/{id}', [IndicatorController::class, 'destroy']);

});
