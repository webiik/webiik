<?php
namespace Webiik;

class TestMw
{
    private $router;

    /**
     * AuthMw constructor.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function run(Request $request, \Closure $next)
    {
        $this->router->routeInfo['name'] = 'login';
        $next($request);
    }

}