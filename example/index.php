<?php
//$before = microtime(true);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/private/vendor/autoload.php';

//// Load app config
//$config = new \Webiik\Config();
//$config = $config->loadConfig(__DIR__ . '/private/app/config/');
//
//// Create logger container
//$logger = new \Webiik\Log();
//
//// Add file logger to container
//$logger->add('file', new Webiik\FileLogger(__DIR__ . '/private/app/logs', 'errlog'));
//
//// Add email notice logger to container
//$logger->add('emailNotice', new \Webiik\EmailNoticeLogger(__DIR__ . '/private/app/logs', 'jiri@mihal.me'));
//
//// Set up improved errors handling
//$err = new \Webiik\Error($config['hideErrors'], true);
//
//// Add error log handler to Error
//$logHandler = function ($msgShort, $msgHtml, \Webiik\Log $logger) {
//    $logger->get('file')->log($msgShort);
//    $logger->get('emailNotice')->log($msgHtml);
//};
//$err->addLogger($logger, $logHandler);

// Main app
require __DIR__ . '/private/app/app.php';

//$after = microtime(true);
//echo '<br/><br/>Execution time: '. ($after-$before) . ' sec';