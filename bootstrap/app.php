<?php

use App\Bootstrap\LoadConfiguration as AppLoadConfiguration;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

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

$app = Application::configure(basePath: dirname(__DIR__))
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
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() !== 403 || $request->expectsJson()) {
                return $response;
            }

            $user = $request->user();

            return Inertia::render('Errors/Forbidden', [
                'message' => 'You do not have access to this page.',
                'missingRole' => $user !== null && $user->role_id === null,
            ])
                ->toResponse($request)
                ->setStatusCode(403);
        });
    })->create();

// Laravel merges vendor framework configs at boot; skip vendor database.php
// (PDO::MYSQL_ATTR_SSL_CA deprecation on PHP 8.5+) via App\Bootstrap\LoadConfiguration.
$app->singleton(LoadConfiguration::class, AppLoadConfiguration::class);

return $app;
