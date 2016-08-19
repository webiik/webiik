<?php
namespace Webiik;

use Pimple\Container;

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
     * Middleware storage
     * Every middleware must be array with the following keys:
     * 'params' - array of parameters, can be whatever
     * 'mw' - callable or string, must be defined by:
     * 1. callable: anonymous function | or
     * 2. string: class name of invokable class eg.: Webiik\ClassName | or
     * 3. string: class name and method to be launched eg.: Webiik\ClassName:launch
     * @var array
     */
    private $middlewares = [];

    /**
     * @var string|callable
     */
    private $routeHandler;

    /**
     * @var Container
     */
    private $container;

    /**
     * Middleware constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add handler that will be run after all middlewares
     * @param $handler
     * @throws \Exception
     */
    public function addDestination($handler)
    {
        $this->checkHandler($handler);
        $this->routeHandler = $handler;
    }

    /**
     * Add array of middlewares
     * @param array $middlewares
     * @throws \Exception
     */
    public function add(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            if (!isset($middleware['params'])) {
                throw new \Exception('Middleware \'' . $middleware . '\' is missing the \'params\' key.');
            }
            $this->checkHandler($middleware);
        }
    }

    /**
     * Run all middlewares
     * @throws \Exception
     */
    public function run()
    {
        if (count($this->middlewares) > 0) {
            // Run first middleware
            $mw = $this->middlewares[0]['mw'];
            $params = $this->middlewares[0]['params'];
            $this->runMiddleware(null, $mw, $params);
        } else {
            $this->runRouteHandler($this->routeHandler);
        }
    }

    /**
     * Check if handler is valid callable or class name with optional method
     * @param $handler
     * @throws \Exception
     */
    private function checkHandler($handler)
    {
        if (is_string($handler)) { // We got class name

            $handler = explode(':', $handler);

            // Does class exist?
            if (!class_exists($handler[0])) {

                throw new \Exception('Class \'' . $handler[0] . '\' does not exist.');

            } else {

                if (isset($handler[1])) { // We got method name

                    // Does method exist in this class?
                    if (!method_exists($handler[0], $handler[1])) {
                        throw new \Exception('Method \'' . $handler[1] . '\' does not exist in class \'' . $handler[0] . '\'.');
                    }
                }
            }
        } elseif (!is_callable($handler)) {
            throw new \Exception('Handler must be callable or string.');
        }
    }

    /**
     * Run middleware and inject its dependencies
     * @param null $request
     * @param $mw
     * @param null $params
     * @throws \Exception
     */
    private function runMiddleware($request = null, $mw, $params = null)
    {
        // Instantiate middleware when is defined by class name and inject dependencies
        if (is_string($mw)) {

            $mw = explode(':', $mw);

            if (class_exists($mw[0])) {

                $method = isset($mw[1]) ? $mw[1] : false;

                if (is_array($params)) {
                    $mw = new $mw[0]($request, ...$params);
                } elseif ($params) {
                    $mw = new $mw[0]($request, $params);
                } else {
                    $mw = new $mw[0]($request);
                }

                // Inject dependencies
                Core::commentDI($mw, $this->container);
                Core::methodDI($mw, $this->container);
            }
        }

        // Check and call middleware with dependencies
        if (is_object($mw) && $mw instanceof \Closure) {

            // Closure
            $mw($request, $this->next(), ...$params);

        } elseif (is_object($mw) && is_callable($mw)) {

            // Invokable class
            $mw($request, $this->next(), ...$params);

        } elseif (is_object($mw) && isset($method) && $method && method_exists($mw, $method)) {

            // Class with valid method
            $mw->$method($request, $this->next(), ...$params);

        } elseif (!is_object($mw)) {

            // No object, closure, invokable or object with valid method
            throw new \Exception('Middleware \'' . $mw[0] . '\' must be callable or valid className:methodName(optional).');
        }
    }

    /**
     * Return callable which runs next middleware
     * @return \Closure
     * @throws \Exception
     */
    private function next()
    {
        $next = function ($request) {

            $nextMw = next($this->middlewares);

            if ($nextMw) {
                $mw = $nextMw['mw'];
                $params = $nextMw['params'];
                $this->runMiddleware($request, $mw, $params);
            } else {
                $this->runRouteHandler($this->routeHandler);
            }
        };

        return $next;
    }

    /**
     * Run route handler and inject its dependencies
     * @param $handler
     * @throws \Exception
     */
    private function runRouteHandler($handler)
    {
        // Instantiate route handler when is defined by class name and inject dependencies
        if (is_string($handler)) {

            $handler = explode(':', $handler);

            if (class_exists($handler[0])) {

                $class = $handler[0];
                $method = isset($handler[1]) ? $handler[1] : false;
                $handler = new $class(...Core::constructorDI($class, $this->container));

                Core::commentDI($handler, $this->container);
                Core::methodDI($handler, $this->container);
            }
        }

        // Check and call handler with dependencies
        if (is_object($handler) && $handler instanceof \Closure) {

            // Closure
            $handler($this->container);

        } elseif (is_object($handler) && is_callable($handler)) {

            // Invokable class
            isset($this->container[$class . ':__invoke']) ? $handler->$method(...$this->container[$class . ':__invoke']) : $handler->$method();

        } elseif (is_object($handler) && isset($method) && $method && method_exists($handler, $method)) {

            // Class with valid method
            isset($this->container[$class . ':' . $method]) ? $handler->$method(...$this->container[$class . ':' . $method]) : $handler->$method();

        } elseif (!is_object($handler)) {

            // No object, closure, invokable or object with valid method
            throw new \Exception('Middleware \'' . $handler[0] . '\' must be callable or valid className:methodName(optional).');
        }
    }
}