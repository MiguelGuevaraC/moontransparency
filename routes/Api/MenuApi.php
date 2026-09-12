<?php

use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;

Route::get('menu', [MenuController::class, 'index'])->middleware('permission:roles.view');
