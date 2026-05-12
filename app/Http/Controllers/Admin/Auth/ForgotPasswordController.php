<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(Request $request): Response
    {
        return Inertia::render('admin/auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Send a reset link for the admin reset flow.
     *
     * Uses the `admins` password broker, which is backed by
     * AdminEligibleUserProvider — emails belonging to users without
     * `access_admin_panel` resolve to INVALID_USER and silently send
     * nothing.
     *
     * Regardless of the broker outcome, we always flash the same status
     * message. This prevents enumeration: an attacker cannot distinguish
     * between "no such email", "email exists but is frontend-only", and
     * "email exists and is admin-eligible".
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = Str::lower(trim((string) $request->input('email')));

        // Fire-and-forget. We deliberately discard the broker result so
        // that the response shape does not vary by outcome.
        Password::broker('admins')->sendResetLink(['email' => $email]);

        return back()->with('status', __('passwords.sent'));
    }
}
