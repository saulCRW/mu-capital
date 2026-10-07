<?php

namespace Tests\Feature\Rebalancing;

use App\Modules\Portfolios\Models\TargetAllocation;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Ejemplo a mano: deposita 10,000 y compra 100 NAFTRAC (ETF) @ 50 → ETF 5,000 (50 %), efectivo 5,000 (50 %).
 * Objetivo ETF 30 % / efectivo 70 %: el ETF está 20 puntos arriba → vender 2,000 = 40 títulos.
 */
class RebalanceTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');

        $this->depositar('10000', '2026-01-05');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '100', 'precio' => '50']);
    }

    private function objetivo(array $porcentajes): void
    {
        foreach ($porcentajes as $clave => $pct) {
            TargetAllocation::create(['organization_id' => $this->org->id, 'portfolio_id' => $this->portafolio->id, 'dimension' => 'clase', 'clave' => $clave, 'porcentaje_objetivo' => $pct]);
        }
    }

    public function test_compara_y_sugiere_las_operaciones_para_volver_al_objetivo(): void
    {
        $this->objetivo(['etf' => 30, 'efectivo' => 70]);

        $response = $this->actingAs($this->asesor)->get(route('portfolios.rebalanceo', $this->portafolio));

        $response->assertSee('2 grupos están fuera de la banda.');
        $response->assertSeeInOrder(['Efectivo', '5,000.00', '50.00 %', '70.00 %', '-20.00 pp', 'Comprar 2,000.00']);
        $response->assertSeeInOrder(['ETF', '5,000.00', '50.00 %', '30.00 %', '+20.00 pp', 'Vender 2,000.00', 'Fuera de banda']);
        $response->assertSeeInOrder(['Operaciones sugeridas', 'Vender', 'NAFTRAC', 'ETF', '40', '2,000.00']);
        $response->assertDontSee('elige cuál comprar');
        $response->assertSee(e(route('portfolios.operar', [$this->portafolio, 'tipo' => 'VENTA', 'asset_id' => $this->naftrac->id, 'cantidad' => '40'])), false);
    }

    public function test_dentro_de_la_banda_no_sugiere_operaciones(): void
    {
        $this->objetivo(['etf' => 47, 'efectivo' => 53]);

        $response = $this->actingAs($this->asesor)->get(route('portfolios.rebalanceo', $this->portafolio));

        $response->assertSee('Todos los grupos están dentro de la banda.');
        $response->assertDontSee('Operaciones sugeridas');
    }

    public function test_guarda_la_asignacion_objetivo_que_suma_100(): void
    {
        $this->objetivo(['etf' => 30, 'efectivo' => 70]);

        $this->actingAs($this->asesor)->put(route('portfolios.rebalanceo.update', $this->portafolio), [
            'dimension' => 'clase',
            'objetivos' => ['etf' => '40', 'efectivo' => '50', 'accion' => ''],
            'nueva_clave' => 'bono', 'nuevo_porcentaje' => '10',
        ])->assertRedirect(route('portfolios.rebalanceo', [$this->portafolio, 'dimension' => 'clase']));

        $this->assertSame(
            ['bono' => '10.0000', 'efectivo' => '50.0000', 'etf' => '40.0000'],
            TargetAllocation::where('portfolio_id', $this->portafolio->id)->orderBy('clave')->pluck('porcentaje_objetivo', 'clave')->all(),
        );
    }

    public function test_rechaza_porcentajes_que_no_suman_100(): void
    {
        $this->objetivo(['etf' => 30, 'efectivo' => 70]);

        $this->actingAs($this->asesor)->put(route('portfolios.rebalanceo.update', $this->portafolio), [
            'dimension' => 'clase', 'objetivos' => ['etf' => '40', 'efectivo' => '50'],
        ])->assertSessionHasErrors(['objetivos' => 'Los porcentajes deben sumar 100 % (suman 90 %).']);

        $this->assertSame('30.0000', TargetAllocation::where('clave', 'etf')->value('porcentaje_objetivo'));
    }

    public function test_dejar_todo_en_blanco_quita_el_objetivo(): void
    {
        $this->objetivo(['etf' => 30, 'efectivo' => 70]);

        $this->actingAs($this->asesor)->put(route('portfolios.rebalanceo.update', $this->portafolio), [
            'dimension' => 'clase', 'objetivos' => ['etf' => '', 'efectivo' => ''],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, TargetAllocation::where('portfolio_id', $this->portafolio->id)->count());
    }

    public function test_solo_quien_gestiona_el_portafolio_lo_rebalancea(): void
    {
        $usuarioCliente = User::factory()->create(['organization_id' => $this->org->id, 'rol' => User::ROL_CLIENTE, 'client_id' => $this->cliente->id]);

        $this->actingAs($this->otroAsesor)->get(route('portfolios.rebalanceo', $this->portafolio))->assertForbidden();
        $this->actingAs($usuarioCliente)->get(route('portfolios.rebalanceo', $this->portafolio))->assertForbidden();
        $this->actingAs($this->otroAsesor)->put(route('portfolios.rebalanceo.update', $this->portafolio), ['dimension' => 'clase', 'objetivos' => ['etf' => '100']])
            ->assertForbidden();
    }

    public function test_la_vista_de_operar_toma_la_cantidad_sugerida(): void
    {
        $response = $this->actingAs($this->asesor)->get(route('portfolios.operar', [$this->portafolio, 'tipo' => 'VENTA', 'asset_id' => $this->naftrac->id, 'cantidad' => '40']));

        $response->assertSee('name="cantidad" inputmode="decimal" value="40"', false);
    }
}
