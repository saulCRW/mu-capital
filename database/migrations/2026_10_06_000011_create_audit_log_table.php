<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            // Nullable: acciones del superadmin o de procesos sin organizacion.
            $table->foreignId('organization_id')->nullable()->constrained('organizations');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('accion', 20);
            $table->string('entidad', 100);
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->json('antes')->nullable();
            $table->json('despues')->nullable();
            $table->dateTime('fecha');
            $table->string('ip', 45)->nullable();

            $table->index(['entidad', 'entidad_id']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
