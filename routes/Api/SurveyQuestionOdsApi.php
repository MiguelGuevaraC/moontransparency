<?php

use App\Http\Controllers\Organization\SurveyQuestionOdsController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('surveyquestionods', [SurveyQuestionOdsController::class, 'list']);
    Route::get('surveyquestionods/{id}', [SurveyQuestionOdsController::class, 'show']);
    Route::post('surveyquestionods', [SurveyQuestionOdsController::class, 'store']);
    Route::put('surveyquestionods/{id}', [SurveyQuestionOdsController::class, 'update']);
    Route::delete('surveyquestionods/{id}', [SurveyQuestionOdsController::class, 'destroy']);
    Route::get('surveys/{surveyId}/charts-by-ods', [SurveyQuestionOdsController::class, 'chartsByOds']);

});