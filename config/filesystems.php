<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */
    'default' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Disks:
    | - public: standard Laravel public storage (/storage symlink)
    | - webroot: writes directly under public_path()
    | - assets: points to either storage/app/public/assets (default)
    |           or public/assets when ASSET_DISK=webroot
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        // Directly under /public (use sparingly; no CDN/symlink layer)
        'webroot' => [
            'driver'     => 'local',
            'root'       => public_path(),
            'url'        => env('APP_URL'),
            'visibility' => 'public',
            'throw'      => false,
        ],

        // Unified disk for your Asset Manager
        'assets' => [
            'driver'     => 'local',
            'root'       => env('ASSET_DISK', 'public') === 'webroot'
                ? public_path('assets')
                : storage_path('app/public/assets'),
            'url'        => env('ASSET_DISK', 'public') === 'webroot'
                ? env('APP_URL') . '/assets'
                : env('APP_URL') . '/storage/assets',
            'visibility' => 'public',
            'throw'      => false,
        ],

        // Example S3 (keep if you use it)
        's3' => [
            'driver'   => 's3',
            'key'      => env('AWS_ACCESS_KEY_ID'),
            'secret'   => env('AWS_SECRET_ACCESS_KEY'),
            'region'   => env('AWS_DEFAULT_REGION'),
            'bucket'   => env('AWS_BUCKET'),
            'url'      => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'    => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Adds the standard /storage link and an /assets link so that
    | /assets/* works when ASSET_DISK=public (storage-backed).
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('assets')  => storage_path('app/public/assets'),
    ],
];
