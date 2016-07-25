<?php
namespace Webiik;

/**
 * Class Middleware
 * @package Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Middleware
{
    /**
     * Into destination we store all informations needed to create route handler.
     * We will create route handler, after all middlewares are completed.
     *
     * In destination we store under the following keys:
     * 'class' - string, route handler class name
     * 'method' - string, method to invoke
     * 'routeInfo' - array, basic route info
     * 'args' - array, args to be injected into handler class during its construction
     *
     * @var array
     */
    private $destination = [];

    /**
     * Here we store all middleware invokables.
     *
     * In middlewares we store under the following keys:
     * 'app' - array, all app middlewares
     * '{routeId}' - array, all route middlewares
     *
     * @var array
     */
    private $middlewares = [];

    /**
     * @param $callable
     */
    public function addAppMiddleware($callable)
    {
        $this->middlewares['app'][] = $callable;
    }

    /**
     * @param $routeId
     * @param $callable
     */
    public function addRouteMiddleware($routeId, $callable)
    {
        $this->middlewares[$routeId][] = $callable;
    }

    /**
     * @param $routeInfo
     * @param $handler
     * @param bool $method
     * @param bool $args
     */
    public function addDestination($routeInfo, $handler, $method = false, $args = false)
    {
        if (is_callable($handler)) {
            $this->destination['fn'] = $handler;
        } else {
            $this->destination['class'] = $handler;
            $this->destination['method'] = $method;
        }

        $this->destination['routeInfo'] = $routeInfo;
        $this->destination['args'] = $args;
    }

    /**
     * Run all middlewares
     * @param $routeId
     */
    public function run($routeId)
    {
        $this->middlewares = array_merge($this->getAppMiddlewares(), $this->getRouteMiddlewares($routeId));

        if (isset($this->middlewares[0])) {
            $this->middlewares[0](null, $this->next());
        } else {
            $this->getDestination(null);
        }
    }

    /**
     * Return invokable which runs next middleware or route handler
     * @return \Closure
     */
    private function next()
    {
        $next = function ($response) {
            $mw = next($this->middlewares);
            if (is_callable($mw)) {
                $mw($response, $this->next());
            } else {
                $destination = function ($response) {
                    $this->getDestination($response);
                };
                $destination($response);
            }
        };

        return $next;
    }

    /**
     * @return array
     */
    private function getAppMiddlewares()
    {
        if (isset($this->middlewares['app'])) {
            return array_reverse($this->middlewares['app']);
        }
        return [];
    }

    /**
     * @param $routeId
     * @return array
     */
    private function getRouteMiddlewares($routeId)
    {
        if (isset($this->middlewares[$routeId])) {
            return array_reverse($this->middlewares[$routeId]);
        }
        return [];
    }

    /**
     * Run route handler and inject dependencies during its construction
     * @param $response
     */
    private function getDestination($response)
    {
        if (isset($this->destination['fn'])) {
            $this->destination['fn']();
        } else {
            $class = $this->destination['class'];
            $method = $this->destination['method'];
            $routeInfo = $this->destination['routeInfo'];
            $args = $this->destination['args'];
            $handler = new $class($response, $routeInfo, ...$args);
            $handler->$method();
        }
    }
}