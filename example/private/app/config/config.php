<?php
$config = [

    // Basic
    'dev' => true,

    // Skeleton
    'db' => [
        'local' => [],
        'outer' => [],
    ],

    'localization' => [

        // Default language
        'dl' => 'en',

        // Default time zone
        'dtz' => 'Europe/Prague',

        // Available languages with time zone and fallback.
        // If you don't specify custom time zone then default time zone will be used instead.
        // If you don't specify fallback then there will be no callback.
        'available' => [
            'en' => [
                'tz' => 'America/Los_Angeles',
            ],
            'cs' => [
                'fallback' => 'sk',
            ],
            'sk' => [
                'tz' => 'Europe/Bratislava',
                'fallback' => 'cs',
            ],
        ],

        // ??
        'conversions' => [
            'en' => ''
        ],

        // Require or not default language in URI.
        // If true then home page for default language 'dl' will be: webiik.com/dl/
        'dlInUri' => false,
    ],

    'view' => 'Twig'

];