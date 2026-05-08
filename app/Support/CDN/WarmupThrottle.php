<?php

declare(strict_types=1);

namespace App\Support\CDN;

use Illuminate\Support\Facades\Cache;

/**
 * Per-host token-bucket throttle for CDN warmup requests.
 *
 * Prevents the warmup pool from hammering origin during cache miss storms.
 * Default: 20 requests / 10s window per host.
 */
final class WarmupThrottle
{
    private const PREFIX = 'cdn:warmup:throttle:';

    public function __construct(
        private readonly int $maxRequests = 20,
        private readonly int $windowSeconds = 10,
    ) {}

    /**
     * Try to consume a slot for $url's host.
     * Returns true if allowed, false if throttled.
     */
    public function allow(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?: 'unknown';
        $key = self::PREFIX . $host;

        $count = (int) Cache::get($key, 0);

        if ($count >= $this->maxRequests) {
            return false;
        }

        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            // TTL = window + small slack so the bucket survives long enough to be summed
            Cache::put($key, 1, $this->windowSeconds);
        }

        return true;
    }

    /**
     * How many seconds the caller should wait before its next attempt
     * for this host (rough estimate based on the window).
     */
    public function suggestedDelay(string $url): int
    {
        $host = parse_url($url, PHP_URL_HOST) ?: 'unknown';
        $count = (int) Cache::get(self::PREFIX . $host, 0);

        if ($count < $this->maxRequests) {
            return 0;
        }

        // Approximate: defer by half the window; jitter handled by the queue
        return (int) ceil($this->windowSeconds / 2);
    }
}
