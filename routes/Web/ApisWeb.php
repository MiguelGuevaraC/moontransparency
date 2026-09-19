<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AllyController;
use App\Http\Controllers\Co2CalculatorController;
use App\Http\Controllers\ContactSenderController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\ProyectController;
use App\Http\Controllers\PublicPlatformController;
use App\Http\Controllers\RespondentController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyedController;
use Illuminate\Support\Facades\Route;

Route::post('contact-send-web', [ContactSenderController::class, 'store']);
Route::get('proyects-web', [ProyectController::class, 'list_web']);
Route::get('ally-web', [AllyController::class, 'list_web']);
Route::get('activity-web', [ActivityController::class, 'list_web']);

Route::get('platform/public', [PublicPlatformController::class, 'show'])
    ->middleware('public.uuid');

Route::get('calculator/co2/surveys', [Co2CalculatorController::class, 'publicSurveys'])
    ->middleware('public.uuid');
Route::post('calculator/co2/embed-link', [Co2CalculatorController::class, 'embedLink'])
    ->middleware('public.uuid');

Route::get('survey-show/{id}', [SurveyController::class, 'show_web']);
Route::post('response-survey', [SurveyedController::class, 'store']);
Route::post('response-survey/{id}/finalize', [SurveyedController::class, 'finalize']);
Route::post('response-survey/{id}', [SurveyedController::class, 'update']);
Route::post('offline-sync', [OfflineSyncController::class, 'store']);

Route::get('respondent-search', [RespondentController::class, 'index_search']);
