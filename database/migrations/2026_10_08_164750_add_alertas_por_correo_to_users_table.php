<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Si el usuario recibe por correo las alertas de precio que se disparan (activado por omisión). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('alertas_por_correo')->default(true)->after('tasa_libre_riesgo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('alertas_por_correo');
        });
    }
};
