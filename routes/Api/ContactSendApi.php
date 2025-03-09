<?php

use App\Http\Controllers\ContactSenderController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::get('contactsend', [ContactSenderController::class, 'index']);
    
    Route::get('contactsend/{id}', [ContactSenderController::class, 'show']);
    Route::put('contactsend/{id}', [ContactSenderController::class, 'update']);
    Route::delete('contactsend/{id}', [ContactSenderController::class, 'destroy']);

});
