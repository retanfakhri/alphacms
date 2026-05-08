<?php

declare(strict_types=1);

namespace App\Support\CDN;

/**
 * Bulkhead — single source of truth for CDN queue names.
 *
 * Isolates concerns so a flood of warmups can't starve high-priority purges,
 * and stats refreshes can't block either:
 *
 *   cdn-purge-high   → critical/breaking purges (flushed immediately)
 *   cdn-purge-normal → batched aggregated purges (every minute)
 *   cdn-warmup       → edge cache warmup HTTP requests
 *   cdn-stats        → background analytics (snapshot/aggregate)
 */
final class CDNQueues
{
    public const PURGE_HIGH = 'cdn-purge-high';
    public const PURGE_NORMAL = 'cdn-purge-normal';
    public const WARMUP = 'cdn-warmup';
    public const STATS = 'cdn-stats';

    /** @return array<int, string> */
    public static function all(): array
    {
        return [self::PURGE_HIGH, self::PURGE_NORMAL, self::WARMUP, self::STATS];
    }
}
