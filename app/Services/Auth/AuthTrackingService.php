<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\SecurityTrackingData;
use App\Enums\DeviceType;
use App\Enums\LoginType;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserSession;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class AuthTrackingService
{
    public function trackLogin(User $user, SecurityTrackingData $data, LoginType $type = LoginType::Manual): UserSession
    {
        $agent = new Agent();
        $agent->setUserAgent($data->userAgent);

        // 1. Resolve Geo Location
        $location = $this->resolveLocation($user, $data, $agent);

        // 2. Calculate Risk Score & Device Hash
        $riskScore = $this->calculateRiskScore($user, $data, $location);

        // 3. Create Session Record
        $userSession = UserSession::create([
            'authenticatable_id' => $user->id,
            'authenticatable_type' => User::class,
            'location_id' => $location->id,
            'guard' => config('auth.defaults.guard'),
            'session_id' => $data->sessionId,
            'login_type' => $type,
            'device_type' => $this->getDeviceType($agent),
            'device_name' => $agent->device(),
            'user_agent' => $data->userAgent,
            'ip_address' => $data->ipAddress,
            'session_fingerprint' => $this->generateFingerprint($data),
            'risk_score' => $riskScore,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'is_active' => true,
        ]);

        // 4. Advanced Risk Mitigation
        if ($riskScore >= 50) {
            $this->triggerSecurityChallenge($user, $userSession);
        }

        // 5. Update User Profile
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $data->ipAddress,
            'last_active_at' => now(),
            'last_seen_at' => now(),
        ]);

        return $userSession;
    }

    protected function resolveLocation(User $user, SecurityTrackingData $data, Agent $agent): UserLocation
    {
        $ip = $data->ipAddress;
        $cacheKey = "user_{$user->id}_location_" . md5($ip);

        return Cache::remember($cacheKey, now()->addDays(1), function () use ($user, $data, $agent, $ip) {
            $geoData = $this->fetchGeoData($ip);

            return UserLocation::firstOrCreate([
                'user_id' => $user->id,
                'ip_address' => $ip,
            ], [
                'country' => $geoData->countryName ?? 'Unknown',
                'country_code' => $geoData->countryCode ?? null,
                'city' => $geoData->cityName ?? null,
                'region' => $geoData->regionName ?? null,
                'timezone' => $geoData->timezone ?? null,
                'latitude' => $geoData->latitude ?? null,
                'longitude' => $geoData->longitude ?? null,
                'isp' => $geoData->isp ?? null,
                'device' => $agent->device() ?? 'unknown',
                'browser' => $agent->browser() ?? 'unknown',
                'platform' => $agent->platform() ?? 'unknown',
            ]);
        });
    }

    protected function fetchGeoData(string $ip): mixed
    {
        try {
            return Location::get($ip);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function calculateRiskScore(User $user, SecurityTrackingData $data, UserLocation $location): int
    {
        $score = 0;

        $lastCountry = Cache::get("user_{$user->id}_last_country");
        if ($lastCountry && $lastCountry !== $location->country_code) {
            $score += 35;
        }
        Cache::put("user_{$user->id}_last_country", $location->country_code, now()->addMonths(1));

        // Check if device is known
        $isKnownDevice = UserSession::where('authenticatable_id', $user->id)
            ->where('session_fingerprint', $this->generateFingerprint($data))
            ->exists();

        if (!$isKnownDevice) {
            $score += 15;
        }

        return $score;
    }

    protected function triggerSecurityChallenge(User $user, UserSession $session): void
    {
        // Integration point for 2FA force or email notifications
    }

    protected function getDeviceType(Agent $agent): DeviceType
    {
        if ($agent->isRobot()) return DeviceType::Bot;
        if ($agent->isTablet()) return DeviceType::Tablet;
        if ($agent->isMobile()) return DeviceType::Mobile;
        if ($agent->isDesktop()) return DeviceType::Desktop;
        
        return DeviceType::Unknown;
    }

    public function generateFingerprint(SecurityTrackingData $data): string
    {
        // Session-level: changes when session expires
        return hash('sha256', $data->userAgent . $data->sessionId . config('app.key'));
    }

    protected function generateDeviceHash(SecurityTrackingData $data): string
    {
        // Device-level: persistent across sessions for same browser/device
        return hash('sha256', $data->userAgent . config('app.key'));
    }
}
