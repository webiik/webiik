<?php
//$before = microtime(true);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/private/vendor/autoload.php';

// Load app config
$config = new \Webiik\Config();
$config = $config->loadConfig(__DIR__.'/private/app/config/');

// Set up improved displaying of errors
$err = new \Webiik\Error([
    'silent' => $config['hideErrors'],
    'log' => true,
    'logDir' => __DIR__ . '/private/app/logs',
    'logFileName' => 'errlog',
    'logMaxFileSize' => 2, // in MB
    'logMaxTotalSize' => 20, // in MB
    'mail' => true,
    'mailTo' => 'jiri@mihal.me',
    'subject' => 'Error report',
]);

// Main app
require __DIR__ . '/private/app/app.php';
//$after = microtime(true);
//echo '<br/><br/>Execution time: '. ($after-$before) . ' sec';