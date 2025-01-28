<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('donation', [UserController::class, 'index']);
    Route::post('donation', [UserController::class, 'store']);
    Route::get('donation/{id}', [UserController::class, 'show']);
    Route::put('donation/{id}', [UserController::class, 'update']);
    Route::delete('donation/{id}', [UserController::class, 'destroy']);

});
