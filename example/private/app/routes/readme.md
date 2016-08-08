# Routes
Files inside this folder are used by Webiik/Skeleton for generating the app routes. These files must return array with route definitions as seen in example below.

### routes.lg.php
'lg' must be replaced with ISO 639-1 language code. These files are not necessary. Use these files only when you need custom routes structure for some language 'lg'. These files override route architecture for specified language 'lg'.

### routes.php
App routes definition file. 

### Content example
Keep in mind that values of 'uri' keys will be always translated during parsing these files. If translation will not be found then the route will not be mapped.
```php
return [
    'home' => [
        'uri' => '/',
        'controller' => 'Webiik\Controller:launch',
        'middlewares' => [
            'hello' => ['world'],
        ],
    ],
    'account' => [
        'uri' => '/about',
        'controller' => 'Webiik\Controller:launch',
        'middlewares' => [
            'auth' => ['user'],
            'hello' => ['world'],
        ],
        'models' => ['Users']
    ],
];
```