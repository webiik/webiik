<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/private/vendor/autoload.php';

// Load app config
$config = new \Webiik\Config();
$config = $config->loadConfig();

// Set up improved displaying of errors
$err = new \Webiik\Error([
    'silent' => !$config['dev'],
    'log' => true,
    'logDir' => __DIR__ . '/private/logs',
    'logFileName' => 'errlog',
    'logMaxFileSize' => 2, // in MB
    'logMaxTotalSize' => 20, // in MB
    'mail' => true,
    'mailTo' => 'jiri@mihal.me',
    'subject' => 'Error report',
]);

// Main app
require __DIR__ . '/private/app.php';