<?php
return [
    // App name
    'name' => 'Webiik',

    'error' => [
        'debug' => false,
        'log' => true,
        'email' => 'jiri@mihal.me',
        'timeZone' => 'America/Los_Angeles',
    ],

    // First database is main app database
    // $dialect, $host, $dbname, $user, $pswd, $encoding
    'database' => [
        'db1' => ['mysql', 'localhost', 'webiik', 'root', 'root'],
    ],

    // First language is default
    // Signature is: [language in ISO 639-1, timezone, [array of fallbacks in ISO 639-1]]
    'language' => [
        'en' => ['America/Los_Angeles', ['cs']],
        'cs' => ['Europe/Prague', ['en']],
        'sk' => ['Europe/Bratislava', ['cs']],
    ],

    // Application structure
    'folder' => [

        // These folders should not be accessible from the web
        'logs' => __DIR__ . '/../logs',
        'routes' => __DIR__ . '/../routes',
        'translations' => __DIR__ . '/../translations',
        'conversions' => __DIR__ . '/../translations/conversions',
        'formats' => __DIR__ . '/../translations/formats',
        'views' => __DIR__ . '/../views',
        'components' => __DIR__ . '/../components',
        'plugins' => __DIR__ . '/../plugins',
        'tmp' => __DIR__ . '/../components',

        // These folders must be accessible from the web
        'js' => __DIR__ . '/../../../assets/js',
        'css' => __DIR__ . '/../../../assets/css',
        'img' => __DIR__ . '/../../../assets/img',
    ],

    // Show default lang in URI? If true then home page for default language will be: webiik.com/en/
    'dlInUri' => false,

    // Template engine
    'view' => 'Twig',
];