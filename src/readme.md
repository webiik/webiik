# Webiik Core

##Routes
Routes are mapped with with method `map(array $methods, $uri, $controller, $name)`. Webiik dispatches to controller. Controller can be: __closure, ClassName, ClassName:methodName__.   
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
Middleware is the code launched before route controller. Middleware can be associated with the whole app or just with the specific route. App middlewares are launched before route middlewares.

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
Middleware can be: __closure, invokable Class or ClassName:methodName__. Required parameters are `Request $request` and `$next`. Both required parameters are automatically filled during executing the middleware, so you don't need to care more about them. Signature of typical middleware looks like: `method($request, $next){}`. You can also add your own parameters: `method($request, $next, $p1, $p2...etc.){}`.

Example of invokable class middleware:
```php
namespace MyNameSpace;
class Mw
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

##Dependency injection
Webiik uses Pimple as dependency injection container. So everything inside container can be injected. Webiik provides automatic dependency injection from container into __middlewares__ and __route controllers__. So you don't need to write dependencies manually. There are three types of automatic injection: 

1. constructor injection
2. comments injection
3. method injection

#### How to inject?
1. At first add service(s) and value(s) you want to inject into Pimple container. Webiik provides the following methods for working with Pimple container: `addService()`, `addServiceFactory()`, `addParam()` and `addFunction()`.
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
You can also use this automatic injection for any other class using the static methods `methodDI($object, Container $container)`, `commentDI($object, Container $container)` and `constructorDI($className, Container $container)`. See examples below:

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