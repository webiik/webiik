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

        // Add Router to container
        $this->container['Webiik\Router'] = function ($c) {
            return new Router();
        };

        // Set router base path
        $this->router()->base($this->getScriptDir());
    }

    /**
     * Add app or route (with route id) middleware to middlewares array
     * @param string|callable $mw : ClassName:method or ClassName or callable
     * @param mixed $params
     * @param number $routeId
     */
    public function add($mw, $params = null, $routeId = null)
    {
        if (is_numeric($routeId)) {
            $this->middlewares[$routeId][] = ['mw' => $mw, 'params' => $params];
        } else {
            $this->middlewares['app'][] = ['mw' => $mw, 'params' => $params];
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

        // Store route info into container to allow its injection in route handlers and middlewares
        $this->addParam('routeInfo', $routeInfo);

        // Instantiate middleware
        $middleware = new Middleware($this->container);

        // Add route handler to be run after last middleware
        $middleware->addDestination($routeInfo['handler']);

        // Add app and route middlewares
        $appMiddleawares = isset($this->middlewares['app']) ? array_reverse($this->middlewares['app']) : [];
        $routeMiddlewares = isset($this->middlewares[$routeInfo['id']]) ? array_reverse($this->middlewares[$routeInfo['id']]) : [];
        $middleware->add(array_merge($appMiddleawares, $routeMiddlewares));

        // Run
        $middleware->run();
    }

    /**
     * Inject dependencies from Container to $object using @inject doc comment
     */
    public static function commentDI($object, Container $container)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $pn = $property->name;
            preg_match('/(\$?(\w*))\s@inject/', $reflection->getProperty($property->name)->getDocComment(), $match);
            if (isset($match[1])) {
                if ($match[1][0] == '$') {
                    // Property isn't class
                    $object->$pn = $container[$match[2]];
                } else {
                    // Property is class
                    $object->$pn = $container[$reflection->getNamespaceName() . '\\' . $match[1]];
                }
            }
        }
    }

    /**
     * Inject dependencies from Container to $object using object constructor method
     */
    public static function constructorDI($className, Container $container)
    {
        return self::prepareMethodParameters($className, '__construct', $container);
    }

    /**
     * Inject dependencies from Container to $object using object methods with inject prefix
     */
    public static function methodDI($object, Container $container)
    {
        $methods = get_class_methods($object);
        foreach ($methods as $method) {
            preg_match('/^inject([A-Z]\w*)$/', $method, $match);
            if (isset($match[0]) && isset($match[1])) {
                $object->$method(...self::prepareMethodParameters($object, $method, $container));
            }
        }
    }

    /**
     * Return array with prepared parameters for give class and method
     * @param $className
     * @param $methodName
     * @param Container $container
     * @return array
     */
    private static function prepareMethodParameters($className, $methodName, Container $container)
    {
        $p = [];

        if (method_exists($className, $methodName)) {
            $reflection = new \ReflectionMethod($className, $methodName);
            $params = $reflection->getParameters();
            foreach ($params as $param) {

                $class = $param->getClass();

                if ($class) {
                    // parameter is class
                    $itemName = $param->getClass()->getName();
                } else {
                    // parameter isn't class
                    $itemName = $param->getName();
                }

                if ($param->isOptional()) {
                    if (isset($container[$itemName])) {
                        $p[] = $container[$itemName];
                    }
                } else {
                    $p[] = $container[$itemName];
                }
            }
        }

        return $p;
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
        return $this->container['Webiik\Router'];
    }

    /**
     * Set Router base path
     */
    protected function getScriptDir()
    {
        return dirname($_SERVER['SCRIPT_NAME']);
    }
}