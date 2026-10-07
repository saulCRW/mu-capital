<?php

namespace Tests\Feature\Prices;

use App\Modules\Assets\Models\Asset;
use App\Modules\Prices\Services\Providers\BanxicoProvider;
use App\Modules\Prices\Services\Providers\ProviderException;
use App\Modules\Prices\Services\Providers\YahooFinanceProvider;
use App\Modules\Prices\Services\Providers\YahooFxProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Lectura de las respuestas de Yahoo Finance y Banxico (respuestas simuladas, sin red). */
class MarketProvidersTest extends TestCase
{
    private function yahoo(): YahooFinanceProvider
    {
        return new YahooFinanceProvider('https://yahoo.test');
    }

    public function test_deduce_el_simbolo_de_yahoo_de_cada_activo(): void
    {
        $activo = fn (array $a) => new Asset($a + ['moneda' => 'MXN', 'tipo' => 'accion', 'exchange' => null]);

        $this->assertSame('WALMEX.MX', $this->yahoo()->simboloPara($activo(['simbolo' => 'WALMEX', 'exchange' => 'BMV'])));
        $this->assertSame('BTC-USD', $this->yahoo()->simboloPara($activo(['simbolo' => 'BTC', 'tipo' => 'cripto', 'moneda' => 'USD'])));
        $this->assertSame('AAPL', $this->yahoo()->simboloPara($activo(['simbolo' => 'AAPL', 'exchange' => 'NASDAQ'])));
        $this->assertSame('FUNO11.MX', $this->yahoo()->simboloPara($activo(['simbolo' => 'FUNO', 'exchange' => 'BMV', 'simbolo_proveedor' => 'FUNO11.MX'])));
        $this->assertNull($this->yahoo()->simboloPara($activo(['simbolo' => 'MXN', 'tipo' => 'efectivo'])));
    }

    /**
     * Barras de la BMV (UTC−6): 05/10 cierra en 46.45; la barra de hoy (06/10) aún no
     * tiene cierre, así que se toma el último precio (46.64).
     */
    public function test_lee_los_cierres_y_el_precio_del_dia_en_curso(): void
    {
        Http::fake(['yahoo.test/*' => Http::response(['chart' => ['result' => [[
            'meta' => ['gmtoffset' => -21600, 'regularMarketPrice' => 46.64, 'regularMarketTime' => 1791316798],
            'timestamp' => [1791207000, 1791293400],
            'indicators' => ['quote' => [['close' => [46.45000076293945, null]]]],
        ]], 'error' => null]])]);

        $cierres = $this->yahoo()->cierres('WALMEX.MX', '2026-10-01', '2026-10-06');

        $this->assertSame(['2026-10-05' => '46.450001', '2026-10-06' => '46.640000'], $cierres);
        Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://yahoo.test/v8/finance/chart/WALMEX.MX?') && $r['interval'] === '1d');
    }

    public function test_un_simbolo_desconocido_es_un_error_del_proveedor(): void
    {
        Http::fake(['yahoo.test/*' => Http::response(['chart' => ['result' => null, 'error' => ['description' => 'No data found, symbol may be delisted']]], 404)]);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Yahoo Finance no tiene datos de XYZ.MX: No data found, symbol may be delisted');

        $this->yahoo()->cierres('XYZ.MX', '2026-10-01', '2026-10-06');
    }

    public function test_lee_los_dividendos_por_titulo(): void
    {
        Http::fake(['yahoo.test/*' => Http::response(['chart' => ['result' => [[
            'meta' => ['gmtoffset' => -14400],
            'events' => ['dividends' => [
                '1778501400' => ['amount' => 0.27, 'date' => 1778501400],
                '1770643800' => ['amount' => 0.26, 'date' => 1770643800],
                '1772136000' => ['amount' => 0.0, 'date' => 1772136000],
            ]],
        ]], 'error' => null]])]);

        $dividendos = $this->yahoo()->dividendos('AAPL', '2026-01-01', '2026-10-06');

        $this->assertSame(['2026-02-09' => '0.260000', '2026-05-11' => '0.270000'], $dividendos);
        Http::assertSent(fn (Request $r) => $r['events'] === 'div');
    }

    public function test_el_tipo_de_cambio_de_yahoo_usa_el_par_contra_el_peso(): void
    {
        Http::fake(['yahoo.test/*' => Http::response(['chart' => ['result' => [[
            'meta' => ['gmtoffset' => 3600],
            'timestamp' => [1791207000],
            'indicators' => ['quote' => [['close' => [18.41]]]],
        ]], 'error' => null]])]);

        $tasas = (new YahooFxProvider($this->yahoo()))->tasasAPesos('USD', '2026-10-01', '2026-10-06');

        $this->assertSame(['2026-10-05' => '18.410000'], $tasas);
        Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://yahoo.test/v8/finance/chart/USDMXN%3DX?'));
    }

    public function test_lee_el_tipo_de_cambio_fix_de_banxico(): void
    {
        Http::fake(['banxico.test/*' => Http::response(['bmx' => ['series' => [[
            'idSerie' => 'SF43718',
            'datos' => [
                ['fecha' => '02/10/2026', 'dato' => '18.3920'],
                ['fecha' => '03/10/2026', 'dato' => 'N/E'],
                ['fecha' => '05/10/2026', 'dato' => '18.4105'],
            ],
        ]]]])]);
        $banxico = new BanxicoProvider('https://banxico.test', 'mi-token');

        $tasas = $banxico->tasasAPesos('USD', '2026-10-01', '2026-10-06');

        $this->assertSame(['2026-10-02' => '18.392000', '2026-10-05' => '18.410500'], $tasas);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://banxico.test/series/SF43718/datos/2026-10-01/2026-10-06' && $r->hasHeader('Bmx-Token', 'mi-token'));
    }

    public function test_banxico_sin_token_no_esta_disponible(): void
    {
        $this->assertFalse((new BanxicoProvider('https://banxico.test', null))->disponible());
        $this->assertTrue((new BanxicoProvider('https://banxico.test', 'x'))->disponible());
    }
}
