<?php

namespace App\Modules\Imports\Services;

use App\Modules\Transactions\Models\Transaction;
use DateTimeImmutable;

/**
 * Lee un CSV (o la tabla de un Excel, ver XlsxReader) de transacciones y
 * valida el formato de cada fila. No toca la base de datos: resolver el
 * símbolo al activo y validar el libro completo le toca a ImportService.
 *
 * Columnas (en cualquier orden, sin importar mayúsculas ni acentos):
 *   tipo, fecha, cantidad                       obligatorias
 *   simbolo (o activo), exchange                para COMPRA, VENTA, DIVIDENDO y SPLIT
 *   precio                                      para COMPRA y VENTA
 *   comision, moneda, tipo_cambio, nota         opcionales
 * Separador: coma, punto y coma o tabulador. Fechas AAAA-MM-DD o DD/MM/AAAA.
 * Números con punto decimal (se admiten comas de miles: 1,234.50).
 */
final class CsvTransactionParser
{
    public const MAX_FILAS = 2000;

    private const COLUMNAS = [
        'tipo' => 'tipo', 'fecha' => 'fecha', 'cantidad' => 'cantidad', 'monto' => 'cantidad',
        'simbolo' => 'simbolo', 'activo' => 'simbolo', 'ticker' => 'simbolo', 'exchange' => 'exchange', 'bolsa' => 'exchange',
        'precio' => 'precio', 'comision' => 'comision', 'moneda' => 'moneda',
        'tipo_cambio' => 'tipo_cambio', 'tipo_de_cambio' => 'tipo_cambio', 'tc' => 'tipo_cambio', 'nota' => 'nota',
    ];

    private const OBLIGATORIAS = ['tipo', 'fecha', 'cantidad'];

    /**
     * @return array{filas: list<array{linea:int, datos:array<string, ?string>, errores:list<string>}>, errores: list<string>}
     */
    public static function leer(string $contenido, string $hoy): array
    {
        return self::leerTabla(self::tabla($contenido), $hoy);
    }

    /**
     * Separa un CSV en celdas. Detecta el separador (coma, punto y coma o tabulador)
     * en la primera línea y convierte a UTF-8 si viene de Excel en Windows-1252.
     *
     * @return list<list<?string>>
     */
    public static function tabla(string $contenido): array
    {
        $contenido = self::utf8($contenido);
        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        $primera = $lineas[0] ?? '';
        $separador = collect([',', ';', "\t"])->sortByDesc(fn ($s) => substr_count($primera, $s))->first();

        $flujo = fopen('php://temp', 'r+');
        fwrite($flujo, $contenido);
        rewind($flujo);

        $tabla = [];
        while (($valores = fgetcsv($flujo, null, $separador, '"', '')) !== false) {
            $tabla[] = $valores === [null] ? [] : $valores;
        }
        fclose($flujo);

        return $tabla;
    }

