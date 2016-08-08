##Middlewares

#### What is middleware?
Middleware is the callable launched before route handler. Middleware can be associated with the whole app or 
just with the specific route. App middlewares will be launched before route middlewares.

#### How to write middleware?
Middleware can be anonymous function, invokable class or just class with method which will be used as middleware. Required parameters are `$response` and `$next`. `$response` can be array, object, whatever...just response you need from your middleware. `$next` is callable of next middleware, you don't need to care more about this parameter. So signature of typical middleware is like: `($response, $next, $p1(optional), $p2(optional), ...)`. What are `$p1`, `$p1`? Just optional parameters that will be injected during middleware call.

Example of invokable class middleware:
```php
namespace MyNameSpace;
class Mw
{
    function run($response, $next, $userName)
    {
        // Some code here...
        echo 'Hello ' . $userName . '!';
        // Calling the next middleware
        // Without this the next middleware will not be launched
        $next($response);       
    }
}
```

#### How to add middleware?
App middleware:
```php
$app->add('\MyNameSpace\Mw:run', ['John']);
```

Route middleware:
```php
$app->map(['GET'], '/', 'MyNameSpace\Class:method', 'home-page')->add('\MyNameSpace\Mw:run', ['John']);
```

##Routes
Routes can be simply mapped with with the `map()` method. Webiik dispatches to controller. Controller must be a class. Read more about route format [here]().   
```php
$app->map(['GET'], '/', 'MyNameSpace\Class:method(optional)', 'home-page');
```

__404__ and __405__ error routes are mapped with methods `error404()` and `error405()`.
```php
$app->error404('MyNameSpace\Class:method(optional)');
$app->error405('MyNameSpace\Class:method(optional)');
```

##Dependency injection
Webiik uses Pimple as dependency injection container. To inject dependencies to route handler just add Pimple service with same name as your route handler. Service will be injected into route handler during its construction.
```php
// Look at $c, it's Pimple container. So you can here access everything what is in the Pimple container. 
$factory = function ($c) {
    return [new MyClass(), new MyClass()];
};
$app->addService('MyNameSpace\Class', $factory);
```

Here is example, how you can inject some external values:
```php
 $factory = function ($c) {
    return [new MyClass(), new MyClass(), $c['myValues']];
};
$app->addParam('myValues', ['a', 'b', 'c']);
$app->addService('MyNameSpace\Class', $factory);
```

Webiik provides following methods for working with Pimple container: `addService()`, `addServiceFactory()`, `addParam()` and `addFunction()`.