<?php

use App\Http\Controllers\SurveyCleanupController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyedController;
use Illuminate\Support\Facades\Route;

Route::get('survey', [SurveyController::class, 'index'])->middleware('permission:surveys.view');
Route::get('survey/{id}', [SurveyController::class, 'show'])->middleware('permission:surveys.view');
Route::post('survey', [SurveyController::class, 'store'])->middleware('permission:surveys.manage');
Route::put('survey/{id}', [SurveyController::class, 'update'])->middleware('permission:surveys.manage');
Route::delete('survey/{id}', [SurveyController::class, 'destroy'])->middleware('permission:surveys.manage');
Route::post('survey/{id}/clean-participations', [SurveyCleanupController::class, 'store'])
    ->middleware('permission:surveys.clean_participations');

Route::get('surveyed', [SurveyedController::class, 'index'])->middleware('permission:participations.view');
Route::get('surveyed/{id}/calculator', [SurveyedController::class, 'calculator'])->middleware('permission:participations.view');
Route::post('surveyed/{id}/reopen', [SurveyedController::class, 'reopen'])->middleware('permission:participations.reopen');
Route::get('surveyed/{id}', [SurveyedController::class, 'show'])->middleware('permission:participations.view');
Route::get('surveyedAll', [SurveyedController::class, 'indexAll'])->middleware('permission:participations.view');
Route::get('surveyedExcel', [SurveyedController::class, 'indexAllExcel'])->middleware('permission:participations.export');
Route::post('surveyed/import-excel', [SurveyedController::class, 'importExcel'])->middleware('permission:participations.import');
