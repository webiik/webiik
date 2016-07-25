<?php
namespace Webiik;

/**
 * Class Route
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 *
 * Route is just helper class for the adding route middlewares.
 * Method 'map' in Webbik's class Core returns this Route object.
 */
class Route
{
    /**
     * @var int
     */
    private $routeId;

    /**
     * @var Middleware
     */
    private $middleware;

    /**
     * @param int $routeId
     */
    public function __construct($routeId, Middleware $middleware)
    {
        $this->routeId = $routeId;
        $this->middleware = $middleware;
    }

    /**
     * @param $callable
     * @return $this
     */
    public function add($callable, $args = null)
    {
        $this->middleware->addRouteMiddleware($this->routeId, $callable, $args);
        return $this;
    }

}