<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Símbolo con el que el proveedor de precios conoce al activo o índice
     * (p. ej. WALMEX.MX, ^MXX). Vacío = se deduce del símbolo y el exchange.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('simbolo_proveedor', 40)->nullable()->after('exchange');
        });
        Schema::table('benchmarks', function (Blueprint $table) {
            $table->string('simbolo_proveedor', 40)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('simbolo_proveedor');
        });
        Schema::table('benchmarks', function (Blueprint $table) {
            $table->dropColumn('simbolo_proveedor');
        });
    }
};
