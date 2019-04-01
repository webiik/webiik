---
layout: default
title: Middleware
permalink: /middleware/
---
# Middleware
Middleware adds additional layers to your Webiik application as you can see on the picture below. It can be useful when you have code that needs to be executed by every request or by more route specific requests. 

## How Does Middleware Work?
Each middleware is executed and can continue to the next one until it reaches a route controller. Route controller itself is also the middleware. Application middleware layers are executed at first, then are executed route specific middleware layers. Route controller is executed at last.

<img class="medium" src="/assets/images/middleware.svg" alt="Middleware"/>

## Writing Middleware
Store your middleware classes in folder **private/app/code/middleware** and use namespace `namespace Webiik\Middleware`. Read [Middleware documentation](https://github.com/webiik/components/blob/master/src/Webiik/Middleware/README.md#writing-middleware) to learn more about writing middleware.

## Registering Middleware
You have written your middleware, now it's time to add it to your Webiik application.

1. Open file **private/app/config/middleware/middleware.php**.
2. Add middleware to the array in the following format: 
   ```php
   string $controller => array $data,
   ``` 
   for example:
   ```php
   'Webiik\Middleware\Core\SetSecurityHeaders:run' => [],
   ```
Webiik adds middleware using the method [add(string $controller, $data = []): void](https://github.com/webiik/components/blob/master/src/Webiik/Middleware/README.md#add).

🌐 Webiik supports language related middleware registration files, for example: **middleware.en.php**. Webiik always loads only one **middleware** registration file with the following priority: *.en.php, *.php.

ℹ️ To add route specific middleware, read [Routing](/routing).

## Configuring Middleware
If you use any configuration values inside your middleware, it can be a good idea to place these values into a separate file. The separate configuration file allows you to configure your middleware according to the environment and/or language.

1. Open file **private/app/config/resources.php**.
2. Add configuration of your middleware under the key `middleware`:
   ```php
   'MiddlewareClassName' => [
       'customKeyName' => 'customValue',
   ],
   ```
   Key name can be custom. However, it’s a good idea to set a key name similar to associated middleware class name, middleware method or parameter.<br/><br/>
3. Configuration is stored in service [wsConfig](/ws-config). You can access this service from middleware constructor. Read about [the Container](/container) to know more.

👨‍💻🌐 Webiik supports local and language related middleware configuration. For example: **resources.en.php**, **resources.en.local.php**. Webiik always loads only one service configuration file with the following priority: *.en.local.php, *.local.php, *.en.php, *.php. Never publish your local configuration file to production. If you deploy your Webiik project using the Git, Webiik ignores local configuration files, so you don’t have to care.