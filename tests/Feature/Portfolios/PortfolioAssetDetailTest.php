<?php

namespace Tests\Feature\Portfolios;

use App\Modules\Prices\Models\Price;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Detalle de un activo del portafolio: KPI de la posición, gráfica de cierres
 * con sus operaciones marcadas y dividendos esperados.
 */
class PortfolioAssetDetailTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');

        $this->depositar('10000', '2026-01-05');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50', 'comision' => '10'])
            ->assertSessionHasNoErrors();
        $this->registrar(['tipo' => 'VENTA', 'fecha' => '2026-02-10', 'asset_id' => $this->naftrac->id, 'cantidad' => '20', 'precio' => '60'])
            ->assertSessionHasNoErrors();
        // insert(): en SQLite el cast de fecha guardaría la hora y rompería la comparación de texto.
        Price::insert([
            ['asset_id' => $this->naftrac->id, 'fecha' => '2025-12-31', 'cierre' => '49'],
            ['asset_id' => $this->naftrac->id, 'fecha' => '2026-01-06', 'cierre' => '50'],
            ['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-10', 'cierre' => '60'],
            ['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '55'],
        ]);
    }

    public function test_muestra_los_kpi_de_la_posicion(): void
    {
        $response = $this->actingAs($this->usuario)->get(route('portfolios.activo', [$this->portafolio, $this->naftrac]));

        // Costo 100 × 50 + 10 = 5,010 → promedio 50.10. La venta de 20 se lleva 1,002 de costo: realizada 1,200 − 1,002 = 198.
        // Quedan 80 × 55 = 4,400 con costo 4,008: no realizada 392 (9.78 %). Efectivo 6,190 → peso 4,400 ÷ 10,590 = 41.55 %.
        // Rendimiento (392 + 198) ÷ 5,010 = 11.78 %.
        $response->assertOk()->assertSeeInOrder([
            'NAFTRAC', 'Comprar', 'Vender',
            'Títulos', '80', 'posición abierta',
            'Costo promedio', '50.10', 'Último precio', '55.00',
            'Valor de mercado', '4,400.00', '41.55 %',
            'G/P no realizada', '392.00', '9.78 %', 'G/P realizada', '198.00',
            'Rendimiento', '11.78 %', 'sobre 5,010.00 invertido',
        ]);
    }

    public function test_la_grafica_trae_los_cierres_desde_la_primera_operacion_y_las_operaciones(): void
    {
        $response = $this->actingAs($this->usuario)->get(route('portfolios.activo', [$this->portafolio, $this->naftrac]));

        $response->assertViewHas('grafica', function (array $grafica) {
            return $grafica['cierres'] === [['2026-01-06', 50.0], ['2026-02-10', 60.0], ['2026-02-27', 55.0]]
                && $grafica['marcadores'] === [
                    ['fecha' => '2026-01-06', 'tipo' => 'COMPRA', 'texto' => 'Compra 100 a 50.00 MXN'],
                    ['fecha' => '2026-02-10', 'tipo' => 'VENTA', 'texto' => 'Venta 20 a 60.00 MXN'],
                ]
                && $grafica['costo'] === 50.1
                && $grafica['cortes']['1M'] === '2026-02-02';
        });
    }

    public function test_el_dividendo_cobrado_se_puede_registrar(): void
    {
        $this->actingAs($this->usuario)->post(route('dividendos.store'), ['asset_id' => $this->naftrac->id, 'fecha_ex' => '2026-02-20', 'fecha_pago' => '2026-02-25', 'monto_por_titulo' => '0.85'])
            ->assertSessionHasNoErrors();

        // Al corte (19/02) quedaban 80 títulos: 80 × 0.85 = 68.
        $this->actingAs($this->usuario)->get(route('portfolios.activo', [$this->portafolio, $this->naftrac]))
            ->assertSeeInOrder(['Dividendos esperados', '20/02/2026', '25/02/2026', '0.85 MXN', '80', '68.00 MXN', 'Registrar cobro']);
    }

    public function test_holdings_enlaza_al_detalle(): void
    {
        $this->actingAs($this->usuario)->get(route('portfolios.show', [$this->portafolio, 'vista' => 'holdings']))
            ->assertSee(route('portfolios.activo', [$this->portafolio, $this->naftrac]));
    }

    public function test_un_activo_que_no_se_opero_en_el_portafolio_no_existe(): void
    {
        $this->actingAs($this->usuario)->get(route('portfolios.activo', [$this->portafolio, $this->aapl]))
            ->assertNotFound();
    }

    public function test_otro_usuario_no_ve_el_detalle(): void
    {
        $this->actingAs($this->otroUsuario)->get(route('portfolios.activo', [$this->portafolio, $this->naftrac]))
            ->assertNotFound();
    }
}
