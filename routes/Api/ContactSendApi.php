<?php
use App\Http\Controllers\ContactSenderController;
use Illuminate\Support\Facades\Route;
Route::get('contactsend', [ContactSenderController::class, 'index'])->middleware('permission:content.view');
Route::get('contactsend/{id}', [ContactSenderController::class, 'show'])->middleware('permission:content.view');
Route::put('contactsend/{id}', [ContactSenderController::class, 'update'])->middleware('permission:content.manage');
Route::delete('contactsend/{id}', [ContactSenderController::class, 'destroy'])->middleware('permission:content.manage');
