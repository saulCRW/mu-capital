<?php

namespace App\Modules\Prices\Services;

use App\Modules\Prices\Models\PriceAlert;
use App\Modules\Prices\Notifications\PriceAlertTriggered;
use Illuminate\Support\Collection;

/**
 * Revisa las alertas de precio activas contra un precio por activo y marca
 * como disparadas las que se cumplen (con la fecha y el precio que las disparó).
 *
 * Se revisan con el precio del momento mientras la página Mercado está abierta
 * (cada 15 s) y con los cierres de cada sincronización de mercado. Cada alerta
 * disparada manda un correo a su dueño si lo tiene activado en Mi cuenta.
 */
class PriceAlertService
{
    /**
     * @param  array<int, float>  $precios  asset_id => precio
     * @param  int|null  $usuario  solo las alertas de ese usuario (null = todas, p. ej. en la sincronización)
     * @return Collection<int, PriceAlert> las que se dispararon ahora
     */
    public function revisar(array $precios, ?int $usuario = null): Collection
    {
        if ($precios === []) {
            return collect();
        }

        $consulta = $usuario === null ? PriceAlert::withoutGlobalScopes() : PriceAlert::query()->where('user_id', $usuario);

        return $consulta->activas()->whereIn('asset_id', array_keys($precios))->with(['asset:id,simbolo,moneda', 'user:id,nombre,email,alertas_por_correo'])->get()
            ->filter(fn (PriceAlert $alerta) => $alerta->cumple($precios[$alerta->asset_id]))
            ->each(function (PriceAlert $alerta) use ($precios) {
                $alerta->update(['disparada_en' => now(), 'precio_disparo' => $precios[$alerta->asset_id]]);
                if ($alerta->user->alertas_por_correo) {
                    $alerta->user->notify(new PriceAlertTriggered($alerta));
                }
            })
            ->values();
    }

    /** Texto del aviso: "AAPL subió a 336.67 USD (tu alerta: 335.00)". */
    public static function mensaje(PriceAlert $alerta): string
    {
        $numero = fn ($v) => number_format((float) $v, 2, '.', ',');
        $verbo = $alerta->condicion === PriceAlert::ARRIBA ? 'subió a' : 'bajó a';

        return "{$alerta->asset->simbolo} {$verbo} {$numero($alerta->precio_disparo)} {$alerta->asset->moneda} (tu alerta: {$numero($alerta->precio)})";
    }
}
