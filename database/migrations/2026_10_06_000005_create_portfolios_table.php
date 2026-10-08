<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            // Dueño del portafolio (Global Scope: cada usuario solo ve lo suyo).
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('nombre', 150);
            $table->char('moneda_base', 3);
            $table->foreignId('benchmark_id')->nullable()->constrained('benchmarks');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
