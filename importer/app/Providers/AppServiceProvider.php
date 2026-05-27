<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // PHP 8.2 deprecaterar `${var}`-syntaxen som används i facade/ignition
        // (paketet är arkiverat och fixas inte). Utan denna rad konverterar
        // Laravels error-handler deprecation-notisen till en fatal exception
        // när Ignition autoloadar sina SolutionProviders under felrendering,
        // vilket dödar artisan-kommandon mitt i en loop.
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
