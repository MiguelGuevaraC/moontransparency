<?php

use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyedController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('survey', [SurveyController::class, 'index']);
    Route::post('survey', [SurveyController::class, 'store']);
    
    Route::get('surveyed', [SurveyedController::class, 'index']);
    Route::get('survey/{id}', [SurveyController::class, 'show']);
    Route::put('survey/{id}', [SurveyController::class, 'update']);
    Route::delete('survey/{id}', [SurveyController::class, 'destroy']);

});
