<?php

namespace App\Modules\Prices\Notifications;

use App\Modules\Prices\Models\PriceAlert;
use App\Modules\Prices\Services\PriceAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de una alerta de precio que se disparó. Va a la cola (ShouldQueue):
 * la página Mercado no espera al servidor de correo; la cola la vacía el
 * programador cada minuto (routes/console.php).
 */
class PriceAlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    /** Intentos si el servidor de correo falla. */
    public int $tries = 3;

    public function __construct(public PriceAlert $alerta) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $alerta = $this->alerta->loadMissing('asset:id,simbolo,moneda');
        $numero = fn ($v) => number_format((float) $v, 2, '.', ',');
        $verbo = $alerta->condicion === PriceAlert::ARRIBA ? 'subió a' : 'bajó a';

        return (new MailMessage)
            ->subject(config('app.name')." · {$alerta->asset->simbolo} {$verbo} {$numero($alerta->precio_disparo)} {$alerta->asset->moneda}")
            ->greeting("Hola, {$notifiable->nombre}")
            ->line(PriceAlertService::mensaje($alerta).'.')
            ->line('Se disparó el '.$alerta->disparada_en->format('d/m/Y \a \l\a\s H:i').' (hora del centro de México).')
            ->action('Ver en Mercado', route('mercado'))
            ->line('Puedes dejar de recibir estos correos en Mi cuenta, en "Alertas de precio".');
    }
}
