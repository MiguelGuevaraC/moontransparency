<?php

use App\Http\Controllers\RolController;
use Illuminate\Support\Facades\Route;

Route::get('rol', [RolController::class, 'index'])->middleware('permission:roles.view');
Route::get('rol/{id}', [RolController::class, 'show'])->middleware('permission:roles.view');
Route::post('rol', [RolController::class, 'store'])->middleware('permission:roles.create');
Route::put('rol/{id}', [RolController::class, 'update'])->middleware('permission:roles.update');
Route::patch('rol/{id}/activate', [RolController::class, 'activate'])->middleware('permission:roles.update');
Route::patch('rol/{id}/deactivate', [RolController::class, 'deactivate'])->middleware('permission:roles.deactivate');
Route::delete('rol/{id}', [RolController::class, 'destroy'])->middleware('permission:roles.delete');
Route::post('rol/{id}/permissions', [RolController::class, 'assignPermissions'])
    ->middleware('permission:roles.assign_permissions');
Route::delete('rol/{id}/permissions/{permissionId}', [RolController::class, 'revokePermission'])
    ->middleware('permission:roles.revoke_permissions');
Route::put('rol/{id}/setaccess', [RolController::class, 'setAccess'])
    ->middleware(['permission:roles.assign_permissions', 'permission:roles.revoke_permissions']);
