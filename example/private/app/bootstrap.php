<?php
// Load app configuration file
$config = \Webiik\Config::loadConfig(__DIR__ . '/config');

// Setup the Log class
// Log class is used by Translation, Router and Error for logging errors and sending email notices.
\Webiik\Log::setup(
    $config['skeleton']['privateDir'] . '/app/logs',
    $config['log']['logEmail'],
    $config['log']['logEmailSubject'],
    $config['log']['timeZone'],
    2,
    10
);
\Webiik\Log::addLogger('router', 'router.log');
\Webiik\Log::addLogger('translation', 'trans.log');
\Webiik\Log::addLogger('app', 'error.log');

// Set improved error handling
$err = new \Webiik\Error(!$config['error']['debug'], $config['log']['log']);
unset($err);

// Load main application file
require __DIR__ . '/app.php';