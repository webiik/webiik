# Core
Core provides methods for [routing](#routing), adding app or route [middlewares](#middlewares) and working with [DI container](#dependency-injection). This is good foundation for every web application.

## Installation
Core is part of [Webiik platform](readme.md). Before using Core in your project, install it with the following command:
```bash
composer require mihi/webiik
```

## Simple example
```php
// Instatiate Webiik Core
$app = new \Webiik\Core();

// Add middlewaras, routes, services and so on...

// Run app
$app->run();
```

## Routing
Core uses [Webiik Router](router.md), but provides own three methods for working with the router. Methods: `map`, `error404` and `error405`. Core dispatches routes to specified controllers.
   
#### Adding the route
Simply add route with method `map(array $methods, string $uri, string|closure $controller, string $name):Route`. Signature of controller MUST be: __closure, Class or Class:method__.  
```php
$app->map(['GET'], '/', 'Namespace\Class:method', 'home-page');
```

#### Adding the 404 and 405 routes
Error routes are mapped with methods `error404(string $controller)` and `error405(string $controller)`. Signature of controller MUST be: __Class:method__.
```php
$app->error404('Namespace\Class:method');
$app->error405('Namespace\Class:method');
```

## Middlewares

#### What is middleware?
Middleware is the code launched before route controller. Middleware can be associated with the whole app or just with the specific route. App middlewares are launched before route middlewares.

#### How to add middleware?
Simply add middleware with method `add(string|closure|invokable $middleware, array $parameters)`. Signature of $middleware parameter MUST be: __closure, Class or Class:method__. 

App middleware:
```php
$app->add('Namespace\Class:method', ['John']);
```

Route middleware:
```php
$app->map(['GET'], '/', 'Namespace\Class:method', 'home-page')->add('Namespace\Class', ['John']);
```

#### How to write middleware?
Middleware can be: __closure, invokable Class or Class:method__. Required parameters are `Request $request`([link](request.md)) and `closure $next`. Both required parameters are automatically set during the execution of middleware, so you don't need to care more about them. Signature of typical middleware looks like: `method(Request $request, closure $next){}`. You can also add your own parameters: `method(Request $request, closure $next, $p1, $p2...etc.){}`.

Example of invokable class middleware:
```php
namespace Namespace;
class Class
{
    __invoke(Webiik\Request $request, $next, $userName)
    {
        // Some code here...
        echo 'Hello ' . $userName . '!';
        // Add something to Request obj
        $request->set('user', $userName);
        // Calling next middleware
        // Without this next middleware will not be launched
        $next($request);       
    }
}
```

## Dependency injection
Core uses [Pimple](https://github.com/silexphp/Pimple) as dependency injection container. Webiik provides automatic dependency injection from container into __middlewares__ and __route controllers__. So you don't need to write dependencies manually.

#### How to inject?
1. At first add service(s) and value(s) you want to inject into Pimple container. Webiik provides the following methods for working with Pimple container: `addService(string $name, closure $factory)`, `addServiceFactory(string $name, closure $factory)`, `addParam(string $name, mixed $val)`, `addFunction(string $name, closure $function)` and `get(string $name)`. These methods reflects Pimple's basic functionality.
    ```php
    $app->addService('MyNameSpace\MyClass', function($c){return new \MyNameSpace\MyClass();});
    $app->addParam('appName', 'MyApp');
    ```

2. Then use one of the following methods of injection:

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
    
    Result: $myClass and $appName will be automatically injected from Container.
    
    __Injection using 'inject' method prefix__:
    ```php 
    namespace MyNameSpace;
    class ClassName
    {
        public function injectDependencies(MyClass $myClass, $appName)
        {
        }
    }
    ```
    
    Result: $myClass and $appName will be automatically injected from Container.
    
    __Injection using @inject doc comment__:
    ```php 
    namespace MyNameSpace;
    class ClassName
    {
        /** @var MyClass @inject */
        public $myClass;  // parameter must be public when using @inject injection
    }
    ```
    
    Result: $myClass will be automatically injected from Container.
    
    That's all, so easy!

#### Using automatic DI outside the middlewares and route controllers
You can also use this automatic injection for any other class using the static methods `methodDI(object $object, Container $container)`, `commentDI(object $object, Container $container)` and `constructorDI(string $className, Container $container)`. See examples below:

__Constructor injection:__
```php
$app->addService('MyService', function($container) {
    $dependencies = Core::constructorDI(MyClass, $container)
    return new MyClass(...$dependencies);
});
```

Result: Into MyClass constructor will be injected from Pimple container all required parameters of constructor.

__Injection using @inject doc comment__:
```php
$app->addService('MyService', function($container) {
    $object = new MyClass();
    Core::commentDI($object, $container);
    return $object;
});
```

Result: Into MyClass object will be injected from Pimple container all dependencies defined by comments injection inside MyClass.

__Injection using 'inject' method prefix__:
```php
$app->addService('MyService', function($container) {
    $object = new MyClass();
    Core::methodDI($object, $container);
    return $object;
});
```

Result: Into MyClass object will be injected from Pimple container all dependencies defined by method injection inside MyClass.

## Description of provided methods
<!--Todo: description of provided methods-->