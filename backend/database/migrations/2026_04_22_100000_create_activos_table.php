<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activos', function (Blueprint $table) {
            $table->id();
            $table->string('simbolo', 20)->unique();
            $table->string('nombre', 150);
            $table->string('tipo', 50);
            $table->text('descripcion')->nullable();
            $table->string('exchange', 100)->nullable();
            $table->string('moneda_base', 10)->default('USD');
            $table->integer('decimales')->default(8);
            $table->boolean('activo')->default(true);
            $table->text('logo_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activos');
    }
};
