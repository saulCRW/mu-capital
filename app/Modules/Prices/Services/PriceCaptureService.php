<?php

namespace App\Modules\Prices\Services;

use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Imports\Services\CsvTransactionParser;
use App\Modules\Imports\Services\XlsxReader;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pantalla de Precios (PROYECTO.md, pantalla 9): captura manual e importación
 * por CSV o Excel de cierres de activos y tipos de cambio.
 *
 * Un dato de la misma fecha se reemplaza. Después se recalculan las
 * valuaciones diarias (portfolio_snapshots) desde la primera fecha que cambió,
 * igual que con la sincronización automática.
 *
 * Archivo de precios:   simbolo, exchange (opcional), fecha, cierre
 * Archivo de tipos:     moneda_origen, moneda_destino, fecha, tasa
 * Se reconoce cuál es por el encabezado (cierre o tasa).
 */
class PriceCaptureService
{
    public const MAX_FILAS = 5000;

    public function __construct(private SnapshotService $snapshots) {}

    public function guardarPrecio(Asset $asset, string $fecha, string $cierre): void
    {
        $this->guardarPrecios([['asset_id' => $asset->id, 'fecha' => $fecha, 'cierre' => $cierre]]);
    }

    public function guardarTasa(string $origen, string $destino, string $fecha, string $tasa): void
    {
        $this->guardarTasas([['moneda_origen' => $origen, 'moneda_destino' => $destino, 'fecha' => $fecha, 'tasa' => $tasa]]);
    }

