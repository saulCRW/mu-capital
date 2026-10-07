<?php

namespace Tests\Feature\Audit;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    private function ultimo(string $entidad, string $accion): AuditLog
    {
        return AuditLog::withoutGlobalScopes()->where('entidad', $entidad)->where('accion', $accion)->latest('id')->firstOrFail();
    }

    public function test_registra_el_alta_con_usuario_despacho_e_ip(): void
    {
        $this->actingAs($this->asesor)->post(route('clients.store'), ['nombre' => 'Auditado', 'perfil_riesgo' => 'moderado']);

        $log = $this->ultimo('Client', 'alta');
        $this->assertSame($this->asesor->id, $log->user_id);
        $this->assertSame($this->org->id, $log->organization_id);
        $this->assertSame('Auditado', $log->despues['nombre']);
        $this->assertNull($log->antes);
        $this->assertSame('127.0.0.1', $log->ip);
    }

    public function test_registra_solo_las_columnas_que_cambiaron(): void
    {
        $this->depositar('1000');
        $deposito = Transaction::firstOrFail();

        $this->actingAs($this->asesor)->put(route('transactions.update', $deposito), [
            'tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => '1500', 'nota' => 'Corrección',
        ])->assertSessionHasNoErrors();

        $log = $this->ultimo('Transaction', 'cambio');
        $this->assertSame($deposito->id, $log->entidad_id);
        // Solo las dos columnas que cambiaron (precio, moneda, etc. no aparecen).
        $this->assertEqualsCanonicalizing(['cantidad', 'nota'], array_keys($log->antes));
        $this->assertEquals(1000, $log->antes['cantidad']); // MySQL lo da como "1000.000000", SQLite como 1000
        $this->assertNull($log->antes['nota']);
        $this->assertSame(['cantidad' => '1500', 'nota' => 'Corrección'], $log->despues);
    }

    public function test_registra_la_baja_con_los_valores_anteriores(): void
    {
        $this->depositar('1000');
        $deposito = Transaction::firstOrFail();

        $this->actingAs($this->asesor)->delete(route('transactions.destroy', $deposito));

        $log = $this->ultimo('Transaction', 'baja');
        $this->assertSame('DEPOSITO', $log->antes['tipo']);
        $this->assertNull($log->despues);
    }

    public function test_nunca_guarda_contrasenas(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'nombre' => 'Nueva', 'email' => 'nueva@example.com', 'rol' => User::ROL_ASESOR,
            'password' => 'ClaveSegura1', 'password_confirmation' => 'ClaveSegura1',
        ]);

        $log = $this->ultimo('User', 'alta');
        $this->assertSame('********', $log->despues['password']);
    }
}
