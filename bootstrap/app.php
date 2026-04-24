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
        // 419 "Page expired" (CSRF token mismatch / stale session).
        // Happens when the browser holds an old session cookie — common
        // after APP_KEY rotates, cache:clear, or leaving the login tab
        // open past the session lifetime. A raw 419 error page is a
        // dead-end; bounce back to login with a friendly message so
        // Laravel issues a fresh cookie + token automatically.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Your session has expired. Please try again.'),
                ], 419);
            }

            $redirectTo = $request->is('login') || $request->is('*/login')
                ? route('login')
                : (url()->previous() ?: '/');

            return redirect($redirectTo)
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('warning', __('Your session has expired. Please try again.'));
        });

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

        // Foreign-key integrity violations on delete. MySQL (1451) and
        // SQLite (FOREIGN KEY constraint failed) both surface as a
        // QueryException. Instead of a 500 + stacktrace, bounce the
        // user back with a friendly message explaining the record is
        // in use elsewhere. Controllers can still opt into a more
        // specific error via DomainException.
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;
            $msg        = (string) $e->getMessage();

            $isFkViolation = $sqlState === '23000'
                || $driverCode === 1451
                || $driverCode === 1452
                || str_contains($msg, 'FOREIGN KEY constraint failed')
                || str_contains($msg, 'violates foreign key constraint');

            if (! $isFkViolation) {
                return null; // let the default handler render it
            }

            $friendly = __('This record cannot be deleted because it is linked to other data. Archive it or remove the linked items first.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $friendly], 409);
            }

            return back()->withErrors(['delete' => $friendly]);
        });
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('campaigns:tick')->everyMinute();
    })
    ->create();
