<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLocation extends Model
{
    use HasFactory, MassPrunable;

    protected $fillable = [
        'user_id',
        'country',
        'country_code',
        'city',
        'region',
        'timezone',
        'latitude',
        'longitude',
        'ip_address',
        'isp',
        'device',
        'browser',
        'platform',
    ];

    /**
     * Pruning old location data to maintain performance on high-traffic sites
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(180));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class, 'location_id');
    }
}
