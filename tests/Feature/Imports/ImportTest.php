<?php

namespace Tests\Feature\Imports;

use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\Feature\Concerns\CreaXlsx;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use CreaEscenario, CreaXlsx, RefreshDatabase;

    private const VALIDO = "tipo,fecha,simbolo,cantidad,precio,comision,nota\n"
        ."DEPOSITO,05/01/2026,,10000,,,Aportación\n"
        ."COMPRA,2026-01-06,NAFTRAC,100,50,10,\n"
        ."DIVIDENDO,2026-02-10,NAFTRAC,250,,,\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');
    }

    private function subir(string $csv): TestResponse
    {
        return $this->actingAs($this->usuario)->post(route('portfolios.importar.preview', $this->portafolio), [
            'archivo' => UploadedFile::fake()->createWithContent('movimientos.csv', $csv),
        ]);
    }

    public function test_muestra_la_vista_previa_sin_guardar_y_luego_importa_todo(): void
    {
        $this->subir(self::VALIDO)
            ->assertSee('se registrarán <strong>3</strong> transacciones', false)
            ->assertSeeInOrder(['DEPOSITO', '05/01/2026', '10,000', 'COMPRA', 'NAFTRAC', '100', '50', '10', 'DIVIDENDO', '250']);
        $this->assertSame(0, Transaction::count());

        $this->actingAs($this->usuario)->post(route('portfolios.importar.confirmar', $this->portafolio))
            ->assertRedirect(route('portfolios.show', $this->portafolio))
            ->assertSessionHas('status', 'Se importaron 3 transacciones.');

        $this->assertSame(['DEPOSITO', 'COMPRA', 'DIVIDENDO'], Transaction::orderBy('fecha')->pluck('tipo')->all());
        $this->assertDatabaseHas('transactions', ['tipo' => 'COMPRA', 'asset_id' => $this->naftrac->id, 'comision' => '10.000000', 'created_by' => $this->usuario->id]);
        // Efectivo: 10,000 − 5,010 + 250 = 5,240; NAFTRAC 100 × 50 = 5,000.
        $this->assertSame('10240.000000', PortfolioSnapshot::orderByDesc('fecha')->value('valor'));
    }

    public function test_marca_las_filas_con_activos_que_no_existen(): void
    {
        $this->subir("tipo,fecha,simbolo,cantidad,precio\nDEPOSITO,2026-01-05,,1000,\nCOMPRA,2026-01-06,NOEXISTE,1,10\n")
            ->assertSee('1 fila tiene errores.')
            ->assertSee('El activo NOEXISTE no existe en el catálogo; dalo de alta en Activos.');

        $this->actingAs($this->usuario)->post(route('portfolios.importar.confirmar', $this->portafolio))
            ->assertRedirect(route('portfolios.importar', $this->portafolio))
            ->assertSessionHasErrors(['general' => 'La vista previa ya no está disponible; vuelve a subir el archivo.']);
        $this->assertSame(0, Transaction::count());
    }

    public function test_valida_el_libro_completo_con_el_archivo_aplicado(): void
    {
        $this->subir("tipo,fecha,simbolo,cantidad,precio\nDEPOSITO,2026-01-05,,1000,\nCOMPRA,2026-01-06,NAFTRAC,100,50\n")
            ->assertSee('El 06/01/2026 el efectivo quedaría en -4,000.00 MXN. No se puede gastar efectivo inexistente.')
            ->assertDontSee('se registrarán');
    }

    public function test_muestra_juntos_los_errores_de_las_filas_y_los_del_libro(): void
    {
        $this->subir('tipo,fecha,simbolo,cantidad,precio
DEPOSITO,2026-01-05,,1000,
COMPRA,2026-01-06,NAFTRAC,100,50
COMPRA,2026-01-07,NOEXISTE,1,10
')
            ->assertSee('1 fila tiene errores.')
            ->assertSee('El activo NOEXISTE no existe en el catálogo; dalo de alta en Activos.')
            ->assertSee('Con solo las filas sin errores: El 06/01/2026 el efectivo quedaría en -4,000.00 MXN.');
    }

    public function test_importa_desde_excel(): void
    {
        $contenido = self::xlsx([
            1 => ['Tipo', 'Fecha', 'Símbolo', 'Cantidad', 'Precio'],
            2 => ['DEPOSITO', ['fecha' => 46027], null, 10000],
            3 => ['COMPRA', ['fecha' => 46028], 'NAFTRAC', 100, 50.1],
        ]);

        $this->actingAs($this->usuario)->post(route('portfolios.importar.preview', $this->portafolio), [
            'archivo' => UploadedFile::fake()->createWithContent('movimientos.xlsx', $contenido),
        ])->assertSessionHasNoErrors()
            ->assertSee('se registrarán <strong>2</strong> transacciones', false)
            ->assertSeeInOrder(['DEPOSITO', '05/01/2026', '10,000', 'COMPRA', '06/01/2026', 'NAFTRAC', '100', '50.1']);

        $this->actingAs($this->usuario)->post(route('portfolios.importar.confirmar', $this->portafolio))
            ->assertSessionHas('status', 'Se importaron 2 transacciones.');
        $this->assertDatabaseHas('transactions', ['tipo' => 'COMPRA', 'asset_id' => $this->naftrac->id, 'precio' => '50.100000']);
    }

    public function test_un_simbolo_repetido_en_varios_exchanges_pide_la_columna_exchange(): void
    {
        Asset::create(['simbolo' => 'NAFTRAC', 'nombre' => 'NAFTRAC en otro mercado', 'tipo' => 'etf', 'moneda' => 'MXN', 'exchange' => 'BIVA']);
        $this->naftrac->update(['exchange' => 'BMV']);

        $this->subir("tipo,fecha,simbolo,cantidad,precio\nCOMPRA,2026-01-06,NAFTRAC,1,50\n")
            ->assertSee('Hay varios activos NAFTRAC (BMV, BIVA); agrega la columna exchange.');
        $this->subir("tipo,fecha,simbolo,exchange,cantidad,precio\nDEPOSITO,2026-01-05,,,100,\nCOMPRA,2026-01-06,NAFTRAC,bmv,1,50\n")
            ->assertSee('se registrarán <strong>2</strong> transacciones', false);
    }

    public function test_el_limite_del_plan_cuenta_todo_el_archivo(): void
    {
        $this->usuario->plan->update(['max_transacciones_por_portafolio' => 2]);

        $this->subir(self::VALIDO)
            ->assertSee('Este portafolio tiene 0 y se intentan agregar 3.');
    }

    public function test_un_archivo_que_no_es_csv_se_rechaza(): void
    {
        $this->actingAs($this->usuario)->post(route('portfolios.importar.preview', $this->portafolio), [
            'archivo' => UploadedFile::fake()->create('estado.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('archivo');
    }

    public function test_descarga_la_plantilla(): void
    {
        $response = $this->actingAs($this->usuario)->get(route('portfolios.importar.plantilla', $this->portafolio));

        $response->assertDownload('plantilla-transacciones.csv');
        $this->assertStringContainsString('tipo,fecha,simbolo,exchange,cantidad,precio,comision,moneda,tipo_cambio,nota', $response->streamedContent());
    }

    public function test_solo_el_dueno_importa_a_su_portafolio(): void
    {
        $this->actingAs($this->otroUsuario)->get(route('portfolios.importar', $this->portafolio))->assertNotFound();
        $this->actingAs($this->otroUsuario)->post(route('portfolios.importar.preview', $this->portafolio), [
            'archivo' => UploadedFile::fake()->createWithContent('m.csv', self::VALIDO),
        ])->assertNotFound();
    }
}
