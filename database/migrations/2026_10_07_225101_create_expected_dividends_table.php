<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Dividendos anunciados que cada usuario captura para verlos en su calendario. */
    public function up(): void
    {
        Schema::create('expected_dividends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            // Fecha ex: quien tiene el título al cierre del día anterior cobra. Opcional.
            $table->date('fecha_ex')->nullable();
            $table->date('fecha_pago');
            $table->decimal('monto_por_titulo', 20, 6);
            $table->char('moneda', 3);
            $table->string('nota', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fecha_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expected_dividends');
    }
};
