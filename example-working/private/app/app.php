<?php
$app = new \Webiik\Skeleton($config);

// Add template engine
$app->addService('Twig_Environment', function ($c){

    // Set Twig basic settings
    $loader = new Twig_Loader_Filesystem(__DIR__ . '/views');
    $twig = new Twig_Environment($loader, array(
        'cache' => __DIR__ . '/tmp/cache/views',
        'debug' => true,
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

    // Return configured template engine
    return $twig;
});

// Run app
$app->run();

// Add own error routes handlers
//$app->error404('Webiik\Error404:run');
//$app->error405('Webiik\Error405:run');

//$mw = new \MySpace\Middleware();
//$mw = function(){};
//if(is_object($mw) && is_callable($mw)) echo 'test';


//$app->addService('Webiik\Flash', function () {
//    return new \Webiik\Flash();
//});

//$app->add('MySpace\Middleware', 'a');
//$app->add('MySpace\Middleware', 'b');

// Add routes with optional middlewares
//$app->map(['GET'], '/', 'Webiik\Controller:run', 'home');
//$app->map(['GET', 'POST'], '/login', 'Webiik\Login:run', 'login');

//$app->map(['GET'], $app->_t('routes./'), 'Webiik\Controller:run', 'home');
//$app->map(['GET'], '/page1', 'Webiik\Controller:run', 'page1');

// DI for route handlers
//$factoryController = function ($c) {
//    return [$c['connection'], $c['translation'], $c['router'], $c['auth']];
//};
//$app->addService('Webiik\Controller', $factoryController);



//$conv = new \Webiik\Conversion();
//$conv->addConv('km/h__kmh', 1, 'speed');
//$conv->addConv('mp/h__mph', 0.621371192, 'speed');
//$conv->addConv('czk', 24, 'currency');
//$conv->addConv('usd', 1, 'currency');

//$lang = 'en';
//$trans = new \Webiik\Translation($lang);
//$trans->addConv($conv);
//$trans->addDateFormat($lang, 'default', 'j. M Y');
//$trans->addTimeFormat($lang, 'default', 'H:i:s');
//$trans->addNumberFormat($lang, 'default', [2, '.', ',']);
//$trans->addCurrencyFormat($lang, 'usd', '$ %i');
//$trans->addCurrencyFormat($lang, 'czk', '%i CZK');
//$trans->addTrans($lang, [
//    't1' => 'Today is {timeStamp, date}, the time is {timeStamp, time}.',
//    't2' => '{numCats, plural, =0 {Any cat have} =1 {One cat has} =2+ {{numCats} cats have}} birthday.',
//    't3' => 'This car costs {price, currency, currency}.',
//    't4' => '{gender, select, =male {He} =female {She}} is {gender}. {gender, select, =male {Males like beer.} =female {Females like wine.}}',
//    't5' => 'Maximal allowed speed is {speed, conv, kmh, mph, su}.',
//]);
//$trans->setLang($lang);
//echo $trans->_p('t2', ['numCats' => 1]) . '<br/><br/>';
//echo $trans->_p('t3', ['price' => $conv->conv('500000 czk', 'usd', 0), 'currency' => 'usd']) . '<br/><br/>';
//echo $trans->_p('t3', ['price' => 500000, 'currency' => 'czk']) . '<br/><br/>';
//echo $trans->_p('t4', ['gender' => 'male']) . '<br/><br/>';
//echo $trans->_p('t5', ['speed' => 200]) . '<br/><br/>';