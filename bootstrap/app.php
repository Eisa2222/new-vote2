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
        // Server-side logging is automatic — Laravel writes every
        // unhandled exception to storage/logs/laravel.log via the
        // configured channel (LOG_CHANNEL=daily in production). The
        // user-facing surface is the matching resources/views/errors/
        // template; it shows ONLY the HTTP code + a friendly localised
        // message. Stack traces never reach the browser when
        // APP_DEBUG=false (set this in production).
        //
        // To inspect today's errors on the server:
        //   tail -n 200 storage/logs/laravel.log
        // (or storage/logs/laravel-YYYY-MM-DD.log under the daily
        // channel.)
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

            // Voter flow — any 419 here would otherwise dump the voter
            // back onto the same ballot with stale form values via
            // withInput(), making it look like the "submit" button
            // refreshes the page without doing anything. Send them to
            // the entry page so the start() flow re-issues a clean
            // session + CSRF token.
            if ($request->is('vote/club/*')) {
                $token = explode('/', $request->path())[2] ?? null;
                return $token
                    ? redirect()->route('voting.club.show', $token)
                        ->with('warning', __('Your session has expired. Please try again.'))
                    : redirect('/')
                        ->with('warning', __('Your session has expired. Please try again.'));
            }

            $redirectTo = $request->is('login') || $request->is('*/login')
                ? route('login')
                : (url()->previous() ?: '/');

            // Security H-N1 — do NOT flash submitted form data on a 419.
            // The 419 typically happens because the session cookie was
            // recycled (kiosk hand-off, idle timeout). Flashing the
            // *previous* user's input into the new session leaks PII
            // (national_id, mobile, email, etc.) to whoever picks up
            // the device next. Just send a friendly message and let
            // them retype.
            return redirect($redirectTo)
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
