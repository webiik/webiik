<?php
require __DIR__ . '/classes/MyClass.php';
require __DIR__ . '/middlewares/Middleware.php';
require __DIR__ . '/middlewares/MiddlewareTwo.php';

$app = new \Webiik\Skeleton($config);

// Add error routes
$app->error404('Webiik\Error404:launch');
$app->error405('Webiik\Error405:launch');

// Add routes with optional middlewares
$app->map(['GET'], '/', 'Webiik\Controller:launch', 'home');
$app->map(['GET'], '/page1', 'Webiik\Controller:launch', 'page1');

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
// Todo: All actions needed for Skeleton:
// php_internal_ecnoding('utf-8');
// language detection from URI
// how will work translations and localizations (units, timezone) in Skeleton
// loading routes with middlewares from files
// creating/adding params/factories and models and render
// handling database connections
// $skeleton = new \Webiik\Skeleton($app);

// Add CMS
// $cms = new \Webiik\Cms($app);

// Run app
$app->run();

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
//
//]);
//$trans->setLang($lang);
//echo $trans->_p('t1', ['timeStamp' => time(), 'numCats' => 100000]) . '<br/><br/>';
//echo $trans->_p('t2', ['numCats' => 1]) . '<br/><br/>';
//echo $trans->_p('t3', ['price' => $conv->conv('500000 czk', 'usd', 0), 'currency' => 'usd']) . '<br/><br/>';
//echo $trans->_p('t3', ['price' => 500000, 'currency' => 'czk']) . '<br/><br/>';
//echo $trans->_p('t4', ['gender' => 'male']) . '<br/><br/>';
//echo $trans->_p('t5', ['speed' => 200]) . '<br/><br/>';