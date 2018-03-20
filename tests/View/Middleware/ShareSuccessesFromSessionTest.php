<?php

namespace Laratools\View\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Laratools\Providers\LaratoolsServiceProvider;
use Illuminate\Session\Store as SessionStore;
use Mockery;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ShareSuccessesFromSessionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        (new LaratoolsServiceProvider(Mockery::mock(Application::class)))->registerRedirectResponseMacros();
    }

    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_makes_successes_available_to_the_view()
    {
        $factory = Mockery::mock(ViewFactory::class);
        $factory->shouldReceive('share')->once()->with('successes', Mockery::type(ViewErrorBag::class));

        $middleware = new ShareSuccessesFromSession($factory);

        $session = Mockery::mock(SessionInterface::class);
        $session->shouldReceive('get')->with('successes')->andReturn(new ViewErrorBag());

        $request = Request::create('/');
        $request->setSession($session);

        $middleware->handle($request, function() {});
    }

    public function test_it_flashes_successes_on_redirect()
    {
        $session = Mockery::mock(SessionStore::class);
        $session->shouldReceive('get')->with('successes', Mockery::type(ViewErrorBag::class))->andReturn(new ViewErrorBag());
        $session->shouldReceive('flash')->once()->with('successes', Mockery::type(ViewErrorBag::class));

        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/'));
        $response->setSession($session);

        $provider = Mockery::mock(MessageProvider::class);
        $provider->shouldReceive('getMessageBag')->once()->andReturn(new MessageBag());

        $response->withSuccesses($provider);
    }

    public function test_redirect_with_successes_array_converts_to_message_bag()
    {
        $session = Mockery::mock(SessionStore::class);
        $session->shouldReceive('get')->with('successes', Mockery::type(ViewErrorBag::class))->andReturn(new ViewErrorBag());
        $session->shouldReceive('flash')->once()->with('successes', Mockery::type(ViewErrorBag::class));

        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/'));
        $response->setSession($session);

        $provider = ['foo' => 'bar'];

        $response->withSuccesses($provider);
    }
}
