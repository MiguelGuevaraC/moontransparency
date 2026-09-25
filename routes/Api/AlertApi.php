<?php

use App\Http\Controllers\AlertController;
use Illuminate\Support\Facades\Route;

Route::get('alerts', [AlertController::class, 'index']);
Route::get('alerts/unread-count', [AlertController::class, 'unreadCount']);
Route::patch('alerts/read-all', [AlertController::class, 'markAllAsRead']);
Route::patch('alerts/{id}/read', [AlertController::class, 'markAsRead']);
