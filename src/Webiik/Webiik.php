<?php

namespace Webiik;

use Pimple\Container;

class Webiik
{
    /**
     * @var \Pimple\Container
     */
    protected $c;

    public function __construct($config = [])
    {
        // Create Pimple Container
        $this->c = new \Pimple\Container();

        // Add Webiik\Container to Pimple\Container
        $this->c['Webiik\WContainer'] = function ($c) {
            return new WContainer($c);
        };

        // Add stuff to Pimple\Container via Webiik\Container...

        // Add config array
        $this->container()->addParam('WConfig', $config);

        // Add Error
        $this->container()->addService('Webiik\Error', function ($c) {
            $silent = isset($c['WConfig']['Error']['silent']) ? $c['WConfig']['Error']['silent'] : false;
            $err = new \Webiik\Error($silent);
            return $err;
        });

        // Add Log
        $this->container()->addService('Webiik\Log', function ($c) {
            $log = new Log();
            if (isset($c['WConfig']['Log']['timeZone'])) $log->setTimeZone($c['WConfig']['Log']['timeZone']);
            return $log;
        });

        // Add function to add Log handlers
        $this->container()->addFunction('getLogger', function ($name, $level, $emailHandler = null) {

            $logHandlerExists = false;

            // If LogHandlerRotatingFile is configured, add this log handler to Log
            if (isset($this->c['WConfig']['Log']['LogHandlerRotatingFile'])) {
                $this->log()->addHandler($name, '\Webiik\LogHandlerRotatingFile', [
                    $this->c['WConfig']['Log']['LogHandlerRotatingFile']['dir'],
                    $name
                ]);
                $logHandlerExists = true;
            }

            // If LogHandlerEmail is configured, add this log handler to Log
            if (isset($this->c['WConfig']['Log']['LogHandlerEmail'])) {
                $this->log()->addHandler($name, '\Webiik\LogHandlerEmail', [
                    $this->c['WConfig']['Log']['LogHandlerEmail']['recipient'],
                    $this->c['WConfig']['Log']['LogHandlerEmail']['sender'],
                    $this->c['WConfig']['Log']['LogHandlerEmail']['subject'],
                    $this->c['WConfig']['Log']['LogHandlerEmail']['dir'],
                    $name,
                    'log',
                    $emailHandler
                ]);
                $logHandlerExists = true;
            }

            if ($logHandlerExists) {
                $logger = function ($data) use ($name, $level) {
                    $this->log()->log($name, json_encode($data), $level);
                };
            } else {
                $logger = function ($data) {};
            }

            return $logger;
        });

        // Add Request
        $this->container()->addService('Webiik\Request', function ($c) {
            return new Request();
        });

        // Add WMiddleware
        $this->container()->addService('Webiik\WMiddleware', function ($c) {
            return new WMiddleware($this->container());
        });

        // Add WRouter
        $this->container()->addService('Webiik\WRouter', function ($c) {

            $router = new WRouter($this->mw());

            // Configure router...
            $router->setBasePath($this->request()->getWebRootPath());

            if (isset($c['WConfig']['Translation']['languages']) && is_array($c['WConfig']['Translation']['languages'])) {
                $defaultLang = array_keys($c['WConfig']['Translation']['languages'])[0];
                $router->setDefaultLang($defaultLang);
            }

            if (isset($c['WConfig']['Router']['dlInUri'])) {
                $router->setConfig(['defaultLangInUri' => $c['WConfig']['Router']['dlInUri']]);
            }

            if (isset($c['WConfig']['Router']['logWarnings'])) {
                $router->setConfig(['logWarnings' => $c['WConfig']['Router']['logWarnings']]);
            }

            return $router;
        });
    }

    public function run()
    {
        // Add loggers
        $this->error()->setLogger($this->c['getLogger']('error', Log::$ERROR));
        $this->router()->setLogger($this->c['getLogger']('router', Log::$WARNING));

        // Match route
        $routeInfo = $this->router()->match();

        // Handle errors
        $httpStatus = $this->router()->getStatus();
        if ($httpStatus == 404 || $httpStatus == 405) $this->handleError($httpStatus, $routeInfo['handler']);

        // Store route info into WContainer to make it easily accessible without injection whole WRouter
        $this->container()->addParam('routeInfo', $routeInfo);

        // Run middlewares and route controller
        $this->mw()->run($routeInfo);
    }

    /**
     * Add application middleware
     * @param $mw
     * @param null $params
     */
    public function add($mw, $params = null)
    {
        $this->mw()->add($mw, $params);
    }

    /**
     * @return WContainer
     */
    public function container()
    {
        return $this->c['Webiik\WContainer'];
    }

    /**
     * @return Error
     */
    public function error()
    {
        return $this->c['Webiik\Error'];
    }

    /**
     * @return Request
     */
    public function request()
    {
        return $this->c['Webiik\Request'];
    }

    /**
     * @return WRouter
     */
    public function router()
    {
        return $this->c['Webiik\WRouter'];
    }

    /**
     * @return Log
     */
    public function log()
    {
        return $this->c['Webiik\Log'];
    }

    /**
     * @return array
     */
    public function config()
    {
        return $this->c['WConfig'];
    }

    /**
     * @return WMiddleware
     */
    protected function mw()
    {
        return $this->c['Webiik\WMiddleware'];
    }

    /**
     * Run 404/405 error handler if is defined or just return adequate response. Always exit.
     * @param int $httpStatus
     * @param string $handler
     */
    protected function handleError($httpStatus, $handler)
    {
        if ($httpStatus == 404) header('HTTP/1.1 404 Not Found');
        if ($httpStatus == 405) header('HTTP/1.1 405 Method Not Allowed');

        if ($handler) {

            $handlerStr = explode(':', $handler);
            $className = $handlerStr[0];
            $handler = new $className(...WContainer::DIconstructor($className, $this->container()));
            if (isset($handlerStr[1])) {
                $methodName = $handlerStr[1];
                $handler->$methodName();
            }

        } else {

            echo $httpStatus;
        }

        exit;
    }
}