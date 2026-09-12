<?php

use App\Http\Controllers\AllyController;
use Illuminate\Support\Facades\Route;

Route::get('ally', [AllyController::class, 'index'])->middleware('permission:allies.view');
Route::get('ally/{id}', [AllyController::class, 'show'])->middleware('permission:allies.view');
Route::post('ally', [AllyController::class, 'store'])->middleware('permission:allies.manage');
Route::post('ally/{id}', [AllyController::class, 'update'])->middleware('permission:allies.manage');
Route::delete('ally/{id}', [AllyController::class, 'destroy'])->middleware('permission:allies.manage');
