<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('activity', [ActivityController::class, 'index']);
    Route::post('activity', [ActivityController::class, 'store']);
    Route::get('activity/{id}', [ActivityController::class, 'show']);
    Route::put('activity/{id}', [ActivityController::class, 'update']);
    Route::delete('activity/{id}', [ActivityController::class, 'destroy']);

});
