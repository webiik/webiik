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
        'home' => '/',
        'login' => '/login',
        'login-oauth1' => '/login-oauth1',
        'signup' => '/signup',
        'forgot' => '/forgot-password',
        'logout' => '/logout',
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
];