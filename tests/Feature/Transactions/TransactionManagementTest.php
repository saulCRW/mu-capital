<?php

namespace Tests\Feature\Transactions;

use App\Modules\Prices\Models\FxRate;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

class TransactionManagementTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    private function comprarNaftrac(string $cantidad, string $precio, string $fecha = '2026-01-06'): \Illuminate\Testing\TestResponse
    {
        return $this->registrar([
            'tipo' => 'COMPRA', 'fecha' => $fecha, 'asset_id' => $this->naftrac->id,
            'cantidad' => $cantidad, 'precio' => $precio, 'comision' => '10',
        ]);
    }

    public function test_registra_deposito_y_compra(): void
    {
        $this->depositar('10000')->assertRedirect(route('portfolios.show', $this->portafolio));
        $this->comprarNaftrac('100', '60')->assertSessionHasNoErrors();

        $compra = Transaction::where('tipo', 'COMPRA')->firstOrFail();
        $this->assertSame($this->asesor->id, $compra->created_by);
        $this->assertSame($this->org->id, $compra->organization_id);
        $this->assertSame('MXN', $compra->moneda);
        $this->assertSame('1.000000', $compra->tipo_cambio);
    }

    public function test_no_permite_vender_mas_de_lo_que_se_tiene(): void
    {
        $this->depositar('10000');
        $this->comprarNaftrac('100', '60');

        $this->registrar([
            'tipo' => 'VENTA', 'fecha' => '2026-02-02', 'asset_id' => $this->naftrac->id,
            'cantidad' => '150', 'precio' => '65',
        ])->assertSessionHasErrors(['general' => 'El 02/02/2026 se venderían 150 NAFTRAC, pero en esa fecha solo se tendrían 100.']);

        $this->assertSame(0, Transaction::where('tipo', 'VENTA')->count());
    }

    public function test_no_permite_gastar_efectivo_inexistente(): void
    {
        $this->depositar('1000');

        // 100 × 60 + 10 de comisión = 6,010 > 1,000
        $this->comprarNaftrac('100', '60')
            ->assertSessionHasErrors(['general' => 'El 06/01/2026 el efectivo quedaría en -5,010.00 MXN. No se puede gastar efectivo inexistente.']);
    }

    public function test_no_permite_borrar_el_deposito_que_fondea_una_compra(): void
    {
        $this->depositar('10000');
        $this->comprarNaftrac('100', '60');
        $deposito = Transaction::where('tipo', 'DEPOSITO')->firstOrFail();

        $this->actingAs($this->asesor)->delete(route('transactions.destroy', $deposito))
            ->assertSessionHasErrors('general');

        $this->assertModelExists($deposito);
    }

    public function test_editar_revalida_todo_el_libro(): void
    {
        $this->depositar('10000');
        $this->comprarNaftrac('100', '60');
        $compra = Transaction::where('tipo', 'COMPRA')->firstOrFail();

        $base = ['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'precio' => '60', 'comision' => '10'];

        // 200 × 60 + 10 = 12,010 > 10,000 → rechazado
        $this->actingAs($this->asesor)->put(route('transactions.update', $compra), $base + ['cantidad' => '200'])
            ->assertSessionHasErrors('general');
        $this->assertSame('100.000000', $compra->fresh()->cantidad);

        // 150 × 60 + 10 = 9,010 ≤ 10,000 → aceptado
        $this->actingAs($this->asesor)->put(route('transactions.update', $compra), $base + ['cantidad' => '150'])
            ->assertSessionHasNoErrors();
        $this->assertSame('150.000000', $compra->fresh()->cantidad);
    }

    public function test_usa_el_ultimo_tipo_de_cambio_registrado(): void
    {
        FxRate::create(['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => '2026-01-02', 'tasa' => '18.500000']);
        $this->depositar('100000');

        // Compra en USD el 06/01: no hay tasa de ese día, usa la del 02/01.
        $this->registrar([
            'tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->aapl->id,
            'cantidad' => '10', 'precio' => '240', 'moneda' => 'MXN', // la moneda capturada se ignora en COMPRA
        ])->assertSessionHasNoErrors();

        $compra = Transaction::where('tipo', 'COMPRA')->firstOrFail();
        $this->assertSame('USD', $compra->moneda);
        $this->assertSame('18.500000', $compra->tipo_cambio);
    }

    public function test_pide_tipo_de_cambio_si_no_hay_registrado(): void
    {
        $this->depositar('100000');

        $this->registrar([
            'tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->aapl->id,
            'cantidad' => '10', 'precio' => '240',
        ])->assertSessionHasErrors('tipo_cambio');
    }

    public function test_validaciones_basicas_del_formulario(): void
    {
        $this->registrar(['tipo' => 'DEPOSITO', 'fecha' => now()->addDay()->toDateString(), 'cantidad' => '100'])
            ->assertSessionHasErrors('fecha');

        $this->registrar(['tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => '100', 'asset_id' => $this->naftrac->id])
            ->assertSessionHasErrors('asset_id');

        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-05', 'cantidad' => '10'])
            ->assertSessionHasErrors(['asset_id', 'precio']);

        $this->registrar(['tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => '-5'])
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(0, Transaction::count());
    }

    public function test_un_asesor_ajeno_no_puede_registrar_ni_editar(): void
    {
        $this->depositar('1000');
        $deposito = Transaction::firstOrFail();

        $this->actingAs($this->otroAsesor)
            ->post(route('portfolios.transactions.store', $this->portafolio), ['tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => '5'])
            ->assertForbidden();

        $this->actingAs($this->otroAsesor)->get(route('transactions.edit', $deposito))->assertForbidden();
    }

    public function test_filtra_la_tabla_por_tipo(): void
    {
        $this->depositar('10000');
        $this->comprarNaftrac('100', '60');

        $this->actingAs($this->asesor)->get(route('portfolios.show', [$this->portafolio, 'tipo' => 'COMPRA']))
            ->assertOk()
            ->assertSee('NAFTRAC')
            ->assertDontSee('<td>DEPOSITO</td>', false);
    }
}
