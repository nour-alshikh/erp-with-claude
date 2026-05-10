<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi('120,1');
        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        });
        $exceptions->render(function (\App\Exceptions\UnbalancedJournalException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (\App\Exceptions\InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e) {
            return response()->json(['message' => 'Forbidden.'], 403);
        });
    })->create();
