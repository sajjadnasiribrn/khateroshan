<?php

use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Throwable $throwable, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if ($throwable instanceof ValidationException
                || $throwable instanceof AuthenticationException
                || $throwable instanceof HttpResponseException
                || $throwable instanceof HttpExceptionInterface) {
                return null;
            }

            return response()->json([
                'message' => 'An unexpected error occurred.',
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
