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
    ],

    'database' => [
        // $name => $dialect, $host, $dbname, $user, $pswd, $encoding
        'db1' => [],
    ],

    // First language is default
    // Signature is: [language in ISO 639-1, timezone, [array of fallbacks in ISO 639-1]]
    'language' => [
        'en' => ['America/Los_Angeles', ['cs']],
        'cs' => ['Europe/Prague', ['sk']],
        'sk' => ['Europe/Bratislava', ['cs']],
    ],

    // Show default lang in URI? If true then home page for default language will be: webiik.com/en/
    'dlInUri' => false,

    'view' => 'Twig',

    'appDir' => __DIR__ . '/../',
];