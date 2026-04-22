<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_precios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('activo_id')->constrained('activos');
            $table->decimal('precio', 24, 8);
            $table->decimal('precio_max', 24, 8)->nullable();
            $table->decimal('precio_min', 24, 8)->nullable();
            $table->decimal('volumen', 30, 8)->nullable();
            $table->decimal('market_cap', 30, 2)->nullable();
            $table->decimal('variacion_24h', 8, 4)->nullable();
            $table->string('fuente', 50)->nullable();
            $table->string('intervalo', 10)->default('1d');
            $table->dateTime('timestamp');
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['activo_id', 'timestamp', 'intervalo'], 'uq_activo_ts_intervalo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_precios');
    }
};
