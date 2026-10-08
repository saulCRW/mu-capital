<?php

use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Backups\Services\DatabaseBackupService;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Prices\Services\MarketSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('analytics:snapshots {portfolio? : ID del portafolio (vacío = todos)} {--desde= : Recalcular desde esta fecha (AAAA-MM-DD)}', function (SnapshotService $snapshots) {
    $portafolios = $this->argument('portfolio')
        ? Portfolio::whereKey($this->argument('portfolio'))->get()
        : Portfolio::orderBy('id')->get();

    foreach ($portafolios as $portfolio) {
        $filas = $snapshots->recalcular($portfolio, $this->option('desde'));
        $this->line("Portafolio {$portfolio->id} ({$portfolio->nombre}): {$filas} snapshots generados.");
    }
})->purpose('Regenera portfolio_snapshots a partir del libro de transacciones y los precios');

Artisan::command('mercado:sincronizar {--desde= : Volver a traer desde esta fecha (AAAA-MM-DD)}', function (MarketSyncService $mercado) {
    $r = $mercado->sincronizar($this->option('desde'));
    $this->info("Precios: {$r['precios']} · Índices: {$r['indices']} · Tipos de cambio: {$r['tasas']} · Portafolios recalculados: {$r['portafolios']}");
    foreach ($r['avisos'] as $aviso) {
        $this->warn($aviso);
    }
    foreach ($r['errores'] as $error) {
        $this->error($error);
    }
})->purpose('Trae precios, índices y tipos de cambio de los proveedores y recalcula los portafolios');

Artisan::command('base:respaldar', function (DatabaseBackupService $respaldo) {
    try {
        $r = $respaldo->respaldar();
    } catch (RuntimeException $e) {
        $this->error($e->getMessage());

        return 1;
    }
    $this->info('Respaldo creado: '.$r['archivo'].' ('.number_format($r['bytes'] / 1024, 1).' KB).');
    foreach ($r['borrados'] as $viejo) {
        $this->line('Borrado por antigüedad: '.basename($viejo));
    }

    return 0;
})->purpose('Respalda la base de datos (mysqldump + gzip) en storage/app/private/respaldos y conserva los últimos');

// Respaldo diario de la base a las 2:00.
Schedule::command('base:respaldar')->dailyAt('02:00')->timezone('America/Mexico_City')->withoutOverlapping();

// Mercado: cada 15 minutos en horario de bolsa (precio del día casi en tiempo real) y un cierre por la noche.
// En el servidor necesita el cron de Laravel (o `php artisan schedule:work` en desarrollo).
Schedule::command('mercado:sincronizar')->weekdays()->everyFifteenMinutes()
    ->between('8:30', '15:30')->timezone('America/Mexico_City')->withoutOverlapping();
Schedule::command('mercado:sincronizar')->dailyAt('18:00')->timezone('America/Mexico_City')->withoutOverlapping();
