<?php

use App\Http\Controllers\ProyectController;
use Illuminate\Support\Facades\Route;

Route::get('proyect', [ProyectController::class, 'index'])->middleware('permission:projects.view');
Route::get('proyect/{id}', [ProyectController::class, 'show'])->middleware('permission:projects.view');
Route::post('proyect', [ProyectController::class, 'store'])->middleware('permission:projects.manage');
Route::post('proyect/{id}', [ProyectController::class, 'update'])->middleware('permission:projects.manage');
Route::delete('proyect/{id}', [ProyectController::class, 'destroy'])->middleware('permission:projects.manage');
