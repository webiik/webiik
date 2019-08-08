---
layout: default
title: Extensions
permalink: /extensions/
---
# Extensions
Webiik allows you to use extensions. Extensions are reusable parts of Webiik application.

## Creating Extension
1. Go to folder **private/extensions**. If it doesn't exist, create it. 
2. Inside folder **private/extensions** create a new extension by executing the following command. You can change extension folder name **Extension** to the desired name of your extension. ⚠️ Extension folder name must begin with a big letter.
   ```bash
   composer create-project webiik/extension Extension
   ```
3. If you changed extension folder name for example to **Admin**, you have to update extension namespace according to the new extension folder name. Open file **private/extensions/Admin/code/controllers/Page.php** and update namespace to **WE\Admin\Controller**

⚠️ Code of every extension has to live in its own namespace beginning with letters **WE**, followed by name of extension folder. For example: 
* `WE\Admin\Controller`
* `WE\Admin\Middleware`
* `WE\Admin\Model`
* `WE\Admin\Component`
* `WE\Admin\Trait`

## Extension Limitations
Unlike the main Webiik application, extensions have some limitations:
* you can't define new services within an extension
* you can't register application middleware within an extension
* you can't configure anything within an extension

It doesn't mean your extension can't use services or access configuration. If your extension requires some specific service or configuration, you have to add it manually in the main application. These limitations are here to prevent conflicts between extensions and the main application.

## Enabling Extension
To enable the extension, you have to register it within your main application.

1. Open file **private/app/app.php**.
2. Before `$app->run();` add the following line:
   ```php
   $app->use('Admin', '/admin');
   ```
   The first parameter is the name of extension folder inside **private/extensions** folder, the second parameter is URI of extension within your main application.
3. Open file **private/composer.json**.
4. Add autoloading paths for your extension, for example:
   ```json
   "autoload": {
       "psr-4": {
            "WE\\Admin\\Controller\\": "extensions/Admin/code/controllers",
            "WE\\Admin\\Middleware\\": "extensions/Admin/code/middleware",
            "WE\\Admin\\Model\\": "extensions/Admin/code/models",
            "WE\\Admin\\Trait\\": "extensions/Admin/code/traits"
       },
       "classmap": ["extensions/Admin/code/components"]
     }
   ``` 