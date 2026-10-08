<?php

namespace Tests\Feature\Backups;

use App\Modules\Backups\Services\DatabaseBackupService;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/** Respaldo de la base: mysqldump simulado; los archivos van a una carpeta temporal, no a storage real. */
class DatabaseBackupTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mu-respaldos-'.uniqid();
        $this->app->useStoragePath($this->storage);
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => '3306', 'database' => 'mu_prueba', 'username' => 'root', 'password' => 'secreta'],
            'services.respaldos' => ['mysqldump' => 'mysqldump', 'conservar' => 3],
        ]);
        $this->travelTo('2026-10-07 02:00:00');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        parent::tearDown();
    }

    public function test_genera_el_respaldo_comprimido_sin_poner_la_contrasena_en_el_comando(): void
    {
        Process::fake(function (PendingProcess $proceso) {
            $archivo = substr(collect($proceso->command)->first(fn ($a) => str_starts_with($a, '--result-file=')), strlen('--result-file='));
            file_put_contents($archivo, "CREATE TABLE `portfolios` (id int);\n");

            return Process::result();
        });

        $this->artisan('base:respaldar')->assertSuccessful();

        $archivo = DatabaseBackupService::carpeta().DIRECTORY_SEPARATOR.'mu_prueba-2026-10-07-020000.sql.gz';
        $this->assertFileExists($archivo);
        $this->assertSame("CREATE TABLE `portfolios` (id int);\n", gzdecode(file_get_contents($archivo)));
        $this->assertFileDoesNotExist(substr($archivo, 0, -3)); // el .sql sin comprimir se borra

        Process::assertRan(fn (PendingProcess $p) => $p->environment === ['MYSQL_PWD' => 'secreta']
            && ! in_array('secreta', $p->command, true)
            && in_array('--single-transaction', $p->command, true)
            && end($p->command) === 'mu_prueba');
    }

    public function test_conserva_solo_los_ultimos(): void
    {
        File::ensureDirectoryExists(DatabaseBackupService::carpeta());
        foreach (['01', '02', '03', '04', '05'] as $dia) {
            touch(DatabaseBackupService::carpeta().DIRECTORY_SEPARATOR."mu_prueba-2026-10-{$dia}-020000.sql.gz");
        }

        $borrados = app(DatabaseBackupService::class)->limpiar(3);

        $this->assertSame(['mu_prueba-2026-10-01-020000.sql.gz', 'mu_prueba-2026-10-02-020000.sql.gz'], array_map('basename', $borrados));
        $this->assertCount(3, File::glob(DatabaseBackupService::carpeta().DIRECTORY_SEPARATOR.'*.sql.gz'));
    }

    public function test_si_mysqldump_falla_avisa_y_no_deja_archivos(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'Access denied for user', exitCode: 2)]);

        $this->artisan('base:respaldar')
            ->expectsOutputToContain('mysqldump falló: Access denied for user')
            ->assertFailed();

        $this->assertSame([], File::glob(DatabaseBackupService::carpeta().DIRECTORY_SEPARATOR.'*'));
    }

    public function test_solo_respalda_mysql(): void
    {
        config(['database.default' => 'sqlite']);

        $this->artisan('base:respaldar')->expectsOutputToContain('El respaldo solo funciona con MySQL')->assertFailed();
    }
}
