<?php
/**
 * If there si no specific language('lg') file like route.lg.php,
 * then this file will be used. It is very handy for one to one
 * translations. Uris will be automatically translated
 * during parsing this array.
 */

return [
    'home' => [
        'methods' => ['GET'],
        'utk' => '/',
        'controller' => 'Webiik\Controller:run',
        'middlewares' => [
            'MySpace\Middleware' => ['world'],
        ],
    ],
    'account' => [
        'methods' => ['GET'],
        'utk' => '/account',
        'controller' => 'Webiik\Controller:run',
    ],
];