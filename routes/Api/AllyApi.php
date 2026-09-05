<?php
use App\Http\Controllers\AllyController;
use Illuminate\Support\Facades\Route;
Route::get('ally', [AllyController::class, 'index'])->middleware('permission:content.view');
Route::get('ally/{id}', [AllyController::class, 'show'])->middleware('permission:content.view');
Route::post('ally', [AllyController::class, 'store'])->middleware('permission:content.manage');
Route::post('ally/{id}', [AllyController::class, 'update'])->middleware('permission:content.manage');
Route::delete('ally/{id}', [AllyController::class, 'destroy'])->middleware('permission:content.manage');
