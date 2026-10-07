<?php

namespace Tests\Feature\Billing;

use App\Modules\Clients\Models\Client;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/** El escenario ya tiene 1 cliente con 1 portafolio. */
class PlanLimitsTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');
    }

    private function limites(array $limites): void
    {
        $this->org->plan->update($limites);
    }

    private function nuevoCliente(string $nombre)
    {
        return $this->actingAs($this->asesor)->post(route('clients.store'), ['nombre' => $nombre, 'perfil_riesgo' => 'moderado']);
    }

    private function comprar(string $asset, string $cantidad, string $precio = '10')
    {
        return $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $asset, 'cantidad' => $cantidad, 'precio' => $precio, 'tipo_cambio' => '1']);
    }

    public function test_no_pasa_del_maximo_de_clientes(): void
    {
        $this->limites(['max_clientes' => 2]);

        $this->nuevoCliente('Dos')->assertSessionHasNoErrors();
        $this->nuevoCliente('Tres')->assertSessionHasErrors(['general' => 'Tu plan '.$this->org->plan->nombre.' permite hasta 2 clientes. Para agregar más, pide a tu proveedor un cambio de plan.']);

        $this->assertSame(2, Client::count());
    }

    public function test_no_pasa_del_maximo_de_portafolios_por_cliente(): void
    {
        $this->limites(['max_portafolios_por_cliente' => 1]);

        $this->actingAs($this->asesor)->post(route('clients.portfolios.store', $this->cliente), ['nombre' => 'Otro', 'moneda_base' => 'MXN'])
            ->assertSessionHasErrors('general');

        $this->assertSame(1, Portfolio::count());
    }

    public function test_no_pasa_del_maximo_de_transacciones_por_portafolio(): void
    {
        $this->limites(['max_transacciones_por_portafolio' => 2]);

        $this->depositar('1000')->assertSessionHasNoErrors();
        $this->depositar('1000')->assertSessionHasNoErrors();
        $this->depositar('1000')->assertSessionHasErrors('general');

        $this->assertSame(2, Transaction::count());
    }

    public function test_una_compra_no_abre_mas_activos_de_los_permitidos(): void
    {
        $this->limites(['max_holdings_por_portafolio' => 1]);
        $this->depositar('10000');

        $this->comprar($this->naftrac->id, '10')->assertSessionHasNoErrors();
        $this->comprar($this->naftrac->id, '5')->assertSessionHasNoErrors();
        $this->comprar($this->aapl->id, '1')->assertSessionHasErrors('general');

        $this->registrar(['tipo' => 'VENTA', 'fecha' => '2026-01-07', 'asset_id' => $this->naftrac->id, 'cantidad' => '15', 'precio' => '10']);
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-08', 'asset_id' => $this->aapl->id, 'cantidad' => '1', 'precio' => '10', 'tipo_cambio' => '1'])
            ->assertSessionHasNoErrors();
    }

    public function test_al_bajar_de_plan_lo_existente_se_puede_editar(): void
    {
        $this->depositar('1000');
        $this->depositar('2000');
        $this->limites(['max_transacciones_por_portafolio' => 1]);
        $deposito = Transaction::where('cantidad', '2000')->firstOrFail();

        $this->actingAs($this->asesor)->put(route('transactions.update', $deposito), ['tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => '2500'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2500.000000', $deposito->fresh()->cantidad);
    }

    public function test_el_admin_ve_cuanto_usa_de_su_plan(): void
    {
        $this->limites(['max_clientes' => 10, 'max_holdings_por_portafolio' => 25]);
        $this->depositar('10000');
        $this->comprar($this->naftrac->id, '10');

        $response = $this->actingAs($this->admin)->get(route('admin.plan'));

        $response->assertSeeInOrder(['Clientes', '1', '10', 'Portafolios por cliente', '1', 'Activos en cartera por portafolio', '1', '25', 'Transacciones por portafolio', '2']);
        $this->actingAs($this->asesor)->get(route('admin.plan'))->assertForbidden();
    }
}
