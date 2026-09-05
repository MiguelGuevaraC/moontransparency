<?php

use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::get('permission', [PermissionController::class, 'index'])->middleware('permission:roles.view');
Route::get('permission/{id}', [PermissionController::class, 'show'])->middleware('permission:roles.view');
