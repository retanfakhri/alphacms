<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Enums\PreferredTopic;
use App\Enums\NotificationType;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Features;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $view = str_starts_with($request->route()->getName(), 'admin.') ? 'admin/profile' : 'settings/profile';

        return Inertia::render($view, [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'user' => $request->user()->only([
                'name', 'username', 'email', 'bio', 'phone', 'phone_code', 'has_whatsapp',
                'facebook_url', 'instagram_url', 'tiktok_url', 
                'linkedin_url', 'x_url', 'youtube_url', 'website_url',
                'avatar_url', 'cover_url', 'is_private', 'comments_blocked',
                'preferred_locale', 'preferred_topics', 'notification_preferences', 'reading_preferences'
            ]),
            'topics' => collect(PreferredTopic::cases())->map(fn($t) => ['name' => $t->name, 'value' => $t->value])->toArray(),
            'notificationTypes' => collect(NotificationType::cases())->map(fn($t) => ['name' => $t->name, 'value' => $t->value])->toArray(),
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'twoFactorEnabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'sessions' => $request->user()->activeSessions()->with('location')->latest('last_activity_at')->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated successfully.')]);

        return back();
    }

    /**
     * Update the user's profile images.
     */
    public function updateImages(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['nullable', 'image', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:5120'],
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatars');
            $user->addMedia($request->file('avatar'))->toMediaCollection('avatars');
        }

        if ($request->hasFile('cover')) {
            $user->clearMediaCollection('covers');
            $user->addMedia($request->file('cover'))->toMediaCollection('covers');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Images updated successfully.')]);

        return back();
    }

    /**
     * Logout from a specific session.
     */
    public function logoutSession(Request $request, string $sessionId): RedirectResponse
    {
        // 1. Invalidate in Laravel's sessions table
        \Illuminate\Support\Facades\DB::table('sessions')
            ->where('id', $sessionId)
            ->delete();

        // 2. Update our custom tracking table
        $request->user()->allSessions()
            ->where('session_id', $sessionId)
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
                'logout_reason' => \App\Enums\LogoutReason::SecurityRevocation,
            ]);

        Inertia::flash('toast', [
            'type' => 'warning',
            'message' => __('Session revoked successfully.')
        ]);

        return back();
    }

    /**
     * Logout from all other sessions.
     */
    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($request->password);

        // Also update our custom tracking table
        $request->user()->allSessions()
            ->where('session_id', '!=', session()->getId())
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
                'logout_reason' => \App\Enums\LogoutReason::SecurityRevocation,
            ]);

        Inertia::flash('toast', [
            'type' => 'success', 
            'message' => __('Logged out from all other devices successfully.')
        ]);

        return back();
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
