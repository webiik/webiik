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
        $this->checkHandler($handler, false);
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
            $this->checkHandler($middleware['mw']);
        }

        $this->middlewares = $middlewares;
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
            $this->runMiddleware($this->container['Webiik\Request'], $mw, $params);
        } else {
            $this->runRouteHandler($this->routeHandler);
        }
    }

    /**
     * Check if handler is valid callable or class name with optional method
     * @param mixed $handler
     * @param bool $isMw
     * @throws \Exception
     */
    private function checkHandler($handler, $isMw = true)
    {
        if (is_string($handler)) { // We got class name

            $handler = explode(':', $handler);

            // Does class exist?
            if (!class_exists($handler[0])) {
                throw new \Exception('Class \'' . $handler[0] . '\' does not exist.');
            }

            // Has middleware valid method?
            if ($isMw && !isset($handler[1])) {
                throw new \Exception('Middleware defined only by className \'' . $handler[0] . '\' but should be closure, invokable class or className:methodName.');
            }

            // If we got method name, check if method exists
            if (isset($handler[1]) && !method_exists($handler[0], $handler[1])) {
                throw new \Exception('Method \'' . $handler[1] . '\' does not exist in class \'' . $handler[0] . '\'.');
            }

        } else { // We got something different than string

            if($isMw && is_array($handler)) {
                throw new \Exception('Middleware must be defined by closure, invokable class or className:methodName. Array given.');
            }

            if(!$isMw && is_array($handler)) {
                throw new \Exception('Controller must be defined by closure, className or className:methodName. Array given.');
            }

            // It can be ok, if it is middleware and it is callable
            if ($isMw && !is_callable($handler)) {
                throw new \Exception('Middleware must be defined by closure, invokable class or className:methodName.');
            }

            // It can be ok, if it is controller and it is closure
            if (!$isMw && !$handler instanceof \Closure) {
                throw new \Exception('Controller must be defined by closure, className or className:methodName.');
            }
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
            $method = isset($mw[1]) ? $mw[1] : false;
            $class = $mw[0];

            if (isset($this->container[$class])) { // Is middleware in container?

                // Get middleware from container
                $mw = $this->container[$class];

            } elseif ((class_exists($class))) { // Middleware isn't in container but is valid class

                // Instantiate that class
                if (method_exists($class, '__construct')) {
                    $mw = new $class(...Core::constructorDI($class, $this->container));
                } else {
                    $mw = new $class();
                }

                // Inject dependencies
                Core::commentDI($mw, $this->container);
                Core::methodDI($mw, $this->container);
            }
        }

        // Check and call middleware with dependencies
        if (is_object($mw)) {

            if ($mw instanceof \Closure) {

                // Closure
                $mw($request, $this->next(), ...$params);

            } elseif (is_callable($mw)) {

                // Invokable
                $mw($request, $this->next(), ...$params);

            } elseif (isset($method) && $method && method_exists($mw, $method)) {

                // Class:method
                $mw->$method($request, $this->next(), ...$params);

            }
        }

        // Unable to create middleware
        if (!is_object($mw)) {
            throw new \Exception('Unable to run middleware. Middleware \'' . $mw . '\' must be defined by valid closure, invokable class or className:methodName.');
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
        // Instantiate route handler if is defined by class name and inject dependencies
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
        if (is_object($handler)) {

            if ($handler instanceof \Closure) {

                // Closure
                $handler($this->container);

            } elseif (is_object($handler) && isset($method) && $method && method_exists($handler, $method)) {

                // Class with valid method
                $handler->$method();
            }
        }

        // Unable to create route handler
        if (!is_object($handler)) {
            throw new \Exception('Unable to run controller. Controller \'' . $handler . '\' must be defined by valid closure, className or className:methodName.');
        }
    }
}