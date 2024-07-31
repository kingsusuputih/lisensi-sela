<?php

namespace Kingsusuputih\LisensiSela;

use Illuminate\Support\ServiceProvider;

class SelaLisensiServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Binding dan register service di sini
        new Sela();
    }

    public function boot()
    {
        // Bootstrap service di sini
    }
}
