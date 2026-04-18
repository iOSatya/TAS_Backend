<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::put('/profile', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');
Route::delete('/profile', [AuthController::class, 'destroy'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::put('/users/{id}/admin-status', [UserController::class, 'assignAdmin']);
});
