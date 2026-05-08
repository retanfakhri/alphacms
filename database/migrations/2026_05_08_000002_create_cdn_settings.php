<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cdn.cdn_enabled', false);
        $this->migrator->add('cdn.cdn_provider', 'cloudflare');
        $this->migrator->add('cdn.cdn_token', '');
        $this->migrator->add('cdn.cdn_zone_id', '');
        $this->migrator->add('cdn.cdn_plan', 'free');
        $this->migrator->add('cdn.cdn_auto_purge', true);
        $this->migrator->add('cdn.cdn_fallback_provider', '');
        $this->migrator->add('cdn.cdn_fallback_token', '');
        $this->migrator->add('cdn.cdn_fallback_zone_id', '');
        $this->migrator->add('cdn.cdn_fallback_plan', 'free');
        $this->migrator->add('cdn.cdn_alert_email', '');
        $this->migrator->add('cdn.cdn_alert_slack_webhook', '');
    }
};
