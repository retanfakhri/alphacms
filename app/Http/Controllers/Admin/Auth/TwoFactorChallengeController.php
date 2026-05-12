<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Fortify;

/**
 * Admin-owned two-factor authentication challenge.
 *
 * Mirrors Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController
 * verbatim for the verification logic (recovery code consumption, TOTP
 * verification, valid-code event emission), but lives under /admin/auth/
 * so the entire admin authentication boundary is self-contained.
 *
 * Differences from Fortify's controller:
 *   1. Routes/redirects target admin endpoints (admin.auth.login,
 *      admin.dashboard) instead of root Fortify routes.
 *   2. Verifies the challenged user still has access_admin_panel before
 *      logging them in — guards against an admin's permission being
 *      revoked between password step and 2FA step.
 *   3. remember-me is hard-disabled for admin (passes false to login()).
 *      The admin login form no longer collects the remember field.
 */
class TwoFactorChallengeController extends Controller
{
    /**
     * Show the admin 2FA challenge view, or bounce back to login if no
     * pending challenge exists in the session.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $this->hasChallengedUser($request)) {
            return redirect()->route('admin.auth.login');
        }

        return Inertia::render('admin/auth/two-factor-challenge');
    }

    /**
     * Verify the supplied TOTP code or recovery code and complete the
     * admin login on success.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code'          => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = $this->challengedUser($request);

        if (! $user || ! $user->canAccessAdminPanel()) {
            $request->session()->forget(['login.id', 'login.remember']);
            throw ValidationException::withMessages([
                'code' => [__('auth.failed')],
            ]);
        }

        // Recovery code path (mirrors Fortify's TwoFactorLoginRequest::validRecoveryCode).
        if ($code = $this->validRecoveryCode($user, (string) $request->input('recovery_code'))) {
            $user->replaceRecoveryCode($code);
            $request->session()->forget('login.id');
        }
        // TOTP path (mirrors Fortify's TwoFactorLoginRequest::hasValidCode).
        elseif (! $this->hasValidCode($user, (string) $request->input('code'))) {
            event(new TwoFactorAuthenticationFailed($user));
            throw ValidationException::withMessages([
                'code' => [__('auth.failed')],
            ]);
        } else {
            $request->session()->forget('login.id');
        }

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        // remember-me is hard-disabled for admin sessions by design.
        Auth::guard('web')->login($user, false);

        $request->session()->forget('login.remember');
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Detect a pending challenge: session has login.id pointing to a real user.
     */
    private function hasChallengedUser(Request $request): bool
    {
        $id = $request->session()->get('login.id');

        return $id !== null && User::query()->whereKey($id)->exists();
    }

    /**
     * Resolve the user whose 2FA challenge is pending.
     */
    private function challengedUser(Request $request): ?User
    {
        $id = $request->session()->get('login.id');

        return $id ? User::find($id) : null;
    }

    /**
     * Mirror of Fortify\Http\Requests\TwoFactorLoginRequest::hasValidCode.
     */
    private function hasValidCode(User $user, string $code): bool
    {
        if ($code === '') {
            return false;
        }

        return app(TwoFactorAuthenticationProvider::class)->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            $code,
        );
    }

    /**
     * Mirror of Fortify\Http\Requests\TwoFactorLoginRequest::validRecoveryCode.
     *
     * Constant-time comparison via hash_equals, then returns the matched
     * stored code so the caller can rotate it (replaceRecoveryCode).
     */
    private function validRecoveryCode(User $user, string $candidate): ?string
    {
        if ($candidate === '') {
            return null;
        }

        foreach ((array) $user->recoveryCodes() as $stored) {
            if (hash_equals((string) $stored, $candidate)) {
                return (string) $stored;
            }
        }

        return null;
    }
}
