<?php

namespace Kingsusuputih\LisensiSela;

use Illuminate\Support\ServiceProvider;

class SelaLisensiServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Binding singleton untuk SelaLisensi
        $this->app->singleton(SelaLisensi::class, function ($app) {
            return new SelaLisensi();
        });

        $this->mergeConfigFrom(
            __DIR__ . '/config/view.php',
            'view'
        );

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'LisensiSela');
    }

    public function boot()
    {
        // Inisialisasi SelaLisensi saat boot
        $this->publishes([
            __DIR__ . '/config/view.php' => config_path('view.php'),
        ], 'config');
    }
}