    /**
     * Valida una tabla ya separada en celdas (la primera fila es el encabezado). La usan
     * el CSV y el Excel; el índice de cada fila + 1 es su número de línea en el archivo.
     *
     * @param  list<list<?string>>  $tabla
     * @return array{filas: list<array{linea:int, datos:array<string, ?string>, errores:list<string>}>, errores: list<string>}
     */
    public static function leerTabla(array $tabla, string $hoy): array
    {
        $encabezado = $tabla[0] ?? [];
        if (count(array_filter($encabezado, fn ($v) => trim((string) $v) !== '')) === 0) {
            return ['filas' => [], 'errores' => ['El archivo está vacío.']];
        }

        $columnas = array_map(fn ($c) => self::COLUMNAS[self::clave((string) $c)] ?? null, $encabezado);
        $faltan = array_diff(self::OBLIGATORIAS, $columnas);
        if ($faltan !== []) {
            return ['filas' => [], 'errores' => ['Faltan columnas obligatorias: '.implode(', ', $faltan).'. El encabezado debe tener al menos tipo, fecha y cantidad.']];
        }

        $filas = [];
        foreach (array_slice($tabla, 1) as $i => $valores) {
            if (count(array_filter($valores, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            if (count($filas) === self::MAX_FILAS) {
                return ['filas' => [], 'errores' => ['El archivo pasa de '.self::MAX_FILAS.' filas; divídelo en varios.']];
            }

            $datos = array_fill_keys(array_unique(array_values(self::COLUMNAS)), null);
            foreach ($columnas as $c => $columna) {
                if ($columna !== null && isset($valores[$c]) && trim((string) $valores[$c]) !== '') {
                    $datos[$columna] = trim((string) $valores[$c]);
                }
            }
            $filas[] = self::validar($i + 2, $datos, $hoy);
        }

        return ['filas' => $filas, 'errores' => $filas === [] ? ['El archivo no tiene filas de datos.'] : []];
    }

    /** @param  array<string, ?string>  $d */
    private static function validar(int $linea, array $d, string $hoy): array
    {
        $errores = [];

        $d['tipo'] = $d['tipo'] === null ? null : strtoupper(self::sinAcentos($d['tipo']));
        if (! in_array($d['tipo'], Transaction::TIPOS, true)) {
            $errores[] = 'Tipo "'.($d['tipo'] ?? '').'" no válido; usa '.implode(', ', Transaction::TIPOS).'.';
        }

        $fecha = self::fecha($d['fecha']);
        if ($fecha === null) {
            $errores[] = 'Fecha "'.($d['fecha'] ?? '').'" no válida; usa AAAA-MM-DD o DD/MM/AAAA.';
        } elseif ($fecha > $hoy) {
            $errores[] = 'La fecha no puede ser futura.';
        }
        $d['fecha'] = $fecha;

        foreach (['cantidad', 'precio', 'comision', 'tipo_cambio'] as $campo) {
            if ($d[$campo] !== null) {
                $numero = self::numero($d[$campo]);
                if ($numero === null) {
                    $errores[] = ucfirst(str_replace('_', ' ', $campo)).' "'.$d[$campo].'" no es un número válido (positivo, punto decimal, máximo 6 decimales).';
                }
                $d[$campo] = $numero;
            }
        }

        if ($d['cantidad'] === null || bccomp($d['cantidad'] ?? '0', '0', 6) <= 0) {
            $errores[] = 'La cantidad debe ser mayor que cero.';
        }

        $tipo = $d['tipo'];
        if (in_array($tipo, Transaction::REQUIEREN_ACTIVO, true) && $d['simbolo'] === null) {
            $errores[] = 'Este tipo de transacción requiere el símbolo del activo.';
        }
        if (in_array($tipo, Transaction::SIN_ACTIVO, true) && $d['simbolo'] !== null) {
            $errores[] = 'Los depósitos y retiros no llevan activo.';
        }
        if (in_array($tipo, Transaction::OPERACIONES, true) && ($d['precio'] === null || bccomp($d['precio'], '0', 6) <= 0)) {
            $errores[] = 'Las compras y ventas requieren precio mayor que cero.';
        }
        if ($d['tipo_cambio'] !== null && bccomp($d['tipo_cambio'], '0', 6) <= 0) {
            $errores[] = 'El tipo de cambio debe ser mayor que cero.';
        }

        $d['simbolo'] = $d['simbolo'] === null ? null : strtoupper($d['simbolo']);
        $d['exchange'] = $d['exchange'] === null ? null : strtoupper($d['exchange']);
        if ($d['moneda'] !== null) {
            $d['moneda'] = strtoupper($d['moneda']);
            if (! preg_match('/^[A-Z]{3}$/', $d['moneda'])) {
                $errores[] = 'La moneda debe ser un código de 3 letras (ej. MXN, USD).';
            }
        }
        if ($d['nota'] !== null && mb_strlen($d['nota']) > 1000) {
            $errores[] = 'La nota pasa de 1000 caracteres.';
        }

        return ['linea' => $linea, 'datos' => $d, 'errores' => $errores];
    }

    public static function fecha(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $formato) {
            // DateTimeImmutable devuelve false si no coincide (Carbon lanzaría excepción); el
            // segundo format() descarta fechas que PHP "corrige", como el 31/02.
            $f = DateTimeImmutable::createFromFormat('!'.$formato, $texto);
            if ($f !== false && $f->format($formato) === $texto) {
                return $f->format('Y-m-d');
            }
        }
        // Días y meses de un dígito (5/1/2026).
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $texto, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return null;
    }

    /** "1,234.50" → "1234.50". null si no es un número positivo con hasta 6 decimales. */
    public static function numero(string $texto): ?string
    {
        $texto = str_replace([' ', '$'], '', $texto);
        if (preg_match('/^\d{1,3}(,\d{3})+(\.\d{1,6})?$/', $texto)) {
            $texto = str_replace(',', '', $texto);
        }

        return preg_match('/^\d+(\.\d{1,6})?$/', $texto) ? $texto : null;
    }

    public static function clave(string $encabezado): string
    {
        return preg_replace('/[^a-z_]/', '', str_replace([' ', '-'], '_', strtolower(self::sinAcentos(trim($encabezado)))));
    }

    private static function sinAcentos(string $texto): string
    {
        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'ñ' => 'n', 'Ñ' => 'N']);
    }

    /** Quita el BOM y convierte desde Windows-1252 si no es UTF-8 (CSV guardado por Excel). */
    private static function utf8(string $contenido): string
    {
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        return mb_check_encoding($contenido, 'UTF-8') ? $contenido : mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
    }
}
