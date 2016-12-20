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
        'timeZone' => 'America/Los_Angeles',
    ],

    // Skeleton class settings
    'skeleton' => [

        // First language is default
        // Signature is: [language in ISO 639-1, timezone, [array of fallback languages in ISO 639-1]]
        'languages' => [
            'en' => ['America/Los_Angeles'],
            'cs' => ['Europe/Prague', ['en']],
        ],

        // Show default lang in URI? If true then home page for default language will be: webiik.com/en/
        'dlInUri' => false, // Todo: Fix when set to true: http://localhost/skeletons/webiik/example/

        // Folder structure
        'publicDir' => __DIR__ . '/../../../',
        'privateDir' => __DIR__ . '/../../',
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
        'permanentLoginCookieName' => 'PC',
        'withActivation' => true,
    ],

    // AuthMw class settings
    'authMw' => [
        'loginRouteName' => 'login',
    ],

    // Separate application parts settings
    // These parts are built on top of Skeleton. Normally controllers, middlewares etc.

    // Accounts service routes settings
    'accounts' => [

        // We will send authorisation messages with these credential
        'email' => 'no-reply@webiik.com',
        'name' => 'Webiik',

        // Accounts service routes
        'routes' => [
            'loginRouteName' => 'login',
            'signupRouteName' => 'signup',
            'passwordRenewalRequestRouteName' => 'forgot-request',
            'passwordRenewalConfirmationRouteName' => 'forgot-confirm',
            'activationRequestRouteName' => 'activate-request',
            'activationConfirmationRouteName' => 'activate-confirm',
            'logoutRouteName' => 'logout',
        ],

        // Default actions
        'defaultAfterLoginRouteName' => 'account',
        'defaultAfterLogoutRouteName' => 'login',
    ],
];