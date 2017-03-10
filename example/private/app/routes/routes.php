<?php
/**
 * Route definitions for all languages
 * Signature: 'routeName' => ['methods' => array, 'controller' => string, (optional)'middlewares' => array]
 * You can also define routes for specific language in /routes/routes.{lang}.php
 * To make routes working properly you also need to add translations of these routes in /translations/_app.{lang}.php
 */
return [
    // User account service routes
    // Todo: All auth ajax routes and its controllers
    'signup' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\AuthSignup:run',
    ],
    'login' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\AuthLogin:run',
    ],
    'social-facebook' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\AuthSocialFacebook:run',
    ],
    'password-renewal' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\AuthPasswordRenewal:run',
    ],
    'password-update' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\AuthPasswordUpdate:run',
    ],
    'activation-send' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\AuthActivationSend:run',
    ],
    'activation-confirm' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\AuthActivationConfirm:run',
    ],
    'social-pairing-send' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\AuthSocialPairingSend:run',
    ],
    'social-pairing-confirm' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\AuthSocialPairingConfirm:run',
    ],
    'logout' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\AuthLogout:run',
    ],
    // Website routes
    'home' => [
        'methods' => ['GET'],
        'controller' => 'Webiik\Page:run',
    ],
    'account' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Account:run',
        'middlewares' => [
            'Webiik\AuthMw:can' => ['access-account'],
        ],
    ],
    'admin' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Admin:run',
    ],
];