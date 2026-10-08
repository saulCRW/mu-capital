<?php

namespace Tests\Feature\Api;

use App\Modules\Prices\Models\Price;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * API interna v1: respuesta { ok, data, error }. Escenario: deposita 10,000 y compra
 * 100 NAFTRAC a 50 con 10 de comisión; cierre de 55 → total 10,490 (4,990 + 5,500).
 */
class PortfolioApiTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');
        $this->depositar('10000', '2026-01-05');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50', 'comision' => '10']);
        Price::insert(['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '55']);
    }

    public function test_lista_solo_los_portafolios_del_usuario(): void
    {
        $this->actingAs($this->usuario)->getJson(route('api.v1.portfolios.index'))
            ->assertOk()
            ->assertExactJson(['ok' => true, 'error' => null, 'data' => [
                ['id' => $this->portafolio->id, 'nombre' => 'Principal', 'moneda_base' => 'MXN', 'benchmark' => null],
            ]]);

        $this->actingAs($this->otroUsuario)->getJson(route('api.v1.portfolios.index'))->assertJson(['ok' => true, 'data' => []]);
    }

    public function test_el_detalle_trae_los_kpi_a_hoy(): void
    {
        $this->actingAs($this->usuario)->getJson(route('api.v1.portfolios.show', $this->portafolio))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.saldo.fecha', '2026-03-02')
            ->assertJsonPath('data.saldo.valor_total', '10490.000000')
            ->assertJsonPath('data.saldo.efectivo', '4990.000000')
            ->assertJsonPath('data.saldo.gp_total', '490.000000')
            ->assertJsonPath('data.saldo.rendimiento_total', 0.049)
            ->assertJsonPath('data.saldo.holdings', 1);
    }

    public function test_holdings_y_transacciones_paginadas_con_filtros(): void
    {
        $this->actingAs($this->usuario)->getJson(route('api.v1.portfolios.holdings', $this->portafolio))
            ->assertOk()
            ->assertJsonPath('data.0.simbolo', 'NAFTRAC')
            ->assertJsonPath('data.0.valor_base', '5500.000000')
            ->assertJsonPath('data.0.abierta', true);

        $this->actingAs($this->usuario)->getJson(route('api.v1.portfolios.transactions', [$this->portafolio, 'tipo' => 'COMPRA']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo', 'COMPRA')
            ->assertJsonPath('data.0.activo', 'NAFTRAC')
            ->assertJsonPath('data.0.fecha', '2026-01-06')
            ->assertJsonPath('paginacion.total', 1);
    }

    public function test_los_errores_usan_el_mismo_formato(): void
    {
        // Sin sesión (el setUp dejó al usuario autenticado).
        $this->app['auth']->forgetGuards();
        $this->getJson(route('api.v1.portfolios.index'))
            ->assertUnauthorized()
            ->assertExactJson(['ok' => false, 'data' => null, 'error' => ['code' => 'no_autenticado', 'message' => 'Inicia sesión para usar la API.']]);

        // El portafolio de otro usuario no existe para él (Global Scope).
        $this->actingAs($this->otroUsuario)->getJson(route('api.v1.portfolios.show', $this->portafolio))
            ->assertNotFound()
            ->assertJsonPath('error.code', 'no_encontrado');

        $this->actingAs($this->usuario)->getJson(route('api.v1.portfolios.transactions', [$this->portafolio, 'tipo' => 'REGALO']))
            ->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'validacion')
            ->assertJsonStructure(['error' => ['campos' => ['tipo']]]);

        // El superadmin administra la plataforma, no ve portafolios.
        $this->actingAs($this->superadmin)->getJson(route('api.v1.portfolios.index'))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'prohibido');
    }
}
