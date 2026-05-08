<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSecurityProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'risk_score',
        'trusted_devices_count',
        'last_suspicious_activity_at',
        'login_anomalies_count',
        'security_score',
        'mfa_enabled',
        'last_password_change_at',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'integer',
            'trusted_devices_count' => 'integer',
            'last_suspicious_activity_at' => 'datetime',
            'login_anomalies_count' => 'integer',
            'security_score' => 'integer',
            'mfa_enabled' => 'boolean',
            'last_password_change_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
