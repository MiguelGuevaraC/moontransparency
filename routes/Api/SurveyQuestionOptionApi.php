<?php

use App\Http\Controllers\SurveyQuestionOptionController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('surveyquestionoption', [SurveyQuestionOptionController::class, 'index']);
    Route::post('surveyquestionoption', [SurveyQuestionOptionController::class, 'store']);
    Route::get('surveyquestionoption/{id}', [SurveyQuestionOptionController::class, 'show']);
    Route::put('surveyquestionoption/{id}', [SurveyQuestionOptionController::class, 'update']);
    Route::delete('surveyquestionoption/{id}', [SurveyQuestionOptionController::class, 'destroy']);

});
