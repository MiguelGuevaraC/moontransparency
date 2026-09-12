<?php

use App\Http\Controllers\DonationController;
use Illuminate\Support\Facades\Route;

Route::get('donation', [DonationController::class, 'index'])->middleware('permission:donations.view');
Route::get('donation/{id}', [DonationController::class, 'show'])->middleware('permission:donations.view');
Route::post('donation', [DonationController::class, 'store'])->middleware('permission:donations.manage');
Route::post('donation/{id}', [DonationController::class, 'update'])->middleware('permission:donations.manage');
Route::delete('donation/{id}', [DonationController::class, 'destroy'])->middleware('permission:donations.manage');
