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
