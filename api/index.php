<?php

// Suppress deprecations and notices from bleeding-edge PHP 8.5 runtime
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

// Set serverless environment flags
$_ENV['VERCEL'] = '1';
$_SERVER['VERCEL'] = '1';
putenv('VERCEL=1');
putenv('APP_STORAGE_PATH=/tmp/storage');
putenv('LOG_CHANNEL=stderr');
putenv('LARAVEL_LOG_PATH=/tmp/storage/logs/laravel.log');

// Create required writable directories in /tmp for Vercel serverless environment
$dirs = [
    '/tmp/storage',
    '/tmp/storage/logs',
    '/tmp/storage/framework',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/app',
    '/tmp/storage/app/public',
    '/tmp/views',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

@touch('/tmp/storage/logs/laravel.log');

// Forward Vercel requests to Laravel public/index.php
require __DIR__ . '/../public/index.php';
