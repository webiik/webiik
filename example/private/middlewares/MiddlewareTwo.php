<?php
namespace MySpace;

/**
 * Example of middleware class
 * Class Middleware
 * @package MySpace
 */
class MiddlewareTwo
{
    function launch($response, $next, $args)
    {
        echo 'BEFORE ';
        echo '<br/>ARGS: ' . $args . '<br/>';
        $next($response);
        echo ' AFTER';
    }
}