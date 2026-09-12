<?php

use App\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Route;

Route::get('activity', [ActivityController::class, 'index'])->middleware('permission:activities.view');
Route::get('activity/{id}', [ActivityController::class, 'show'])->middleware('permission:activities.view');
Route::post('activity', [ActivityController::class, 'store'])->middleware('permission:activities.manage');
Route::put('activity/{id}', [ActivityController::class, 'update'])->middleware('permission:activities.manage');
Route::delete('activity/{id}', [ActivityController::class, 'destroy'])->middleware('permission:activities.manage');
