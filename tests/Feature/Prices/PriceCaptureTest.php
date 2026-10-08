<?php

namespace Tests\Feature\Prices;

use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Prices\Models\BenchmarkValue;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use App\Modules\Users\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\Feature\Concerns\CreaXlsx;
use Tests\TestCase;

/**
 * Pantalla de Precios (superadmin). El usuario deposita 10,000 y compra 100 NAFTRAC a 50:
 * 5,000 de efectivo + 100 × cierre. Con un cierre de 60 el portafolio vale 5,000 + 6,000 = 11,000.
 */
class PriceCaptureTest extends TestCase
{
    use CreaEscenario, CreaXlsx, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');
        $this->depositar('10000', '2026-01-05');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50'])
            ->assertSessionHasNoErrors();
    }

    private function ultimoValor(): string
    {
        // Sin sesión la consulta no se filtra por dueño (ver TenantContext).
        auth()->logout();
        $this->assertTrue(TenantContext::isGlobal());

        return PortfolioSnapshot::where('portfolio_id', $this->portafolio->id)->orderByDesc('fecha')->value('valor');
    }

    public function test_capturar_un_cierre_revalua_los_portafolios_que_tienen_el_activo(): void
    {
        $this->actingAs($this->superadmin)->post(route('superadmin.precios.store'), ['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '60'])
            ->assertRedirect(route('superadmin.precios.index', ['asset_id' => $this->naftrac->id]))
            ->assertSessionHas('status', 'Cierre de NAFTRAC guardado; los portafolios que lo tienen se recalcularon.');

        $this->assertSame('60.000000', Price::where('asset_id', $this->naftrac->id)->value('cierre'));
        $this->assertSame('11000.000000', $this->ultimoValor());

        // Capturar otra vez la misma fecha reemplaza el dato.
        $this->actingAs($this->superadmin)->post(route('superadmin.precios.store'), ['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '55']);
        $this->assertSame(1, Price::count());
        $this->assertSame('10500.000000', $this->ultimoValor());
    }

    public function test_captura_tipos_de_cambio_en_mayusculas(): void
    {
        $this->actingAs($this->superadmin)->post(route('superadmin.precios.tasas.store'), ['moneda_origen' => 'usd', 'moneda_destino' => 'mxn', 'fecha' => '2026-02-27', 'tasa' => '18.25'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fx_rates', ['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'tasa' => '18.250000']);
        $this->actingAs($this->superadmin)->get(route('superadmin.precios.index', ['tab' => 'tipos']))->assertSeeInOrder(['27/02/2026', 'USD', 'MXN', '18.25']);
    }

    public function test_valida_la_captura(): void
    {
        $this->actingAs($this->superadmin)->post(route('superadmin.precios.store'), ['asset_id' => $this->naftrac->id, 'fecha' => '2026-03-03', 'cierre' => '0'])
            ->assertSessionHasErrors(['fecha' => 'La fecha no puede ser futura.', 'cierre' => 'El cierre debe ser mayor que cero.']);
        $this->actingAs($this->superadmin)->post(route('superadmin.precios.tasas.store'), ['moneda_origen' => 'MXN', 'moneda_destino' => 'MXN', 'fecha' => '2026-02-27', 'tasa' => '1'])
            ->assertSessionHasErrors(['moneda_destino' => 'Las dos monedas deben ser distintas.']);
    }

    public function test_importa_precios_desde_csv(): void
    {
        $csv = "Símbolo;Fecha;Cierre\nNAFTRAC;26/02/2026;58\nNAFTRAC;2026-02-27;\"1,060.5\"\n";

        $this->actingAs($this->superadmin)->post(route('superadmin.precios.importar'), ['archivo' => UploadedFile::fake()->createWithContent('precios.csv', $csv)])
            ->assertSessionHas('status', 'Se importaron 2 precios; los portafolios se recalcularon.');

        $this->assertSame(['58.000000', '1060.500000'], Price::orderBy('fecha')->pluck('cierre')->all());
    }

    public function test_si_una_fila_falla_no_importa_nada(): void
    {
        $csv = "simbolo,fecha,cierre\nNAFTRAC,2026-02-26,58\nNOEXISTE,2026-02-26,10\nNAFTRAC,31/02/2026,-1\n";

        $this->actingAs($this->superadmin)->followingRedirects()
            ->post(route('superadmin.precios.importar'), ['archivo' => UploadedFile::fake()->createWithContent('precios.csv', $csv)])
            ->assertSee('No se importó nada')
            ->assertSee('Línea 3: el activo NOEXISTE no existe en el catálogo.')
            ->assertSee('Línea 4: fecha &quot;31/02/2026&quot; no válida (AAAA-MM-DD o DD/MM/AAAA); cierre &quot;-1&quot; debe ser un número mayor que cero.', false);

        $this->assertSame(0, Price::count());
    }

    public function test_importa_tipos_de_cambio_desde_excel(): void
    {
        $contenido = self::xlsx([
            1 => ['moneda_origen', 'moneda_destino', 'fecha', 'tasa'],
            2 => ['USD', 'MXN', ['fecha' => 46080], 17.95],
        ]);

        $this->actingAs($this->superadmin)->post(route('superadmin.precios.importar'), ['archivo' => UploadedFile::fake()->createWithContent('tipos.xlsx', $contenido)])
            ->assertRedirect(route('superadmin.precios.index', ['tab' => 'tipos']))
            ->assertSessionHas('status', 'Se importaron 1 tipos de cambio; los portafolios se recalcularon.');

        $this->assertSame(1, FxRate::where('moneda_origen', 'USD')->where('tasa', '17.95')->count());
    }

    /**
     * CETES no está en Yahoo: se crea el índice y se capturan sus valores a mano.
     * Índice acumulado 100 el 31/12/2025 y 101.2 el 27/02/2026 → +1.20 % en el periodo.
     */
    public function test_crea_un_indice_y_captura_sus_valores_para_usarlo_de_benchmark(): void
    {
        $this->actingAs($this->superadmin)->post(route('superadmin.precios.indices.store'), ['nombre' => 'CETES 28'])
            ->assertSessionHasNoErrors();
        $cetes = Benchmark::where('nombre', 'CETES 28')->firstOrFail();
        $this->assertNull($cetes->simbolo_proveedor);

        $this->actingAs($this->superadmin)->post(route('superadmin.precios.indices.valores.store'), ['benchmark_id' => $cetes->id, 'fecha' => '2025-12-31', 'valor' => '100'])
            ->assertRedirect(route('superadmin.precios.index', ['tab' => 'indices', 'benchmark_id' => $cetes->id]));
        $csv = "Índice,Fecha,Valor\nCETES 28,27/02/2026,101.2\n";
        $this->actingAs($this->superadmin)->post(route('superadmin.precios.importar'), ['archivo' => UploadedFile::fake()->createWithContent('cetes.csv', $csv)])
            ->assertSessionHas('status', 'Se importaron 1 valores de índices.');

        $this->assertSame(['100.000000', '101.200000'], BenchmarkValue::where('benchmark_id', $cetes->id)->orderBy('fecha')->pluck('valor')->all());
        $this->actingAs($this->superadmin)->get(route('superadmin.precios.index', ['tab' => 'indices']))->assertSeeInOrder(['27/02/2026', 'CETES 28', '101.2']);

        // El usuario lo elige como benchmark y lo ve en el análisis.
        $this->portafolio->update(['benchmark_id' => $cetes->id]);
        $this->actingAs($this->usuario)->get(route('portfolios.show', [$this->portafolio, 'vista' => 'analisis']))
            ->assertSeeInOrder(['Benchmark (CETES 28)', '+1.20 %']);
    }

    public function test_no_repite_indices_ni_importa_valores_de_uno_que_no_existe(): void
    {
        $this->actingAs($this->superadmin)->post(route('superadmin.precios.indices.store'), ['nombre' => 'IPC'])->assertSessionHasNoErrors();
        $this->actingAs($this->superadmin)->post(route('superadmin.precios.indices.store'), ['nombre' => 'IPC'])
            ->assertSessionHasErrors(['nombre' => 'Ya existe un índice con ese nombre.']);

        $this->actingAs($this->superadmin)->followingRedirects()
            ->post(route('superadmin.precios.importar'), ['archivo' => UploadedFile::fake()->createWithContent('i.csv', "indice,fecha,valor\nCETES,2026-02-27,100\n")])
            ->assertSee('Línea 2: el índice &quot;CETES&quot; no existe; créalo primero en la pestaña Índices.', false);
        $this->assertSame(0, BenchmarkValue::count());
    }

    public function test_solo_el_superadmin_entra_a_precios(): void
    {
        $this->actingAs($this->usuario)->get(route('superadmin.precios.index'))->assertForbidden();
        $this->actingAs($this->usuario)->post(route('superadmin.precios.store'), ['asset_id' => $this->naftrac->id, 'fecha' => '2026-02-27', 'cierre' => '60'])->assertForbidden();
        $this->assertSame(0, Price::count());
    }
}
