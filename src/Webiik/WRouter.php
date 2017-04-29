<?php

namespace Webiik;

class WRouter extends Router
{
    /**
     * @var WMiddleware
     */
    protected $mw;

    /**
     * WRouter constructor.
     * @param WMiddleware $mw
     */
    public function __construct(WMiddleware $mw)
    {
        $this->mw = $mw;
    }

    /**
     * Map route with router and return Route object that helps to add route middleware(s)
     * @param array $methods
     * @param string $route
     * @param mixed $handler
     * @param bool|string $name
     * @return WRoute
     * @throws \Exception
     */
    public function map($methods = [], $route, $handler, $name = false)
    {
        $routeId = parent::map($methods, $route, $handler, $name);
        return new WRoute($routeId, $this->mw);
    }

    /**
     * Return array with informations about current route
     */
    public function getRouteInfo()
    {
        $this->routeInfo;
    }
}