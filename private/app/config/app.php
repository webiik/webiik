<?php
return [
    'app' => [
        // Application mode 'development' or 'production'
        'mode' => 'production',

        // Base URI of application, usually '/'
        'baseUri' => '/',

        // Array of all available languages of application
        // format: ISO 639-1 => [timezone, encoding]
        'languages' => [
            'en' => ['America/New_York', 'utf-8'],
        ],

        // Default language of application in ISO 639-1
        // Can be a string or an array(hostname => lang, ...)
        'defaultLanguage' => 'en',
    ],
];