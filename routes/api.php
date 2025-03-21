<?php

use App\Http\Controllers\AuthenticationController;
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
    require __DIR__ . '/Api/ProyectApi.php';     //PROYECTS
    require __DIR__ . '/Api/OdsApi.php';         //ODS
    require __DIR__ . '/Api/AllyApi.php';        //ALLIES
    require __DIR__ . '/Api/ActivityApi.php';    //ACTIVITIES
    require __DIR__ . '/Api/DonationApi.php';    //DONATIONS
    require __DIR__ . '/Api/IndicatorApi.php';   //INDICATORS
    require __DIR__ . '/Api/SurveyApi.php';      //SURVEYS
    require __DIR__ . '/Api/ImagesApi.php';      //IMAGENES
    require __DIR__ . '/Api/ContactSendApi.php'; //CONTACTOS DE ENVIO
});

require __DIR__ . '/Web/ApisWeb.php'; //APIS PARA WEB
require __DIR__ . '/Web/GraficosApi.php'; //GRAFICOS
