<?php

use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Auth\Controllers\PasswordResetController;
use App\Modules\Organizations\Controllers\OrganizationController;
use App\Modules\Users\Controllers\UserController;
use App\Modules\Users\Models\User;
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

    // SUPOSICIÓN: pantalla provisional; el dashboard real del asesor llega en la Fase 6.
    Route::get('dashboard', function () {
        if (auth()->user()->rol === User::ROL_SUPERADMIN) {
            return redirect()->route('superadmin.organizations.index');
        }

        return view('dashboard');
    })->name('dashboard');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });

    Route::middleware('role:superadmin')->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::resource('organizations', OrganizationController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    });
});
