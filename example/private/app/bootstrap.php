<?php
// Load config
$config = \Webiik\Config::loadConfig(__DIR__ . '/config');

// Setup the Webiik logging
\Webiik\Log::setup(
    $config['folder']['logs'],
    $config['internal']['logEmail'],
    $config['internal']['name'] . ' error notice',
    $config['internal']['timeZone'],
    2,
    10
);
\Webiik\Log::addLogger('router', 'router.log');
\Webiik\Log::addLogger('translation', 'trans.log');
\Webiik\Log::addLogger('app', 'error.log');

// Init improved error handling
$err = new \Webiik\Error(!$config['internal']['debug'], $config['internal']['log']);
unset($err);

require __DIR__ . '/app.php';