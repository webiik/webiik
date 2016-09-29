# Components proposal
Component is UI element driven by its own controller and rendered by its own template. Component can access DI container so it can be really powerful. Optionally you can add translations and assets 

Runtime of component is during executing the `render` method of `View` class.

## Component folder structure
Folders marked with * are optional.

component_name
├── controller
|   └── class.php
├── translations*
|   └── en.php
|   └── cs.php
├── view
|   └── view.twig
├── assets*
|   ├── css
|   |   └── main.css
|   ├── img
|   |   └── img.jpg
|   └── js
|       └── main.js
└── desc.php
 
## File desc.php
Here comes your component description.
```php
return [
    // Controller class
    'class' => 'Class',
    // Class namespace
    'namespace' => 'Namespace',
    'ver' => 1.1,
    'author' => 'Firstname Lastname',
    'link' => 'http://www.webiik.org',
    'description' => 'Nice component.',
    'tags' => 'nice, component, webiik',
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