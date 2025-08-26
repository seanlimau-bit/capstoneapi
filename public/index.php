<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// If the application is in maintenance mode, load the maintenance file
if (file_exists(__DIR__.'/../storage/framework/maintenance.php')) {
    require __DIR__.'/../storage/framework/maintenance.php';
}

// Register Composer autoloader
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the incoming request
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
