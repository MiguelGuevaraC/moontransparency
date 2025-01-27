<?php

use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('login', [AuthenticationController::class, 'login']);
Route::group(["middleware" => ["auth:sanctum"]], function () {

    require __DIR__ . '/Api/AuthApi.php';        //AUTHENTICATE
    require __DIR__ . '/Api/UserApi.php';        //USER
    require __DIR__ . '/Api/RolApi.php';         //ROL
    require __DIR__ . '/Api/PermissionApi.php';  //PERMISSIONS
    require __DIR__ . '/Api/ProyectApi.php';  //PROYECTS
    require __DIR__ . '/Api/OdsApi.php';  //ODS
    require __DIR__ . '/Api/AllyApi.php';  //ALLIES
});