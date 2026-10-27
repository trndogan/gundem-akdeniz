<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance mode check
if (file_exists($maintenance = __DIR__.'/../Akdenizgundemlaravel/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader
require __DIR__.'/../Akdenizgundemlaravel/vendor/autoload.php';

// Bootstrap
$app = require_once __DIR__.'/../Akdenizgundemlaravel/bootstrap/app.php';

// Handle request
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());