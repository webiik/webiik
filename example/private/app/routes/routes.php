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
        'controller' => 'Webiik\Controller:run',
        'middlewares' => [
            'Webiik\AuthMiddleware' => [$this->container['connection']],
        ],
    ],
    'login' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Login:run',
    ],
    'account' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Controller:run',
    ],
];