<?php

namespace App\Modules\Imports\Services;

use DateInterval;
use DateTimeImmutable;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Lee la primera hoja de un Excel (.xlsx) como tabla de textos, para que
 * CsvTransactionParser la valide igual que un CSV.
 *
 * Un .xlsx es un ZIP con XML; se lee con ZipArchive y SimpleXML, sin
 * librerías externas. Las celdas con formato de fecha se entregan como
 * AAAA-MM-DD y los números sin el ruido de punto flotante (245.1, no
 * 245.09999999999999).
 */
final class XlsxReader
{
    /** Tamaño máximo de cada XML ya descomprimido (protege contra archivos "bomba"). */
    private const MAX_XML = 20 * 1024 * 1024;

    /** Fila más lejana que se acepta (las filas vacías intermedias se rellenan). */
    private const MAX_FILA = 10000;

    private const NS_RELACIONES = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /** Formatos de número de Excel predefinidos que son fechas. */
    private const FORMATOS_FECHA = [14, 15, 16, 17, 22, 27, 28, 29, 30, 31, 34, 35, 36, 50, 51, 52, 53, 54, 57, 58];

    /** Un .xlsx empieza con la firma de ZIP. */
    public static function esXlsx(string $contenido): bool
    {
        return str_starts_with($contenido, "PK\x03\x04");
    }

    /**
     * @return list<list<?string>>
     *
     * @throws RuntimeException si el archivo no es un Excel legible
     */
    public static function tabla(string $contenido): array
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($ruta, $contenido);
        $zip = new ZipArchive;
        $abierto = false;

