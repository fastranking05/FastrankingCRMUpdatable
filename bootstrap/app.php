<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'any.module.read' => \App\Http\Middleware\AnyModuleReadMiddleware::class,
            'chat.security' => \App\Http\Middleware\ChatSecurityMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Keep exception logging in one place so controller/service failures
         * that are not handled locally still contain enough context for a
         * developer to find the failing operation quickly.
         */
        $exceptions->report(function (Throwable $exception, Request $request): void {
            Log::error('Unhandled application exception', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'route' => $request->route()?->getName(),
                'controller' => $request->route()?->getControllerClass(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_id' => $request->user()?->id,
                'request_id' => $request->header('X-Request-ID'),
                'trace' => $exception->getTraceAsString(),
            ]);
        });

        /*
         * API exceptions should always have the existing JSON error shape.
         * This only handles exceptions that escaped a controller/service;
         * validation and existing custom responses remain unchanged.
         */
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $headers = $exception instanceof HttpExceptionInterface
                ? $exception->getHeaders()
                : [];

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request',
                'error' => config('app.debug') ? $exception->getMessage() : null,
            ], $status, $headers);
        });
    })
    ->create();
