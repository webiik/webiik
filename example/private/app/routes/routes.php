<?php
/**
 * Route definitions for all languages
 * Signature: 'routeName' => ['methods' => array, 'controller' => string, (optional)'middlewares' => array]
 * You can also define routes for specific language in /routes/routes.{lang}.php
 * To make routes working properly you also need to add translations of these routes in /translations/_app.{lang}.php
 */
return [
    // User account service routes
    'signup' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Signup:run',
    ],
    'login' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Login:run',
    ],
    'forgot-request' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\ForgotRequest:run',
    ],
    'forgot-confirm' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\ForgotConfirm:run',
    ],
    'activate' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Activate:run',
    ],
    'activate-request' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\ActivateRequest:run',
    ],
    'activate-confirm' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\ActivateConfirm:run',
    ],
    'logout' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Logout:run',
    ],
    // Website routes
    'home' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Page:run',
    ],
    'account' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Account:run',
    ],
    'admin' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Admin:run',
    ],
];