<?php

namespace Laratools\View\Middleware;

use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ViewErrorBag;

class ShareNoticesFromSession
{
    /**
     * @var ViewFactory
     */
    protected $view;

    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->view->share(
            'notices', $request->session()->get('notices') ?: new ViewErrorBag()
        );

        return $next($request);
    }
}
