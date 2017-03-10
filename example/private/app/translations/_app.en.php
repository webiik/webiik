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
        // User account service routes
        'signup' => '/signup',
        'login' => '/login',
        'password-renewal' => '/password-renewal',
        'password-update' => '/password-update',
        'activation-send' => '/activation-send',
        'activation-confirm' => '/activation-confirm',
        'social-pairing-send' => '/social-account-pairing-send',
        'social-pairing-confirm' => '/social-account-pairing-confirm',
        'social-facebook' => '/social-facebook',
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
    'auth' => [
        'msg' => [
            // Todo: Move auth translations to separate files associated with controllers
            // Login and sign-up
            'csrf-mismatch' => 'CSRF token mismatch.',
            'csrf-form' => 'Form can be sent only using the button.',
            'correct-red-field' => 'Correct red marked fields.',
            'welcome-first' => 'Welcome! It\'s great that you are here.',
            'welcome-again' => 'Welcome! It\'s great to see you again.',
            'user-banned' => 'You have a ban to access this site.',
            'too-many-attempts' => 'Too many attempts. Try it later.',
            'user-already-exists' => 'Same account already exists.',
            'user-does-not-exist-1' => 'Account does not exist.',
            'user-does-not-exist-2' => 'Sign up first.',
            'user-activate-1' => 'Take a look to your email box and activate your account.',
            'user-activate-2' => 'Send activation email again?',
            'entry-required' => 'Required field.',
            'entry-invalid' => 'Invalid format.',
            'entry-long' => 'Entry is too long.',
            'entry-short' => 'Entry is too short',
            'pswd-incorrect' => 'Incorrect password.',
            'pswd-not-set-1' => 'Password is not set. Login with {providers} and set the password.',
            'pswd-not-set-2' => 'or',
            'social-pair-1' => 'Social account is not paired with main account.',
            'social-pair-2' => 'Send pairing email?',
            'user-has-no-permissions' => 'You don\'t have insufficient permissions to view this site.',
            'user-not-logged' => 'Log in to view content of this site.',
            'unexpected-err' => 'Unexpected error({operation}{errNum}).',
            // Tokens processing
            'key-not-set' => 'Key is not set.',
            'key-invalid-format' => 'Invalid key format.',
            'key-invalid' => 'Invalid key.',
            'key-cant-generate' => 'Can\'t generate key.',
            'provider-not-set' => 'Provider is not set.',
            'provider-unsupported' => 'Unsupported provider.',
            'account-is-already-activated' => 'You have already activated your account.',
            'account-activated-by-other-user' => 'Account has been already activated by another user. You cannot use this account any more.',
            'account-activated' => 'Your account has been activated. Just log in.',
            'account-paired' => 'Your main account has been paired with {provider}. Now you can use {provider} to login.',
            'activation-sent' => 'Activation message has been sent to {email}.',
            'pairing-sent' => 'Pairing message has been send to {email}.',
        ],
    ],
];