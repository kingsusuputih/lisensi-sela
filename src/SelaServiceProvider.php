<?php

namespace Kingsusuputih\LisensiSela;

use Illuminate\Support\ServiceProvider;

class SelaServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Binding dan register service di sini
    }

    public function boot()
    {
        // Bootstrap service di sini
    }

    // Tambahkan metode isDeferred jika diperlukan
    public function isDeferred()
    {
        return false;
    }
}
