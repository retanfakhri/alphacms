<?php

declare(strict_types=1);

namespace App\Services\CDN;

use App\Contracts\CDNInterface;
use App\Settings\CDNSettings;

/**
 * CDN Manager — resolves the active CDN provider based on settings.
 *
 * This implementation supports primary-secondary failover and lazy
 * instantiation of providers.
 */
class CDNManager
{
    private ?CDNInterface $cached = null;

    public function __construct(
        private readonly CDNSettings $settings,
    ) {}

    /**
     * Resolve the active CDN provider instance (cached per request).
     */
    public function provider(): CDNInterface
    {
        return $this->cached ??= $this->resolveProvider();
    }

    /**
     * Force re-resolve (e.g. after settings change).
     */
    public function refresh(): static
    {
        $this->cached = null;

        return $this;
    }

    private function resolveProvider(): CDNInterface
    {
        if (! $this->settings->cdn_enabled) {
            return new NullCDNService();
        }

        $primary = $this->buildProvider(
            (string) $this->settings->cdn_provider,
            (string) $this->settings->cdn_token,
            (string) $this->settings->cdn_zone_id,
            (string) $this->settings->cdn_plan,
        );

        // Optional fallback provider (e.g. Cloudflare → Bunny)
        $fallbackProvider = (string) $this->settings->cdn_fallback_provider;

        if ($fallbackProvider !== '' && $fallbackProvider !== $this->settings->cdn_provider) {
            $fallback = $this->buildProvider(
                $fallbackProvider,
                (string) $this->settings->cdn_fallback_token,
                (string) $this->settings->cdn_fallback_zone_id,
                (string) $this->settings->cdn_fallback_plan,
            );

            if ($fallback->enabled()) {
                return new FailoverCDNService($primary, $fallback);
            }
        }

        return $primary;
    }

    private function buildProvider(string $provider, string $token, string $zoneId, string $plan): CDNInterface
    {
        return match ($provider) {
            'cloudflare' => new CloudflareCDNService($token, $zoneId, $plan),
            // Future: 'cloudfront' => new CloudFrontCDNService(...),
            // Future: 'bunny' => new BunnyCDNService(...),
            default => new NullCDNService(),
        };
    }

    /**
     * Whether auto-purge is enabled (CDN enabled + auto_purge toggled on).
     */
    public function autoPurgeEnabled(): bool
    {
        return $this->settings->cdn_enabled && $this->settings->cdn_auto_purge;
    }

    /**
     * Shortcut: get the active CDN provider.
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->provider()->{$method}(...$arguments);
    }
}
