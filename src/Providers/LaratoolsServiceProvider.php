<?php

namespace Laratools\Providers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ViewErrorBag;

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
    }

    protected function registerRedirectResponseMacros()
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
}
