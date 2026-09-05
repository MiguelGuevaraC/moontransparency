<?php

use App\Http\Controllers\Organization\SurveyQuestionOdsController;
use Illuminate\Support\Facades\Route;

Route::get('surveyquestionods', [SurveyQuestionOdsController::class, 'list'])->middleware('permission:surveys.view');
Route::get('surveyquestionods/{id}', [SurveyQuestionOdsController::class, 'show'])->middleware('permission:surveys.view');
Route::get('surveys/{surveyId}/charts-by-ods', [SurveyQuestionOdsController::class, 'chartsByOds'])->middleware('permission:surveys.view');
Route::post('surveyquestionods', [SurveyQuestionOdsController::class, 'store'])->middleware('permission:surveys.manage');
Route::put('surveyquestionods/{id}', [SurveyQuestionOdsController::class, 'update'])->middleware('permission:surveys.manage');
Route::delete('surveyquestionods/{id}', [SurveyQuestionOdsController::class, 'destroy'])->middleware('permission:surveys.manage');
