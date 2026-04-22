<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfil_riesgo_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('nivel_riesgo', 20);
            $table->integer('horizonte_temporal')->nullable();
            $table->decimal('tolerancia_perdida', 5, 2)->nullable();
            $table->decimal('objetivo_retorno', 5, 2)->nullable();
            $table->json('cuestionario_resp')->nullable();
            $table->integer('puntuacion')->nullable();
            $table->dateTime('evaluado_en')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfil_riesgo_detalle');
    }
};
