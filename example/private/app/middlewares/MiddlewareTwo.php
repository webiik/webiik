<?php
namespace MySpace;

/**
 * Example of middleware class
 * Class Middleware
 * @package MySpace
 */
class MiddlewareTwo
{
    function run($response, $next, $param1)
    {
        echo 'BEFORE ';
        echo '<br/>ARGS: ' . $param1 . '<br/>';
        $next($response);
        echo ' AFTER';
    }
}