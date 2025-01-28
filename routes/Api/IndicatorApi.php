<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('indicator', [UserController::class, 'index']);
    Route::post('indicator', [UserController::class, 'store']);
    Route::get('indicator/{id}', [UserController::class, 'show']);
    Route::put('indicator/{id}', [UserController::class, 'update']);
    Route::delete('indicator/{id}', [UserController::class, 'destroy']);

});
