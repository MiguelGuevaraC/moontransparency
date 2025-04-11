<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AllyController;
use App\Http\Controllers\ContactSenderController;
use App\Http\Controllers\ProyectController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyedController;
use Illuminate\Support\Facades\Route;

Route::post('contact-send-web', [ContactSenderController::class, 'store']);
Route::get('proyects-web', [ProyectController::class, 'list_web']);
Route::get('ally-web', [AllyController::class, 'list_web']);
Route::get('activity-web', [ActivityController::class, 'list_web']);

Route::get('survey-show/{id}', [SurveyController::class, 'show_web']);
Route::post('response-survey', [SurveyedController::class, 'store']);