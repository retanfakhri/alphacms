<?php

declare(strict_types=1);

namespace App\Domains\Security\Services;

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
    public function __construct(
        protected RiskEngine $riskEngine,
        protected SecurityOrchestrator $orchestrator
    ) {}

    public function trackLogin(User $user, SecurityTrackingData $data, LoginType $type = LoginType::Manual): UserSession
    {
        $agent = new Agent();
        $agent->setUserAgent($data->userAgent);

        // 1. Resolve Geo Location
        $location = $this->resolveLocation($user, $data, $agent);

        // 2. Generate Hashes
        $fingerprint = $this->generateFingerprint($data);
        $deviceHash = $this->generateDeviceHash($data);

        // 3. Determine if device was previously recognized
        $isRecognizedDevice = UserSession::where('authenticatable_id', $user->id)
            ->where('trusted_device_hash', $deviceHash)
            ->exists();

        // 4. Calculate Risk Score via Engine
        $riskScoreRaw = $this->riskEngine->calculate($user, $data, $location, $deviceHash);
        $riskScore = new \App\Domains\Security\ValueObjects\RiskScore($riskScoreRaw);

        // 5. Create or Update Session Record
        $userSession = UserSession::updateOrCreate([
            'session_id' => $data->sessionId,
        ], [
            'authenticatable_id' => $user->id,
            'authenticatable_type' => User::class,
            'location_id' => $location->id,
            'guard' => config('auth.defaults.guard') ?? 'web',
            'login_type' => $type,
            'device_type' => $this->getDeviceType($agent),
            'device_name' => $agent->device() ?: 'Unknown',
            'user_agent' => $data->userAgent,
            'ip_address' => $data->ipAddress,
            'session_fingerprint' => $fingerprint,
            'trusted_device_hash' => $deviceHash,
            'risk_score' => $riskScore->value,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'is_active' => true,
        ]);

        // 6. Security Orchestration
        if (!$isRecognizedDevice) {
            \App\Domains\Security\Events\NewDeviceDetected::dispatch($user, $userSession);
        }

        $this->orchestrator->handleLoginSecurity($user, $userSession, $riskScore);

        // 7. Update Security Profile Aggregate
        $this->updateSecurityProfile($user, $riskScore, $deviceHash);

        // 8. Update User Profile
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $data->ipAddress,
            'last_active_at' => now(),
            'last_seen_at' => now(),
        ]);

        return $userSession;
    }

    protected function updateSecurityProfile(User $user, \App\Domains\Security\ValueObjects\RiskScore $risk, string $deviceHash): void
    {
        $profile = $user->securityProfile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'risk_score' => 0,
                'security_score' => 100,
            ]
        );

        $profile->increment('login_anomalies_count', $risk->isHigh() ? 1 : 0);
        
        $trustedDevicesCount = UserSession::where('authenticatable_id', $user->id)
            ->where('is_trusted_device', true)
            ->distinct('trusted_device_hash')
            ->count();

        $profile->update([
            'risk_score' => $risk->value,
            'trusted_devices_count' => $trustedDevicesCount,
            'last_suspicious_activity_at' => $risk->isHigh() ? now() : $profile->last_suspicious_activity_at,
        ]);
    }

    protected function resolveLocation(User $user, SecurityTrackingData $data, Agent $agent): UserLocation
    {
        $ip = $data->ipAddress;
        $cacheKey = "user_{$user->id}_location_" . md5($ip);

        $cached = Cache::get($cacheKey);

        if ($cached instanceof UserLocation) {
            return $cached;
        }

        // If we get here, cache is empty or invalid (Incomplete Class)
        $geoData = $this->fetchGeoData($ip);

        $location = UserLocation::firstOrCreate([
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

        Cache::put($cacheKey, $location, now()->addDays(1));

        return $location;
    }

    protected function fetchGeoData(string $ip): mixed
    {
        try {
            return Location::get($ip);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function generateFingerprint(SecurityTrackingData $data): string
    {
        return hash('sha256', $data->userAgent . $data->sessionId . config('app.key'));
    }

    public function generateDeviceHash(SecurityTrackingData $data): string
    {
        $agent = new Agent();
        $agent->setUserAgent($data->userAgent);

        $normalizedData = [
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device' => $agent->device(),
            'is_desktop' => $agent->isDesktop(),
        ];

        return hash('sha256', json_encode($normalizedData, JSON_THROW_ON_ERROR) . config('app.key'));
    }

    protected function getDeviceType(Agent $agent): DeviceType
    {
        if ($agent->isRobot()) return DeviceType::Bot;
        if ($agent->isTablet()) return DeviceType::Tablet;
        if ($agent->isMobile()) return DeviceType::Mobile;
        if ($agent->isDesktop()) return DeviceType::Desktop;
        
        return DeviceType::Unknown;
    }
}
