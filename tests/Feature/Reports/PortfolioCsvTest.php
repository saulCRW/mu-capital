<?php

namespace Tests\Feature\Reports;

use App\Modules\Prices\Models\Price;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Deposita 10,000 y compra 100 NAFTRAC a 50 con 10 de comisión; cierre de 55.
 * Efectivo 4,990 + posición 5,500 = 10,490. G/P 490 = 4.90 %; NAFTRAC rinde 490 ÷ 5,010 = 9.78 %.
 */
class PortfolioCsvTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');
    }

    public function test_descarga_el_estado_del_portafolio_en_csv(): void
    {
        $this->depositar('10000', '2026-01-05');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50', 'comision' => '10']);
        Price::insert(['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '55']);

        $response = $this->actingAs($this->usuario)->get(route('portfolios.estado.csv', $this->portafolio));

        $response->assertDownload('estado-principal-2026-03-02.csv');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        foreach ([
            // fputcsv pone entre comillas los campos con espacios.
            'Portafolio,Principal',
            '"Valor total",10490',
            'Efectivo,4990',
            '"G/P total",490',
            '"Rendimiento total %",4.90',
            'simbolo,nombre,tipo,sector,moneda,cantidad,costo_promedio,precio,valor_mxn,peso_%,gp_no_realizada,gp_realizada,rendimiento_%,estado',
            'NAFTRAC,"iShares NAFTRAC",ETF,,MXN,100,50.1,55,5500,52.43,490,0,9.78,abierta',
        ] as $linea) {
            $this->assertStringContainsString($linea, $csv);
        }
    }

    public function test_un_portafolio_sin_transacciones_lo_indica(): void
    {
        $csv = $this->actingAs($this->usuario)->get(route('portfolios.estado.csv', $this->portafolio))->streamedContent();

        $this->assertStringContainsString('Sin transacciones', $csv);
    }

    public function test_no_se_descarga_el_portafolio_de_otro(): void
    {
        $this->actingAs($this->otroUsuario)->get(route('portfolios.estado.csv', $this->portafolio))->assertNotFound();
    }
}
