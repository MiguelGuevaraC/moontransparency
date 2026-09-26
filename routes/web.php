<?php

use App\Http\Controllers\CalculatorViewController;
use App\Http\Controllers\GeobosquesMapController;
use App\Http\Controllers\SurveyPreviewViewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/calculadora', [CalculatorViewController::class, 'index'])->name('calculator');
Route::get('/calculadora/embed', [CalculatorViewController::class, 'embed'])
    ->middleware('signed:relative')
    ->name('calculator.embed');
Route::get('/encuestas/{survey}/vista-previa', [SurveyPreviewViewController::class, 'show'])
    ->middleware('signed:relative')
    ->name('surveys.preview.embed');
Route::get('/mapa', [GeobosquesMapController::class, 'show'])
    ->name('geobosques.map');