    /**
     * Lee el archivo y, si todas las filas son válidas, guarda todo; si alguna falla, no guarda nada.
     *
     * @return array{tipo: ?string, guardados: int, errores: list<string>}
     */
    public function importar(string $contenido, string $hoy): array
    {
        try {
            $tabla = XlsxReader::esXlsx($contenido) ? XlsxReader::tabla($contenido) : CsvTransactionParser::tabla($contenido);
        } catch (RuntimeException $e) {
            return ['tipo' => null, 'guardados' => 0, 'errores' => [$e->getMessage()]];
        }

        $columnas = array_map(fn ($c) => CsvTransactionParser::clave((string) $c), $tabla[0] ?? []);
        $tipo = in_array('cierre', $columnas, true) ? 'precios' : (in_array('tasa', $columnas, true) ? 'tasas' : null);
        if ($tipo === null) {
            return ['tipo' => null, 'guardados' => 0, 'errores' => ['El encabezado debe tener "simbolo, fecha, cierre" (precios) o "moneda_origen, moneda_destino, fecha, tasa" (tipos de cambio).']];
        }

        $obligatorias = $tipo === 'precios' ? ['simbolo', 'fecha', 'cierre'] : ['moneda_origen', 'moneda_destino', 'fecha', 'tasa'];
        $faltan = array_diff($obligatorias, $columnas);
        if ($faltan !== []) {
            return ['tipo' => $tipo, 'guardados' => 0, 'errores' => ['Faltan columnas: '.implode(', ', $faltan).'.']];
        }

        $filas = [];
        foreach (array_slice($tabla, 1) as $i => $valores) {
            if (count(array_filter($valores, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $dato = [];
            foreach ($columnas as $c => $nombre) {
                $dato[$nombre] = isset($valores[$c]) && trim((string) $valores[$c]) !== '' ? trim((string) $valores[$c]) : null;
            }
            $filas[$i + 2] = $dato;
        }
        if ($filas === []) {
            return ['tipo' => $tipo, 'guardados' => 0, 'errores' => ['El archivo no tiene filas de datos.']];
        }
        if (count($filas) > self::MAX_FILAS) {
            return ['tipo' => $tipo, 'guardados' => 0, 'errores' => ['El archivo pasa de '.number_format(self::MAX_FILAS).' filas; divídelo en varios.']];
        }

        [$validas, $errores] = $tipo === 'precios' ? $this->validarPrecios($filas, $hoy) : $this->validarTasas($filas, $hoy);
        if ($errores !== []) {
            return ['tipo' => $tipo, 'guardados' => 0, 'errores' => $errores];
        }

        $tipo === 'precios' ? $this->guardarPrecios($validas) : $this->guardarTasas($validas);

        return ['tipo' => $tipo, 'guardados' => count($validas), 'errores' => []];
    }

    /**
     * @param  array<int, array<string, ?string>>  $filas  línea => datos
     * @return array{0: list<array{asset_id:int, fecha:string, cierre:string}>, 1: list<string>}
     */
    private function validarPrecios(array $filas, string $hoy): array
    {
        $simbolos = array_values(array_unique(array_filter(array_map(fn ($f) => $f['simbolo'] === null ? null : strtoupper($f['simbolo']), $filas))));
        $catalogo = Asset::whereIn('simbolo', $simbolos)->where('tipo', '!=', 'efectivo')->get(['id', 'simbolo', 'exchange'])->groupBy('simbolo');

        $validas = [];
        $errores = [];
        foreach ($filas as $linea => $f) {
            $problemas = [];
            $simbolo = strtoupper((string) $f['simbolo']);
            $candidatos = $catalogo->get($simbolo, collect());
            if (($f['exchange'] ?? null) !== null) {
                $candidatos = $candidatos->where('exchange', strtoupper($f['exchange']));
            }
            if ($simbolo === '') {
                $problemas[] = 'falta el símbolo';
            } elseif ($candidatos->isEmpty()) {
                $problemas[] = "el activo {$simbolo} no existe en el catálogo";
            } elseif ($candidatos->count() > 1) {
                $problemas[] = "hay varios activos {$simbolo}; agrega la columna exchange";
            }
            [$fecha, $cierre] = $this->fechaYValor($f['fecha'], $f['cierre'], 'cierre', $hoy, $problemas);

            if ($problemas !== []) {
                $errores[] = "Línea {$linea}: ".implode('; ', $problemas).'.';
            } else {
                $validas[] = ['asset_id' => $candidatos->first()->id, 'fecha' => $fecha, 'cierre' => $cierre];
            }
        }

        return [$validas, $errores];
    }

    /**
     * @param  array<int, array<string, ?string>>  $filas
     * @return array{0: list<array{moneda_origen:string, moneda_destino:string, fecha:string, tasa:string}>, 1: list<string>}
     */
    private function validarTasas(array $filas, string $hoy): array
    {
        $validas = [];
        $errores = [];
        foreach ($filas as $linea => $f) {
            $problemas = [];
            $origen = strtoupper((string) $f['moneda_origen']);
            $destino = strtoupper((string) $f['moneda_destino']);
            if (! preg_match('/^[A-Z]{3}$/', $origen) || ! preg_match('/^[A-Z]{3}$/', $destino)) {
                $problemas[] = 'las monedas deben ser códigos de 3 letras (ej. USD, MXN)';
            } elseif ($origen === $destino) {
                $problemas[] = 'las dos monedas son iguales';
            }
            [$fecha, $tasa] = $this->fechaYValor($f['fecha'], $f['tasa'], 'tasa', $hoy, $problemas);

            if ($problemas !== []) {
                $errores[] = "Línea {$linea}: ".implode('; ', $problemas).'.';
            } else {
                $validas[] = ['moneda_origen' => $origen, 'moneda_destino' => $destino, 'fecha' => $fecha, 'tasa' => $tasa];
            }
        }

        return [$validas, $errores];
    }

    /**
     * @param  list<string>  $problemas  se le agregan los errores encontrados
     * @return array{0: ?string, 1: ?string}
     */
    private function fechaYValor(?string $texto, ?string $valor, string $nombre, string $hoy, array &$problemas): array
    {
        $fecha = CsvTransactionParser::fecha($texto);
        if ($fecha === null) {
            $problemas[] = 'fecha "'.$texto.'" no válida (AAAA-MM-DD o DD/MM/AAAA)';
        } elseif ($fecha > $hoy) {
            $problemas[] = 'la fecha no puede ser futura';
        }

        $numero = $valor === null ? null : CsvTransactionParser::numero($valor);
        if ($numero === null || bccomp($numero, '0', 6) <= 0) {
            $problemas[] = "{$nombre} \"{$valor}\" debe ser un número mayor que cero";
        }

        return [$fecha, $numero];
    }

    /** @param  list<array{asset_id:int, fecha:string, cierre:string}>  $precios */
    private function guardarPrecios(array $precios): void
    {
        DB::transaction(function () use ($precios) {
            $ahora = now();
            Price::upsert(array_map(fn ($p) => $p + ['created_at' => $ahora, 'updated_at' => $ahora], $precios), ['asset_id', 'fecha'], ['cierre', 'updated_at']);

            // Solo cambian las valuaciones de los portafolios que tienen esos activos.
            $activos = array_values(array_unique(array_column($precios, 'asset_id')));
            $this->recalcular(Portfolio::whereHas('transactions', fn ($q) => $q->whereIn('asset_id', $activos))->orderBy('id')->get(), min(array_column($precios, 'fecha')));
        });
    }

    /** @param  list<array{moneda_origen:string, moneda_destino:string, fecha:string, tasa:string}>  $tasas */
    private function guardarTasas(array $tasas): void
    {
        DB::transaction(function () use ($tasas) {
            $ahora = now();
            FxRate::upsert(array_map(fn ($t) => $t + ['created_at' => $ahora, 'updated_at' => $ahora], $tasas), ['moneda_origen', 'moneda_destino', 'fecha'], ['tasa', 'updated_at']);
            $this->recalcular(Portfolio::orderBy('id')->get(), min(array_column($tasas, 'fecha')));
        });
    }

    /** @param  iterable<Portfolio>  $portafolios */
    private function recalcular(iterable $portafolios, string $desde): void
    {
        foreach ($portafolios as $portfolio) {
            $this->snapshots->recalcular($portfolio, $desde);
        }
    }
}
