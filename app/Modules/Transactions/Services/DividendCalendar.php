<?php

namespace App\Modules\Transactions\Services;

use App\Modules\Analytics\Services\PortfolioLedger;
use App\Modules\Analytics\Services\PriceBook;
use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\ExpectedDividend;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use Carbon\CarbonImmutable;

/**
 * Calendario de dividendos esperados de un usuario.
 *
 *   monto esperado = títulos al corte × dividendo por título   (en la moneda del dividendo)
 *
 * Ejemplo: NAFTRAC paga 0.85 por título; el día anterior a la fecha ex tenías
 * 1,000 títulos en "Patrimonial" → esperas 1,000 × 0.85 = 850 MXN en ese portafolio.
 *
 * Los títulos salen del libro de transacciones de cada portafolio a la fecha
 * de corte (ver ExpectedDividend::fechaCorte). Un dividendo cuenta como
 * "registrado" en un portafolio si ahí hay una transacción DIVIDENDO del mismo
 * activo entre 7 días antes y 15 días después de la fecha de pago.
 */
class DividendCalendar
{
    /** @var array<string, array<int|string, string>> "portafolio|fecha" => asset_id => cantidad */
    private array $titulos = [];

    /**
     * @param  string  $mes  AAAA-MM
     * @return array{mes: CarbonImmutable, semanas: list<list<?string>>, dividendos: list<array<string, mixed>>, por_dia: array<string, list<array<string, mixed>>>}
     */
    public function mes(User $user, string $mes, string $hoy): array
    {
        $inicio = CarbonImmutable::createFromFormat('!Y-m', $mes)->startOfMonth();
        $fin = $inicio->endOfMonth();
        $portafolios = Portfolio::where('user_id', $user->id)->orderBy('nombre')->get();

        $dividendos = ExpectedDividend::where('user_id', $user->id)
            ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
            ->with('asset:id,simbolo,nombre,moneda')
            ->orderBy('fecha_pago')->orderBy('id')->get()
            ->map(fn (ExpectedDividend $d) => $this->detalle($d, $portafolios, $hoy))
            ->all();

        $porDia = [];
        foreach ($dividendos as $d) {
            $porDia[$d['dividendo']->fecha_pago->toDateString()][] = $d;
        }

        return ['mes' => $inicio, 'semanas' => self::semanas($inicio), 'dividendos' => $dividendos, 'por_dia' => $porDia];
    }

    /**
     * Semanas del mes de lunes a domingo; null en los días que no son del mes.
     *
     * @return list<list<?string>>
     */
    public static function semanas(CarbonImmutable $inicio): array
    {
        $dias = array_fill(0, $inicio->dayOfWeekIso - 1, null);
        for ($dia = $inicio; $dia->month === $inicio->month; $dia = $dia->addDay()) {
            $dias[] = $dia->toDateString();
        }
        while (count($dias) % 7 !== 0) {
            $dias[] = null;
        }

        return array_chunk($dias, 7);
    }

    /**
     * @param  iterable<Portfolio>  $portafolios
     * @return array{dividendo: ExpectedDividend, corte: string, portafolios: list<array{portfolio: Portfolio, titulos: string, total: string, registrado: bool}>, titulos: string, total: string}
     */
    private function detalle(ExpectedDividend $d, iterable $portafolios, string $hoy): array
    {
        $corte = $d->fechaCorte($hoy);
        $filas = [];
        foreach ($portafolios as $p) {
            $titulos = $this->titulosAl($p, $corte)[$d->asset_id] ?? '0';
            if (bccomp($titulos, '0', 6) <= 0) {
                continue;
            }
            $filas[] = [
                'portfolio' => $p,
                'titulos' => $titulos,
                'total' => bcmul($titulos, (string) $d->monto_por_titulo, 6),
                'registrado' => Transaction::where('portfolio_id', $p->id)->where('asset_id', $d->asset_id)->where('tipo', Transaction::DIVIDENDO)
                    ->whereBetween('fecha', [$d->fecha_pago->copy()->subDays(7)->toDateString(), $d->fecha_pago->copy()->addDays(15)->toDateString()])
                    ->exists(),
            ];
        }

        return [
            'dividendo' => $d,
            'corte' => $corte,
            'portafolios' => $filas,
            'titulos' => array_reduce($filas, fn ($s, $f) => bcadd($s, $f['titulos'], 6), '0'),
            'total' => array_reduce($filas, fn ($s, $f) => bcadd($s, $f['total'], 6), '0'),
        ];
    }

    /** @return array<int|string, string> asset_id => títulos al cierre de la fecha */
    private function titulosAl(Portfolio $portfolio, string $fecha): array
    {
        return $this->titulos["{$portfolio->id}|{$fecha}"] ??= array_column(
            PortfolioLedger::reproducir(SnapshotService::transacciones($portfolio, $fecha), $portfolio->moneda_base)
                ->valuar($fecha, new PriceBook)['posiciones'],
            'cantidad',
            'asset_id',
        );
    }
}
