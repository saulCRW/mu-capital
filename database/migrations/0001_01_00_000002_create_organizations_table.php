<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->foreignId('plan_id')->constrained('plans');
            $table->enum('estado', ['activa', 'suspendida'])->default('activa');
            $table->date('fecha_alta');
            // Tasa libre de riesgo anual en fraccion (0.105000 = 10.5%), usada en Sharpe y Sortino.
            $table->decimal('tasa_libre_riesgo', 9, 6)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
