<?php
use App\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Route;
Route::get('activity', [ActivityController::class, 'index'])->middleware('permission:content.view');
Route::get('activity/{id}', [ActivityController::class, 'show'])->middleware('permission:content.view');
Route::post('activity', [ActivityController::class, 'store'])->middleware('permission:content.manage');
Route::put('activity/{id}', [ActivityController::class, 'update'])->middleware('permission:content.manage');
Route::delete('activity/{id}', [ActivityController::class, 'destroy'])->middleware('permission:content.manage');
