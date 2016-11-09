<?php
/**
 * Route definitions for all languages
 * Signature: 'routeName' => ['methods' => array, 'controller' => string]
 * You can also define routes for specific language in /routes/routes.{lang}.php
 * To make routes working properly you also need to add translations of these routes in /translations/_app.{lang}.php
 */
return [
    'home' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Controller:run',
//        'middlewares' => [
//            'Webiik\AuthMw' => [],
//        ],
    ],
    'login' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Login:run',
        'middlewares' => [
            'Webiik\AuthMw:redirectLoggedUser' => ['account'],
        ],
    ],
    'login-oauth1' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\LoginOauth:run',
        // Todo: Don't provide this route when user is logged in
    ],
    'signup' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Signup:run',
        'middlewares' => [
            'Webiik\AuthMw:redirectLoggedUser' => ['account'],
        ],
    ],
    'forgot' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Forgot:run',
        'middlewares' => [
            'Webiik\AuthMw:redirectLoggedUser' => ['account'],
        ],
    ],
    'logout' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Logout:run',
    ],
    'account' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Controller:run',
        'middlewares' => [
            'Webiik\AuthMw:redirectUnloggedUser' => [],
        ],
    ],
    'admin' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Controller:run',
        'middlewares' => [
            'Webiik\AuthMw:userCan' => ['access-admin'],
        ],
    ],
];