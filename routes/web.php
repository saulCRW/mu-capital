<?php

use App\Modules\Assets\Controllers\AssetController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Auth\Controllers\PasswordResetController;
use App\Modules\Auth\Controllers\RegisterController;
use App\Modules\Billing\Controllers\PlanController;
use App\Modules\Imports\Controllers\ImportController;
use App\Modules\Portfolios\Controllers\PortfolioController;
use App\Modules\Prices\Controllers\MarketController;
use App\Modules\Prices\Controllers\MarketSyncController;
use App\Modules\Prices\Controllers\PriceController;
use App\Modules\Rebalancing\Controllers\RebalanceController;
use App\Modules\Reports\Controllers\DashboardController;
use App\Modules\Reports\Controllers\ReportController;
use App\Modules\Transactions\Controllers\TransactionController;
use App\Modules\Users\Controllers\AccountController;
use App\Modules\Users\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// ── Invitados (sin sesión) ──────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('registro', [RegisterController::class, 'create'])->name('register');
    Route::post('registro', [RegisterController::class, 'store'])->middleware('throttle:6,1');

    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

// ── Usuarios con cuenta activa ──────────────────────────────────────────
Route::middleware(['auth', 'cuenta'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('dashboard/vivo', [DashboardController::class, 'vivo'])->middleware('throttle:120,1')->name('dashboard.vivo');

    Route::get('cuenta', [AccountController::class, 'edit'])->name('cuenta.edit');
    Route::put('cuenta', [AccountController::class, 'update'])->name('cuenta.update');

    // Cada usuario gestiona sus propios portafolios (el Global Scope y las policies lo limitan a lo suyo).
    // "shallow": las rutas de una transacción existente no repiten al portafolio.
    Route::middleware('role:usuario')->group(function () {
        Route::resource('portfolios', PortfolioController::class);
        Route::resource('portfolios.transactions', TransactionController::class)->shallow()->except(['index', 'show']);
        Route::get('portfolios/{portfolio}/operar', [TransactionController::class, 'operar'])->name('portfolios.operar');
        Route::get('portfolios/{portfolio}/rebalanceo', [RebalanceController::class, 'show'])->name('portfolios.rebalanceo');
        Route::put('portfolios/{portfolio}/rebalanceo', [RebalanceController::class, 'update'])->name('portfolios.rebalanceo.update');
        Route::get('portfolios/{portfolio}/importar', [ImportController::class, 'create'])->name('portfolios.importar');
        Route::post('portfolios/{portfolio}/importar', [ImportController::class, 'preview'])->name('portfolios.importar.preview');
        Route::post('portfolios/{portfolio}/importar/confirmar', [ImportController::class, 'confirm'])->name('portfolios.importar.confirmar');
        Route::get('portfolios/{portfolio}/importar/plantilla', [ImportController::class, 'plantilla'])->name('portfolios.importar.plantilla');
        Route::get('portfolios/{portfolio}/reporte', [ReportController::class, 'trimestral'])->name('portfolios.reporte');
        Route::get('portfolios/{portfolio}/reporte/pdf', [ReportController::class, 'pdf'])->name('portfolios.reporte.pdf');
        Route::get('plan', PlanController::class)->name('plan');
    });

    // Mercado en vivo (cotizaciones del momento; la página las vuelve a pedir cada pocos segundos).
    Route::get('mercado', [MarketController::class, 'index'])->name('mercado');
    Route::get('mercado/cotizaciones', [MarketController::class, 'cotizaciones'])->middleware('throttle:120,1')->name('mercado.cotizaciones');
    Route::post('mercado/lista', [MarketController::class, 'seguir'])->name('mercado.lista.store');
    Route::delete('mercado/lista/{asset}', [MarketController::class, 'dejarDeSeguir'])->name('mercado.lista.destroy');

    // Catálogo global de activos: todos lo consultan y dan de alta; solo el superadmin edita o borra.
    Route::resource('assets', AssetController::class)->except('show');

    // Administración de la plataforma.
    Route::middleware('role:superadmin')->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'edit', 'update']);
        Route::post('mercado/sincronizar', MarketSyncController::class)->middleware('throttle:6,1')->name('mercado.sincronizar');
        Route::get('precios', [PriceController::class, 'index'])->name('precios.index');
        Route::post('precios', [PriceController::class, 'store'])->name('precios.store');
        Route::post('precios/tipos', [PriceController::class, 'storeTasa'])->name('precios.tasas.store');
        Route::post('precios/importar', [PriceController::class, 'importar'])->name('precios.importar');
    });
});
