<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('simbolo', 30)->index();
            $table->string('nombre', 150);
            $table->enum('tipo', ['accion', 'etf', 'fondo', 'bono', 'cripto', 'divisa', 'efectivo']);
            $table->string('sector', 100)->nullable();
            $table->string('pais', 100)->nullable();
            $table->char('moneda', 3);
            $table->string('exchange', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
