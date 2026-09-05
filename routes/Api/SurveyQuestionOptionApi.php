<?php

use App\Http\Controllers\SurveyQuestionOptionController;
use Illuminate\Support\Facades\Route;

Route::get('surveyquestionoption', [SurveyQuestionOptionController::class, 'index'])->middleware('permission:surveys.view');
Route::get('surveyquestionoption/{id}', [SurveyQuestionOptionController::class, 'show'])->middleware('permission:surveys.view');
Route::post('surveyquestionoption', [SurveyQuestionOptionController::class, 'store'])->middleware('permission:surveys.manage');
Route::put('surveyquestionoption/{id}', [SurveyQuestionOptionController::class, 'update'])->middleware('permission:surveys.manage');
Route::delete('surveyquestionoption/{id}', [SurveyQuestionOptionController::class, 'destroy'])->middleware('permission:surveys.manage');
