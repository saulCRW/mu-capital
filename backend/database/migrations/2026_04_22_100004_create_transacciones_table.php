<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portafolio_id')->constrained('portafolios');
            $table->foreignId('activo_id')->constrained('activos');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('tipo', 20);
            $table->string('estado', 20)->default('pendiente');
            $table->decimal('cantidad', 30, 8);
            $table->decimal('precio_unitario', 24, 8);
            $table->decimal('monto_total', 20, 2);
            $table->decimal('comision', 20, 8)->default(0);
            $table->string('moneda', 10)->default('USD');
            $table->string('exchange', 100)->nullable();
            $table->string('tx_hash', 255)->nullable();
            $table->text('notas')->nullable();
            $table->dateTime('ejecutada_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones');
    }
};
