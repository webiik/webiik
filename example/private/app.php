<?php
require __DIR__ . '/classes/MyClass.php';
require __DIR__ . '/middlewares/Middleware.php';
require __DIR__ . '/middlewares/MiddlewareTwo.php';

$app = new \Webiik\Core($config);

// Todo:2 Think how to implement Skeleton, CMS and services like Flash messages, Logs...
// If they should be part of Core or Skeleton.

// Todo: Think how will work the translations of app and posts.

// Add middlewares
$app->add('\MySpace\Middleware', 'app1');
$app->add('\MySpace\MiddlewareTwo:launch', ['app2']);

// function ($response, $next, ...$args)
$mw1 = function ($response, $next, $role, $action = null) {
    echo 'BEFORE ';
    echo '<br/>ROLE: ' . $role;
    echo '<br/>ACTION: ' . $action . '<br/>';
    $next($response);
    echo ' AFTER';
};

// Add routes with optional middlewares
$app->map(['GET'], '/', 'Webiik\Controller:launch', 'home')->add($mw1, ['route1']);
$app->map(['GET'], '/page1', 'Webiik\Controller:launch', 'page1');

// Add error routes
$app->error404('Webiik\Error404:launch');
$app->error405('Webiik\Error405:launch');

// Factory
$factoryController = function ($c) {
    return [new MyClass(), new MyClass()];
};

// DI
// How does it work?
// Service Webiik\Controller is injected into Webiik\Controller during its construction.
// Service and controller are paired by same name.
$app->addService('Webiik\Controller', $factoryController);

// Add skeleton
// $skeleton = new \Webiik\Skeleton();
//$skeleton->install();
//$skeleton->map($app);

// Add CMS
// $cms = new \Webiik\Cms();

// Run app
$app->run();

// Uncomment for memory usage testing
echo '<br/><br/>Peak memory usage: ' . (memory_get_peak_usage() / 1000000) . ' MB';
echo '<br/>End memory usage: ' . (memory_get_usage() / 1000000) . ' MB';

// Uncomment for getting of all defined PHP vars
//print_r(get_defined_vars());