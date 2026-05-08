<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class SecurityTrackingData
{
    public function __construct(
        public string $ipAddress,
        public string $userAgent,
        public ?string $sessionId = null,
        public ?string $fingerprint = null,
        public ?string $deviceType = null,
        public ?string $deviceName = null,
        public ?int $riskScore = 0,
        public array $locationData = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            ipAddress: $request->ip(),
            userAgent: $request->userAgent() ?? 'unknown',
            sessionId: $request->session()?->getId(),
        );
    }
}
