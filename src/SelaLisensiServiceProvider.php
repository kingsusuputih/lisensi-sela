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
    }

    public function boot()
    {
        // Inisialisasi SelaLisensi saat boot
    }
}
