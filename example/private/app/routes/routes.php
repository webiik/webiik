<?php
/**
 * Here come all Skeleton route definitions: 'routeName' => ['methods' => array, 'controller' => string]
 * Assign these routes to specific language in /translations/_app.{lang}.php
 */
return [
    'home' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Controller:run',
    ],
    'login' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Login:run',
    ],
    'account' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Controller:run',
        'middlewares' => [
            'Webiik\AuthMw:isUserLogged' => [],
//            'Webiik\AuthMw:userCan' => ['access-admin'],
        ],
    ],
];