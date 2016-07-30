<?php
return [
    'home' => [
        'uri' => '/',
        'controller' => 'Webiik\Controller:launch',
        'middlewares' => [
            'hello' => ['world'],
        ],
    ],
];