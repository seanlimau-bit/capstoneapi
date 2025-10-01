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
     'domain'    => env('SESSION_DOMAIN', null),            // null is best for localhost
     'secure'    => env('SESSION_SECURE_COOKIE', false),    // false for http locally
     'same_site' => env('SESSION_SAME_SITE', 'lax'),        // 'lax' works on http
     'http_only' => env('SESSION_HTTP_ONLY', true),
     'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
];
