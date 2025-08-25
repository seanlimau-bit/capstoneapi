<?php

return [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'connection' => null,
    'table' => 'sessions',
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => 'laravel_session',
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', 'localhost'), // fallback to localhost
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => env('SESSION_HTTP_ONLY', true),
    'same_site' => 'none',
    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
];
