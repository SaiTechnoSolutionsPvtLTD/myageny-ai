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

        // Handle generic server exceptions with our premium exception error page
        $exceptions->render(function (\Throwable $e, $request) {
            // Bypass standard validation/unauthenticated/unauthorized exceptions
            if ($e instanceof \Illuminate\Validation\ValidationException ||
                $e instanceof \Illuminate\Auth\AuthenticationException ||
                $e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return null;
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $statusCode = $e->getStatusCode();
                if ($statusCode < 500) {
                    return null;
                }
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'An unexpected error occurred.',
                    'exception' => get_class($e),
                ], 500);
            }

            return response()->view('errors.custom_exception', [
                'exception' => $e,
                'message' => $e->getMessage() ?: 'An unexpected server error occurred.',
                'title' => class_basename($e),
            ], 500);
        });
    })->create();
