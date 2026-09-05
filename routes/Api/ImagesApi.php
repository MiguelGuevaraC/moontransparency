<?php
use App\Http\Controllers\ImagenesController;
use Illuminate\Support\Facades\Route;
Route::get('images-list', [ImagenesController::class, 'list'])->middleware('permission:content.view');
Route::get('images/{id}', [ImagenesController::class, 'show'])->middleware('permission:content.view');
Route::post('images', [ImagenesController::class, 'store'])->middleware('permission:content.manage');
Route::post('images/{id}', [ImagenesController::class, 'update'])->middleware('permission:content.manage');
Route::delete('images/{id}', [ImagenesController::class, 'destroy'])->middleware('permission:content.manage');
