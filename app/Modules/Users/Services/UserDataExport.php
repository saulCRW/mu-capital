<?php

namespace App\Modules\Users\Services;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Models\TargetAllocation;
use App\Modules\Prices\Models\PriceAlert;
use App\Modules\Transactions\Models\ExpectedDividend;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use RuntimeException;
use ZipArchive;

/**
 * Todos los datos de un usuario en un .zip con un CSV por tema. transacciones.csv
 * lleva las columnas de la plantilla de importación (más "portafolio"): las filas
 * de un portafolio, sin esa columna, se vuelven a importar tal cual.
 * No incluye datos de la plataforma (precios, tipos de cambio, índices), la
 * auditoría ni secretos (contraseña, clave del doble factor, códigos de recuperación).
 */
final class UserDataExport
{
    /** Columnas de la plantilla de importación (ImportController::plantilla). */
    public const COLUMNAS_TRANSACCIONES = ['tipo', 'fecha', 'simbolo', 'exchange', 'cantidad', 'precio', 'comision', 'moneda', 'tipo_cambio', 'nota'];

    /**
     * @return array<string, list<list<string|null>>> nombre del archivo => filas (la primera es el encabezado)
     */
    public function archivos(User $user): array
    {
        $portafolios = Portfolio::where('user_id', $user->id)->with('benchmark:id,nombre')->withCount('transactions')->orderBy('nombre')->get();
        $nombres = $portafolios->pluck('nombre', 'id');

        return [
            'cuenta.csv' => [
                ['nombre', 'correo', 'plan', 'tasa_libre_riesgo_pct', 'alta', 'doble_factor'],
                [$user->nombre, $user->email, $user->plan?->nombre, self::numero(bcmul((string) $user->tasa_libre_riesgo, '100', 6)), $user->created_at?->toDateString(), $user->tieneDosFactores() ? 'si' : 'no'],
            ],
            'portafolios.csv' => [
                ['nombre', 'moneda_base', 'benchmark', 'creado', 'transacciones'],
                ...$portafolios->map(fn (Portfolio $p) => [$p->nombre, $p->moneda_base, $p->benchmark?->nombre, $p->created_at?->toDateString(), (string) $p->transactions_count])->all(),
            ],
            'transacciones.csv' => [
                ['portafolio', ...self::COLUMNAS_TRANSACCIONES],
                ...$this->transacciones($user, $portafolios->pluck('moneda_base', 'id')->all(), $nombres->all()),
            ],
            'asignacion_objetivo.csv' => [
                ['portafolio', 'dimension', 'clave', 'porcentaje_objetivo'],
                ...TargetAllocation::where('user_id', $user->id)->orderBy('portfolio_id')->orderBy('dimension')->orderBy('clave')->get()
                    ->map(fn (TargetAllocation $t) => [$nombres[$t->portfolio_id] ?? null, $t->dimension, $t->clave, self::numero($t->porcentaje_objetivo)])->all(),
            ],
            'lista_mercado.csv' => [
                ['simbolo', 'nombre', 'exchange', 'moneda'],
                ...$user->watchlist()->orderBy('simbolo')->get()->map(fn ($a) => [$a->simbolo, $a->nombre, $a->exchange, $a->moneda])->all(),
            ],
            'alertas_precio.csv' => [
                ['simbolo', 'exchange', 'condicion', 'precio', 'estado', 'creada', 'disparada', 'precio_disparo'],
                ...PriceAlert::where('user_id', $user->id)->with('asset:id,simbolo,exchange')->orderBy('id')->get()
                    ->map(fn (PriceAlert $a) => [
                        $a->asset?->simbolo, $a->asset?->exchange, PriceAlert::CONDICIONES[$a->condicion] ?? $a->condicion, self::numero($a->precio),
                        $a->disparada_en === null ? 'activa' : 'disparada', $a->created_at?->toDateString(), $a->disparada_en?->toDateString(), self::numero($a->precio_disparo),
                    ])->all(),
            ],
            'dividendos_esperados.csv' => [
                ['simbolo', 'exchange', 'fecha_ex', 'fecha_pago', 'monto_por_titulo', 'moneda', 'nota'],
                ...ExpectedDividend::where('user_id', $user->id)->with('asset:id,simbolo,exchange')->orderBy('fecha_pago')->orderBy('id')->get()
                    ->map(fn (ExpectedDividend $d) => [
                        $d->asset?->simbolo, $d->asset?->exchange, $d->fecha_ex?->toDateString(), $d->fecha_pago->toDateString(),
                        self::numero($d->monto_por_titulo), $d->moneda, $d->nota,
                    ])->all(),
            ],
        ];
    }

    /** Arma el .zip en un archivo temporal y devuelve su ruta (quien lo descarga lo borra). */
    public function zip(User $user): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'datos');
        $zip = new ZipArchive;
        if ($zip->open($ruta, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo .zip.');
        }

        foreach ($this->archivos($user) as $nombre => $filas) {
            $zip->addFromString($nombre, self::csv($filas));
        }
        $zip->close();

        return $ruta;
    }

    /**
     * @param  array<int, string>  $monedasBase  portfolio_id => moneda base
     * @param  array<int, string>  $nombres  portfolio_id => nombre
     * @return list<list<string|null>>
     */
    private function transacciones(User $user, array $monedasBase, array $nombres): array
    {
        return Transaction::where('user_id', $user->id)
            ->with('asset:id,simbolo,exchange')
            ->orderBy('portfolio_id')->orderBy('fecha')->orderBy('id')
            ->get()
            ->sortBy(fn (Transaction $t) => $nombres[$t->portfolio_id] ?? '', SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn (Transaction $t) => [
                $nombres[$t->portfolio_id] ?? null,
                $t->tipo,
                $t->fecha->toDateString(),
                $t->asset?->simbolo,
                $t->asset?->exchange,
                self::numero($t->cantidad),
                in_array($t->tipo, Transaction::OPERACIONES, true) ? self::numero($t->precio) : null,
                bccomp((string) $t->comision, '0', 6) === 0 ? null : self::numero($t->comision),
                $t->moneda,
                // En la moneda base el tipo de cambio es 1; la importación lo pone sola.
                $t->moneda === ($monedasBase[$t->portfolio_id] ?? null) ? null : self::numero($t->tipo_cambio),
                $t->nota,
            ])
            ->values()->all();
    }

    /** "100.500000" → "100.5"; sin separador de miles para que se pueda volver a leer. */
    private static function numero(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $texto = (string) $valor;

        return str_contains($texto, '.') ? rtrim(rtrim($texto, '0'), '.') : $texto;
    }

    /** @param  list<list<string|null>>  $filas */
    private static function csv(array $filas): string
    {
        $salida = fopen('php://temp', 'r+');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel abre los acentos bien
        foreach ($filas as $fila) {
            fputcsv($salida, $fila, ',', '"', '');
        }
        rewind($salida);
        $contenido = stream_get_contents($salida);
        fclose($salida);

        return $contenido;
    }
}
