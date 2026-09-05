<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('user', [UserController::class, 'index'])->middleware('permission:users.view');
Route::get('user/{id}', [UserController::class, 'show'])->middleware('permission:users.view');
Route::post('user', [UserController::class, 'store'])->middleware('permission:users.create');
Route::put('user/{id}', [UserController::class, 'update'])->middleware('permission:users.update');
Route::patch('user/{id}/activate', [UserController::class, 'activate'])->middleware('permission:users.update');
Route::patch('user/{id}/deactivate', [UserController::class, 'deactivate'])->middleware('permission:users.update');
Route::delete('user/{id}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
