<?php

return [
    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.googlemail.com'),
            'port' => env('MAIL_PORT', 465),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME', 'info.allgifted@gmail.com'),
            'password' => env('MAIL_PASSWORD', '16Matlock'),
            'timeout' => null,
            'auth_mode' => null,
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'info.allgifted@gmail.com'),
        'name' => env('MAIL_FROM_NAME', 'All Gifted Admin'),
    ],
];
