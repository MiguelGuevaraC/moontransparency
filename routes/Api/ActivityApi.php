<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('activity', [UserController::class, 'index']);
    Route::post('activity', [UserController::class, 'store']);
    Route::get('activity/{id}', [UserController::class, 'show']);
    Route::put('activity/{id}', [UserController::class, 'update']);
    Route::delete('activity/{id}', [UserController::class, 'destroy']);

});
