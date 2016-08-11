<?php
// Load config
$config = \Webiik\Config::loadConfig(__DIR__ . '/config');

// Init improved error handling
$err = new \Webiik\Error($config['error']['hide'], $config['error']['log']);

// Add error logging capability to Error class
$err->addLogHandler(
    function ($msgShort, $msgHtml, $p) {
        $fileLoger = new \Webiik\FileLogger($p['logDir'], 'errlog', $p['timeZone']);
        $emailLogger = new \Webiik\EmailNotice(
            $p['logDir'],
            $p['email'],
            $p['timeZone'],
            $p['name'] . ' error notice',
            '!err_notice_sent.log'
        );
        $fileLoger->log($msgShort);
        $emailLogger->log($msgHtml);
    },
    [
        'logDir' => $config['folder']['logs'],
        'email' => $config['error']['email'],
        'timeZone' => $config['error']['timeZone'],
        'name' => $config['name']
    ]
);

// Remove $err from memory
unset($err);

require __DIR__ . '/app.php';