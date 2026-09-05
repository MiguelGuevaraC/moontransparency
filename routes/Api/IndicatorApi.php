<?php
use App\Http\Controllers\IndicatorController;
use Illuminate\Support\Facades\Route;
Route::get('indicator', [IndicatorController::class, 'index'])->middleware('permission:content.view');
Route::get('indicator/{id}', [IndicatorController::class, 'show'])->middleware('permission:content.view');
Route::post('indicator', [IndicatorController::class, 'store'])->middleware('permission:content.manage');
Route::put('indicator/{id}', [IndicatorController::class, 'update'])->middleware('permission:content.manage');
Route::delete('indicator/{id}', [IndicatorController::class, 'destroy'])->middleware('permission:content.manage');
