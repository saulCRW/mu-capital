<?php

namespace Tests\Feature\Transactions;

use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/** Vista de compra y venta de activos (portfolios/{portfolio}/operar). */
class OperarTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');
    }

    public function test_muestra_efectivo_tenencia_y_ultimo_precio_de_cada_activo(): void
    {
        $this->depositar('10000', '2026-01-05');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50']);
        // insert(): en SQLite el cast de fecha guardaría la hora y rompería la comparación de texto.
        Price::insert([
            ['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-26', 'cierre' => '54'],
            ['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '55.5'],
            ['asset_id' => $this->naftrac->id, 'fecha' => '2026-03-03', 'cierre' => '99'],
        ]);
        FxRate::insert([
            ['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => '2026-02-26', 'tasa' => '17.5'],
            ['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => '2026-02-27', 'tasa' => '18.25'],
        ]);

        $response = $this->actingAs($this->asesor)->get(route('portfolios.operar', $this->portafolio));

        // Efectivo: 10,000 − 100 × 50 = 5,000. El cierre del 03/03 es futuro y no cuenta.
        $response->assertSee('Efectivo disponible hoy: <strong>5,000.00 MXN</strong>', false);
        $response->assertSeeInOrder(['data-simbolo="AAPL"', 'data-tenencia="0"', 'data-precio=""', 'data-tasa="18.25"'], false);
        $response->assertSeeInOrder(['data-simbolo="NAFTRAC"', 'data-tenencia="100"', 'data-precio="55.5"', 'data-tasa="1"'], false);
    }

    public function test_preselecciona_la_venta_del_activo_indicado(): void
    {
        $response = $this->actingAs($this->asesor)
            ->get(route('portfolios.operar', [$this->portafolio, 'tipo' => 'VENTA', 'asset_id' => $this->aapl->id]));

        $response->assertSee('id="tipo-venta" name="tipo" value="VENTA" checked', false);
        $response->assertSee('<option value="'.$this->aapl->id.'" selected', false);
    }

    public function test_una_venta_rechazada_regresa_a_la_vista_con_el_error(): void
    {
        $this->depositar('10000', '2026-01-05');
        $operar = route('portfolios.operar', $this->portafolio);

        $response = $this->from($operar)->registrar([
            'tipo' => 'VENTA', 'fecha' => '2026-02-02', 'asset_id' => $this->naftrac->id, 'cantidad' => '5', 'precio' => '60',
        ]);

        $response->assertRedirect($operar);
        $response->assertSessionHasErrors('general');
        $this->assertSame(0, Transaction::where('tipo', 'VENTA')->count());
    }

    public function test_un_asesor_ajeno_no_puede_operar_el_portafolio(): void
    {
        $this->actingAs($this->otroAsesor)->get(route('portfolios.operar', $this->portafolio))->assertForbidden();
    }
}
