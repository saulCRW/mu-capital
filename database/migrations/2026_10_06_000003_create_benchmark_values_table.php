<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Serie de valores por fecha de cada benchmark.
        Schema::create('benchmark_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benchmark_id')->constrained('benchmarks')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('valor', 20, 6);
            $table->timestamps();

            $table->unique(['benchmark_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benchmark_values');
    }
};
