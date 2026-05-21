<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $branches = Branch::withoutGlobalScopes()->where('is_active', true)->orderBy('name')->get();

        return view('pages.auth.signin', compact('branches'));
    }

    /**
     * Handle login submission.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'     => ['required', 'string', 'email'],
            'password'  => ['required', 'string'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        // Rate limiting — 5 attempts per minute per IP+email
        $this->ensureIsNotRateLimited($request);

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $user = Auth::user();

        // Branch check — verify user belongs to the selected branch
        if ($request->filled('branch_id')) {
            if ($user->branch_id && (string) $user->branch_id !== (string) $request->branch_id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'branch_id' => 'You do not have access to the selected branch.',
                ])->withInput($request->except('password'));
            }
        }

        // Check if user account is active
        if (! $user->is_active) {
            Auth::logout();
            return back()->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        if ($user->company_id && $user->company && $user->company->company_status !== 'active') {
            Auth::logout();
            return back()->with('error', 'Your company account is inactive. Please contact the main administrator.');
        }

        $request->session()->regenerate();

        // Store active branch in session
        $activeBranch = $request->filled('branch_id') ? $request->branch_id : $user->branch_id;
        if ($activeBranch) {
            session(['active_branch_id' => $activeBranch]);
        }

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $defaultRoute = $user->dashboardRoute();

        return redirect()->intended(route($defaultRoute))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been signed out successfully.');
    }


    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')) . '|' . $request->ip());
    }
}
