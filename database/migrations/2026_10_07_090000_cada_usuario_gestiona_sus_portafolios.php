<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cambio de modelo: de despachos de asesoría (organizaciones, asesores y
 * clientes) a una herramienta personal, donde cada usuario es dueño de sus
 * portafolios y solo ve lo suyo.
 *
 * - users: rol superadmin | usuario; el plan, la tasa libre de riesgo y el
 *   estado pasan del despacho al usuario.
 * - portafolios, transacciones, objetivos y snapshots: user_id (dueño).
 *   Los datos existentes quedan a nombre del asesor que gestionaba el portafolio.
 * - Se eliminan clients y organizations; los planes limitan portafolios por usuario.
 */
return new class extends Migration
{
    private const DE_PORTAFOLIO = ['transactions', 'target_allocations', 'portfolio_snapshots'];

    public function up(): void
    {
        // 1. Usuarios: plan, tasa libre de riesgo y estado (antes del despacho).
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('id')->constrained('plans');
            $table->decimal('tasa_libre_riesgo', 9, 6)->default(0)->after('rol');
            $table->string('estado', 20)->default('activo')->after('tasa_libre_riesgo');
        });
        foreach (DB::table('organizations')->get() as $org) {
            DB::table('users')->where('organization_id', $org->id)->update([
                'plan_id' => $org->plan_id,
                'tasa_libre_riesgo' => $org->tasa_libre_riesgo,
                'estado' => $org->estado === 'activa' ? 'activo' : 'suspendido',
            ]);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->change();
        });
        DB::table('users')->where('rol', '!=', 'superadmin')->update(['rol' => 'usuario']);

        // 2. Dueño de cada portafolio: el asesor del cliente.
        Schema::table('portfolios', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users');
        });
        foreach (DB::table('clients')->get(['id', 'asesor_id']) as $cliente) {
            DB::table('portfolios')->where('client_id', $cliente->id)->update(['user_id' => $cliente->asesor_id]);
        }
        foreach (self::DE_PORTAFOLIO as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users');
            });
            foreach (DB::table('portfolios')->get(['id', 'user_id']) as $p) {
                DB::table($tabla)->where('portfolio_id', $p->id)->update(['user_id' => $p->user_id]);
            }
        }

        // 3. Fuera despachos y clientes.
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropConstrainedForeignId('organization_id');
        });
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropConstrainedForeignId('organization_id');
        });
        foreach ([...self::DE_PORTAFOLIO, 'audit_log'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('organization_id');
            });
        }
        Schema::dropIfExists('clients');
        Schema::dropIfExists('organizations');

        // 4. Planes por usuario.
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_clientes');
        });
        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('max_portafolios_por_cliente', 'max_portafolios');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migración cambia el modelo de datos y no se puede revertir; restaura un respaldo de la base.');
    }
};
