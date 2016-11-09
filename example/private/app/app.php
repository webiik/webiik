<?php
$app = new \Webiik\Skeleton($config);

// Add template engine
$app->addService('Twig_Environment', function ($c){

    // Set Twig basic settings
    $loader = new Twig_Loader_Filesystem(__DIR__ . '/views');
    $twig = new Twig_Environment($loader, array(
        'cache' => __DIR__ . '/tmp/cache/views',
        'debug' => $c['config']['internal']['debug'],
    ));

    // Add Twig extension(s)
    $twig->addExtension(new \Twig_Extension_Debug());

    // Add additional params, functions, etc.

    // Add root path
    $twig->addGlobal('ROOT', $c['ROOT']);

    // Add router functions

    /* @var $router \Webiik\Router */
    $router = $c['Webiik\Router'];

    // Return uri for given route name
    $function = new \Twig_SimpleFunction('uriFor', function ($routeName, $lang = false) use ($router) {
        return $router->getUriFor($routeName, $lang);
    });
    $twig->addFunction($function);

    // Return url for given route name
    $function = new \Twig_SimpleFunction('urlFor', function ($routeName, $lang = false) use ($router) {
        return $router->getUrlFor($routeName, $lang);
    });
    $twig->addFunction($function);

    // Return current route name
    $function = new \Twig_SimpleFunction('currentRoute', function () use ($router) {
        return $router->routeInfo['name'];
    });
    $twig->addFunction($function);

    // Check if given route name is current route
    $function = new \Twig_SimpleFunction('isCurrentRoute', function ($routeName) use ($router) {
        return $routeName == $router->routeInfo['name'] ? true : false;
    });
    $twig->addFunction($function);

    // Add translation functions

    /* @var $translation \Webiik\Translation */
    $translation = $c['Webiik\Translation'];

    // Return translation for given key
    $function = new \Twig_SimpleFunction('_t', function ($key) use ($translation) {
        return $translation->_t($key);
    });
    $twig->addFunction($function);

    // Return parsed translation for given key
    $function = new \Twig_SimpleFunction('_p', function ($key, $val) use ($translation) {
        return $translation->_p($key, $val);
    });
    $twig->addFunction($function);

    // Add auth functions

    /* @var $auth \Webiik\Auth */
    $auth = $c['Webiik\Auth'];

    // Return user id on success otherwise false
    $function = new \Twig_SimpleFunction('isUserLogged', function () use ($auth) {
        return $auth->isUserLogged();
    });
    $twig->addFunction($function);

    // Return user id if user can do the action otherwise false
    $function = new \Twig_SimpleFunction('userCan', function ($action) use ($auth) {
        return $auth->userCan($action);
    });
    $twig->addFunction($function);

    // Return configured template engine
    return $twig;
});

// Add own error routes handlers
$app->error404('Webiik\Error404:run');
$app->error405('Webiik\Error405:run');

// Run app
$app->run();