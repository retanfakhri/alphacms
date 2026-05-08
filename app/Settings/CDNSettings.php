<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CDNSettings extends Settings
{
    public bool $cdn_enabled;

    public string $cdn_provider;

    public string $cdn_token;

    public string $cdn_zone_id;

    public string $cdn_plan;

    public bool $cdn_auto_purge;

    // Failover provider (optional secondary CDN)
    public string $cdn_fallback_provider;

    public string $cdn_fallback_token;

    public string $cdn_fallback_zone_id;

    public string $cdn_fallback_plan;

    // Alert routing
    public string $cdn_alert_email;

    public string $cdn_alert_slack_webhook;

    public static function group(): string
    {
        return 'cdn';
    }
}
