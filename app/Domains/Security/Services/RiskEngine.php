<?php

declare(strict_types=1);

namespace App\Domains\Security\Services;

use App\DTOs\SecurityTrackingData;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserSession;
use Illuminate\Support\Facades\Cache;

class RiskEngine
{
    public function calculate(User $user, SecurityTrackingData $data, UserLocation $location, string $deviceHash): int
    {
        $score = 0;

        // 1. Geography Check
        $lastCountry = Cache::get("user_{$user->id}_last_country");
        if ($lastCountry && $lastCountry !== $location->country_code) {
            $score += 35; // Traveling too fast?
        }
        Cache::put("user_{$user->id}_last_country", $location->country_code, now()->addMonths(1));

        // 2. Persistent Device Check
        $isKnownDevice = UserSession::where('authenticatable_id', $user->id)
            ->where('trusted_device_hash', $deviceHash)
            ->exists();

        if (!$isKnownDevice) {
            $score += 15;
        }

        // 3. Login Anomaly Check (e.g. multiple failed attempts recently - could be integrated later)
        
        return $score;
    }
}
