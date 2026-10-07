<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->unsignedInteger('max_clientes');
            $table->unsignedInteger('max_portafolios_por_cliente');
            $table->unsignedInteger('max_holdings_por_portafolio');
            $table->unsignedInteger('max_transacciones_por_portafolio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
