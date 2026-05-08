<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeviceType;
use App\Enums\LoginType;
use App\Enums\LogoutReason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'authenticatable_id',
        'authenticatable_type',
        'location_id',
        'guard',
        'session_id',
        'login_type',
        'device_type',
        'device_name',
        'user_agent',
        'ip_address',
        'session_fingerprint',
        'trusted_device_hash',
        'is_trusted_device',
        'risk_score',
        'logged_in_at',
        'logged_out_at',
        'logout_reason',
        'last_activity_at',
        'is_active',
        'is_quarantined',
        'quarantined_at',
        'quarantine_reason',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
            'logged_out_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'is_active' => 'boolean',
            'is_quarantined' => 'boolean',
            'quarantined_at' => 'datetime',
            'is_trusted_device' => 'boolean',
            'risk_score' => 'integer',
            'login_type' => LoginType::class,
            'device_type' => DeviceType::class,
            'logout_reason' => LogoutReason::class,
        ];
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(UserLocation::class, 'location_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeQuarantined(Builder $query): Builder
    {
        return $query->where('is_quarantined', true);
    }
}
