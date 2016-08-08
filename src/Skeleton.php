<?php
namespace Webiik;

/**
 * Class Skeleton
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Skeleton extends Core
{
    /**
     * Skeleton constructor.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->addService('translation', function ($c) {
            return new Translation();
        });

        $this->addService('conversion', function ($c) {
            return new Conversion();
        });

        $this->addService('connection', function ($c) {
            return new Connection();
        });
    }

    /**
     * @return Connection
     */
    private function connection()
    {
        return $this->container['connection'];
    }

    /**
     * @return Translation
     */
    private function trans()
    {
        return $this->container['trans'];
    }

    /**
     * @return Conversion
     */
    private function conv()
    {
        return $this->container['conv'];
    }

    private function detectLang()
    {
        return 'en';
    }

    private function loadRoutes()
    {
    }

    private function loadTrans($routeName)
    {
        // Check and load shared/app translations

        // Check and load route translations
        return $routeName;
    }

    public function run()
    {
        // Detect lang from URI
        $lang = $this->detectLang();

        // Load routes for that lang
        $this->loadRoutes();

        // Match URI against defined routes
        $routeInfo = $this->router()->match();

        // If there is no match, show error page
        if ($routeInfo['http_status'] == 404) $this->error(404);
        if ($routeInfo['http_status'] == 405) $this->error(405);

        // Get handler class name
        $handler = explode(':', $routeInfo['handler']);
        $className = $handler[0];

        // Prepare args for current route handler
        $args[] = array_merge(
            $routeInfo,
            ['base' => $this->getWebRoot()],
            ['lang' => $lang]
        );
        if (isset($this->container[$className])) {
            $args = array_merge($args, $this->container[$className]);
        }

        // Todo: Load DI?

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

    private function getHostRoot()
    {
        $pageURL = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $pageURL .= 's';
        }
        $pageURL .= '://' . $_SERVER['SERVER_NAME'];

        return $pageURL;
    }

    private function getWebRoot()
    {
        $pageURL = $this->getHostRoot();
        $pageURL .= rtrim($this->getScriptDir(), '/');
        return $pageURL;
    }
}