        try {
            $abierto = $zip->open($ruta) === true;
            if (! $abierto) {
                throw new RuntimeException('No se pudo abrir el archivo de Excel.');
            }

            $libro = self::xml($zip, 'xl/workbook.xml');
            $base1904 = in_array((string) ($libro->workbookPr['date1904'] ?? ''), ['1', 'true'], true);

            return self::filas(
                self::xml($zip, self::primeraHoja($zip, $libro)),
                self::textosCompartidos($zip),
                self::estilosDeFecha($zip),
                $base1904,
            );
        } finally {
            if ($abierto) {
                $zip->close();
            }
            @unlink($ruta);
        }
    }

    /** Ruta dentro del ZIP de la primera hoja del libro. */
    private static function primeraHoja(ZipArchive $zip, SimpleXMLElement $libro): string
    {
        $hoja = $libro->sheets->sheet[0] ?? null;
        $id = $hoja === null ? null : (string) $hoja->attributes(self::NS_RELACIONES)['id'];
        if ($id === null || $id === '') {
            throw new RuntimeException('El archivo de Excel no tiene hojas.');
        }

        foreach (self::xml($zip, 'xl/_rels/workbook.xml.rels')->Relationship as $relacion) {
            if ((string) $relacion['Id'] === $id) {
                $destino = (string) $relacion['Target'];

                return str_starts_with($destino, '/') ? ltrim($destino, '/') : 'xl/'.$destino;
            }
        }

        throw new RuntimeException('No se encontró la primera hoja del archivo de Excel.');
    }

    /** @return list<string> */
    private static function textosCompartidos(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $textos = [];
        foreach (self::xml($zip, 'xl/sharedStrings.xml')->si as $si) {
            $textos[] = self::texto($si);
        }

        return $textos;
    }

    /**
     * Índices de estilo de celda (atributo s) cuyo formato es de fecha.
     *
     * @return array<int, true>
     */
    private static function estilosDeFecha(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/styles.xml') === false) {
            return [];
        }

        $estilos = self::xml($zip, 'xl/styles.xml');
        $propios = [];
        foreach ($estilos->numFmts->numFmt ?? [] as $formato) {
            // Sin textos entre comillas ni [colores]/[$moneda], una fecha lleva d o y.
            $codigo = preg_replace('/"[^"]*"|\[[^\]]*\]/', '', strtolower((string) $formato['formatCode']));
            $propios[(int) $formato['numFmtId']] = str_contains($codigo, 'd') || str_contains($codigo, 'y');
        }

        $fechas = [];
        $indice = 0;
        foreach ($estilos->cellXfs->xf ?? [] as $xf) {
            $id = (int) $xf['numFmtId'];
            if ($propios[$id] ?? in_array($id, self::FORMATOS_FECHA, true)) {
                $fechas[$indice] = true;
            }
            $indice++;
        }

        return $fechas;
    }

    /**
     * @param  list<string>  $compartidos
     * @param  array<int, true>  $fechas
     * @return list<list<?string>>
     */
    private static function filas(SimpleXMLElement $hoja, array $compartidos, array $fechas, bool $base1904): array
    {
        $tabla = [];
        $siguiente = 1;
        foreach ($hoja->sheetData->row ?? [] as $row) {
            $numero = isset($row['r']) ? (int) $row['r'] : $siguiente;
            if ($numero > self::MAX_FILA) {
                throw new RuntimeException('El archivo de Excel tiene datos más allá de la fila '.number_format(self::MAX_FILA).'.');
            }
            $siguiente = $numero + 1;

            $celdas = [];
            $columna = 0;
            foreach ($row->c as $c) {
                $columna = isset($c['r']) ? self::columna((string) $c['r']) : $columna + 1;
                $celdas[$columna - 1] = self::valor($c, $compartidos, $fechas, $base1904);
            }
            $tabla[$numero - 1] = $celdas === [] ? [] : array_replace(array_fill(0, max(array_keys($celdas)) + 1, null), $celdas);
        }

        if ($tabla === []) {
            return [];
        }

        // Rellena las filas que Excel omite por vacías, para que el número de línea coincida.
        return array_values(array_replace(array_fill(0, max(array_keys($tabla)) + 1, []), $tabla));
    }

    /**
     * @param  list<string>  $compartidos
     * @param  array<int, true>  $fechas
     */
    private static function valor(SimpleXMLElement $c, array $compartidos, array $fechas, bool $base1904): ?string
    {
        $tipo = (string) ($c['t'] ?? 'n');
        $v = isset($c->v) ? (string) $c->v : null;

        $valor = match ($tipo) {
            's' => $compartidos[(int) $v] ?? null,
            'inlineStr' => isset($c->is) ? self::texto($c->is) : null,
            'b' => $v === null ? null : ($v === '1' ? 'TRUE' : 'FALSE'),
            'e' => null,
            'd' => $v === null ? null : substr($v, 0, 10),
            default => $v,
        };

        if ($tipo === 'n' && $valor !== null && is_numeric($valor)) {
            return isset($fechas[(int) ($c['s'] ?? 0)]) ? self::fecha((float) $valor, $base1904) : self::numero($valor);
        }

        return $valor;
    }

    /** Texto de un <si> o <is>: simple (<t>) o con formato (varios <r><t>). */
    private static function texto(SimpleXMLElement $nodo): string
    {
        if (isset($nodo->t)) {
            return (string) $nodo->t;
        }

        $texto = '';
        foreach ($nodo->r as $r) {
            $texto .= (string) $r->t;
        }

        return $texto;
    }

    /** "AB12" → 28. */
    private static function columna(string $referencia): int
    {
        $numero = 0;
        foreach (str_split(preg_replace('/[^A-Z]/', '', strtoupper($referencia))) as $letra) {
            $numero = $numero * 26 + ord($letra) - 64;
        }

        return max($numero, 1);
    }

    /** Número de serie de Excel → AAAA-MM-DD (la hora se descarta). */
    private static function fecha(float $serie, bool $base1904): string
    {
        $origen = new DateTimeImmutable($base1904 ? '1904-01-01' : '1899-12-30');

        return $origen->add(new DateInterval('P'.max((int) floor($serie), 0).'D'))->format('Y-m-d');
    }

    /** "245.09999999999999" → "245.1"; "1E-3" → "0.001". */
    private static function numero(string $valor): string
    {
        if (preg_match('/^-?\d+$/', $valor)) {
            return $valor;
        }

        $texto = rtrim(rtrim(sprintf('%.10F', (float) $valor), '0'), '.');

        return $texto === '-0' ? '0' : $texto;
    }

    private static function xml(ZipArchive $zip, string $nombre): SimpleXMLElement
    {
        $info = $zip->statName($nombre);
        if ($info === false) {
            throw new RuntimeException('El archivo no es un Excel válido (.xlsx).');
        }
        if ($info['size'] > self::MAX_XML) {
            throw new RuntimeException('El archivo de Excel es demasiado grande una vez descomprimido.');
        }

        $erroresPrevios = libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string) $zip->getFromName($nombre), SimpleXMLElement::class, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($erroresPrevios);
        if ($xml === false) {
            throw new RuntimeException('El archivo de Excel está dañado.');
        }

        return $xml;
    }
}
