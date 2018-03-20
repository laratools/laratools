<?php

namespace Laratools\View\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Laratools\Providers\LaratoolsServiceProvider;
use Mockery;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ShareNoticesFromSessionTest extends PHPUnit_Framework_TestCase
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

    public function test_it_makes_notices_available_to_the_view()
    {
        $factory = Mockery::mock(ViewFactory::class);
        $factory->shouldReceive('share')->once()->with('notices', Mockery::type(ViewErrorBag::class));

        $middleware = new ShareNoticesFromSession($factory);

        $session = Mockery::mock(SessionInterface::class);
        $session->shouldReceive('get')->with('notices')->andReturn(new ViewErrorBag());

        $request = Request::create('/');
        $request->setSession($session);

        $middleware->handle($request, function() {});
    }

    public function test_it_flashes_notices_on_redirect()
    {
        $session = Mockery::mock(SessionStore::class);
        $session->shouldReceive('get')->with('notices', Mockery::type(ViewErrorBag::class))->andReturn(new ViewErrorBag());
        $session->shouldReceive('flash')->once()->with('notices', Mockery::type(ViewErrorBag::class));

        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/'));
        $response->setSession($session);

        $provider = Mockery::mock(MessageProvider::class);
        $provider->shouldReceive('getMessageBag')->once()->andReturn(new MessageBag());

        $response->withNotices($provider);
    }

    public function test_redirect_with_notices_array_converts_to_message_bag()
    {
        $session = Mockery::mock(SessionStore::class);
        $session->shouldReceive('get')->with('notices', Mockery::type(ViewErrorBag::class))->andReturn(new ViewErrorBag());
        $session->shouldReceive('flash')->once()->with('notices', Mockery::type(ViewErrorBag::class));

        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/'));
        $response->setSession($session);

        $provider = ['foo' => 'bar'];

        $response->withNotices($provider);
    }
}
