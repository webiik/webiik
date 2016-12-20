<?php
/**
 * App wide translations for current language.
 * These translations will be available on every page. Great for header, footer etc.
 * Don't place here translations for separate pages!
 */
return [
    // Special key 'routes' - here you write URI translations of routes.
    // Only routes with translated URIs will be active.
    'routes' => [
        // Todo: _app.php shared translation file across translations
        // User account service routes
        'signup' => '/signup',
        'login' => '/login',
        'activate' => '/activate',
        'forgot-request' => '/password-renewal-request',
        'forgot-confirm' => '/password-renewal-confirmation',
        'activate-request' => '/activation-request',
        'activate-confirm' => '/activation-confirmation',
        'logout' => '/logout',
        // Website routes
        'home' => '/',
        'account' => '/account',
        'admin' => '/admin',
    ],
    'nav' => [
        // 0 - always show nav item
        // 1 - show nav item only when user is not logged in
        // 2 - show nav item only when user is logged in
        'home' => ['Home page', 0],
        'login' => ['Log in', 1],
        'signup' => ['Sign up', 1],
        'forgot' => ['Forgot password?', 1],
        'account' => ['My account', 2],
        'admin' => ['Admin', 2],
        'logout' => ['Logout', 2],
    ],
    'msgUnauthorised' => 'You don\'t have permissions to view this site.',
];