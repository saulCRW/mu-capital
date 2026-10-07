<?php

namespace App\Modules\Prices\Services\Providers;

/**
 * Tipos de cambio a MXN desde Yahoo Finance (pares USDMXN=X, EURMXN=X…).
 * Gratis y sin token: se usa cuando no hay BANXICO_TOKEN configurado.
 */
class YahooFxProvider implements FxProvider
{
    private const MONEDAS = ['USD', 'EUR', 'CAD', 'GBP', 'JPY', 'CHF'];

    public function __construct(private YahooFinanceProvider $yahoo) {}

    public function disponible(): bool
    {
        return true;
    }

    public function monedas(): array
    {
        return self::MONEDAS;
    }

    public function tasasAPesos(string $moneda, string $desde, string $hasta): array
    {
        if (! in_array($moneda, self::MONEDAS, true)) {
            throw new ProviderException("No hay tipo de cambio de {$moneda} a MXN en Yahoo Finance.");
        }

        return $this->yahoo->cierres("{$moneda}MXN=X", $desde, $hasta);
    }
}
