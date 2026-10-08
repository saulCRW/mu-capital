<?php

namespace Tests\Unit\Imports;

use App\Modules\Imports\Services\XlsxReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Feature\Concerns\CreaXlsx;

class XlsxReaderTest extends TestCase
{
    use CreaXlsx;

    public function test_lee_textos_numeros_y_fechas_de_la_primera_hoja(): void
    {
        $contenido = self::xlsx([
            1 => ['tipo', 'fecha', 'simbolo', 'cantidad', 'precio'],
            2 => ['COMPRA', ['fecha' => 46028], 'AAPL', 10, 245.09999999999999],
            // La fila 3 no existe en el archivo (Excel omite las vacías).
            4 => ['DEPOSITO', ['fecha' => 46027], null, 100000],
        ]);

        $this->assertTrue(XlsxReader::esXlsx($contenido));
        $this->assertSame([
            ['tipo', 'fecha', 'simbolo', 'cantidad', 'precio'],
            ['COMPRA', '2026-01-06', 'AAPL', '10', '245.1'],
            [],
            ['DEPOSITO', '2026-01-05', null, '100000'],
        ], XlsxReader::tabla($contenido));
    }

    public function test_un_zip_que_no_es_excel_se_rechaza(): void
    {
        $ruta = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new \ZipArchive;
        $zip->open($ruta, \ZipArchive::OVERWRITE);
        $zip->addFromString('foto.txt', 'hola');
        $zip->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('El archivo no es un Excel válido (.xlsx).');

        try {
            XlsxReader::tabla(file_get_contents($ruta));
        } finally {
            unlink($ruta);
        }
    }
}
