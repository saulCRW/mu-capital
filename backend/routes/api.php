<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PortafolioController;
use App\Http\Controllers\Api\ActivoController;
use App\Http\Controllers\Api\TransaccionController;
use App\Http\Controllers\Api\AlertaController;
use App\Http\Controllers\Api\UsuarioController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::apiResource('portafolios',   PortafolioController::class);
    Route::apiResource('activos',       ActivoController::class);
    Route::apiResource('transacciones', TransaccionController::class);
    Route::apiResource('alertas',       AlertaController::class);

    // Panel de administración: lectura global de usuarios y sus carteras
    Route::middleware('admin')->group(function () {
        Route::get('/usuarios',      [UsuarioController::class, 'index']);
        Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
    });
});
