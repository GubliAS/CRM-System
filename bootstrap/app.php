<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

/*
| Laravel 11 still evaluates vendor/laravel/framework/config/database.php when
| merging base framework config. That file references PDO::MYSQL_ATTR_SSL_CA,
| which PHP 8.5 deprecates. App config already uses Pdo\Mysql::ATTR_SSL_CA;
| we cannot patch vendor while staying on Laravel 11. Exclude E_DEPRECATED so
| those known notices do not dump into HTML. Warnings/errors stay visible.
*/
if (PHP_VERSION_ID >= 80500) {
    error_reporting(E_ALL & ~E_DEPRECATED);
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
