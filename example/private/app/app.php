<?php
require __DIR__ . '/../classes/MyClass.php';
require __DIR__ . '/middlewares/Middleware.php';
require __DIR__ . '/middlewares/MiddlewareTwo.php';

$app = new \Webiik\Core($config);

// Todo: Move most content from here to Skeleton

// Get URI from web root
$uri = str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI']);

// Get lang from URI
preg_match('/^\/([\w]{2})\/|^\/([\w]{2})$/', $uri, $matches);

// Did we find some language in URI?
if (count($matches) > 0) {
    // Yes we do. So check if the lang is valid lang.
    foreach ($config['language'] as $lang => $prop) {
        if ($lang == $matches[1]) break;
    }
}

// If we didn't find any lang, it can be still ok, if default lang doesn't need to be in URI
// Otherwise page doesn't exist.
if (!isset($lang) && !$config['dlInUri']) {
    $lang = key($config['language']);
} else {
    // Todo: Error 404
}

// Check if there are some fallback languages configured and store info about that for further usage
if (isset($config['language'][$lang][1]) && is_array($config['language'][$lang][1])) {
    $fallbackLangs = $config['language'][$lang][1];
} else {
    $fallbackLangs = false;
}

// Add services we will use across our app
$app->addService('translation', function ($c) {
    return new \Webiik\Translation();
});
$app->addService('conversion', function ($c) {
    return new \Webiik\Conversion();
});
$app->addService('connection', function ($c) {
    return new \Webiik\Connection();
});

// Add error routes
$app->error404('Webiik\Error404:run');
$app->error405('Webiik\Error405:run');

// Add routes with optional middlewares
//$app->map(['GET'], '/', 'Webiik\Controller:run', 'home');
$app->map(['GET'], '/page1', 'Webiik\Controller:run', 'page1');

// Load file with app translations. If key is specified and exists add translation for
// that key to Translation object, otherwise add to Translation object all translations from loaded file.
// Save memory by using keys.
function loadTranslation($lang, \Webiik\Translation $trans, $key = false)
{
    if (file_exists(__DIR__ . '/translations/app.' . $lang . '.php')) {

        $val = require __DIR__ . '/translations/app.' . $lang . '.php';

        // If key exists, get value from translation array by dot notation signature
        if ($key) {
            $pieces = explode('.', $key);
            foreach ($pieces as $piece) {
                if (isset($val[$piece])) {
                    $val = $val[$piece];
                } else {
                    $val = false;
                }
            }
        }

        if($val) $trans->addTrans($lang, $val, $key);
    }
}

// Return translation for given key in current lang. If key does not exist in current
// lang, try to load translation of that key from fallback langs. Return false if
// key does not exist in any lang.
function _t($key, $fallbackLangs, $fallbackIndex = 0, \Webiik\Translation $trans)
{
    $t = $trans->_t($key);
    if (!$t) {
        if ($fallbackLangs && isset($fallbackLangs[$fallbackIndex])) {
            loadTranslation($fallbackLangs[$fallbackIndex], $trans, $key);
            $fallbackIndex++;
            $t = _t($key, $fallbackLangs, $fallbackIndex, $trans);
        }
    }
    return $t;
}

// Map the route that has the translation.
function mapTranslatedRoute($name, $p, $fallbackLangs, \Webiik\Translation $trans, \Webiik\Core $app)
{
    $uri = _t('routes.' . $p['utk'], $fallbackLangs, $addedFallbacksCount = 0, $trans);

    if ($uri) {
        $route = $app->map($p['methods'], $uri, $p['controller'], $name);
        if (isset($p['middlewares'])) {
            foreach ($p['middlewares'] as $mw => $params) {
                $route->add($mw, $params);
            }
        }
    }
}

$trans = new \Webiik\Translation();

// Set translation language
$trans->setLang($lang);

// Set translation fallback languages
if ($fallbackLangs && is_array($fallbackLangs)) $trans->setFallbacks($lang, $fallbackLangs);

// Load route translations in current lang
loadTranslation($lang, $trans, 'routes');

// Load route definitions from file
if (file_exists(__DIR__ . '/routes/routes.' . $lang . '.php')) {
    $routes = require __DIR__ . '/routes/routes.' . $lang . '.php';
} elseif (file_exists(__DIR__ . '/routes/routes.php')) {
    $routes = require __DIR__ . '/routes/routes.php';
}

// If we have some routes definitions, map routes that can be translated
if (isset($routes)) {
    foreach ($routes as $name => $p) {
        mapTranslatedRoute($name, $p, $fallbackLangs, $trans, $app);
    }
}

//echo _t('other.a.b', $fallbackLangs, 0, $trans);
//echo _t('key', $fallbackLangs, 0, $trans);

// DI for route handlers
$factoryController = function ($c) {
    return [$c['translation'], $c['connection']];
};
$app->addService('Webiik\Controller', $factoryController);

// Run app
$app->run();

// Todo: Do steps after route is matched

// Uncomment for memory usage testing
    echo '<br/><br/>Peak memory usage: ' . (memory_get_peak_usage() / 1000000) . ' MB';
    echo '<br/>End memory usage: ' . (memory_get_usage() / 1000000) . ' MB';

// Uncomment for getting of all defined PHP vars
//print_r(get_defined_vars());

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