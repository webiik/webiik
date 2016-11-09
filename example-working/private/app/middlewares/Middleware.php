<?php
namespace MySpace;

/**
 * Example of invokable middleware class
 * Class Middleware
 * @package MySpace
 */
class Middleware
{
    function __invoke($request, $next, $param1)
    {
        echo 'BEFORE ';
        echo '<br/>ARGS: ' . $param1 . '<br/>';
        $next($request);
        echo ' AFTER';
    }
}