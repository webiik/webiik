<?php
require __DIR__ . '/../classes/MyClass.php';
require __DIR__ . '/middlewares/Middleware.php';
require __DIR__ . '/middlewares/MiddlewareTwo.php';

// Todo: THINK ABOUT installers (what basic folders and files, installer steps) for Core(API), Skeleton, CMS

//$app = new \Webiik\Core();
$app = new \Webiik\Skeleton($config);

// Add own error routes handlers
//$app->error404('Webiik\Error404:run');
//$app->error405('Webiik\Error405:run');

// Add routes with optional middlewares
$app->map(['GET'], '/', 'Webiik\Controller:run', 'home');
//$app->map(['GET'], $app->_t('routes./'), 'Webiik\Controller:run', 'home');
//$app->map(['GET'], '/page1', 'Webiik\Controller:run', 'page1');

// DI for route handlers
//$factoryController = function ($c) {
//    return [$c['translation']];
//};
//$app->addService('Webiik\Controller', $factoryController);

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
//]);
//$trans->setLang($lang);
//echo $trans->_p('t2', ['numCats' => 1]) . '<br/><br/>';
//echo $trans->_p('t3', ['price' => $conv->conv('500000 czk', 'usd', 0), 'currency' => 'usd']) . '<br/><br/>';
//echo $trans->_p('t3', ['price' => 500000, 'currency' => 'czk']) . '<br/><br/>';
//echo $trans->_p('t4', ['gender' => 'male']) . '<br/><br/>';
//echo $trans->_p('t5', ['speed' => 200]) . '<br/><br/>';