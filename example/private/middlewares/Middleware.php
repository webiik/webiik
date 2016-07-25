<?php
namespace MySpace;

/**
 * Example of invokable middleware class
 * Class Middleware
 * @package MySpace
 */
class Middleware
{
    function __invoke($response, $next)
    {
        echo 'BEFORE ';
        $next($response);
        echo ' AFTER';
    }
}