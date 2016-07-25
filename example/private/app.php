<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/classes/MyClass.php';
require __DIR__ . '/middlewares/Middleware.php';
require __DIR__ . '/middlewares/MiddlewareTwo.php';

$app = new \Webiik\Core();

// App base path, relative to localhost
$app->base('/skeletons/webiik/example/');

// Todo: Config error reporting, Catching, printing the exceptions and the errors
//throw new Exception('Uhh oh');

// Todo: Think how to implement Flash messages, Logs... If they should be part of Core or Skeleton. And of
// course think about how to implement Skeleton itself.

// Todo: Think how will work the translations of app and posts.

// Add middlewares
$app->add('\MySpace\Middleware', 'mw1');
$app->add('\MySpace\MiddlewareTwo:launch', ['mw2']);

// function ($response, $next, ...$args)
$mw1 = function ($response, $next, $role, $action = null) {
    echo 'BEFORE ';
    echo '<br/>ROLE: ' . $role;
    echo '<br/>ACTION: ' . $action . '<br/>';
    $next($response);
    echo ' AFTER';
};

$arr = [];
$arr[] = $mw1;
$arr[] = $mw1;

// Todo: Middlewares - class Middleware should care just about middlewares, it should not recognize app, route, etc
// Todo: Middlewares - optimize storing of same annonymous functions
if($arr[0] == $arr[1]){
    echo 'SAME';
} else {
    echo 'DIFFERENT';
}



// Add routes with optional middlewares
$app->map(['GET'], '/', 'Webiik\Controller:launch', 'home')->add($mw1, ['user', 'select']);
$app->map(['GET'], '/page1', 'Webiik\Controller:launch', 'page1');

// Add error routes
$app->error404('Webiik\Error404:launch');
$app->error405('Webiik\Error405:launch');

// Factory
$factoryController = function () {
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
echo '<br/>' . (memory_get_usage() / 1000000) . ' MB<br>';

// Uncomment for getting of all defined PHP vars
//print_r(get_defined_vars());