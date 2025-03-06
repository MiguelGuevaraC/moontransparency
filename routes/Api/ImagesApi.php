<?php

use App\Http\Controllers\ImagenesController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {

    Route::get('images-list', [ImagenesController::class, 'list']);
    Route::post('images', [ImagenesController::class, 'store']);
    Route::get('images/{id}', [ImagenesController::class, 'show']);
    Route::post('images/{id}', [ImagenesController::class, 'update']);
    Route::delete('images/{id}', [ImagenesController::class, 'destroy']);
});
