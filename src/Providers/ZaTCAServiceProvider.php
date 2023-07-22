<?php

namespace Salla\ZATCA\Providers;

use Illuminate\Support\ServiceProvider;

class ZaTCAServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'zatca');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/zatca'),
        ], 'translations');
    }

    public function register()
    {
        //
    }
}
