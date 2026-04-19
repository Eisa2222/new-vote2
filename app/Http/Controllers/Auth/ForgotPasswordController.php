<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Password-recovery flow, intentionally tiny:
 *   GET  /password/forgot          — request form
 *   POST /password/forgot          — send reset link (Laravel's broker)
 *   GET  /password/reset/{token}   — reset form
 *   POST /password/reset           — validate token + new password
 *
 * Rate-limited by email+IP on the send endpoint: one request every 60s
 * keeps the mailbox from being used as a SPAM relay.
 */
final class ForgotPasswordController extends Controller
{
    private const SEND_DECAY = 60;

    public function show(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $key = 'pwd-forgot|'.Str::lower((string) $request->input('email')).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => __('Please wait :seconds seconds before requesting another email.', ['seconds' => $seconds]),
            ]);
        }
        RateLimiter::hit($key, self::SEND_DECAY);

        // The broker itself always responds with a neutral status —
        // we never leak whether the email exists (prevents user enumeration).
        Password::sendResetLink($request->only('email'));

        return back()->with('success', __('If that email exists in our system, a password reset link has been sent.'));
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(10)->mixedCase()->letters()->numbers()->symbols()],
        ]);

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => \Illuminate\Support\Facades\Hash::make($password),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return redirect()->route('login')
            ->with('success', __('Your password has been reset — you can sign in now.'));
    }
}
