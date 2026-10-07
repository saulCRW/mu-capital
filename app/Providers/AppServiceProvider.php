<?php

namespace App\Providers;

use App\Modules\Prices\Services\Providers\BanxicoProvider;
use App\Modules\Prices\Services\Providers\FxProvider;
use App\Modules\Prices\Services\Providers\PriceProvider;
use App\Modules\Prices\Services\Providers\YahooFinanceProvider;
use App\Modules\Prices\Services\Providers\YahooFxProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Proveedores de mercado gratuitos; para cambiar de proveedor basta con otra implementación.
        $this->app->bind(PriceProvider::class, fn () => new YahooFinanceProvider(config('services.yahoo_finance.url')));
        // Tipo de cambio: Banxico (oficial) si hay token; si no, Yahoo Finance.
        $this->app->bind(FxProvider::class, fn ($app) => filled(config('services.banxico.token'))
            ? new BanxicoProvider(config('services.banxico.url'), config('services.banxico.token'))
            : new YahooFxProvider($app->make(PriceProvider::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
