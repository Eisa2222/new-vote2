<?php

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
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Domain rule violations (e.g. "campaign already approved",
        // "votes exist") are *expected* failures, not bugs. Return them
        // as 422 with a clean JSON body for API clients; web requests
        // continue to flow through the controller's catch + flash flow.
        $exceptions->render(function (\DomainException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }
        });
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('campaigns:tick')->everyMinute();
    })
    ->create();
