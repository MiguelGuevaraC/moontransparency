<?php

use App\Http\Controllers\DonationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('donation', [DonationController::class, 'index']);
    Route::post('donation', [DonationController::class, 'store']);
    Route::get('donation/{id}', [DonationController::class, 'show']);
    Route::post('donation/{id}', [DonationController::class, 'update']);
    Route::delete('donation/{id}', [DonationController::class, 'destroy']);

});
