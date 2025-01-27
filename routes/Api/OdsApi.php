<?php

use App\Http\Controllers\OdsController;

use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('ods', [OdsController::class, 'index']);


});
