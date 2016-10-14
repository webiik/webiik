# AuthMw
Auth middleware is part of Webiik Skeleton.

## Configuration
AuthMw requires configuration of login route name. AuthMw is configured via [config.php]() like any other core services in Webiik Skeleton. 
```php
'auth' => [
        'permanentLoginCookieName' => 'PC',
        'withActivation' => false,
        'loginRouteName' => 'login', // <-- this option is related to AuthMw 
    ],
```

You can also make configuration manually using the method `setLoginRouteName`:
```php
$authMw->setLoginRouteName('login');
```

## Basic usage
Simply use AuthMw like any other middleware. If you need, read more about middlewares [here](core.md).
```php
$app->map(['GET'], '/', 'Namespace\Class:method', 'home-page')->add('Webiik\Auth:userCan', ['access-admin']);
```

## Usage in routes.php
You can use AuthMw also in [routes.php]() file.
```php
return [
    'account' => [
        'methods' => ['GET'],
        'controller' => 'Namaspace\Class:method',
        'middlewares' => [
            // Check if user is logged in, if not redirect user to login page
            'Webiik\AuthMw:isUserLogged' => [],
        ],
    ],
    // Don't forget to specify login page
    'login' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'Webiik\Login:run',
    ],   
];
```
    
## Description of provided methods

#### setLoginRouteName(string $string)
Sets login route name.
#### isUserLogged()
It uses `isUserLogged` method from [Auth.php](auth.php). If user is logged in, it writes user id into Request object under key 'uid' and runs next middleware. Otherwise it redirects user to login page usign the Auth's `redirect` method.
#### userCan(string $action)
It uses `userCan` method from [Auth.php](auth.php). If user is logged and can perform the action, it writes user id into Request object under key 'uid' and runs next middleware. Otherwise it redirects user to login page usign the Auth's `redirect` method. 