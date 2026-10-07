<?php

use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Portfolios\Models\Portfolio;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
