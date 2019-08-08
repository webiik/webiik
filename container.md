---
layout: default
title: Container
permalink: /container/
---
# Container
The Container is storage of services. Any class can be added as a service. The concept of services is very useful because once you add a service, you can get the same instance of the service everywhere within the Webiik application.

⚠️ Container and services make the backbone of every Webiik application. Give it a time to learn it perfectly.  

## Defining Services
You can define services in two files: **private/app/config/container/services.php** and **private/app/config/container/models.php**. File&nbsp;**models.php** should contain only services representing models.

1. Open a service definition file.
2. Add service definition to the array in the following format: 
   ```php
   string $name => callable $factory
   ```
   for example:
   ```php
   'Name\Space\ClassName' => function (\Webiik\Container\Container $c) {
        return new \Name\Space\ClassName();
    },
   ```
   Webiik adds service definitions using the method [addService(string $name, callable $factory): void](https://github.com/webiik/components/blob/master/src/Webiik/Container/README.md#addservice).
   
🌐 Webiik supports language-related service definition files, for example: **services.en.php**. Webiik always loads only one version of each service definition file with the following priority: *.en.php, *.php.
   
⚠️ The service name can be custom. However, if you want to use automatic dependency injection into middleware and controllers, you have to follow one of these naming conventions: 

### Name Service by Class Name
Name your service according to class name the service returns incl. the class namespace, for example:
```php
'Webiik\Flash\Flash' => function (\Webiik\Container\Container $c) {
    return new \Webiik\Flash\Flash();
},
```

### Name Service with Short Name
Name your service to match the following regex `ws[A-Z]`, for example:
```php
'wsFlash' => function (\Webiik\Container\Container $c) {
    return new \Webiik\Flash\Flash();
},
```

## Configuring Services
If you use any configuration values inside your service definition, it can be a good idea to place these values into a separate file. The separate configuration file allows you to configure your service according to the environment and/or language.

1. Open file **private/app/config/resources.php**.
2. Add configuration of your service under key `services`. In case your service is a model, add configuration under key `models`:
   ```php
   'ClassName' => [
        'customMethod' => 'customValue',
   ],
   ```
Key name can be custom. However, it's a good idea to set a key name similar to the service name, service method name or service parameter name.<br/><br/>
3. You can access the configuration using the service [wsConfig](/ws-config).

👨‍💻🌐 Webiik supports local and language related configuration of services. For example: **resources.en.php**, **resources.en.local.php**. Webiik always loads only one service configuration file with the following priority: *.en.local.php, *.local.php, *.en.php, *.php. Never publish your local configuration file to production. If you deploy your Webiik project using the Git, Webiik ignores local configuration files, so you don’t have to care.
   
## Accessing Services
All services are stored in [the Container](https://github.com/webiik/components/blob/master/src/Webiik/Container). You can get services directly from Container, or you can access services from the constructor in middleware or route controllers.

### Accessing Service Directly from Container
Usually you will access services directly from Container within [service definition](#defining-services). For example:
```php
'Webiik\Translation\Translation' => function (\Webiik\Container\Container $c) {
    $arr = $c->get('Webiik\Arr\Arr'); // Get service Webiik\Arr\Arr from Container
    return new \Webiik\Translation\Translation($arr);   
},
```

### Accessing Service by Class Name
If you [name your service by class name](#naming-services), you can access the service by class name from constructor in middleware or route controller:
```php
public function __construct(\Webiik\Flash\Flash $array)
{
   $this->flash = $flash;
}
```
Container will search for the service with name `Webiik\Flash\Flash`.
   
### Accessing Service by Short Name
If you [name your service with short name](#naming-services), you can access the service by short name from constructor in middleware or route controller:
1. Assign the service name to the class the service returns:
   ```php
   use Webiik\Flash\Flash as wsFlash;
   ```
2. Use service name as a type parameter in the constructor of your class and add PHPdoc:   
   ```php
/**
* @param wsFlash $flash
*/   
   public function __construct(wsFlash $flash)
   {
       $this->flash = $flash;
   }
   ```
Container will search for service with name `wsFlash`.