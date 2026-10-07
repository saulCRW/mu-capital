<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_rates', function (Blueprint $table) {
            $table->id();
            $table->char('moneda_origen', 3);
            $table->char('moneda_destino', 3);
            $table->date('fecha');
            $table->decimal('tasa', 20, 6);
            $table->timestamps();

            $table->unique(['moneda_origen', 'moneda_destino', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_rates');
    }
};
