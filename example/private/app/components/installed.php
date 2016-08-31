<?php
return [
    'installed' => [
        'namespace' => [
            'Poll' => [
                'status' => 'active',
                'author' => '',
                'link' => '',
                'license' => '',
                'version' => '',
            ],
        ],
    ],
    'local' => [
        __DIR__ . '/src/namespace/component', // desc.php
        __DIR__ . '/src/namespace/component',
    ],
    'remote' => [
        'https://github.com/Jiri-Mihal/RocketRouter/archive/master.zip',
    ],
    'github' => [
        'https://github.com/Jiri-Mihal/RocketRouter', // /archive/master.zip /blob/master/desc.php
    ],
];