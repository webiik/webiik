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
     * Middleware storage
     * We can store middlewares under the following keys:
     * 'app' - all app middlewares
     * '{routeId}' - all route middlewares
     * 'dest' - last middleware to be run
     *
     * @var array
     */
    private $middlewares = [];

    /**
     * Middleware can be defined by:
     * 1. callable - anonymous function
     * 2. string - class name of invokable class eg.: Webiik\ClassName
     * 3. string - class name and method to be launched eg.: Webiik\ClassName:launch
     * @param string|callable $mw
     */
    public function addAppMiddleware($mw, $args = null)
    {
        $this->middlewares['app'][] = ['mw' => $mw, 'args' => $args];
    }

    /**
     * @param $routeId
     * @param string|callable $mw
     */
    public function addRouteMiddleware($routeId, $mw, $args = null)
    {
        $this->middlewares[$routeId][] = ['mw' => $mw, 'args' => $args];
    }

    /**
     * @param string|callable $mw
     */
    public function addDestination($mw, $args = null)
    {
        $this->middlewares['dest'][0] = ['mw' => $mw, 'args' => $args];
    }

    /**
     * Run all middlewares
     * @param $routeId
     * @throws \Exception
     */
    public function run($routeId)
    {
        $this->middlewares = array_merge($this->getAppMiddlewares(), $this->getRouteMiddlewares($routeId), $this->getDestination());

        // Run first middleware
        $mw = $this->middlewares[0]['mw'];
        $args = $this->middlewares[0]['args'];
        $this->runMiddleware($mw, $args);
    }

    /**
     * @param $mw
     * @param $args
     * @param null $response
     * @throws \Exception
     */
    private function runMiddleware($mw, $args, $response = null)
    {
        // Instantiate middleware when is defined by class name
        if (is_string($mw)) {

            $mw = explode(':', $mw);

            if (class_exists($mw[0])) {
                if (isset($mw[1])) {
                    $method = $mw[1];
                    $mw = new $mw[0]($response, ...$args);
                } else {
                    $mw = new $mw[0]();
                }
            }
        }

        // Check and call middleware
        if (is_callable($mw)) {
            is_array($args) ? $mw($response, $this->next(), ...$args) : $mw($response, $this->next(), $args);
        } elseif (is_object($mw) && !is_callable($mw) && method_exists($mw, $method)) {
            is_array($args) ? $mw->$method($response, $this->next(), ...$args) : $mw->$method($response, $this->next(), $args);
        } else {
            throw new \Exception('Invalid middleware.');
        }
    }

    /**
     * Return callable which runs next middleware
     * @return \Closure
     * @throws \Exception
     */
    private function next()
    {
        $next = function ($response) {

            $nextMw = next($this->middlewares);

            if ($nextMw) {
                $mw = $nextMw['mw'];
                $args = $nextMw['args'];
                $this->runMiddleware($mw, $args, $response);
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
     * @return array
     */
    private function getDestination()
    {
        if (isset($this->middlewares['dest'])) {
            return $this->middlewares['dest'];
        }
        return [];
    }
}