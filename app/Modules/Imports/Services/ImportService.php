<?php

namespace App\Modules\Imports\Services;

use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Services\TransactionService;
use App\Modules\Users\Models\User;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Importación de transacciones desde CSV o Excel (.xlsx) en dos pasos: vista
 * previa (se lee, se resuelve cada símbolo y se simula todo el libro sin
 * guardar) y confirmación (se registra todo o nada).
 */
class ImportService
{
    public function __construct(private TransactionService $transacciones) {}

    /**
     * @return array{filas: list<array{linea:int, datos:array<string, mixed>, errores:list<string>}>, errores: list<string>, valido: bool}
     */
    public function previsualizar(Portfolio $portfolio, string $contenido, User $autor): array
    {
        try {
            $leido = XlsxReader::esXlsx($contenido)
                ? CsvTransactionParser::leerTabla(XlsxReader::tabla($contenido), today()->toDateString())
                : CsvTransactionParser::leer($contenido, today()->toDateString());
        } catch (RuntimeException $e) {
            return ['filas' => [], 'errores' => [$e->getMessage()], 'valido' => false];
        }
        if ($leido['errores'] !== []) {
            return ['filas' => [], 'errores' => $leido['errores'], 'valido' => false];
        }

        $filas = $this->resolverActivos($leido['filas']);
        $correctas = array_values(array_filter($filas, fn ($f) => $f['errores'] === []));
        $hayFilasConErrores = count($correctas) < count($filas);

        // El libro se simula también cuando hay filas con errores (con las demás), para
        // mostrar todos los problemas en una sola vuelta y no de uno en uno.
        $errores = [];
        if ($correctas !== []) {
            try {
                $this->transacciones->importar($portfolio, self::paraServicio($correctas), $autor, simular: true);
            } catch (ValidationException $e) {
                $mensaje = collect($e->errors())->flatten()->first();
                $errores[] = $hayFilasConErrores ? "Con solo las filas sin errores: {$mensaje}" : $mensaje;
            }
        }

        return [
            'filas' => $filas,
            'errores' => $errores,
            'valido' => $errores === [] && ! $hayFilasConErrores,
        ];
    }

    /** @param  list<array{linea:int, datos:array<string, mixed>}>  $filas  las de una vista previa válida */
    public function importar(Portfolio $portfolio, array $filas, User $autor): int
    {
        return $this->transacciones->importar($portfolio, self::paraServicio($filas), $autor);
    }

    /**
     * Busca cada símbolo en el catálogo; si hay varios activos con el mismo
     * símbolo, la columna exchange decide.
     */
    private function resolverActivos(array $filas): array
    {
        $simbolos = array_values(array_unique(array_filter(array_map(fn ($f) => $f['datos']['simbolo'], $filas))));
        $catalogo = Asset::whereIn('simbolo', $simbolos)->get(['id', 'simbolo', 'exchange', 'moneda'])->groupBy('simbolo');

        foreach ($filas as $i => $fila) {
            $simbolo = $fila['datos']['simbolo'];
            $filas[$i]['datos']['asset_id'] = null;
            if ($simbolo === null) {
                continue;
            }

            $candidatos = $catalogo->get($simbolo, collect());
            if ($fila['datos']['exchange'] !== null) {
                $candidatos = $candidatos->where('exchange', $fila['datos']['exchange']);
            }

            if ($candidatos->isEmpty()) {
                $filas[$i]['errores'][] = "El activo {$simbolo}".($fila['datos']['exchange'] ? " ({$fila['datos']['exchange']})" : '').' no existe en el catálogo; dalo de alta en Activos.';
            } elseif ($candidatos->count() > 1) {
                $filas[$i]['errores'][] = "Hay varios activos {$simbolo} (".$candidatos->pluck('exchange')->map(fn ($e) => $e ?? 'sin exchange')->implode(', ').'); agrega la columna exchange.';
            } else {
                $filas[$i]['datos']['asset_id'] = $candidatos->first()->id;
            }
        }

        return $filas;
    }

    /** @return list<array<string, mixed>> */
    private static function paraServicio(array $filas): array
    {
        return array_map(fn ($f) => ['linea' => $f['linea']] + array_intersect_key($f['datos'], array_flip([
            'tipo', 'fecha', 'asset_id', 'cantidad', 'precio', 'comision', 'moneda', 'tipo_cambio', 'nota',
        ])), $filas);
    }
}
