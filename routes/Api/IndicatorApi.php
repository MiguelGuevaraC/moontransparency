<?php

use App\Http\Controllers\IndicatorController;
use Illuminate\Support\Facades\Route;

Route::get('indicator', [IndicatorController::class, 'index'])->middleware('permission:indicators.view');
Route::get('indicator/{id}', [IndicatorController::class, 'show'])->middleware('permission:indicators.view');
Route::post('indicator', [IndicatorController::class, 'store'])->middleware('permission:indicators.manage');
Route::put('indicator/{id}', [IndicatorController::class, 'update'])->middleware('permission:indicators.manage');
Route::delete('indicator/{id}', [IndicatorController::class, 'destroy'])->middleware('permission:indicators.manage');
