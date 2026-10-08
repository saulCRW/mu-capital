<?php

namespace App\Modules\Backups\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Respaldo de la base de datos MySQL con mysqldump, comprimido con gzip, en
 * storage/app/private/respaldos (fuera de public y de git). Conserva los
 * últimos N archivos y borra los más viejos.
 *
 * La contraseña va por la variable de entorno MYSQL_PWD, no en la línea de
 * comandos (así no aparece en la lista de procesos).
 */
class DatabaseBackupService
{
    public const CARPETA = 'respaldos';

    /** @return array{archivo: string, bytes: int, borrados: list<string>} */
    public function respaldar(): array
    {
        $conexion = config('database.default');
        $db = config("database.connections.{$conexion}");
        if (($db['driver'] ?? null) !== 'mysql' && ($db['driver'] ?? null) !== 'mariadb') {
            throw new RuntimeException("El respaldo solo funciona con MySQL; la conexión \"{$conexion}\" usa {$db['driver']}.");
        }

        $carpeta = self::carpeta();
        File::ensureDirectoryExists($carpeta);
        $sql = $carpeta.DIRECTORY_SEPARATOR.$db['database'].'-'.now()->format('Y-m-d-His').'.sql';

        $resultado = Process::env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])
            ->timeout(600)
            ->run([
                config('services.respaldos.mysqldump'),
                '--host='.$db['host'],
                '--port='.$db['port'],
                '--user='.$db['username'],
                '--single-transaction', // copia consistente sin bloquear las tablas
                '--routines',
                '--default-character-set=utf8mb4',
                '--result-file='.$sql,
                $db['database'],
            ]);

        if ($resultado->failed() || ! File::exists($sql)) {
            File::delete($sql);
            throw new RuntimeException('mysqldump falló: '.trim($resultado->errorOutput() ?: $resultado->output() ?: 'sin mensaje').'. Revisa RESPALDO_MYSQLDUMP en .env.');
        }

        $archivo = $sql.'.gz';
        self::comprimir($sql, $archivo);
        File::delete($sql);

        return ['archivo' => $archivo, 'bytes' => File::size($archivo), 'borrados' => $this->limpiar((int) config('services.respaldos.conservar'))];
    }

    /** @return list<string> respaldos borrados por exceder el máximo a conservar */
    public function limpiar(int $conservar): array
    {
        $archivos = collect(File::glob(self::carpeta().DIRECTORY_SEPARATOR.'*.sql.gz'))->sort()->values();
        $sobran = $archivos->slice(0, max(0, $archivos->count() - max(1, $conservar)))->values()->all();
        File::delete($sobran);

        return $sobran;
    }

    public static function carpeta(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.self::CARPETA);
    }

    /** gzip por bloques, para no cargar todo el respaldo en memoria. */
    private static function comprimir(string $origen, string $destino): void
    {
        $entrada = fopen($origen, 'rb');
        $salida = gzopen($destino, 'wb6');
        while (! feof($entrada)) {
            gzwrite($salida, (string) fread($entrada, 1024 * 1024));
        }
        fclose($entrada);
        gzclose($salida);
    }
}
