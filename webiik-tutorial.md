---
layout: default
title: Webiik Tutorial
permalink: /webiik-tutorial/
---
# Step by Step Tutorial

## 1. Install Webiik
Follow the [installation guide](/installation).    

## 2. Configure Webiik
* Open config file **app.php** located in **private/app/config** folder.
* If your Webiik project isn't located in web server root folder, update **baseUri** to match your Webiik project location.
* Update **languages** and **defaultLanguage** according to your needs.
* If your production environment differs from your local development environment, copy **app.php** configuration file to **app.local.php**. Update both files to match your environments.

> 😺 Now when you open your Webiik project inside your web browser, you should see message "Meow World!".

* Open config file **use.php** located in **private/app/config** folder and configure built-in Webiik services. To learn more about configuration values, check documentation of individual services.
* If your production environment differs from your local development environment, copy **use.php** configuration file to **use.local.php**. You can also use language specific configuration files e.g. **use.en.php**, **use.en.local.php**. Update all files to match your environments.

## 4. Adding Services


## 3. Adding Services
Webiik comes with just few built-in services. In the following steps you will learn how to add most common services using the Webiik components. But remember, **you can use whatever components/libraries you want**, it makes Webiik very flexible.

### Error Handling
* Open config file **services.php** located in **private/app/config/use** folder.
* Add the Webiik component "Error" as a service:
```php
return [
   'Webiik\Error\Error' => function () {
        return new \Webiik\Error\Error();
    },
];
```

### Logging
* Open config file **services.php** located in **private/app/config/use** folder.
* Add the Webiik component "Log" as a service:
```php
return [
    'Webiik\Log\Log' => function (\Webiik\Container\Container $c) {
        $log = new \Webiik\Log\Log();

        // Configure silent mode
        $log->setSilent($c->get('wsConfig')->get('services')['Error']['silent']);

        // Add ErrorLogger for messages in group error
        $log->addLogger(function () {
            $logger = new \Webiik\Log\Logger\ErrorLogger();
            $logger->setMessageType(3);
            $logger->setDestination(WEBIIK_BASE_DIR . '/../tmp/logs/error.log');
            return $logger;
        })->setGroup('error');
       
        return $log;
    },
]
``` 