<?php
/**
 * All shared translations of current lang.
 *
 * Special key:
 * 'routes' - here you assign translated URIs to route definition '/routes/routes.php' by route name
 *
 * Note: Only assigned routes in current/fallback(s) lang(s) will be available in current lang.
 */
return [
    'routes' => [
        'home' => '/',
        'login' => '/login',
        'account' => '/account',
    ],
    'nav' => [
        'home' => 'Home page',
        'login' => 'Login',
        'account' => 'My account',
    ],
    'login' => [
        'msg-ok' => 'Welcome {name} to your account!',
        'msg-err-1' => 'Wrong email.',
        'msg-err-2' => 'Wrong password.',
        'msg-err-3' => 'Active account in confirmation email.',
        'msg-err-4' => 'Your account has been banned.',
        'link-activation' => 'Re-send activation email.',
    ],
    'other' => [
        'key' => 'val',
    ],
];