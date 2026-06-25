<?php

use App\Http\Middleware\CheckActiveUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         * Spatie Permission middleware aliases.
         * These are registered automatically if you use Laravel 10 Kernel style.
         * In Laravel 11, register them manually here.
         */
        $middleware->web(append: [
            CheckActiveUser::class,
        ]);

        $middleware->alias([
            // 'role'              => \Spatie\Permission\Middleware\RoleMiddleware::class,
            // 'permission'        => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            // 'role_or_permission'=> \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            // 'check.active'      => CheckActiveUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle Spatie's UnauthorizedException with a clean 403 page
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have the required permissions.',
                ], 403);
            }

            return response()->view('errors.403', [
                'message' => 'You do not have permission to access this page.',
            ], 403);
        });
    })->create();
