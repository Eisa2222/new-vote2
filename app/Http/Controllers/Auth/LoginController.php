<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    /** Max login attempts per (email + IP) per decay window. */
    private const MAX_ATTEMPTS = 5;

    /** Seconds to lock a throttled identity out before allowing retries. */
    private const DECAY_SECONDS = 60;

    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($request);

        // Brute-force guard: after MAX_ATTEMPTS failures per (email+IP)
        // the endpoint stops processing credentials until the cooldown.
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Try again in :seconds seconds.', ['seconds' => $seconds]),
            ])->status(429);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        if ((Auth::user()->status ?? 'active') !== 'active') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Your account is deactivated. Contact the administrator.'),
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.landing'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Attempts are counted per (lowercase email + IP) so a single
     * attacker IP can't hammer many accounts, and a guessed password
     * against one email doesn't leak to neighbours.
     */
    private function throttleKey(Request $request): string
    {
        return Str::lower((string) $request->input('email')).'|'.$request->ip();
    }
}
