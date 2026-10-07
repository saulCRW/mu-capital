<?php

use App\Modules\Assets\Controllers\AssetController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Auth\Controllers\PasswordResetController;
use App\Modules\Clients\Controllers\ClientController;
use App\Modules\Organizations\Controllers\OrganizationController;
use App\Modules\Portfolios\Controllers\PortfolioController;
use App\Modules\Reports\Controllers\DashboardController;
use App\Modules\Reports\Controllers\ReportController;
use App\Modules\Transactions\Controllers\TransactionController;
use App\Modules\Users\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// ── Invitados (sin sesión) ──────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

// ── Usuarios autenticados de un despacho activo ─────────────────────────
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Clientes → portafolios → transacciones. "shallow" = las rutas de un registro
    // existente no repiten al padre: /portfolios/5 en vez de /clients/2/portfolios/5.
    Route::middleware('role:admin,asesor')->group(function () {
        Route::resource('clients', ClientController::class);
        Route::resource('clients.portfolios', PortfolioController::class)->shallow()->except('index');
        Route::resource('portfolios.transactions', TransactionController::class)->shallow()->except(['index', 'show']);
        Route::get('portfolios/{portfolio}/operar', [TransactionController::class, 'operar'])->name('portfolios.operar');
    });

    // Reporte trimestral: también lo consulta el usuario cliente (la policy limita a su cliente).
    Route::middleware('role:admin,asesor,cliente')->group(function () {
        Route::get('portfolios/{portfolio}/reporte', [ReportController::class, 'trimestral'])->name('portfolios.reporte');
        Route::get('portfolios/{portfolio}/reporte/pdf', [ReportController::class, 'pdf'])->name('portfolios.reporte.pdf');
    });

    // Catálogo global de activos (la policy decide quién edita o borra).
    Route::middleware('role:admin,asesor,superadmin')->group(function () {
        Route::resource('assets', AssetController::class)->except('show');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });

    Route::middleware('role:superadmin')->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::resource('organizations', OrganizationController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    });
});
