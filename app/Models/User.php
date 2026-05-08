<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, InteractsWithMedia, HasRoles, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone',
        'phone_code',
        'phone_verified_at',
        'local_password_enabled',
        'password_changed_at',
        'last_login_at',
        'last_login_ip',
        'last_active_at',
        'last_seen_at',
        'last_article_read_at',
        'reading_preferences',
        'bio',
        'has_whatsapp',
        'comments_blocked',
        'is_private',
        'is_active',
        'account_status',
        'banned_at',
        'ban_reason',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'linkedin_url',
        'telegram_url',
        'x_url',
        'youtube_url',
        'website_url',
        'provider',
        'provider_id',
        'preferred_locale',
        'preferred_topics',
        'notification_preferences',
        'onboarding_completed_at',
        'user_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $appends = [
        'avatar_url',
        'cover_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_active_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_article_read_at' => 'datetime',
            'reading_preferences' => 'array',
            'banned_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'local_password_enabled' => 'boolean',
            'has_whatsapp' => 'boolean',
            'comments_blocked' => 'boolean',
            'is_private' => 'boolean',
            'is_active' => 'boolean',
            'account_status' => AccountStatus::class,
            'user_type' => 'array',
            'preferred_topics' => 'array',
            'notification_preferences' => 'array',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'account_status', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('account_status', AccountStatus::Active);
    }

    /**
     * Relationships
     */

    public function locations(): HasMany
    {
        return $this->hasMany(UserLocation::class);
    }

    public function activeSessions(): MorphMany
    {
        return $this->morphMany(UserSession::class, 'authenticatable')->where('is_active', true);
    }

    public function allSessions(): MorphMany
    {
        return $this->morphMany(UserSession::class, 'authenticatable');
    }

    public function securityProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserSecurityProfile::class);
    }

    /**
     * Media Collections
     */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile()
            ->useFallbackUrl(asset('images/defaults/avatar.webp'));

        $this->addMediaCollection('covers')
            ->singleFile()
            ->useFallbackUrl(asset('images/defaults/cover.webp'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 150, 150)
            ->format('webp');

        $this->addMediaConversion('optimized')
            ->fit(Fit::Max, 1200, 1200)
            ->format('webp')
            ->withResponsiveImages();
    }

    /**
     * Accessors
     */

    public function getAvatarUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('avatars', 'thumb') ?: $this->getFirstMediaUrl('avatars') ?: asset('images/defaults/avatar.webp');
    }

    public function getCoverUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('covers', 'optimized') ?: $this->getFirstMediaUrl('covers') ?: '/assets/images/menu-heade.jpg';
    }

    /**
     * Helper Methods
     */

    public function isActive(): bool
    {
        return $this->is_active && $this->account_status === AccountStatus::Active;
    }

    public function isBanned(): bool
    {
        return $this->account_status === AccountStatus::Banned || !is_null($this->banned_at);
    }

    public function canAccessAdminPanel(): bool
    {
        if (!is_array($this->user_type)) {
            return ($this->user_type === 'admin' || $this->user_type === 'writer') && $this->isActive();
        }

        return (in_array('admin', $this->user_type) || in_array('writer', $this->user_type)) && $this->isActive();
    }
}
