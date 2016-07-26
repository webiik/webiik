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
     * Every middleware must be array with the following keys:
     * 'args' - array of arguments, can be whatever
     * 'mw' - callable or string, must be defined by:
     * 1. callable: anonymous function | or
     * 2. string: class name of invokable class eg.: Webiik\ClassName | or
     * 3. string: class name and method to be launched eg.: Webiik\ClassName:launch
     * @var array
     */
    private $middlewares = [];

    /**
     * Run all middlewares
     * @param $middlewares
     * @throws \Exception
     */
    public function run($middlewares)
    {
        $this->middlewares = $middlewares;

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
}