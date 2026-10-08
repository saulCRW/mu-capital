<?php

namespace Tests\Feature\Billing;

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
        $this->usuario->plan->update($limites);
    }

    private function comprar(string $asset, string $cantidad, string $precio = '10')
    {
        return $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $asset, 'cantidad' => $cantidad, 'precio' => $precio, 'tipo_cambio' => '1']);
    }

    public function test_no_pasa_del_maximo_de_portafolios(): void
    {
        $this->limites(['max_portafolios' => 2]);
        $nuevo = fn (string $nombre) => $this->actingAs($this->usuario)->post(route('portfolios.store'), ['nombre' => $nombre, 'moneda_base' => 'MXN']);

        $nuevo('Dos')->assertSessionHasNoErrors();
        $nuevo('Tres')->assertSessionHasErrors(['general' => 'Tu plan '.$this->usuario->plan->nombre.' permite hasta 2 portafolios. Cambia de plan para agregar más.']);

        $this->assertSame(2, Portfolio::where('user_id', $this->usuario->id)->count());
    }

    public function test_los_portafolios_de_otro_usuario_no_cuentan(): void
    {
        $this->limites(['max_portafolios' => 1]);
        $this->otroUsuario->update(['plan_id' => $this->usuario->plan_id]);

        $this->actingAs($this->otroUsuario)->post(route('portfolios.store'), ['nombre' => 'Suyo', 'moneda_base' => 'MXN'])
            ->assertSessionHasNoErrors();
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

        $this->actingAs($this->usuario)->put(route('transactions.update', $deposito), ['tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => '2500'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2500.000000', $deposito->fresh()->cantidad);
    }

    public function test_el_usuario_ve_cuanto_usa_de_su_plan(): void
    {
        $this->limites(['max_portafolios' => 3, 'max_holdings_por_portafolio' => 25]);
        $this->depositar('10000');
        $this->comprar($this->naftrac->id, '10');

        $response = $this->actingAs($this->usuario)->get(route('plan'));

        $response->assertSeeInOrder(['Portafolios', '1', '3', 'Activos en cartera por portafolio', '1', '25', 'Transacciones por portafolio', '2']);
        $this->actingAs($this->superadmin)->get(route('plan'))->assertForbidden();
    }

    public function test_el_superadmin_ve_el_uso_del_plan_de_cada_usuario(): void
    {
        $this->limites(['max_portafolios' => 3, 'max_holdings_por_portafolio' => 1]);
        $this->depositar('10000');
        $this->comprar($this->naftrac->id, '10');

        // Llegó al máximo de activos en cartera (1 de 1): se marca como alerta (ámbar).
        $this->actingAs($this->superadmin)->get(route('superadmin.users.edit', $this->usuario))
            ->assertSeeInOrder(['Uso del plan', 'Portafolios', '1', '3', 'Activos en cartera por portafolio', 'class="num warn"', '1', '1', 'Transacciones por portafolio', '2'], false)
            ->assertDontSee('NAFTRAC');
    }
}
