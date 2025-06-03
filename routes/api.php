<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->middleware('jwt.auth');
    Route::post('/', [UserController::class, 'store'])->middleware('jwt.auth');
    Route::get('/{id}', [UserController::class, 'show'])->middleware('jwt.auth');
    Route::put('/{id}', [UserController::class, 'update'])->middleware('jwt.auth');
    Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('jwt.auth');
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout')->middleware('jwt.auth');
    Route::post('refresh', 'refresh')->middleware('jwt.refresh');
    Route::get('user-profile', 'userProfile')->middleware('jwt.auth');
});

// Rotas protegidas
Route::middleware('auth:api')->group(function () {
    // Adicione aqui suas rotas protegidas
});
