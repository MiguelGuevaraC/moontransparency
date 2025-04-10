<?php

use App\Http\Controllers\SurveyQuestionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('surveyquestion', [SurveyQuestionController::class, 'index']);
    Route::post('surveyquestion', [SurveyQuestionController::class, 'store']);
    Route::get('surveyquestion/{id}', [SurveyQuestionController::class, 'show']);
    Route::put('surveyquestion/{id}', [SurveyQuestionController::class, 'update']);
    Route::delete('surveyquestion/{id}', [SurveyQuestionController::class, 'destroy']);

});

