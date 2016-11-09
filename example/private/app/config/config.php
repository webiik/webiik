<?php
return [
    // App internal settings
    'internal' => [
        'name' => 'Webiik',
        'debug' => true,
        'log' => true,
        'logEmail' => 'jiri@mihal.me', // Todo: change it to void@webiik.org
        'timeZone' => 'America/Los_Angeles',
    ],

    // First database is main app database
    // $dialect, $host, $dbname, $user, $pswd, $encoding
    'database' => [
        'user' => ['mysql', 'localhost', 'webiik', 'root', 'root'],
        'admin' => ['mysql', 'localhost', 'webiik', 'root', 'root'],
    ],

    'auth' => [
        'permanentLoginCookieName' => 'PC',
        'withActivation' => false,
        'loginRouteName' => 'login', // <-- this value is used only by AuthMw
    ],

    'sessions' => [
        'name' => 'US', // string|false = default name
        'dir' => __DIR__ . '/../tmp/sessions', // path|false = default path
        'lifetime' => 1440, // int sec, 0 = till session is valid
    ],

    'cookies' => [
        'domain' => '', // (sub)domain
        'uri' => '/', // uri
        'secure' => false, // bool
        'httpOnly' => false, // bool
    ],

    'csrf' => [
        'tokenName' => 'csrf',
    ],

    // First language is default
    // Signature is: [language in ISO 639-1, timezone, [array of fallbacks in ISO 639-1]]
    'language' => [
        'en' => ['America/Los_Angeles'],
        'cs' => ['Europe/Prague', ['en']],
    ],

    // Show default lang in URI? If true then home page for default language will be: webiik.com/en/
    'dlInUri' => false,

    // App folder structure
    'folder' => [

        // Web root folder
        'public' => __DIR__ . '/../../../',

        // This folder should not be accessible from the web
        'private' => __DIR__ . '/../../',
    ],
];