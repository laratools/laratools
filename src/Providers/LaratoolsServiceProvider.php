<?php

namespace Laratools\Providers;

use Illuminate\Database\Schema\Blueprint;
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
        $this->registerBinaryUuid();
    }

    protected function registerBinaryUuid()
    {
        Blueprint::macro('binaryUuid', function (string $name = 'uuid') {
            /** @var Blueprint $this */
            return $this->addColumn('binaryUuid', $name);
        });
    }

    }
}
