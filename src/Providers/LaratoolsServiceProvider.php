<?php

namespace Laratools\Providers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ViewErrorBag;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\Grammar as QueryGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar as SchemaGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as IlluminateMySqlGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar as IlluminateSQLiteGrammar;
use Laratools\Database\Schema\Grammar\MySqlGrammar;
use Laratools\Database\Schema\Grammar\SQLiteGrammar;

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
        $this->registerRedirectResponseMacros();
        $this->registerBinaryUuid();
    }

    public function registerRedirectResponseMacros()
    {
        if (! RedirectResponse::hasMacro('withNotices')) {
            RedirectResponse::macro('withNotices', function ($provider, $key = 'default') {
                /** @var RedirectResponse $this */
                $value = $this->parseErrors($provider);

                $notices = $this->session->get('notices', new ViewErrorBag());

                if (! $notices instanceof ViewErrorBag) {
                    $notices = new ViewErrorBag();
                }

                $this->session->flash(
                    'notices', $notices->put($key, $value)
                );

                return $this;
            });
        }

        if (! RedirectResponse::hasMacro('withSuccesses')) {
            RedirectResponse::macro('withSuccesses', function ($provider, $key = 'default') {
                $value = $this->parseErrors($provider);

                $successes = $this->session->get('successes', new ViewErrorBag());

                if (! $successes instanceof ViewErrorBag) {
                    $successes = new ViewErrorBag();
                }

                $this->session->flash(
                    'successes', $successes->put($key, $value)
                );

                return $this;
            });
        }
    }

    protected function registerBinaryUuid()
    {
        Blueprint::macro('binaryUuid', function (string $name = 'uuid') {
            /** @var Blueprint $this */
            return $this->addColumn('binaryUuid', $name);
        });

        /** @var Connection $connection */
        $connection = $this->app->make('db')->connection();
        $connection->setSchemaGrammar($this->makeGrammar($connection));
    }

    protected function makeGrammar(Connection $connection): SchemaGrammar
    {
        $original = $connection->getQueryGrammar();
        $enhanced = $this->getEnhancedGrammarFromIlluminate($original);

        $enhanced->setTablePrefix($original->getTablePrefix());

        return $enhanced;
    }

    protected function getEnhancedGrammarFromIlluminate(QueryGrammar $original): SchemaGrammar
    {
        switch (get_class($original)) {
            case IlluminateMySqlGrammar::class:
                return new MySqlGrammar();
            case IlluminateSQLiteGrammar::class:
                return new SQLiteGrammar();
            default:
                throw new Exception(
                    sprintf(
                        'Only MySQL and SQLite grammars support binary uuids. [%s] was used',
                        get_class($original)
                    )
                );
        }
    }
}
