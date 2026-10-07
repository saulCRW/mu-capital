<?php

namespace App\Modules\Imports\Services;

use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Services\TransactionService;
use App\Modules\Users\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Importación de transacciones desde CSV en dos pasos: vista previa (se lee,
 * se resuelve cada símbolo y se simula todo el libro sin guardar) y
 * confirmación (se registra todo o nada).
 */
class ImportService
{
    public function __construct(private TransactionService $transacciones) {}

    /**
     * @return array{filas: list<array{linea:int, datos:array<string, mixed>, errores:list<string>}>, errores: list<string>, valido: bool}
     */
    public function previsualizar(Portfolio $portfolio, string $contenido, User $autor): array
    {
        $leido = CsvTransactionParser::leer($contenido, today()->toDateString());
        if ($leido['errores'] !== []) {
            return ['filas' => [], 'errores' => $leido['errores'], 'valido' => false];
        }

        $filas = $this->resolverActivos($leido['filas']);
        $errores = [];
        if (! collect($filas)->contains(fn ($f) => $f['errores'] !== [])) {
            try {
                $this->transacciones->importar($portfolio, self::paraServicio($filas), $autor, simular: true);
            } catch (ValidationException $e) {
                $errores[] = collect($e->errors())->flatten()->first();
            }
        }

        return [
            'filas' => $filas,
            'errores' => $errores,
            'valido' => $errores === [] && ! collect($filas)->contains(fn ($f) => $f['errores'] !== []),
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
