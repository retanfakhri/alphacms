<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Security\Services\AuthTrackingService;
use App\DTOs\SecurityTrackingData;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionSecurityMiddleware
{
    public function __construct(
        protected AuthTrackingService $trackingService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // 1. Kick out inactive, banned, or suspended users immediately
            if (!$user->isActive()) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();
                return redirect()->route('login')->with('error', 'Your account is inactive or banned.');
            }

            // 2. Session Hijacking Protection (Fixed: Removed IP dependency)
            $trackingData = SecurityTrackingData::fromRequest($request);
            $currentFingerprint = $this->trackingService->generateFingerprint($trackingData);
            $session = $user->activeSessions()->where('session_id', session()->getId())->first();

            if ($session) {
                if ($session->session_fingerprint !== $currentFingerprint) {
                    Auth::logout();
                    session()->invalidate();
                    session()->regenerateToken();
                    return redirect()->route('login')->with('error', 'Security alert: Device mismatch.');
                }

                // 3. Update Activity
                $session->update(['last_activity_at' => now()]);
                $user->update(['last_seen_at' => now()]);
            }
        }

        return $next($request);
    }
}
