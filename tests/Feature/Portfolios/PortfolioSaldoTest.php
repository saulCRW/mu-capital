<?php

namespace Tests\Feature\Portfolios;

use App\Modules\Prices\Models\Price;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Página del portafolio: KPI valuados a hoy con el último cierre registrado
 * de cada activo, y las pestañas Resumen y Holdings.
 */
class PortfolioSaldoTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');
    }

    public function test_muestra_los_kpi_valuados_al_ultimo_cierre(): void
    {
        $this->escenarioConCompra();

        $response = $this->actingAs($this->usuario)->get(route('portfolios.show', $this->portafolio));

        // Efectivo: 10,000 − 100 × 50 − 10 de comisión = 4,990. Posición: 100 × 55 = 5,500. Total 10,490.
        // G/P total 490 sobre 10,000 aportados = 4.90 %; en 56 días (05/01 → 02/03) anualizado = 1.049^(365/56) − 1.
        $anualizado = number_format((1.049 ** (365 / 56) - 1) * 100, 2).' %';
        $response->assertSeeInOrder([
            'al 02/03/2026',
            'Valor total', '10,490.00',
            'Efectivo', '4,990.00', '47.57 %',
            'G/P total', '490.00', '4.90 %', 'sobre 10,000.00 aportado',
            'Rendimiento anualizado', $anualizado, 'desde el 05/01/2026',
            'Holdings', '1', 'posición abierta',
        ]);
    }

    public function test_el_resumen_trae_grafica_asignacion_y_top(): void
    {
        $this->escenarioConCompra();

        $response = $this->actingAs($this->usuario)->get(route('portfolios.show', $this->portafolio));

        $response->assertSee('id="grafica-datos"', false)->assertSee('TradingView Lightweight Charts');
        // Asignación por clase: ETF 5,500 / 10,490 = 52.43 %, efectivo 47.57 %.
        $response->assertSeeInOrder(['Asignación', 'Clase de activo', 'ETF', '52.43 %', 'Efectivo', '47.57 %', 'Sector', 'Moneda', 'MXN', '100.00 %']);
        // NAFTRAC gana 490 sobre un costo de 5,010 = 9.78 %.
        $response->assertSeeInOrder(['Ganadores', 'NAFTRAC', '490.00', '+9.78 %', 'Perdedores', 'Ninguna posición en pérdida.']);
    }

    public function test_holdings_muestra_cada_posicion_con_su_rendimiento(): void
    {
        $this->escenarioConCompra();

        $response = $this->actingAs($this->usuario)->get(route('portfolios.show', [$this->portafolio, 'vista' => 'holdings']));

        // Costo promedio con la comisión: 5,010 / 100 = 50.10. Peso 5,500 / 10,490 = 52.43 %.
        $response->assertSeeInOrder(['NAFTRAC', '100', '50.10 MXN', '55.00', 'MXN', '5,500.00', '52.43 %', '490.00', '0.00', '9.78 %', 'Vender']);
        $response->assertSee('data-posicion="'.$this->naftrac->id.'"', false);
    }

    public function test_holdings_ordena_y_agrupa_con_subtotales(): void
    {
        $this->escenarioConCompra();
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-07', 'asset_id' => $this->aapl->id, 'cantidad' => '1', 'precio' => '200', 'tipo_cambio' => '18'])
            ->assertSessionHasNoErrors();

        // AAPL vale 1 × 200 × 18 = 3,600 MXN: por valor descendente va después de NAFTRAC (5,500).
        $this->actingAs($this->usuario)->get(route('portfolios.show', [$this->portafolio, 'vista' => 'holdings']))
            ->assertSeeInOrder(['NAFTRAC', 'AAPL']);
        $this->actingAs($this->usuario)->get(route('portfolios.show', [$this->portafolio, 'vista' => 'holdings', 'orden' => 'valor', 'dir' => 'asc']))
            ->assertSeeInOrder(['AAPL', 'NAFTRAC']);

        $agrupada = $this->actingAs($this->usuario)->get(route('portfolios.show', [$this->portafolio, 'vista' => 'holdings', 'agrupar' => 'moneda']));
        $agrupada->assertSeeInOrder(['MXN', 'NAFTRAC', 'Subtotal MXN', '5,500.00', 'USD', 'AAPL', 'Subtotal USD', '3,600.00', 'Total posiciones', '9,100.00']);
        // Agrupada no se actualiza en vivo (los subtotales quedarían desfasados).
        $agrupada->assertDontSee('data-posicion="'.$this->naftrac->id.'"', false);
    }

    public function test_las_posiciones_vendidas_solo_aparecen_si_se_piden(): void
    {
        $this->depositar('10000', '2026-01-05');
        $this->comprar('100', '50', '2026-01-06');
        $this->registrar(['tipo' => 'VENTA', 'fecha' => '2026-01-07', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '60']);

        $this->actingAs($this->usuario)->get(route('portfolios.show', $this->portafolio))
            ->assertSeeInOrder(['Valor total', '11,000.00', 'Holdings', '0', 'posiciones abiertas']);
        $this->actingAs($this->usuario)->get(route('portfolios.show', [$this->portafolio, 'vista' => 'holdings']))
            ->assertSee('No hay posiciones abiertas.');
        // Vendió 100 a 60 lo que costó 50: G/P realizada 1,000 = 20 % sobre 5,000 invertidos.
        $this->actingAs($this->usuario)->get(route('portfolios.show', [$this->portafolio, 'vista' => 'holdings', 'vendidas' => 1]))
            ->assertSeeInOrder(['NAFTRAC', 'vendida', '1,000.00', '20.00 %']);
    }

    public function test_una_vista_que_no_existe_se_rechaza(): void
    {
        $this->actingAs($this->usuario)->get(route('portfolios.show', [$this->portafolio, 'vista' => 'otra']))
            ->assertSessionHasErrors('vista');
    }

    public function test_un_portafolio_sin_transacciones_no_muestra_saldo(): void
    {
        $response = $this->actingAs($this->usuario)->get(route('portfolios.show', $this->portafolio));

        $response->assertDontSee('Valor total');
        $response->assertSee('Este portafolio todavía no tiene transacciones.');
    }

    private function escenarioConCompra(): void
    {
        $this->depositar('10000', '2026-01-05');
        $this->comprar('100', '50', '2026-01-06', '10');
        // insert(): en SQLite el cast de fecha guardaría la hora y rompería la comparación de texto.
        Price::insert(['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '55']);
    }

    private function comprar(string $cantidad, string $precio, string $fecha, string $comision = '0'): void
    {
        $this->registrar([
            'tipo' => 'COMPRA', 'fecha' => $fecha, 'asset_id' => $this->naftrac->id,
            'cantidad' => $cantidad, 'precio' => $precio, 'comision' => $comision,
        ])->assertSessionHasNoErrors();
    }
}
