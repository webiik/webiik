<?php
$config = [

    'hideErrors' => false,

    'database' => [
        'db1' => [
            'local' => [],
            'server' => [],
        ],
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

    'view' => 'Twig'
];