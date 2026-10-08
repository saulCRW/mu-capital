<?php

namespace Tests\Feature\Reports;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Hoy = 02/03/2026. El usuario tiene un portafolio en MXN (deposita 10,000) y otro
 * en USD (deposita 1,000). Con USD→MXN = 20 su patrimonio es 10,000 + 1,000 × 20 = 30,000 MXN.
 * Otro usuario tiene su propio portafolio con 5,000 MXN.
 */
class DashboardTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    private Portfolio $enDolares;

    private Portfolio $ajeno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');

        $this->enDolares = Portfolio::create(['user_id' => $this->usuario->id, 'nombre' => 'Dólares', 'moneda_base' => 'USD']);
        $this->ajeno = Portfolio::create(['user_id' => $this->otroUsuario->id, 'nombre' => 'Ajeno', 'moneda_base' => 'MXN']);

        $this->depositar('10000', '2026-01-05');
        $this->depositarEn($this->enDolares, '1000', $this->usuario);
        $this->depositarEn($this->ajeno, '5000', $this->otroUsuario);
    }

    private function depositarEn(Portfolio $portfolio, string $monto, User $como): void
    {
        $this->actingAs($como)->post(route('portfolios.transactions.store', $portfolio), ['tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => $monto])
            ->assertSessionHasNoErrors();
    }

    public function test_ve_sus_portafolios_con_el_total_convertido(): void
    {
        // insert(): en SQLite el cast de fecha guardaría la hora y rompería la comparación de texto.
        FxRate::insert(['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => '2026-01-02', 'tasa' => '20']);

        $response = $this->actingAs($this->usuario)->get(route('dashboard'));

        $response->assertSeeInOrder([
            'Tu patrimonio', '30,000.00', 'MXN · 2 portafolios',
            'Aportaciones netas 2026', '30,000.00',
        ]);
        // Ordenados por valor en MXN: 20,000 (USD) antes que 10,000; peso 66.67 % y 33.33 %.
        $response->assertSeeInOrder(['Dólares', '1,000.00', 'USD', '66.67 %', 'Principal', '10,000.00', 'MXN', '33.33 %']);
        $response->assertDontSee('Ajeno');
    }

    public function test_sin_tipo_de_cambio_el_portafolio_queda_fuera_del_total(): void
    {
        $response = $this->actingAs($this->usuario)->get(route('dashboard'));

        $response->assertSeeInOrder(['Tu patrimonio', '10,000.00']);
        $response->assertSee('Sin tipo de cambio a MXN para USD: esos portafolios no entran en los totales.');
    }

    public function test_el_otro_usuario_solo_ve_lo_suyo(): void
    {
        $response = $this->actingAs($this->otroUsuario)->get(route('dashboard'));

        $response->assertSeeInOrder(['Tu patrimonio', '5,000.00', 'MXN · 1 portafolio']);
        $response->assertDontSee('Principal');
    }

    public function test_un_usuario_nuevo_ve_como_empezar(): void
    {
        $nuevo = User::factory()->create();

        $this->actingAs($nuevo)->get(route('dashboard'))
            ->assertSee('Todavía no tienes portafolios.')
            ->assertSee(route('portfolios.create'), false);
    }

    /** Respuesta intradía de Yahoo con el precio del momento. */
    private static function cotizacion(float $precio, float $anterior): array
    {
        $hora = now()->timestamp;

        return ['chart' => ['result' => [[
            'meta' => ['regularMarketPrice' => $precio, 'previousClose' => $anterior, 'regularMarketTime' => $hora,
                'currentTradingPeriod' => ['regular' => ['start' => $hora - 3600, 'end' => $hora + 3600]]],
            'timestamp' => [$hora],
            'indicators' => ['quote' => [['close' => [$precio]]]],
        ]], 'error' => null]];
    }

    /**
     * Principal: 5,000 de efectivo y 100 NAFTRAC al cierre guardado de 60 (11,000); en vivo a 66 vale 11,600.
     * Dólares: 1,000 USD a 20 guardado y 21 en vivo = 21,000 MXN. Patrimonio en vivo 32,600; antes 31,000.
     */
    public function test_en_vivo_revalua_con_precios_y_tipo_de_cambio_del_momento(): void
    {
        FxRate::insert(['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => '2026-01-02', 'tasa' => '20']);
        Price::insert(['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '60']);
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50']);
        config(['services.yahoo_finance.url' => 'https://yahoo.test']);
        Http::fake([
            'yahoo.test/v8/finance/chart/NAFTRAC.MX*' => Http::response(self::cotizacion(66, 60)),
            'yahoo.test/v8/finance/chart/USDMXN*' => Http::response(self::cotizacion(21, 20)),
            'yahoo.test/*' => Http::response(['chart' => ['result' => null, 'error' => ['code' => 'Not Found']]], 404),
        ]);

        $datos = $this->actingAs($this->usuario)->getJson(route('dashboard.vivo'))->assertOk()->json();

        $this->assertSame('MXN', $datos['moneda']);
        $this->assertEqualsWithDelta(32600, $datos['total']['valor'], 1e-6);
        // Ganancia del año guardada: 31,000 − 30,000 aportados = 1,000; más 1,600 del mercado y el tipo de cambio de hoy.
        $this->assertEqualsWithDelta(2600, $datos['total']['ganancia'], 1e-6);
        $principal = $datos['portafolios'][$this->portafolio->id];
        $this->assertEqualsWithDelta(11600, $principal['valor'], 1e-6);
        $this->assertEqualsWithDelta(1600, $principal['ganancia'], 1e-6);
        $this->assertEqualsWithDelta(11600 / 32600, $principal['peso'], 1e-9);
        $this->assertEqualsWithDelta(1000, $datos['portafolios'][$this->enDolares->id]['valor'], 1e-6);
        $this->assertArrayNotHasKey($this->ajeno->id, $datos['portafolios']);
    }
}
