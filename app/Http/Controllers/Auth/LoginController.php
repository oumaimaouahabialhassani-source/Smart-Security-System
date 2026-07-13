<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Display the login page.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login attempt.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = strtolower($request->input('email')).'|'.$request->ip();

        // Attempt ceiling and lock duration come from the Security settings.
        $maxAttempts = (int) \App\Models\Setting::get('security.max_login_attempts', 5);
        $lockSeconds = (int) \App\Models\Setting::get('security.lock_duration_minutes', 1) * 60;

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($throttleKey),
                ]),
            ]);
        }

        // Note: accounts pending their invitation email (null password)
        // fail here with the same generic message, so the form cannot
        // be used to probe which emails exist in the system.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, $lockSeconds);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Single-account mode: only the Super Admin may sign in.
        if (Auth::user()->role !== \App\Enums\UserRole::SuperAdmin) {
            Auth::logout();
            RateLimiter::hit($throttleKey, $lockSeconds);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (Auth::user()->status !== UserStatus::Active) {
            $status = Auth::user()->status;
            Auth::logout();
            RateLimiter::hit($throttleKey, $lockSeconds);

            throw ValidationException::withMessages([
                'email' => 'This account is '.strtolower($status->label()).'. Contact your administrator.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::user()->forceFill(['last_login' => now()])->save();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Welcome back, '.Auth::user()->name.'!');
    }

    /**
     * A GET on /logout performs no action (logout requires the
     * CSRF-protected POST); it just routes the visitor somewhere
     * sensible instead of a "405 Method Not Allowed" page.
     */
    public function logoutRedirect(): RedirectResponse
    {
        return redirect(Auth::check() ? route('dashboard') : route('login'));
    }

    /**
     * Log the user out and invalidate the session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'You have been signed out.');
    }
}
