<?php
use App\Http\Controllers\ProyectController;
use Illuminate\Support\Facades\Route;
Route::get('proyect', [ProyectController::class, 'index'])->middleware('permission:content.view');
Route::get('proyect/{id}', [ProyectController::class, 'show'])->middleware('permission:content.view');
Route::post('proyect', [ProyectController::class, 'store'])->middleware('permission:content.manage');
Route::post('proyect/{id}', [ProyectController::class, 'update'])->middleware('permission:content.manage');
Route::delete('proyect/{id}', [ProyectController::class, 'destroy'])->middleware('permission:content.manage');
