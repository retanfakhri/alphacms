<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

enum QuarantineReason: string
{
    case SuspiciousIP = 'suspicious_ip';
    case ImpossibleTravel = 'impossible_travel';
    case HighRiskScore = 'high_risk_score';
    case UnusualDevice = 'unusual_device';
    case Manual = 'manual';
}
