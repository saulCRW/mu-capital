<?php

namespace Tests\Feature\Prices;

use App\Modules\Prices\Models\PriceAlert;
use App\Modules\Prices\Notifications\PriceAlertTriggered;
use App\Modules\Prices\Services\PriceAlertService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/** Correo de las alertas de precio que se disparan (a la cola; se manda solo a su dueño y una vez). */
class PriceAlertMailTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-10-08 13:05:00');
        Notification::fake();
    }

    public function test_al_dispararse_manda_un_correo_solo_a_su_duenio_y_una_vez(): void
    {
        $alerta = PriceAlert::create(['user_id' => $this->usuario->id, 'asset_id' => $this->aapl->id, 'condicion' => 'abajo', 'precio' => '320']);
        PriceAlert::create(['user_id' => $this->otroUsuario->id, 'asset_id' => $this->aapl->id, 'condicion' => 'abajo', 'precio' => '310']);

        app(PriceAlertService::class)->revisar([$this->aapl->id => 315.0]);
        app(PriceAlertService::class)->revisar([$this->aapl->id => 305.0]);

        Notification::assertSentToTimes($this->usuario, PriceAlertTriggered::class, 1);
        Notification::assertSentTo($this->usuario, PriceAlertTriggered::class, fn (PriceAlertTriggered $n) => $n->alerta->is($alerta));
        // La del otro usuario se disparó en la segunda revisión: su propio correo, también una vez.
        Notification::assertSentToTimes($this->otroUsuario, PriceAlertTriggered::class, 1);
        Notification::assertCount(2);
    }

    public function test_con_la_casilla_apagada_no_manda_correo(): void
    {
        $this->usuario->update(['alertas_por_correo' => false]);
        PriceAlert::create(['user_id' => $this->usuario->id, 'asset_id' => $this->aapl->id, 'condicion' => 'abajo', 'precio' => '320']);

        $disparadas = app(PriceAlertService::class)->revisar([$this->aapl->id => 315.0]);

        $this->assertCount(1, $disparadas);
        Notification::assertNothingSent();
    }

    public function test_el_correo_trae_el_aviso_y_el_enlace_a_mercado(): void
    {
        $alerta = PriceAlert::create(['user_id' => $this->usuario->id, 'asset_id' => $this->aapl->id, 'condicion' => 'arriba', 'precio' => '335']);
        app(PriceAlertService::class)->revisar([$this->aapl->id => 336.67]);

        $correo = (new PriceAlertTriggered($alerta->fresh()))->toMail($this->usuario);

        $this->assertSame('MU Intelligence · AAPL subió a 336.67 USD', $correo->subject);
        $this->assertContains('AAPL subió a 336.67 USD (tu alerta: 335.00).', $correo->introLines);
        $this->assertContains('Se disparó el 08/10/2026 a las 13:05 (hora del centro de México).', $correo->introLines);
        $this->assertSame(route('mercado'), $correo->actionUrl);
        $this->assertStringContainsString('Ver en Mercado', (string) $correo->render());
        $this->assertInstanceOf(ShouldQueue::class, new PriceAlertTriggered($alerta));
    }

    public function test_la_cola_se_vacia_cada_minuto(): void
    {
        $cola = collect(app(Schedule::class)->events())
            ->first(fn (Event $e) => str_contains($e->command, 'queue:work --stop-when-empty'));

        $this->assertNotNull($cola);
        $this->assertSame('* * * * *', $cola->expression);
    }
}
