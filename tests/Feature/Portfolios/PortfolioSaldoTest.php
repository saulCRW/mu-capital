<?php

namespace Tests\Feature\Portfolios;

use App\Modules\Prices\Models\Price;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Vista de saldo actual en la página del portafolio: valuada a hoy con el
 * último cierre registrado de cada activo.
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

    public function test_muestra_el_saldo_valuado_al_ultimo_cierre(): void
    {
        $this->depositar('10000', '2026-01-05');
        $this->comprar('100', '50', '2026-01-06', '10');
        // insert(): en SQLite el cast de fecha guardaría la hora y rompería la comparación de texto.
        Price::insert(['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '55']);

        $response = $this->actingAs($this->usuario)->get(route('portfolios.show', $this->portafolio));

        // Efectivo: 10,000 − 100 × 50 − 10 de comisión = 4,990. Posición: 100 × 55 = 5,500.
        // Costo promedio con la comisión: 5,010 / 100 = 50.10 → G/P no realizada 490 = 9.78 %; peso 5,500 / 10,490.
        $response->assertSeeInOrder([
            'Saldo actual', 'al 02/03/2026',
            'Valor total', '10,490.00',
            'Efectivo', '4,990.00',
            'Posiciones', '5,500.00', '1 activo',
            'Aportaciones netas', '10,000.00',
            'Ganancia / pérdida total', '490.00', '4.90 %',
        ]);
        $response->assertSeeInOrder(['NAFTRAC', '100', '50.10', '55.00', 'MXN', '5,500.00', '490.00', '9.78 %', '52.43 %']);
    }

    public function test_no_lista_posiciones_cerradas(): void
    {
        $this->depositar('10000', '2026-01-05');
        $this->comprar('100', '50', '2026-01-06');
        $this->registrar(['tipo' => 'VENTA', 'fecha' => '2026-01-07', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '60']);

        $response = $this->actingAs($this->usuario)->get(route('portfolios.show', $this->portafolio));

        $response->assertSeeInOrder(['Valor total', '11,000.00', 'Posiciones', '0.00', '0 activos']);
        $response->assertDontSee('Costo prom.');
    }

    public function test_un_portafolio_sin_transacciones_no_muestra_saldo(): void
    {
        $response = $this->actingAs($this->usuario)->get(route('portfolios.show', $this->portafolio));

        $response->assertDontSee('Saldo actual');
        $response->assertSee('Este portafolio todavía no tiene transacciones.');
    }

    private function comprar(string $cantidad, string $precio, string $fecha, string $comision = '0'): void
    {
        $this->registrar([
            'tipo' => 'COMPRA', 'fecha' => $fecha, 'asset_id' => $this->naftrac->id,
            'cantidad' => $cantidad, 'precio' => $precio, 'comision' => $comision,
        ])->assertSessionHasNoErrors();
    }
}
