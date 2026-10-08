<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Alertas de precio de cada usuario: avisan cuando un activo sube o baja a un precio. */
    public function up(): void
    {
        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            // arriba: avisa cuando el precio llegue o pase hacia arriba; abajo: cuando llegue o baje.
            $table->string('condicion', 10);
            $table->decimal('precio', 20, 6);
            // Se llenan al dispararse; una alerta disparada ya no se vuelve a revisar.
            $table->timestamp('disparada_en')->nullable();
            $table->decimal('precio_disparo', 20, 6)->nullable();
            // Cuándo el usuario vio el aviso (deja de mostrarse en Inicio y Mercado).
            $table->timestamp('vista_en')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'disparada_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_alerts');
    }
};
