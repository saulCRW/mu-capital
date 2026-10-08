<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            // Símbolo en Yahoo Finance (ej. ^MXX); null si sus valores se capturan a mano.
            $table->string('simbolo_proveedor', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benchmarks');
    }
};
