<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('survey', [UserController::class, 'index']);
    Route::post('survey', [UserController::class, 'store']);
    Route::get('survey/{id}', [UserController::class, 'show']);
    Route::put('survey/{id}', [UserController::class, 'update']);
    Route::delete('survey/{id}', [UserController::class, 'destroy']);

});
