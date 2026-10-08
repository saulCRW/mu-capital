<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('portfolio_id')->constrained('portfolios')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('valor', 20, 6);
            $table->decimal('efectivo', 20, 6);
            $table->decimal('flujo_neto', 20, 6);
            $table->timestamps();

            $table->unique(['portfolio_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
    }
};
