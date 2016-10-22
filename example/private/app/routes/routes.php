<?php
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
//            'Webiik\Auth' => [],
            'Webiik\AuthMw:isUserLogged' => [],
//            'Webiik\AuthMw:userCan' => ['access-admin'],
        ],
    ],
];