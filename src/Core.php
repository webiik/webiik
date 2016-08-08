<?php
namespace Webiik;

use Pimple\Container;

class Core
{
    // Todo: Write comments

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
    public function __construct($config = [])
    {
        $this->container = new Container();

        $this->container['router'] = function ($c) {
            return new Router();
        };
        $this->router()->base($this->getScriptDir());

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
        $appMiddleawares = isset($this->middlewares['app']) ? $this->middlewares['app'] : [];
        $routeMiddlewares = isset($this->middlewares[$routeInfo['id']]) ? $this->middlewares[$routeInfo['id']] : [];
        $middleware->run(array_merge(
            array_reverse($appMiddleawares),
            array_reverse($routeMiddlewares),
            [['mw' => $routeInfo['handler'], 'args' => $args]]
        ));
    }

    public function error($error)
    {
        if (isset($this->container['error' . $error])) {

            $handlerStr = $this->container['error' . $error];
            $handlerStr = explode(':', $handlerStr);
            $className = $handlerStr[0];
            $handler = new $className();
            if(isset($handlerStr[1])) {
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