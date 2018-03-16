<?php

namespace Laratools\Providers;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\ServiceProvider;
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
        $this->registerBinaryUuid();
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

    protected function makeGrammar(Connection $connection): Grammar
    {
        $original = $connection->getSchemaGrammar();
        $enhanced = $this->getEnhancedGrammarFromIlluminate($original);

        $enhanced->setTablePrefix($original->getTablePrefix());

        return $enhanced;
    }

    protected function getEnhancedGrammarFromIlluminate(Grammar $original): Grammar
    {
        switch(get_class($original))
        {
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
