---
layout: default
title: Routing
permalink: /routing/
---
# Routing
Any Webiik application can't exist without routes. Webiik provides a powerful router.

## Defining Routes
All routes are defined in **routes** file(s). 

🌐 Routes files are language dependent. Fresh Webiik application comes with English routes file.

Open file **private/app/config/routes/routes.en.php**. It contains array:
```php
return [
   'home' => [
	   'uri' => '/',
	   'methods' => ['get'],
	   'controller' => 'Page:run',
	   'mw' => [],
   ],
];
```
* **home** - route name   
* **uri** - route URI regex (without delimiters), can include [parameters](https://github.com/webiik/components/blob/master/src/Webiik/Router/README.md#addroute)
* **methods** - array of supported http methods, usually 'get', 'post'
* **controller** - [route controller](#route-controller) in format className:methodName
* **mw** - array of [route middleware](#route-middleware)

To add new route simply add new route record, for example: 
```php
return [
   'home' => [
	   'uri' => '/',
	   'methods' => ['get'],
	   'controller' => 'Page:run',
	   'mw' => [],
   ],
   'about' => [
	   'uri' => '/about',
	   'methods' => ['get'],
	   'controller' => 'Page:run',
	   'mw' => [],
   ],
];
```

## Route Controller
Store your route controller classes in folder **private/app/code/controllers** and use namespace root `namespace Webiik\Controller`. A method that initiates controller must be compatible with [middleware](/middleware). Simplest route controller looks like this:
```php
declare(strict_types=1);

namespace Webiik\Controller;

class Page
{  
    public function run(\Webiik\Data\Data $data): void
    {
        echo 'Meow World!';
    }
}
```

## Route middleware
You can add route specific middleware by filling **mw** array with middleware. Route middleware is always executed after application middleware. The format of route middleware array is as same as [application middleware array](/middleware#adding-middleware). For example: 
```php
return [
   'home' => [
	   'uri' => '/',
	   'methods' => ['get'],
	   'controller' => 'Page:run',
	   'mw' => [
	       'Webiik\Middleware\Core\SetSecurityHeaders:run' => [],
	   ],
   ],  
];
```

## Accessing the Current Route
The current route is stored in Container as a service **Webiik\Router\Route**. Read more about [Route](https://github.com/webiik/components/blob/master/src/Webiik/Router/README.md#route) and [accessing services](/container#accessing-service). Example of accessing current route from route controller:
```php
declare(strict_types=1);

namespace Webiik\Controller;

use Webiik\Router\Route;

class Home
{
    private $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function run(\Webiik\Data\Data $data): void
    {
        echo $this->route->getName();
    }
}
```

## 404 and 405 routes
Route 404 is shown when URI doesn't match any route definition. Route 405 is shown when the route doesn't support HTTP method of an HTTP request. Webiik comes with preconfigured controllers for 404 and 405 routes, look into **private/app/code/controllers** folder. You can update them according to your needs. 