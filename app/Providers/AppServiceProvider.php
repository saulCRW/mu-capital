<?php

namespace App\Providers;

use App\Modules\Prices\Services\Providers\BanxicoProvider;
use App\Modules\Prices\Services\Providers\FxProvider;
use App\Modules\Prices\Services\Providers\PriceProvider;
use App\Modules\Prices\Services\Providers\YahooFinanceProvider;
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
        $this->app->bind(FxProvider::class, fn () => new BanxicoProvider(config('services.banxico.url'), config('services.banxico.token')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
