---
layout: default
title: Configuration
permalink: /configuration/
---
# Configuration
Fresh Webiik application has 6 configuration files inside `private/config` folder and associated subfolders. The main configuration file is `app.php`. Other configuration files belongs to [container](/container), [middleware](/middleware) and [routing](/routing) and are described under related sections.

The Content of the `app.php` configuration file is pretty simple:
```php
return [
    'app' => [
        // Application mode 'development' or 'production'
        'mode' => 'production',

        // Base URI of application, usually '/'
        'baseUri' => '/',

        // Array of all available languages of application
        // format: ISO 639-1 => [timezone, encoding]
        'languages' => [
            'en' => ['America/New_York', 'utf-8'],
        ],

        // Default language of application in ISO 639-1
        // Can be a string or an array(hostname => lang, ...)  
        'defaultLanguage' => 'en'
    ],
];
```

* `mode` By default the development mode displays errors on screen and production mode logs and sends errors by email. This setting affects value of global constant [WEBIIK_DEBUG](/constants).   
* `baseUri` If you place your Webiik application outside web server root, for example, `https://localhost/your-app/public`, then you have to update the value of baseUri to `/your-app/public`.
* `languages` An associative array of all languages available for current Webiik application. You have to define at least one language.
* `defaultLanguage` Default language is the language used when no valid language is detected in URI. Default language can be a string or an array. When the array is used, the default language is determined by hostname. If current hostname doesn't match any hostname defined in the array, the first language in the array is used as default. The format of the array must be in the format: [string hostname => string lang, ...]

ℹ️ Configuration is stored in the service [wsConfig](/ws-config).

## Local (dev) Environment
Webiik supports local configuration files. Just copy configuration file `app.php` to `app.local.php`. When Webiik detects file `app.local.php`, it ignores file `app.php`. It means, never publish your local configuration files to production. If you deploy your Webiik project using the Git, Webiik ignores local configuration files, so you don’t have to take care.