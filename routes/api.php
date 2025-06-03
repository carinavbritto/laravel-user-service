<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Rotas de autenticação com prefixo /auth e rate limiting
Route::prefix('auth')->group(function () {
    // Rotas públicas com limite mais restrito
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // 5 tentativas por minuto
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:3,1'); // 3 tentativas por minuto

    // Rotas protegidas com limite mais generoso
    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->middleware('throttle:10,1'); // 10 tentativas por minuto
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:10,1'); // 10 tentativas por minuto
        Route::get('user-profile', [AuthController::class, 'userProfile'])->middleware('throttle:30,1'); // 30 tentativas por minuto
    });
});

// Rotas de usuários com rate limiting
Route::prefix('users')->middleware(['jwt.auth', 'throttle:60,1'])->group(function () { // 60 tentativas por minuto
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
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
    Route::post('refresh', 'refresh')->middleware('jwt.auth');
    Route::get('user-profile', 'userProfile')->middleware('jwt.auth');
});

// Rotas protegidas
Route::middleware('auth:api')->group(function () {
    // Adicione aqui suas rotas protegidas
});
