<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets');
            $table->date('fecha');
            $table->decimal('cierre', 20, 6);
            $table->timestamps();

            $table->unique(['asset_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
