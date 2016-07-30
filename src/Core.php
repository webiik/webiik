<?php
namespace Webiik;

use Pimple\Container;

class Core
{
    // Todo: Write comments

    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * Core constructor.
     */
    public function __construct($config = [])
    {
        $this->container = new Container();
        $this->container = new Container();

        $this->container['router'] = function ($c) {
            return new Router();
        };
        $this->routerBase();

        $this->addParam('config', $config);
    }

    public function add($mw, $args = null, $routeId = null)
    {
        if (is_numeric($routeId)) {
            $this->middlewares[$routeId][] = ['mw' => $mw, 'args' => $args];
        } else {
            $this->middlewares['app'][] = ['mw' => $mw, 'args' => $args];
        }
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

    public function map($methods, $route, $handler, $name = false)
    {
        $routeId = $this->router()->map($methods, $route, $handler, $name);
        return new Route($routeId, $this);
    }

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
        $middleware->run(array_merge(
            array_reverse($this->middlewares['app']),
            array_reverse($this->middlewares[$routeInfo['id']]),
            [['mw' => $routeInfo['handler'], 'args' => $args]]
        ));
    }

    public function error($error)
    {
        if (isset($this->container['error' . $error])) {

            $handler = $this->container['error' . $error];
            $handler = explode(':', $handler);
            $className = $handler[0];
            $methodName = $handler[1];
            $handler = new $className;
            $handler->$methodName();

        } else {

            if ($error == 404) header('HTTP/1.1 404 Not Found');
            if ($error == 404) header('HTTP/1.1 405 Method Not Allowed');
            echo '<h1>' . $error . '</h1>';
        }

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
     * Set Router base path
     */
    private function routerBase()
    {
        $this->router()->base(dirname($_SERVER['SCRIPT_NAME']));
    }
}