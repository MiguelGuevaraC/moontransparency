<?php
use App\Http\Controllers\OdsController;
use Illuminate\Support\Facades\Route;
Route::get('ods', [OdsController::class, 'index'])->middleware('permission:content.view');
