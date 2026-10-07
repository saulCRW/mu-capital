<?php

namespace App\Modules\Prices\Services\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Tipos de cambio oficiales del Banco de México (API SIE). Gratis con token:
 * https://www.banxico.org.mx/SieAPIRest/service/v1/token
 * Límite: 200 consultas cada 5 minutos y 10,000 al día.
 */
class BanxicoProvider implements FxProvider
{
    /** Series SIE: pesos por unidad de moneda extranjera. USD es el tipo de cambio FIX. */
    private const SERIES = [
        'USD' => 'SF43718',
        'EUR' => 'SF46410',
        'CAD' => 'SF60632',
        'GBP' => 'SF46407',
        'JPY' => 'SF46406',
    ];

    public function __construct(private string $url, private ?string $token) {}

    public function disponible(): bool
    {
        return filled($this->token);
    }

    public function monedas(): array
    {
        return array_keys(self::SERIES);
    }

    public function tasasAPesos(string $moneda, string $desde, string $hasta): array
    {
        $serie = self::SERIES[$moneda] ?? throw new ProviderException("Banxico no publica el tipo de cambio de {$moneda}.");

        try {
            $respuesta = Http::withHeaders(['Bmx-Token' => (string) $this->token])
                ->acceptJson()
                ->timeout(20)
                ->retry(2, 500, throw: false)
                ->get("{$this->url}/series/{$serie}/datos/{$desde}/{$hasta}");
        } catch (ConnectionException $e) {
            throw new ProviderException('Banxico no respondió.', previous: $e);
        }

        if (! $respuesta->successful()) {
            throw new ProviderException("Banxico rechazó la consulta de {$moneda} (HTTP {$respuesta->status()}). Revisa BANXICO_TOKEN.");
        }

        $tasas = [];
        foreach ($respuesta->json('bmx.series.0.datos') ?? [] as $d) {
            // "N/E" = no existe dato ese día (fin de semana o feriado).
            if (is_numeric(str_replace(',', '', $d['dato'] ?? ''))) {
                $fecha = CarbonImmutable::createFromFormat('d/m/Y', $d['fecha'])->toDateString();
                $tasas[$fecha] = number_format((float) str_replace(',', '', $d['dato']), 6, '.', '');
            }
        }

        return $tasas;
    }
}
