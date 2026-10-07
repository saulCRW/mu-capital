<?php

namespace Tests\Feature\Concerns;

use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Users\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * Escenario común: un usuario con un portafolio en MXN, otro usuario (para
 * probar que nadie ve lo ajeno), el superadmin y dos activos (uno en USD).
 */
trait CreaEscenario
{
    protected User $usuario;

    protected User $otroUsuario;

    protected User $superadmin;

    protected Portfolio $portafolio;

    protected Asset $naftrac;

    protected Asset $aapl;

    protected function crearEscenario(): void
    {
        $this->usuario = User::factory()->create(['nombre' => 'Usuario Uno']);
        $this->otroUsuario = User::factory()->create(['nombre' => 'Usuario Dos']);
        $this->superadmin = User::factory()->superadmin()->create();

        $this->portafolio = Portfolio::create([
            'user_id' => $this->usuario->id,
            'nombre' => 'Principal',
            'moneda_base' => 'MXN',
        ]);

        $this->naftrac = Asset::create(['simbolo' => 'NAFTRAC', 'nombre' => 'iShares NAFTRAC', 'tipo' => 'etf', 'moneda' => 'MXN', 'exchange' => 'BMV']);
        $this->aapl = Asset::create(['simbolo' => 'AAPL', 'nombre' => 'Apple Inc.', 'tipo' => 'accion', 'moneda' => 'USD', 'exchange' => 'NASDAQ']);
    }

    /** Registra una transacción en el portafolio del escenario como su dueño. */
    protected function registrar(array $datos): TestResponse
    {
        return $this->actingAs($this->usuario)
            ->post(route('portfolios.transactions.store', $this->portafolio), $datos);
    }

    protected function depositar(string $monto, string $fecha = '2026-01-05'): TestResponse
    {
        return $this->registrar(['tipo' => 'DEPOSITO', 'fecha' => $fecha, 'cantidad' => $monto]);
    }
}
