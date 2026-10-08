<?php

namespace Tests\Feature\Users;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Services\UserDataExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;
use ZipArchive;

/** "Descargar mis datos": un .zip con un CSV por tema, solo con lo del usuario. */
class UserDataExportTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    private const ARCHIVOS = [
        'cuenta.csv', 'portafolios.csv', 'transacciones.csv', 'asignacion_objetivo.csv',
        'lista_mercado.csv', 'alertas_precio.csv', 'dividendos_esperados.csv',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');

        $this->depositar('10000', '2026-01-05');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50.25', 'comision' => '10', 'nota' => 'Primera, "con comillas"'])
            ->assertSessionHasNoErrors();
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-07', 'asset_id' => $this->aapl->id, 'cantidad' => '1', 'precio' => '200', 'tipo_cambio' => '18'])
            ->assertSessionHasNoErrors();
    }

    public function test_el_zip_trae_un_csv_por_tema(): void
    {
        $archivos = $this->descargar();

        $this->assertSame(self::ARCHIVOS, array_keys($archivos));
        $this->assertSame(['nombre', 'correo', 'plan', 'tasa_libre_riesgo_pct', 'alta', 'doble_factor'], $archivos['cuenta.csv'][0]);
        $this->assertSame([$this->usuario->nombre, $this->usuario->email], array_slice($archivos['cuenta.csv'][1], 0, 2));
        $this->assertSame(['Principal', 'MXN', '', $this->portafolio->created_at->toDateString(), '3'], $archivos['portafolios.csv'][1]);
    }

    public function test_trae_las_transacciones_de_todos_sus_portafolios_y_nada_ajeno(): void
    {
        $segundo = Portfolio::create(['user_id' => $this->usuario->id, 'nombre' => 'Ahorro', 'moneda_base' => 'MXN']);
        $this->actingAs($this->usuario)->post(route('portfolios.transactions.store', $segundo), ['tipo' => 'DEPOSITO', 'fecha' => '2026-02-01', 'cantidad' => '500'])
            ->assertSessionHasNoErrors();
        $ajeno = Portfolio::create(['user_id' => $this->otroUsuario->id, 'nombre' => 'Ajeno', 'moneda_base' => 'MXN']);
        $this->actingAs($this->otroUsuario)->post(route('portfolios.transactions.store', $ajeno), ['tipo' => 'DEPOSITO', 'fecha' => '2026-02-01', 'cantidad' => '999'])
            ->assertSessionHasNoErrors();

        $this->assertSame([
            ['portafolio', ...UserDataExport::COLUMNAS_TRANSACCIONES],
            ['Ahorro', 'DEPOSITO', '2026-02-01', '', '', '500', '', '', 'MXN', '', ''],
            ['Principal', 'DEPOSITO', '2026-01-05', '', '', '10000', '', '', 'MXN', '', ''],
            ['Principal', 'COMPRA', '2026-01-06', 'NAFTRAC', 'BMV', '100', '50.25', '10', 'MXN', '', 'Primera, "con comillas"'],
            ['Principal', 'COMPRA', '2026-01-07', 'AAPL', 'NASDAQ', '1', '200', '', 'USD', '18', ''],
        ], $this->descargar()['transacciones.csv']);
    }

    public function test_las_transacciones_de_un_portafolio_se_vuelven_a_importar_igual(): void
    {
        $filas = array_slice($this->descargar()['transacciones.csv'], 1);
        $csv = implode(',', UserDataExport::COLUMNAS_TRANSACCIONES)."\n";
        foreach ($filas as $fila) {
            $csv .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', $v).'"', array_slice($fila, 1)))."\n";
        }
        $copia = Portfolio::create(['user_id' => $this->usuario->id, 'nombre' => 'Copia', 'moneda_base' => 'MXN']);

        $this->actingAs($this->usuario)->post(route('portfolios.importar.preview', $copia), ['archivo' => UploadedFile::fake()->createWithContent('transacciones.csv', $csv)])
            ->assertOk();
        $this->post(route('portfolios.importar.confirmar', $copia))->assertSessionHas('status', 'Se importaron 3 transacciones.');

        $campos = ['tipo', 'fecha', 'asset_id', 'cantidad', 'precio', 'comision', 'moneda', 'tipo_cambio', 'nota'];
        $comoArreglo = fn (int $portfolioId) => Transaction::where('portfolio_id', $portfolioId)->orderBy('fecha')->get()
            ->map(fn (Transaction $t) => array_map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v, $t->only($campos)))->all();
        $this->assertSame($comoArreglo($this->portafolio->id), $comoArreglo($copia->id));
    }

    public function test_no_incluye_la_contrasena_ni_el_doble_factor(): void
    {
        $this->usuario->forceFill(['dos_factores_secreto' => 'JBSWY3DPEHPK3PXP', 'dos_factores_confirmado_en' => now(), 'dos_factores_recuperacion' => ['huella']])->save();

        $todo = json_encode($this->descargar());

        $this->assertStringNotContainsString($this->usuario->password, $todo);
        $this->assertStringNotContainsString('JBSWY3DPEHPK3PXP', $todo);
        $this->assertStringNotContainsString('huella', $todo);
        $this->assertStringContainsString('"si"', $todo);
    }

    public function test_un_invitado_va_al_login(): void
    {
        auth()->logout();

        $this->get(route('cuenta.exportar'))->assertRedirect(route('login'));
    }

    /** @return array<string, list<list<string>>> archivo => filas */
    private function descargar(): array
    {
        /** @var TestResponse $response */
        $response = $this->actingAs($this->usuario)->get(route('cuenta.exportar'));
        $response->assertOk()->assertDownload('mu-intelligence-mis-datos-2026-03-02.zip');

        $zip = new ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());
        $archivos = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $zip->getFromIndex($i));
            $lineas = array_filter(explode("\n", $contenido), fn ($l) => $l !== '');
            $archivos[$zip->getNameIndex($i)] = array_values(array_map(fn ($l) => str_getcsv($l, ',', '"', ''), $lineas));
        }
        $zip->close();

        return $archivos;
    }
}
