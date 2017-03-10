<?php
return [
    // Error class settings
    'error' => [
        'debug' => true,
    ],

    // Log class settings
    'log' => [
        'log' => true,
        'logEmail' => 'void@webiik.com',
        'logEmailSubject' => 'Webiik error notice',
        'timeZone' => 'America/New_York',
    ],

    // Skeleton class settings
    'skeleton' => [

        // First language is default
        // Signature is: [language in ISO 639-1, timezone, [array of fallback languages in ISO 639-1]]
        'languages' => [
            'en' => ['America/New_York'],
            'cs' => ['Europe/Prague', ['en']],
        ],

        // Folder structure
        'publicDir' => __DIR__ . '/../../..',
        'privateDir' => __DIR__ . '/../..',
    ],

    // Router class settings
    'router' => [
        // Use default lang in URI? If it set to true, home page for default language will be: /en/
        'dlInUri' => true,
    ],

    // Connection class settings
    // First connection is used as default. Skeleton uses default connection for Auth and Attempts.
    'connection' => [
        // $dialect, $host, $dbname, $user, $pswd, $encoding
        'user' => ['mysql', 'localhost', 'webiik', 'root', 'root'],
        'admin' => ['mysql', 'localhost', 'webiik', 'root', 'root'],
    ],

    // Sessions class settings
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

    // Csrf class settings
    'csrf' => [
        'tokenName' => 'csrf',
    ],

    // Auth class settings
    'auth' => [
        'distinguishLanguages' => false,
        'loginSessionName' => 'logged',
        'permanentLoginCookieName' => 'PC',
        'permanentLoginHours' => 1,
        'withActivation' => true,
    ],

    // AuthMwRedirect class settings
    'authMwRedirect' => [
        'loginRouteName' => 'login',
    ],

    // Email class settings
    // Todo: Email class
    'email' => [
        'fromName' => 'Webiik',
        'fromEmail' => 'no-reply@webiik.com',
        'isSMPT' => false,
        'host' => '',
        'port' => '',
        'SMTPsecure' => 'tls',
        'SMTPAuth' => false,
        'SMTPAuthUserName' => '',
        'SMTPAuthPswd' => '',
        'SMTPOptions' => [],
    ],

    // Separate application parts settings
    // These parts are built on top of Skeleton. Normally controllers, middlewares etc.
];