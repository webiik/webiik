<?php
return [
    'routes' => [
        'home' => '/',
        'login' => '/prihlaseni',
        'login-oauth1' => '/login-oauth1',
        'signup' => '/registrace',
        'forgot' => '/zapomenute-heslo',
        'account' => '/ucet',
        'admin' => '/admin',
    ],
    'nav' => [
        'home' => ['Úvodní stránka', 0],
        'login' => ['Přihlášení', 1],
        'signup' => ['Registrace', 1],
        'forgot' => ['Zapomenuté heslo', 1],
        'account' => ['Můj účet', 2],
        'admin' => ['Admin', 2],
    ],
];