<?php

namespace Tests\Feature\Concerns;

use App\Modules\Assets\Models\Asset;
use App\Modules\Clients\Models\Client;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Users\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * Escenario común: un despacho con admin, dos asesores, un cliente del
 * primer asesor con un portafolio en MXN, y dos activos (uno en USD).
 */
trait CreaEscenario
{
    protected Organization $org;
    protected User $admin;
    protected User $asesor;
    protected User $otroAsesor;
    protected Client $cliente;
    protected Portfolio $portafolio;
    protected Asset $naftrac;
    protected Asset $aapl;

    protected function crearEscenario(): void
    {
        $this->org = Organization::factory()->create();
        $this->admin = User::factory()->admin()->create(['organization_id' => $this->org->id]);
        $this->asesor = User::factory()->create(['organization_id' => $this->org->id, 'nombre' => 'Asesor Uno']);
        $this->otroAsesor = User::factory()->create(['organization_id' => $this->org->id, 'nombre' => 'Asesor Dos']);

        $this->cliente = Client::create([
            'organization_id' => $this->org->id,
            'asesor_id' => $this->asesor->id,
            'nombre' => 'Cliente Uno',
            'perfil_riesgo' => 'moderado',
        ]);

        $this->portafolio = Portfolio::create([
            'organization_id' => $this->org->id,
            'client_id' => $this->cliente->id,
            'nombre' => 'Principal',
            'moneda_base' => 'MXN',
        ]);

        $this->naftrac = Asset::create(['simbolo' => 'NAFTRAC', 'nombre' => 'iShares NAFTRAC', 'tipo' => 'etf', 'moneda' => 'MXN', 'exchange' => 'BMV']);
        $this->aapl = Asset::create(['simbolo' => 'AAPL', 'nombre' => 'Apple Inc.', 'tipo' => 'accion', 'moneda' => 'USD', 'exchange' => 'NASDAQ']);
    }

    /** Registra una transacción en el portafolio del escenario como el asesor. */
    protected function registrar(array $datos): TestResponse
    {
        return $this->actingAs($this->asesor)
            ->post(route('portfolios.transactions.store', $this->portafolio), $datos);
    }

    protected function depositar(string $monto, string $fecha = '2026-01-05'): TestResponse
    {
        return $this->registrar(['tipo' => 'DEPOSITO', 'fecha' => $fecha, 'cantidad' => $monto]);
    }
}
