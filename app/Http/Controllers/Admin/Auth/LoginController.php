<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Auth\AdminLoginRateLimiter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Pre-computed bcrypt hash used as a constant-time dummy when the email is
     * unknown. Prevents an attacker from distinguishing "no such user" from
     * "wrong password" by timing the response.
     */
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function __construct(private readonly AdminLoginRateLimiter $limiter) {}

    public function showLoginForm(Request $request): Response
    {
        return Inertia::render('admin/auth/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($this->limiter->tooManyAttempts($request)) {
            Event::dispatch(new Lockout($request));
            $this->throwThrottled($request);
        }

        $email = Str::lower(trim((string) $request->input('email')));
        $user  = User::where('email', $email)->first();

        // Always run Hash::check so the response time does not leak whether
        // the email exists.
        $passwordOk = Hash::check(
            (string) $request->input('password'),
            $user?->password ?? self::DUMMY_HASH,
        );

        // Single failure path. From the attacker's point of view, these three
        // outcomes are indistinguishable:
        //   - email does not exist
        //   - email exists but password is wrong
        //   - email + password are valid but the user lacks access_admin_panel
        if (! $user || ! $passwordOk || ! $user->canAccessAdminPanel()) {
            $this->limiter->increment($request);
            Event::dispatch(new Failed('web', $user, ['email' => $email]));
            $this->throwInvalidCredentials();
        }

        $this->limiter->clear($request);

        // Session fixation prevention: regenerate ID and CSRF token before login.
        $request->session()->regenerate();

        // Two-factor handoff: stash the challenged user id in the (admin)
        // session and redirect to the admin-owned 2FA challenge under
        // /admin/auth/. The user is NOT logged in yet at this point.
        //
        // Remember-me is disabled for admin sessions by design — no
        // `login.remember` key is set, and the admin login form does not
        // collect the remember field.
        if ($user->two_factor_secret) {
            $request->session()->put(['login.id' => $user->getKey()]);

            return redirect()->route('admin.auth.two-factor.login');
        }

        // Remember-me hard-disabled for admin login.
        Auth::guard('web')->login($user, false);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.auth.login');
    }

    private function throwInvalidCredentials(): never
    {
        throw ValidationException::withMessages([
            'email' => [__('auth.failed')],
        ]);
    }

    private function throwThrottled(Request $request): never
    {
        $seconds = $this->limiter->availableIn($request);

        throw ValidationException::withMessages([
            'email' => [
                __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ],
        ]);
    }
}
