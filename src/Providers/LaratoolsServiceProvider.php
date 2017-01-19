<?php

namespace Laratools\Providers;

use Illuminate\Support\ServiceProvider;

class LaratoolsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../migrations/' => database_path('migrations')
        ], 'migrations');
    }

    public function register()
    {
        //
    }
}
