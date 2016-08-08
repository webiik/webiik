<?php
/**
 * If there si no specific language('lg') file like route.lg.php,
 * then this file will be used. It is very handy for one to one
 * translations. Uris will be automatically translated
 * during parsing this array.
 */
return [
    'home' => [
        'uri' => '/',
        'controller' => 'Webiik\Controller:launch',
        'middlewares' => [
            'hello' => ['world'],
        ],
    ],
    'account' => [
        'uri' => '/about',
        'controller' => 'Webiik\Controller:launch',
        'middlewares' => [
            'auth' => ['user'],
            'hello' => ['world'],
        ],
        'models' => ['Users']
    ],
];