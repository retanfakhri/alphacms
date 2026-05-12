<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, ?string $token = null): Response
    {
        return Inertia::render('admin/auth/reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Perform a password reset against the admin broker.
     *
     * The `admins` broker is backed by AdminEligibleUserProvider — tokens
     * issued for, or used against, users without `access_admin_panel`
     * resolve to INVALID_USER. A stolen frontend reset token therefore
     * cannot be redeemed at /admin/auth/reset-password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('admin.auth.login')->with('status', __($status));
        }

        return back()->withErrors(['email' => [__($status)]]);
    }
}
