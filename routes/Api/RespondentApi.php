<?php

use App\Http\Controllers\RespondentController;
use Illuminate\Support\Facades\Route;

Route::get('respondent', [RespondentController::class, 'index'])->middleware('permission:respondents.view');
Route::get('respondent/{id}', [RespondentController::class, 'show'])->middleware('permission:respondents.view');
Route::post('respondent', [RespondentController::class, 'store'])->middleware('permission:respondents.manage');
Route::put('respondent/{id}', [RespondentController::class, 'update'])->middleware('permission:respondents.manage');
Route::delete('respondent/{id}', [RespondentController::class, 'destroy'])->middleware('permission:respondents.manage');
