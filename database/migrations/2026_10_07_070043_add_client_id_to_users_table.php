<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Usuarios con rol cliente: el registro de cliente cuyos portafolios pueden consultar. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('organization_id')->constrained('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
