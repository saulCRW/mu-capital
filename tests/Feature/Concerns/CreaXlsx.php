<?php

namespace Tests\Feature\Concerns;

use ZipArchive;

/** Arma un .xlsx mínimo de una hoja para probar la importación desde Excel. */
trait CreaXlsx
{
    /**
     * Cada celda es un texto (va a sharedStrings), un número, o ['fecha' => serie] con formato de fecha.
     * Las claves de $filas son el número de fila de Excel, así se pueden dejar filas sin escribir.
     *
     * @param  array<int, list<string|int|float|array{fecha: int}|null>>  $filas
     */
    private static function xlsx(array $filas): string
    {
        $textos = [];
        $xmlFilas = '';
        foreach ($filas as $numero => $celdas) {
            $xmlFilas .= "<row r=\"{$numero}\">";
            foreach ($celdas as $i => $valor) {
                $ref = chr(65 + $i).$numero;
                if ($valor === null) {
                    continue;
                }
                if (is_array($valor)) {
                    $xmlFilas .= "<c r=\"{$ref}\" s=\"1\"><v>{$valor['fecha']}</v></c>";
                } elseif (is_string($valor)) {
                    $textos[] = $valor;
                    $xmlFilas .= "<c r=\"{$ref}\" t=\"s\"><v>".(count($textos) - 1).'</v></c>';
                } else {
                    $xmlFilas .= "<c r=\"{$ref}\"><v>{$valor}</v></c>";
                }
            }
            $xmlFilas .= '</row>';
        }

        $main = 'xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
        $archivos = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/hoja.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><workbook {$main} xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\">"
                .'<sheets><sheet name="Movimientos" sheetId="1" r:id="rId7"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId7" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/hoja.xml"/></Relationships>',
            'xl/styles.xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><styleSheet {$main}><numFmts count=\"1\"><numFmt numFmtId=\"164\" formatCode=\"dd/mm/yyyy\"/></numFmts>"
                .'<cellXfs count="2"><xf numFmtId="0"/><xf numFmtId="164"/></cellXfs></styleSheet>',
            'xl/sharedStrings.xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><sst {$main}>"
                .implode('', array_map(fn ($t) => '<si><t>'.htmlspecialchars($t, ENT_XML1).'</t></si>', $textos)).'</sst>',
            'xl/worksheets/hoja.xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><worksheet {$main}><sheetData>{$xmlFilas}</sheetData></worksheet>",
        ];

        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        $zip->open($ruta, ZipArchive::OVERWRITE);
        foreach ($archivos as $nombre => $contenido) {
            $zip->addFromString($nombre, $contenido);
        }
        $zip->close();
        $bytes = file_get_contents($ruta);
        unlink($ruta);

        return $bytes;
    }
}
