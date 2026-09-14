<?php

use App\Http\Controllers\GeobosquesMapController;
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
Route::view('/calculadora', 'calculadora')->name('calculator');
Route::get('/mapa', [GeobosquesMapController::class, 'show'])
    ->name('geobosques.map');
