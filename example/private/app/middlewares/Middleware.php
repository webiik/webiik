<?php
namespace MySpace;

/**
 * Example of invokable middleware class
 * Class Middleware
 * @package MySpace
 */
class Middleware
{
    function __invoke($response, $next, $args)
    {
        echo 'BEFORE ';
        echo '<br/>ARGS: ' . $args . '<br/>';
        $next($response);
        echo ' AFTER';
    }
}