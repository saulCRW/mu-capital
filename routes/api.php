<?php

use App\Modules\Portfolios\Controllers\Api\PortfolioApiController;
use App\Modules\Prices\Controllers\MarketController;
use App\Modules\Reports\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
| API interna v1 (PROYECTO.md sección 2): /api/v1/...
| Respuesta siempre { "ok": bool, "data": ..., "error": { "code", "message" } } (App\Http\ApiResponse).
| Usa la sesión del navegador (grupo "web"), así que las pantallas la llaman sin token.
*/
Route::prefix('v1')->name('api.v1.')->middleware(['web', 'auth', 'cuenta', 'throttle:120,1'])->group(function () {
    // Cotizaciones del momento (Mercado y saldo en vivo) y valores del Inicio en vivo.
    Route::get('mercado/cotizaciones', [MarketController::class, 'cotizaciones'])->name('mercado.cotizaciones');
    Route::get('dashboard/vivo', [DashboardController::class, 'vivo'])->name('dashboard.vivo');

    // Portafolios del usuario (solo lectura).
    Route::middleware('role:usuario')->group(function () {
        Route::get('portfolios', [PortfolioApiController::class, 'index'])->name('portfolios.index');
        Route::get('portfolios/{portfolio}', [PortfolioApiController::class, 'show'])->name('portfolios.show');
        Route::get('portfolios/{portfolio}/holdings', [PortfolioApiController::class, 'holdings'])->name('portfolios.holdings');
        Route::get('portfolios/{portfolio}/transactions', [PortfolioApiController::class, 'transactions'])->name('portfolios.transactions');
    });
});
