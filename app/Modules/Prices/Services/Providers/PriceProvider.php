<?php

namespace App\Modules\Prices\Services\Providers;

use App\Modules\Assets\Models\Asset;

/** Fuente de precios de cierre diarios de activos e índices. */
interface PriceProvider
{
    /** Símbolo del activo en este proveedor, o null si no cotiza (p. ej. efectivo). */
    public function simboloPara(Asset $asset): ?string;

    /**
     * Cierres diarios entre dos fechas (inclusive). El día en curso trae el último precio.
     *
     * @return array<string, string> fecha Y-m-d => cierre
     *
     * @throws ProviderException si el proveedor no responde o no conoce el símbolo
     */
    public function cierres(string $simbolo, string $desde, string $hasta): array;

    /**
     * Dividendos pagados por título, por fecha ex-dividendo, entre dos fechas (inclusive).
     *
     * @return array<string, string> fecha Y-m-d => monto por título en la moneda del activo
     *
     * @throws ProviderException
     */
    public function dividendos(string $simbolo, string $desde, string $hasta): array;
}
