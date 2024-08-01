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
            __DIR__ . '/config/sela-lisensi.php',
            'view'
        );

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'LisensiSela');
    }

    public function boot()
    {
        // Publikasikan konfigurasi
        $this->publishes([
            __DIR__ . '/config/sela-lisensi.php' => config_path('sela-lisensi.php'),
        ], 'config');

        // Publikasikan aset gambar
        $this->publishes([
            __DIR__ . '/assets/lisensi.png' => public_path('assets/defaults/gambar/lisensi.png'),
        ], 'assets');
    }
}
