<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Doble factor (TOTP): clave secreta cifrada con APP_KEY, fecha de confirmación,
 * códigos de recuperación (solo su hash) y el último paso de 30 s usado (un código no se acepta dos veces).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('dos_factores_secreto')->nullable()->after('password');
            $table->timestamp('dos_factores_confirmado_en')->nullable()->after('dos_factores_secreto');
            $table->text('dos_factores_recuperacion')->nullable()->after('dos_factores_confirmado_en');
            $table->unsignedBigInteger('dos_factores_ultimo_paso')->nullable()->after('dos_factores_recuperacion');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dos_factores_secreto', 'dos_factores_confirmado_en', 'dos_factores_recuperacion', 'dos_factores_ultimo_paso']);
        });
    }
};
