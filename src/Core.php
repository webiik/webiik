<?php
namespace Webiik;

use Pimple\Container;

class Core
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * Core constructor.
     */
    public function __construct()
    {
        // Create Pimple container
        $this->container = new Container();

        // Add router to container
        $this->container['router'] = function ($c) {
            return new Router();
        };

        // Set router base path
        $this->router()->base($this->getScriptDir());
    }

    /**
     * Add app or route (with route id) middleware to middlewares array
     * @param string|callable $mw : ClassName:method or ClassName or callable
     * @param mixed $args
     * @param number $routeId
     */
    public function add($mw, $args = null, $routeId = null)
    {
        if (is_numeric($routeId)) {
            $this->middlewares[$routeId][] = ['mw' => $mw, 'args' => $args];
        } else {
            $this->middlewares['app'][] = ['mw' => $mw, 'args' => $args];
        }
    }

    /**
     * Add Pimple service
     * @param string $name
     * @param callable $factory
     */
    public function addService($name, $factory)
    {
        $this->container[$name] = $factory;
    }

    /**
     * Add Pimple service factory
     * @param string $name
     * @param callable $factory
     */
    public function addServiceFactory($name, $factory)
    {
        $this->container[$name] = $this->container->factory($factory);
    }

    /**
     * Add value into Pimple container
     * @param string $name
     * @param mixed $val
     */
    public function addParam($name, $val)
    {
        $this->container[$name] = $val;
    }

    /**
     * Add function into Pimple container
     * @param string $name
     * @param callable $function
     */
    public function addFunction($name, $function)
    {
        $this->container[$name] = $this->container->protect($function);;
    }

    /**
     * Add controller to 404 route
     * @param string $handler : ClassName or ClassName:method
     */
    public function error404($handler)
    {
        $this->container['error404'] = $handler;
    }

    /**
     * Add controller to 404 route
     * @param string $handler : ClassName or ClassName:method
     */
    public function error405($handler)
    {
        $this->container['error405'] = $handler;
    }

    /**
     * Map route with router and return Route object that helps to add route middleware(s)
     * @param array $methods
     * @param string $route
     * @param mixed $handler
     * @param bool|string $name
     * @return Route
     * @throws \Exception
     */
    public function map($methods, $route, $handler, $name = false)
    {
        $routeId = $this->router()->map($methods, $route, $handler, $name);
        return new Route($routeId, $this);
    }

    /**
     * Run Webiik
     * @throws \Exception
     */
    public function run()
    {
        $routeInfo = $this->router()->match();

        // Error handling
        if ($routeInfo['http_status'] == 404) $this->error(404);
        if ($routeInfo['http_status'] == 405) $this->error(405);

        // Get handler class name
        $handler = explode(':', $routeInfo['handler']);
        $className = $handler[0];

        // Prepare args for current route handler
        $args[] = $routeInfo;
        if (isset($this->container[$className])) {
            $args = array_merge($args, $this->container[$className]);
        }

        // Run middlewares
        $middleware = new Middleware();
        $appMiddleawares = isset($this->middlewares['app']) ? $this->middlewares['app'] : [];
        $routeMiddlewares = isset($this->middlewares[$routeInfo['id']]) ? $this->middlewares[$routeInfo['id']] : [];
        $middleware->run(array_merge(
            array_reverse($appMiddleawares),
            array_reverse($routeMiddlewares),
            [['mw' => $routeInfo['handler'], 'args' => $args]]
        ));
    }

    /**
     * Run 404/405 error handler if is defined or just return adequate response. Always exit.
     * @param number $error
     */
    protected function error($error)
    {
        if (is_numeric($error) && isset($this->container['error' . $error])) {

            $handlerStr = $this->container['error' . $error];
            $handlerStr = explode(':', $handlerStr);
            $className = $handlerStr[0];
            $handler = new $className();
            if (isset($handlerStr[1])) {
                $methodName = $handlerStr[1];
                $handler->$methodName();
            }

        } else {

            if ($error == 404) header('HTTP/1.1 404 Not Found');
            if ($error == 404) header('HTTP/1.1 405 Method Not Allowed');
            echo '<h1>' . $error . '</h1>';
        }

        exit;
    }

    /**
     * Return router from Pimple container
     * @return Router
     */
    protected function router()
    {
        return $this->container['router'];
    }

    /**
     * Set Router base path
     */
    protected function getScriptDir()
    {
        return dirname($_SERVER['SCRIPT_NAME']);
    }
}