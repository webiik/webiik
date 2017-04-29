> Please note that Webiik is in version 0.1, it means under development.

## What is Webiik?
Webiik is set of 25+ standalone PHP classes. Webiik is also MVC PHP micro-framework and framework.

###### Micro-framework minimal setup example:
```php
$app = new \Webiik\Webiik();

$app->router()->map(['GET'], '/', function() {
    echo 'Hello World!';
}, 'home');

$app->run();
```

###### Installation:
```bash
php composer.phar require webiik/webiik
```

[Documentation [to be done]]()
[Live example [to be done]]()

## Why other PHP framework?
Because author of Webiik wanted simple framework that he would know through and through. Framework by his specific needs. Webiik is suited for:

  - multilingual websites
  - secured and advanced user accounts
  - easy customisation

## Security vulnerabilities
If you discover a security vulnerability within Webiik, please send me an email at jiri@mihal.me.

## License
Copyright (c) 2017 Jiri Mihal
[MIT license](http://opensource.org/licenses/MIT)