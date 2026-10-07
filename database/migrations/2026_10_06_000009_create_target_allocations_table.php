<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations');
            $table->foreignId('portfolio_id')->constrained('portfolios')->cascadeOnDelete();
            // dimension: clase, sector, moneda o pais. clave: valor dentro de esa dimension (ej. "etf", "MXN").
            $table->string('dimension', 20);
            $table->string('clave', 100);
            $table->decimal('porcentaje_objetivo', 9, 4);
            $table->timestamps();

            $table->unique(['portfolio_id', 'dimension', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_allocations');
    }
};
