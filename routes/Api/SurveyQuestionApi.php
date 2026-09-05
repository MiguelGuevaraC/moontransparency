<?php

use App\Http\Controllers\SurveyQuestionController;
use Illuminate\Support\Facades\Route;

Route::get('surveyquestion', [SurveyQuestionController::class, 'index'])->middleware('permission:surveys.view');
Route::get('surveyquestion/{id}', [SurveyQuestionController::class, 'show'])->middleware('permission:surveys.view');
Route::post('surveyquestion', [SurveyQuestionController::class, 'store'])->middleware('permission:surveys.manage');
Route::put('surveyquestion/{id}', [SurveyQuestionController::class, 'update'])->middleware('permission:surveys.manage');
Route::delete('surveyquestion/{id}', [SurveyQuestionController::class, 'destroy'])->middleware('permission:surveys.manage');
