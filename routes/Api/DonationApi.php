<?php
use App\Http\Controllers\DonationController;
use Illuminate\Support\Facades\Route;
Route::get('donation', [DonationController::class, 'index'])->middleware('permission:content.view');
Route::get('donation/{id}', [DonationController::class, 'show'])->middleware('permission:content.view');
Route::post('donation', [DonationController::class, 'store'])->middleware('permission:content.manage');
Route::post('donation/{id}', [DonationController::class, 'update'])->middleware('permission:content.manage');
Route::delete('donation/{id}', [DonationController::class, 'destroy'])->middleware('permission:content.manage');
