<?php
namespace Webiik;

use Pimple\Container;

class Core
{
    /**
     * @var Container
     */
    private $container;

    /**
     * Core constructor.
     */
    public function __construct()
    {
        $this->container = new Container();

        $this->container['router'] = function ($c) {
            return new Router();
        };

        $this->container['middleware'] = function ($c) {
            return new Middleware();
        };

        $this->container['error404'] = $this->container->protect(function () {
            header('HTTP/1.1 404 Not Found');
            echo '<h1>404</h1>';
        });

        $this->container['error405'] = $this->container->protect(function () {
            header('HTTP/1.1 405 Method Not Allowed');
            echo '<h1>405</h1>';
        });
    }

    public function add($callable, $args = null)
    {
        $this->middleware()->addAppMiddleware($callable, $args);
    }

    public function addService($name, $factory)
    {
        $this->container[$name] = $factory;
    }

    public function addServiceFactory($name, $factory)
    {
        $this->container[$name] = $this->container->factory($factory);
    }

    public function addParam($name, $val)
    {
        $this->container[$name] = $val;
    }

    public function addFunction($name, $function)
    {
        $this->container[$name] = $this->container->protect($function);;
    }

    public function error404($handler)
    {
        $this->container['error404'] = $handler;
    }

    public function error405($handler)
    {
        $this->container['error405'] = $handler;
    }

    public function base($basePath)
    {
        $this->router()->base($basePath);
    }

    public function map($methods, $route, $handler, $name = false)
    {
        $routeId = $this->router()->map($methods, $route, $handler, $name);
        return new Route($routeId, $this->middleware());
    }

    public function run()
    {
        $routeInfo = $this->router()->match();

        // Error handling
        if ($routeInfo['http_status'] == 404) $this->error(404);
        if ($routeInfo['http_status'] == 405) $this->error(405);

        // Assign route handler to middleware destination
        $handler = explode(':', $routeInfo['handler']);
        $className = $handler[0];
        $args[] = $routeInfo;
        if(isset($this->container[$className])){
            $args = array_merge($args, $this->container[$className]);
        }
        $this->middleware()->addDestination($routeInfo['handler'], $args);

        // Run middlewares
        $this->middleware()->run($routeInfo['id']);
    }

    private function error($error)
    {
        $handler = $this->container['error' . $error];

        if(is_callable($handler)){
            $handler();
            exit;
        }

        $handler = explode(':', $handler);
        $className = $handler[0];
        $methodName = $handler[1];
        $handler = new $className;
        $handler->$methodName();
        exit;
    }

    /**
     * @return Router
     */
    private function router()
    {
        return $this->container['router'];
    }

    /**
     * @return Middleware
     */
    private function middleware()
    {
        return $this->container['middleware'];
    }
}