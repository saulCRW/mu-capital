<?php

namespace Tests\Feature\Transactions;

use App\Modules\Transactions\Models\ExpectedDividend;
use App\Modules\Transactions\Services\DividendCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Hoy = 20/03/2026. El usuario deposita 100,000 y compra 1,000 NAFTRAC a 50 el 06/01.
 * NAFTRAC anuncia 0.85 por título: fecha ex 10/03, pago 15/03 → corte el 09/03.
 */
class DividendCalendarTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-20 12:00:00');
        $this->depositar('100000', '2026-01-05');
        $this->vender('2026-01-06', 'COMPRA', '1000');
    }

    private function vender(string $fecha, string $tipo = 'VENTA', string $cantidad = '400'): void
    {
        $this->registrar(['tipo' => $tipo, 'fecha' => $fecha, 'asset_id' => $this->naftrac->id, 'cantidad' => $cantidad, 'precio' => '50'])->assertSessionHasNoErrors();
    }

    private function dividendo(string $pago, ?string $ex = '2026-03-10'): void
    {
        $this->actingAs($this->usuario)->post(route('dividendos.store'), ['asset_id' => $this->naftrac->id, 'fecha_ex' => $ex, 'fecha_pago' => $pago, 'monto_por_titulo' => '0.85'])
            ->assertSessionHasNoErrors();
    }

    public function test_espera_titulos_al_corte_por_dividendo_por_titulo(): void
    {
        $this->dividendo('2026-03-15');
        // Vende 400 DESPUÉS de la fecha ex: cobra por los 1,000 que tenía al corte.
        $this->vender('2026-03-12');

        $response = $this->actingAs($this->usuario)->get(route('dividendos', ['mes' => '2026-03']));

        $response->assertSeeInOrder(['Marzo 2026', 'Esperado en el mes:', '850.00 MXN']);
        $response->assertSeeInOrder(['15/03/2026', '10/03/2026', 'NAFTRAC', '0.85 MXN', '1,000', '850.00 MXN', 'Principal: 850.00', 'Registrar cobro']);
        $response->assertSee(e(route('portfolios.transactions.create', [$this->portafolio, 'tipo' => 'DIVIDENDO', 'fecha' => '2026-03-15', 'asset_id' => $this->naftrac->id, 'cantidad' => '850', 'nota' => 'Dividendo de 0.85 MXN por título'])), false);
    }

    public function test_si_vende_antes_de_la_fecha_ex_espera_menos(): void
    {
        $this->vender('2026-03-05'); // le quedan 600 al corte: 600 × 0.85 = 510
        $this->dividendo('2026-03-15');

        $this->actingAs($this->usuario)->get(route('dividendos', ['mes' => '2026-03']))->assertSeeInOrder(['NAFTRAC', '600', '510.00 MXN']);
    }

    public function test_marca_el_cobro_registrado_y_los_pagos_futuros_quedan_por_cobrar(): void
    {
        $this->dividendo('2026-03-15');
        $this->dividendo('2026-04-10', null); // sin fecha ex y en el futuro: títulos de hoy
        $this->registrar(['tipo' => 'DIVIDENDO', 'fecha' => '2026-03-16', 'asset_id' => $this->naftrac->id, 'cantidad' => '850'])->assertSessionHasNoErrors();

        $this->actingAs($this->usuario)->get(route('dividendos', ['mes' => '2026-03']))->assertSee('registrado')->assertDontSee('>Registrar cobro</a>', false);
        $this->actingAs($this->usuario)->get(route('dividendos', ['mes' => '2026-04']))->assertSeeInOrder(['10/04/2026', '—', 'NAFTRAC', '850.00 MXN', 'por cobrar']);
    }

    public function test_el_formulario_de_transaccion_llega_lleno(): void
    {
        $this->actingAs($this->usuario)->get(route('portfolios.transactions.create', [$this->portafolio, 'tipo' => 'DIVIDENDO', 'fecha' => '2026-03-15', 'asset_id' => $this->naftrac->id, 'cantidad' => '850', 'nota' => 'Dividendo']))
            ->assertSee('<option value="DIVIDENDO" selected', false)
            ->assertSee('value="2026-03-15"', false)
            ->assertSee('value="850"', false);
    }

    public function test_cada_usuario_ve_y_borra_solo_los_suyos(): void
    {
        $this->dividendo('2026-03-15');
        $dividendo = ExpectedDividend::firstOrFail();
        $this->flushSession();

        $this->actingAs($this->otroUsuario)->get(route('dividendos', ['mes' => '2026-03']))->assertDontSee('NAFTRAC</strong>', false);
        $this->actingAs($this->otroUsuario)->delete(route('dividendos.destroy', $dividendo))->assertNotFound();
        $this->actingAs($this->usuario)->delete(route('dividendos.destroy', $dividendo))->assertSessionHas('status', 'Dividendo eliminado del calendario.');
        $this->assertModelMissing($dividendo);
    }

    public function test_valida_las_fechas(): void
    {
        $this->actingAs($this->usuario)->post(route('dividendos.store'), ['asset_id' => $this->naftrac->id, 'fecha_ex' => '2026-03-20', 'fecha_pago' => '2026-03-15', 'monto_por_titulo' => '0'])
            ->assertSessionHasErrors(['fecha_ex' => 'La fecha ex no puede ser posterior a la fecha de pago.', 'monto_por_titulo' => 'El dividendo por título debe ser mayor que cero.']);
    }

    public function test_semanas_del_mes_de_lunes_a_domingo(): void
    {
        // Octubre 2026 empieza en jueves y termina en sábado.
        $semanas = DividendCalendar::semanas(CarbonImmutable::parse('2026-10-01'));

        $this->assertCount(5, $semanas);
        $this->assertSame([null, null, null, '2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04'], $semanas[0]);
        $this->assertSame(['2026-10-26', '2026-10-27', '2026-10-28', '2026-10-29', '2026-10-30', '2026-10-31', null], $semanas[4]);
    }
}
