<?php

namespace Tests\Feature\Reports;

use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Prices\Models\BenchmarkValue;
use App\Modules\Prices\Models\Price;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Ejemplo a mano (hoy = 15/07/2026, así que el último trimestre cerrado es 2T 2026):
 *   05/01 deposita 10,000; 06/01 compra 100 NAFTRAC @ 50 → efectivo 5,000.
 *   31/03 cierre 55 → vale 5,000 + 5,500 = 10,500.   1T: ganancia 500, TWR +5%.
 *   01/04 deposita 1,000 → 11,500 (r = 11,500 ÷ (10,500 + 1,000) − 1 = 0).
 *   30/06 cierre 60 → vale 6,000 + 6,000 = 12,000.   2T: ganancia 500, TWR = 12,000 ÷ 11,500 − 1 = +4.35%.
 *   Año: aportaciones 11,000, ganancia 1,000, TWR = 1.05 × 1.043478 − 1 = +9.57%.
 */
class QuarterlyReportTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-07-15 12:00:00');
    }

    private function cargarEjemplo(): void
    {
        $this->depositar('10000', '2026-01-05');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50']);
        $this->depositar('1000', '2026-04-01');
        // insert(): en SQLite el cast de fecha guardaría la hora y rompería la comparación de texto.
        Price::insert([
            ['asset_id' => $this->naftrac->id, 'fecha' => '2026-03-31', 'cierre' => '55'],
            ['asset_id' => $this->naftrac->id, 'fecha' => '2026-06-30', 'cierre' => '60'],
        ]);
        app(SnapshotService::class)->recalcular($this->portafolio, null, '2026-07-15');
    }

    public function test_por_omision_muestra_el_ultimo_trimestre_cerrado(): void
    {
        $this->cargarEjemplo();

        $response = $this->actingAs($this->asesor)->get(route('portfolios.reporte', $this->portafolio));

        $response->assertSeeInOrder(['Reporte trimestral · 2T 2026', 'Periodo: 01/04/2026 – 30/06/2026']);
        $response->assertSeeInOrder([
            'Valor al 30/06/2026', '12,000.00', 'inicial: 10,500.00',
            'Aportaciones netas', '1,000.00',
            'Ganancia del trimestre', '500.00',
            'Rendimiento (TWR)', '4.35 %',
        ]);
        $response->assertSeeInOrder([
            '1T 2026', '0.00', '10,000.00', '500.00', '10,500.00', '5.00 %',
            '2T 2026', '10,500.00', '1,000.00', '500.00', '12,000.00', '4.35 %',
            'Año a la fecha', '0.00', '11,000.00', '1,000.00', '12,000.00', '9.57 %',
        ]);
        $response->assertSeeInOrder(['Movimientos del trimestre', '01/04/2026', 'DEPOSITO']);
        $response->assertDontSee('06/01/2026');
    }

    /**
     * El índice no tiene valor al 31/12/2025: como el portafolio empezó en el 1T,
     * el benchmark del 1T y del año se mide desde su primer día (05/01).
     */
    public function test_compara_cada_trimestre_con_el_benchmark(): void
    {
        $benchmark = Benchmark::create(['nombre' => 'IPC']);
        $this->portafolio->update(['benchmark_id' => $benchmark->id]);
        BenchmarkValue::insert([
            ['benchmark_id' => $benchmark->id, 'fecha' => '2026-01-05', 'valor' => '100'],
            ['benchmark_id' => $benchmark->id, 'fecha' => '2026-03-31', 'valor' => '110'],
            ['benchmark_id' => $benchmark->id, 'fecha' => '2026-06-30', 'valor' => '121'],
        ]);
        $this->cargarEjemplo();

        $response = $this->actingAs($this->asesor)->get(route('portfolios.reporte', $this->portafolio));

        $response->assertSeeInOrder([
            '1T 2026', '5.00 %', '10.00 %',
            '2T 2026', '4.35 %', '10.00 %',
            'Año a la fecha', '9.57 %', '21.00 %',
        ]);
    }

    public function test_el_primer_trimestre_no_incluye_los_siguientes(): void
    {
        $this->cargarEjemplo();

        $response = $this->actingAs($this->asesor)->get(route('portfolios.reporte', [$this->portafolio, 'anio' => 2026, 'trimestre' => 1]));

        $response->assertSeeInOrder(['Reporte trimestral · 1T 2026', 'Valor al 31/03/2026', '10,500.00', 'Rendimiento (TWR)', '5.00 %']);
        $response->assertDontSee('2T 2026');
    }

    public function test_un_trimestre_sin_datos_lo_indica(): void
    {
        $this->cargarEjemplo();

        $response = $this->actingAs($this->asesor)->get(route('portfolios.reporte', [$this->portafolio, 'anio' => 2025, 'trimestre' => 4]));

        $response->assertSee('El portafolio no tiene movimientos ni valuaciones en este trimestre.');
    }

    public function test_rechaza_un_trimestre_que_no_ha_empezado(): void
    {
        $response = $this->actingAs($this->asesor)->get(route('portfolios.reporte', [$this->portafolio, 'anio' => 2026, 'trimestre' => 4]));

        $response->assertSessionHasErrors(['trimestre' => 'Ese trimestre todavía no empieza.']);
    }

    public function test_descarga_el_reporte_en_pdf(): void
    {
        $this->cargarEjemplo();

        $response = $this->actingAs($this->asesor)->get(route('portfolios.reporte.pdf', [$this->portafolio, 'anio' => 2026, 'trimestre' => 2]));

        $response->assertDownload('reporte-cliente-uno-principal-2T2026.pdf');
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_la_pagina_muestra_las_graficas_y_el_enlace_al_pdf(): void
    {
        $this->cargarEjemplo();

        $response = $this->actingAs($this->asesor)->get(route('portfolios.reporte', $this->portafolio));

        $response->assertSee('aria-label="Valor del portafolio y capital aportado"', false);
        $response->assertSee(e(route('portfolios.reporte.pdf', [$this->portafolio, 'anio' => 2026, 'trimestre' => 2])), false);
    }

    public function test_un_asesor_ajeno_no_puede_ver_el_reporte(): void
    {
        $this->actingAs($this->otroAsesor)->get(route('portfolios.reporte', $this->portafolio))->assertForbidden();
    }
}
