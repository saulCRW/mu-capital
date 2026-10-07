<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations');
            $table->foreignId('portfolio_id')->constrained('portfolios');
            // Nullable: DEPOSITO, RETIRO, INTERES y COMISION pueden no referirse a un activo.
            $table->foreignId('asset_id')->nullable()->constrained('assets');
            $table->enum('tipo', ['COMPRA', 'VENTA', 'DEPOSITO', 'RETIRO', 'DIVIDENDO', 'INTERES', 'COMISION', 'SPLIT']);
            $table->date('fecha');
            $table->decimal('cantidad', 20, 6);
            $table->decimal('precio', 20, 6)->default(0);
            $table->decimal('comision', 20, 6)->default(0);
            $table->char('moneda', 3);
            // Tasa para convertir de "moneda" a la moneda base del portafolio.
            $table->decimal('tipo_cambio', 20, 6)->default(1);
            $table->text('nota')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['portfolio_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
