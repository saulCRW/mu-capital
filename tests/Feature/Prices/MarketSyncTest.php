<?php

namespace Tests\Feature\Prices;

use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Prices\Models\BenchmarkValue;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use App\Modules\Prices\Services\MarketSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use LogicException;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Hoy = 06/10/2026 (martes). El portafolio en MXN compró 100 NAFTRAC @ 50 y 1 AAPL @ 200 USD (TC 18).
 * El proveedor dice: NAFTRAC cerró en 55 el 05/10; AAPL en 210; IPC 60,000; USD→MXN 18.5.
 */
class MarketSyncTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-10-06 12:00:00');
        config(['services.yahoo_finance.url' => 'https://yahoo.test', 'services.banxico.url' => 'https://banxico.test', 'services.banxico.token' => 'mi-token']);

        $this->depositar('20000', '2026-10-01');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-10-01', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50']);
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-10-01', 'asset_id' => $this->aapl->id, 'cantidad' => '1', 'precio' => '200', 'tipo_cambio' => '18']);
        $this->naftrac->update(['exchange' => 'BMV']);
        $this->portafolio->update(['benchmark_id' => Benchmark::create(['nombre' => 'IPC', 'simbolo_proveedor' => '^MXX'])->id]);
        auth()->logout();
    }

    /** Barra diaria de Yahoo para el 05/10/2026 (14:30 UTC) con el cierre dado. */
    private static function yahoo(float $cierre): array
    {
        return ['chart' => ['result' => [[
            'meta' => ['gmtoffset' => -21600],
            'timestamp' => [1791207000],
            'indicators' => ['quote' => [['close' => [$cierre]]]],
        ]], 'error' => null]];
    }

    private function fakeProveedores(): void
    {
        Http::fake([
            'yahoo.test/v8/finance/chart/NAFTRAC.MX*' => Http::response(self::yahoo(55)),
            'yahoo.test/v8/finance/chart/AAPL*' => Http::response(self::yahoo(210)),
            'yahoo.test/v8/finance/chart/%5EMXX*' => Http::response(self::yahoo(60000)),
            'banxico.test/*' => Http::response(['bmx' => ['series' => [['datos' => [['fecha' => '05/10/2026', 'dato' => '18.5000']]]]]]),
        ]);
    }

    public function test_guarda_precios_indices_y_tipos_de_cambio_y_revalua_los_portafolios(): void
    {
        $this->fakeProveedores();

        $r = app(MarketSyncService::class)->sincronizar();

        $this->assertSame(['precios' => 2, 'indices' => 1, 'tasas' => 1, 'portafolios' => 1, 'alertas' => 0, 'errores' => [], 'avisos' => []], $r);
        $this->assertDatabaseHas('prices', ['asset_id' => $this->naftrac->id, 'cierre' => '55.000000']);
        $this->assertDatabaseHas('prices', ['asset_id' => $this->aapl->id, 'cierre' => '210.000000']);
        $this->assertSame(1, BenchmarkValue::count());
        $this->assertSame('18.500000', FxRate::where('moneda_origen', 'USD')->where('moneda_destino', 'MXN')->value('tasa'));

        // Efectivo 20,000 − 5,000 − 3,600 = 11,400; NAFTRAC 100 × 55 = 5,500; AAPL 210 × 18.5 = 3,885.
        $hoy = PortfolioSnapshot::where('portfolio_id', $this->portafolio->id)->orderByDesc('fecha')->first();
        $this->assertSame('20785.000000', $hoy->valor);
    }

    public function test_un_proveedor_que_falla_no_detiene_lo_demas(): void
    {
        Http::fake([
            'yahoo.test/v8/finance/chart/NAFTRAC.MX*' => Http::response(['chart' => ['result' => null, 'error' => ['description' => 'No data found']]], 404),
            'yahoo.test/*' => Http::response(self::yahoo(210)),
            'banxico.test/*' => Http::response(['bmx' => ['series' => [['datos' => []]]]]),
        ]);

        $r = app(MarketSyncService::class)->sincronizar();

        $this->assertSame(['Yahoo Finance no tiene datos de NAFTRAC.MX: No data found'], $r['errores']);
        $this->assertSame(1, Price::where('asset_id', $this->aapl->id)->count());
    }

    public function test_sin_token_de_banxico_toma_el_tipo_de_cambio_de_yahoo(): void
    {
        config(['services.banxico.token' => null]);
        $this->fakeProveedores();
        Http::fake(['yahoo.test/v8/finance/chart/USDMXN%3DX*' => Http::response(self::yahoo(18.25))]);

        $r = app(MarketSyncService::class)->sincronizar();

        $this->assertSame([], $r['avisos']);
        $this->assertSame('18.250000', FxRate::where('moneda_origen', 'USD')->value('tasa'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'banxico.test'));
    }

    public function test_no_corre_con_el_filtro_de_un_despacho(): void
    {
        $this->actingAs($this->usuario);

        $this->expectException(LogicException::class);

        app(MarketSyncService::class)->sincronizar();
    }

    public function test_solo_el_superadmin_la_lanza_desde_la_web(): void
    {
        $this->fakeProveedores();
        $this->actingAs($this->usuario)->post(route('superadmin.mercado.sincronizar'))->assertForbidden();
        $this->actingAs($this->superadmin)->from(route('assets.index'))->post(route('superadmin.mercado.sincronizar'))
            ->assertRedirect(route('assets.index'))
            ->assertSessionHas('status', 'Mercado actualizado: 2 precios, 1 valores de índices y 1 tipos de cambio; 1 portafolios recalculados.');
    }

    public function test_el_comando_reporta_el_resultado(): void
    {
        $this->fakeProveedores();

        $this->artisan('mercado:sincronizar')
            ->expectsOutput('Precios: 2 · Índices: 1 · Tipos de cambio: 1 · Portafolios recalculados: 1')
            ->assertSuccessful();
    }
}
