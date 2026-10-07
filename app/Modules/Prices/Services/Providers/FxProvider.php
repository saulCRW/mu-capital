<?php

namespace App\Modules\Prices\Services\Providers;

/** Fuente de tipos de cambio diarios hacia el peso mexicano. */
interface FxProvider
{
    /** Si el proveedor está configurado (p. ej. tiene su token). */
    public function disponible(): bool;

    /** Monedas que sabe convertir a MXN. @return list<string> */
    public function monedas(): array;

    /**
     * Pesos por 1 unidad de la moneda, por día, entre dos fechas (inclusive).
     *
     * @return array<string, string> fecha Y-m-d => tasa
     *
     * @throws ProviderException
     */
    public function tasasAPesos(string $moneda, string $desde, string $hasta): array;
}
