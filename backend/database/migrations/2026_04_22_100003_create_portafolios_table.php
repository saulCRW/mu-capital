<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portafolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('estrategia', 50)->nullable();
            $table->string('moneda', 10)->default('USD');
            $table->decimal('valor_inicial', 20, 2)->default(0);
            $table->decimal('valor_actual', 20, 2)->default(0);
            $table->decimal('rendimiento_total', 10, 4)->default(0);
            $table->string('benchmark', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('es_publico')->default(false);
            $table->string('color', 7)->nullable();
            $table->timestamps();
        });

        Schema::create('portafolio_activos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portafolio_id')->constrained('portafolios')->onDelete('cascade');
            $table->foreignId('activo_id')->constrained('activos');
            $table->decimal('cantidad', 30, 8);
            $table->decimal('precio_promedio', 24, 8)->nullable();
            $table->decimal('peso_objetivo', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['portafolio_id', 'activo_id'], 'uq_portafolio_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portafolio_activos');
        Schema::dropIfExists('portafolios');
    }
};
