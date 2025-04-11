<?php

use App\Http\Controllers\RespondentController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    
    Route::get('respondent', [RespondentController::class, 'index']);
    Route::post('respondent', [RespondentController::class, 'store']);
    Route::get('respondent/{id}', [RespondentController::class, 'show']);
    Route::put('respondent/{id}', [RespondentController::class, 'update']);
    Route::delete('respondent/{id}', [RespondentController::class, 'destroy']);

});
