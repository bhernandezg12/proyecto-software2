<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::patch('users/{id}/role', [UserController::class, 'changeRole']);
    Route::patch('users/{id}/toggle-active', [UserController::class, 'toggleActive']);
});
