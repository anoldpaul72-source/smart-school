<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$storagePath = (PHP_OS_FAMILY !== 'Windows' || isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || env('VERCEL') || env('APP_STORAGE_PATH') || !is_writable(base_path('storage')))
    ? '/tmp/storage'
    : base_path('storage');

$app->useStoragePath($storagePath);

return $app;
