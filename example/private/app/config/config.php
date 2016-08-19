<?php
$config = [
    'name' => 'Webiik',

    'error' => [
        'hide' => false,
        'log' => true,
        'email' => 'jiri@mihal.me',
        'timeZone' => 'America/Los_Angeles',
    ],

    'folder' => [
        'logs' => __DIR__ . '/../logs',
        'routes' => __DIR__ . '/../routes',
        'translations' => __DIR__ . '/../translations',
        'conversions' => __DIR__ . '/../translations/conversions',
        'formats' => __DIR__ . '/../translations/formats',
        'views' => __DIR__ . '/../views',
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

    // Show default lang in URI? If true then home page for default language will be: webiik.com/en/
    'dlInUri' => false,

    'view' => 'Twig',
];