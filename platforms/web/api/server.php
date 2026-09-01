<?php

$storagePath = '/tmp/appgastos-storage';
$cachePath = $storagePath.'/bootstrap/cache';

if (isset($_GET['__route'])) {
    $_SERVER['REQUEST_URI'] = '/'.ltrim((string) $_GET['__route'], '/');
    unset($_GET['__route']);
}

putenv('LARAVEL_STORAGE_PATH='.$storagePath);
$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

foreach ([
    'APP_SERVICES_CACHE' => $cachePath.'/services.php',
    'APP_PACKAGES_CACHE' => $cachePath.'/packages.php',
    'APP_CONFIG_CACHE' => $cachePath.'/config.php',
    'APP_ROUTES_CACHE' => $cachePath.'/routes.php',
    'APP_EVENTS_CACHE' => $cachePath.'/events.php',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

foreach ([
    $storagePath.'/app',
    $cachePath,
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/testing',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
}

require __DIR__.'/../public/index.php';
