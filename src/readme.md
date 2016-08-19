# Webiik

##Routes
Routes are mapped with with method `map(array $methods, $uri, $controller, $name)`. Webiik dispatches to controller. Controller can be: closure, ClassName, ClassName:methodName.   
```php
$app->map(['GET'], '/', 'MyNameSpace\ClassName:methodName', 'home-page');
```

__404__ and __405__ error routes are mapped with methods `error404()` and `error405()`.
```php
$app->error404('MyNameSpace\ClassName:methodName');
$app->error405('MyNameSpace\ClassName:methodName');
```

##Middlewares
#### What is middleware?
Middleware is the code launched before route handler. Middleware can be associated with the whole app or 
just with the specific route. App middlewares are launched before route middlewares.

#### How to add middleware?
Simply add middleware with method `add($middleware, array $parameters)` 

App middleware:
```php
$app->add('\MyNameSpace\ClassName', ['John']);
```

Route middleware:
```php
$app->map(['GET'], '/', 'MyNameSpace\Class:method', 'home-page')->add('\MyNameSpace\ClassName', ['John']);
```

#### How to write middleware?
Middleware can be: closure, ClassName, ClassName:methodName. Required parameters are `$request` and `$next`. `$request` can be whatever eg. array, object etc.. Parameter `$next` is callable that launches next middleware, you don't need to care more about this parameter. So signature of typical middleware looks like: `method($request, $next){}`. You can also add your own parameters: `method($request, $next, $p1, $p2...etc.){}`.

Example of invokable class middleware:
```php
namespace MyNameSpace;
class Mw
{
    __invoke($request, $next, $userName)
    {
        // Some code here...
        echo 'Hello ' . $userName . '!';
        // Calling the next middleware
        // Without this the next middleware will not be launched
        $next($request);       
    }
}
```

##Dependency injection
Webiik uses Pimple as dependency injection container. So all services, functions etc. inside container can inject dependencies the Pimple way. Webiik also provides automatic dependency injection into middlewares and route handlers. So you don't need to write dependencies manually. You can also use static methods `methodDI($object, Container $container)`, `commentDI($object, Container $container)` and `constructorDI($className, Container $container)` to inject dependencies from container to services.

#### How to inject?
At first add service(s) and value(s) you want to inject: 
```php
$app->addService('MyClass', function($c){return new MyClass();});
$app->addParam('appName', 'MyApp');
```

Then follow one of examples below.

#### Injection into route handlers
__Constructor injection:__
```php 
namespace MyNameSpace;
class ClassName
{ 
    __construct(MyClass $myClass, $appName)
    {
    }
}
```

#### Injection into route handlers and middlewares
__Injection using 'inject' method prefix__:
```php 
namespace MyNameSpace;
class ClassName
{
    injectDependencies(MyClass $myClass, $appName)
    {
    }
}
```

__Injection using @inject doc comment__:
```php 
namespace MyNameSpace;
class ClassName
{
    /** @var MyClass @inject */
    public $myClass;  // parameter must be public when using @inject injection
}
```
#### Injection into services
Injection into services can work same way like injection into route handlers, but you need manually call some DI method during service creation.

__Constructor injection:__
```php
$app->addService('MyService', function($container) {
    $dependencies = Core::constructorDI(MyClass, $container)
    return new MyClass(...$dependencies);
});
```

__Injection using @inject doc comment__:
```php
$app->addService('MyService', function($container) {
    $object = new MyClass();
    Core::commentDI($object, $container);
    return $object;
});
```

__Injection using 'inject' method prefix__:
```php
$app->addService('MyService', function($container) {
    $object = new MyClass();
    Core::methodDI($object, $container);
    return $object;
});
```

Webiik provides following methods for working with Pimple container: `addService()`, `addServiceFactory()`, `addParam()` and `addFunction()`.