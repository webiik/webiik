# Plug-ins proposal
Plug-in is complete part of application with routes, translations etc. Its structure and functionality is same like Webiik's app.

Runtime of plug-in is during executing the `run` method of `Skeleton` class.

## Component folder structure
Folders marked with * are optional.

plug_in_name
├── components*
|   └── component_one
|   └── component_two
├── config*
|   └── config.php
├── controllers
|   └── class_one.php
|   └── class_two.php
├── middlewares*
|   └── mw_one.php
├── routes
|   └── routes.php
├── translations
|   └── en.php
|   └── cs.php
├── views
|   └── view_one.twig
|   └── view_two.twig
└── desc.php
 
## File desc.php
Here comes your plug-in description.
```php
return [
    'folder' => 'plug-in-folder',
    'ver' => 1.1,
    'author' => 'Firstname Lastname',
    'link' => 'http://www.webiik.org',
    'description' => 'Nice plug-in.',
    'tags' => 'nice, plug-in, webiik',
];
```

## Installing component
1. Open terminal window and go to `components` folder and run command `php install.php`
2. Follow the install steps.
3. If component requires some database tables, you need to create them manually.

## Activating / deactivating component
1. Open file `installed.php` inside `components` folder.
2. Change status by desired component to `active` or `inactive`

## Uninstalling component
1. Open terminal window and go to `components` folder and run command `php uninstall.php`
2. Follow the install steps.
3. If component used some database tables, you need to detele them manually.