<?php

namespace Tests\Unit\Imports;

use App\Modules\Imports\Services\CsvTransactionParser;
use PHPUnit\Framework\TestCase;

class CsvTransactionParserTest extends TestCase
{
    private const HOY = '2026-03-02';

    public function test_lee_columnas_en_cualquier_orden_con_acentos_y_formatos_comunes(): void
    {
        $csv = "\xEF\xBB\xBFFecha;Tipo;Símbolo;Cantidad;Precio;Comisión;Nota\n"
            ."05/01/2026;depósito;;\"10,000.00\";;;Aportación inicial\n"
            ."2026-01-06;compra;naftrac;100;50.5;10;\n";

        $r = CsvTransactionParser::leer($csv, self::HOY);

        $this->assertSame([], $r['errores']);
        $this->assertSame([2, 3], array_column($r['filas'], 'linea'));
        $this->assertSame([[], []], array_column($r['filas'], 'errores'));
        $this->assertSame(
            ['tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => '10000.00', 'simbolo' => null, 'nota' => 'Aportación inicial'],
            array_intersect_key($r['filas'][0]['datos'], array_flip(['tipo', 'fecha', 'cantidad', 'simbolo', 'nota'])),
        );
        $this->assertSame(
            ['tipo' => 'COMPRA', 'simbolo' => 'NAFTRAC', 'precio' => '50.5', 'comision' => '10'],
            array_intersect_key($r['filas'][1]['datos'], array_flip(['tipo', 'simbolo', 'precio', 'comision'])),
        );
    }

    public function test_marca_los_errores_de_cada_fila(): void
    {
        $csv = "tipo,fecha,simbolo,cantidad,precio\n"
            ."REGALO,2026-01-05,,10,\n"
            ."COMPRA,31/02/2026,AAPL,-5,\n"
            ."DEPOSITO,2026-04-01,AAPL,100,\n"
            ."VENTA,2026-01-05,,1,abc\n";

        $filas = CsvTransactionParser::leer($csv, self::HOY)['filas'];

        $this->assertStringStartsWith('Tipo "REGALO" no válido', $filas[0]['errores'][0]);
        $this->assertSame([
            'Fecha "31/02/2026" no válida; usa AAAA-MM-DD o DD/MM/AAAA.',
            'Cantidad "-5" no es un número válido (positivo, punto decimal, máximo 6 decimales).',
            'La cantidad debe ser mayor que cero.',
            'Las compras y ventas requieren precio mayor que cero.',
        ], $filas[1]['errores']);
        $this->assertSame(['La fecha no puede ser futura.', 'Los depósitos y retiros no llevan activo.'], $filas[2]['errores']);
        $this->assertSame([
            'Precio "abc" no es un número válido (positivo, punto decimal, máximo 6 decimales).',
            'Este tipo de transacción requiere el símbolo del activo.',
            'Las compras y ventas requieren precio mayor que cero.',
        ], $filas[3]['errores']);
    }

    public function test_sin_las_columnas_obligatorias_rechaza_el_archivo(): void
    {
        $r = CsvTransactionParser::leer("fecha,simbolo\n2026-01-05,AAPL\n", self::HOY);

        $this->assertSame(['Faltan columnas obligatorias: tipo, cantidad. El encabezado debe tener al menos tipo, fecha y cantidad.'], $r['errores']);
    }

    public function test_omite_filas_vacias_y_acepta_windows_1252(): void
    {
        $csv = mb_convert_encoding("tipo,fecha,cantidad,nota\nDEPOSITO,2026-01-05,100,Aportación\n,,,\n", 'Windows-1252', 'UTF-8');

        $r = CsvTransactionParser::leer($csv, self::HOY);

        $this->assertCount(1, $r['filas']);
        $this->assertSame('Aportación', $r['filas'][0]['datos']['nota']);
    }

    public function test_acepta_tabuladores_y_saltos_de_linea_de_windows(): void
    {
        $r = CsvTransactionParser::leer("tipo\tfecha\tcantidad\r\nDEPOSITO\t2026-01-05\t100\r\nRETIRO\t2026-01-06\t40\r\n", self::HOY);

        $this->assertSame([], $r['errores']);
        $this->assertSame([2, 3], array_column($r['filas'], 'linea'));
        $this->assertSame(['100', '40'], array_column(array_column($r['filas'], 'datos'), 'cantidad'));
    }

    public function test_un_archivo_sin_datos_lo_indica(): void
    {
        $this->assertSame(['El archivo no tiene filas de datos.'], CsvTransactionParser::leer("tipo,fecha,cantidad\n", self::HOY)['errores']);
        $this->assertSame(['El archivo está vacío.'], CsvTransactionParser::leer('', self::HOY)['errores']);
    }
}
