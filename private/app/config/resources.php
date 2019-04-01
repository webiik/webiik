<?php
return [
    'middleware' => [],
    'models' => [],
    'services' => [
        'Error' => [
            'silent' => true,
        ],
        'Database' => [
            'user' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'webiik',
                'user' => 'root',
                'password' => 'root',
            ],
        ],
        'Log' => [
            'MailLogger' => [
                'from' => 'info@appdomain.tld',
                'error' => [
                    'to' => 'your@email.tld',
                    'subject' => 'Webiik Error Log',
                    'delay' => 60 * 24 * 7,
                ],
            ],
        ],
        'Mail' => [
            'PHPMailer' => [
                'isSMTP' => false,
                'SMTP' => [
                    'timeout' => 1,
                    'host' => '',
                    'port' => 25,
                    'SMTPSecure' => 'tls',
                    'SMTPAuth' => true,
                    'SMTPAuthUserName' => '',
                    'SMTPAuthPswd' => '',
                    'SMTPOptions' => [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ],
                    ],
                ],
            ],
        ],
        'Router' => [
            'defaultLangInURI' => false,
        ],
        'Cookie' => [
            'domain' => '',
            'uri' => '',
            'secure' => true,
            'httpOnly' => true,
        ],
        'Session' => [
            'name' => 'wSESSION',
            'dir' => __DIR__ . '/../../tmp/session',
            // Delete sessions older than 1440s (24m) with probability 1/100
            'gcProbability' => 1,
            'gcDivisor' => 100,
            'gcLifetime' => 1440,
        ],
        'Csrf' => [
            'name' => 'csrf-token',
            'max' => 5,
        ],
    ],
];