<?php

declare(strict_types=1);

namespace App\Services\CDN;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class CDNStats
{
    private const BASE_PREFIX = 'cdn:stats:';
    private const DEFAULT_PROVIDER = 'cloudflare';
    private const TTL = 86400;
    private const LATENCY_BUCKETS = [50, 100, 200, 300, 500, 1000, 2000, 5000];
    
    public const TYPE_OPERATIONAL = 'operational';
    public const TYPE_DIAGNOSTIC = 'diagnostic';

    /**
     * Finding #6: Probabilistic sampling rate (0.0 to 1.0).
     * Reduces Redis overhead for high-volume operational metrics.
     */
    private const SAMPLING_RATE = 1.0; 

    private static function prefix(string $provider = self::DEFAULT_PROVIDER): string
    {
        return self::BASE_PREFIX . $provider . ':';
    }

    /**
     * Finding #99 & #104: Renamed to recordOutboundAttempt with consistent 
     * cache key to reflect actual semantic meaning.
     */
    public static function recordOutboundAttempt(string $provider = self::DEFAULT_PROVIDER): void
    {
        if (! self::shouldSample()) {
            return;
        }

        $prefix = self::prefix($provider);
        $key = $prefix . 'outbound_attempts:' . self::hourKey();

        Cache::add($key, 0, self::TTL);
        Cache::increment($key);
    }

    public static function recordPurge(int $urlCount = 0, int $tagCount = 0, string $provider = self::DEFAULT_PROVIDER): void
    {
        $hourKey = self::hourKey();
        $prefix = self::prefix($provider);

        foreach (['purges', 'urls', 'tags'] as $metric) {
            Cache::add($prefix . $metric . ':' . $hourKey, 0, self::TTL);
        }

        Cache::increment($prefix . 'purges:' . $hourKey);
        Cache::increment($prefix . 'urls:' . $hourKey, $urlCount);
        Cache::increment($prefix . 'tags:' . $hourKey, $tagCount);
    }

    public static function recordFailure(string $provider = self::DEFAULT_PROVIDER): void
    {
        $prefix = self::prefix($provider);
        $key = $prefix . 'failures:' . self::hourKey();

        Cache::add($key, 0, self::TTL);
        Cache::increment($key);
    }

    public static function recordRateLimit(string $provider = self::DEFAULT_PROVIDER): void
    {
        $prefix = self::prefix($provider);
        $key = $prefix . 'ratelimits:' . self::hourKey();

        Cache::add($key, 0, self::TTL);
        Cache::increment($key);
    }

    public static function recordLatency(float $ms, string $type = self::TYPE_OPERATIONAL, string $provider = self::DEFAULT_PROVIDER): void
    {
        if ($type === self::TYPE_OPERATIONAL && ! self::shouldSample()) {
            return;
        }

        $hourKey = self::hourKey();
        $prefix = self::prefix($provider);
        $typePrefix = "{$type}:";
        
        $sumKey = $prefix . $typePrefix . 'latency_sum:' . $hourKey;
        $countKey = $prefix . $typePrefix . 'latency_count:' . $hourKey;

        Cache::add($sumKey, 0, self::TTL);
        Cache::add($countKey, 0, self::TTL);

        Cache::increment($sumKey, (int) round($ms));
        Cache::increment($countKey);

        if ($type === self::TYPE_OPERATIONAL) {
            $bucket = self::bucketFor($ms);
            $bucketKey = $prefix . 'latency_b' . $bucket . ':' . $hourKey;
            Cache::add($bucketKey, 0, self::TTL);
            Cache::increment($bucketKey);
        }
    }

    public static function getAverageLatency(string $provider = self::DEFAULT_PROVIDER): float
    {
        return self::avgLatencyLastHour($provider, self::TYPE_OPERATIONAL);
    }

    public static function avgLatencyLastHour(string $provider = self::DEFAULT_PROVIDER, string $type = self::TYPE_OPERATIONAL): float
    {
        $prefix = self::prefix($provider);
        $hourKey = self::hourKey();
        $sum = (int) Cache::get($prefix . "{$type}:" . 'latency_sum:' . $hourKey, 0);
        $count = (int) Cache::get($prefix . "{$type}:" . 'latency_count:' . $hourKey, 0);

        // Fallback to legacy key format
        if ($count === 0 && $type === self::TYPE_OPERATIONAL) {
            $sum = (int) Cache::get($prefix . 'latency_sum:' . $hourKey, 0);
            $count = (int) Cache::get($prefix . 'latency_count:' . $hourKey, 0);
        }

        return $count > 0 ? round($sum / $count, 1) : 0.0;
    }

    public static function summary(int $hours = 24, string $provider = self::DEFAULT_PROVIDER): array
    {
        $prefix = self::prefix($provider);
        $totals = ['purges' => 0, 'outbound_attempts' => 0, 'urls' => 0, 'tags' => 0, 'failures' => 0, 'rate_limits' => 0];
        $totalLatencySum = 0;
        $totalLatencyCount = 0;
        $histogramTotals = array_fill_keys(self::LATENCY_BUCKETS, 0);
        $hourly = [];

        $now = Carbon::now();

        for ($i = 0; $i < $hours; $i++) {
            $hourKey = $now->copy()->subHours($i)->format('Y-m-d-H');

            $purges = (int) Cache::get($prefix . 'purges:' . $hourKey, 0);
            
            // Finding #104: Fallback for attempt keys
            $attempts = (int) Cache::get($prefix . 'outbound_attempts:' . $hourKey, 0);
            if ($attempts === 0) {
                $attempts = (int) Cache::get($prefix . 'attempts:' . $hourKey, 0);
            }

            $urls = (int) Cache::get($prefix . 'urls:' . $hourKey, 0);
            $tags = (int) Cache::get($prefix . 'tags:' . $hourKey, 0);
            $failures = (int) Cache::get($prefix . 'failures:' . $hourKey, 0);
            $rateLimits = (int) Cache::get($prefix . 'ratelimits:' . $hourKey, 0);
            
            $latencySum = (int) Cache::get($prefix . self::TYPE_OPERATIONAL . ':latency_sum:' . $hourKey, 0);
            $latencyCount = (int) Cache::get($prefix . self::TYPE_OPERATIONAL . ':latency_count:' . $hourKey, 0);

            if ($latencyCount === 0) {
                $latencySum = (int) Cache::get($prefix . 'latency_sum:' . $hourKey, 0);
                $latencyCount = (int) Cache::get($prefix . 'latency_count:' . $hourKey, 0);
            }

            $totals['purges'] += $purges;
            $totals['outbound_attempts'] += $attempts;
            $totals['urls'] += $urls;
            $totals['tags'] += $tags;
            $totals['failures'] += $failures;
            $totals['rate_limits'] += $rateLimits;
            $totalLatencySum += $latencySum;
            $totalLatencyCount += $latencyCount;

            foreach (self::LATENCY_BUCKETS as $b) {
                $histogramTotals[$b] += (int) Cache::get($prefix . 'latency_b' . $b . ':' . $hourKey, 0);
            }

            $hourly[] = [
                'hour' => $hourKey,
                'purges' => $purges,
                'failures' => $failures,
                'latency_avg' => $latencyCount > 0 ? round($latencySum / $latencyCount) : 0,
            ];
        }

        $successRate = $totals['outbound_attempts'] > 0
            ? round((($totals['outbound_attempts'] - $totals['failures']) / $totals['outbound_attempts']) * 100, 1)
            : null;

        $avgLatency = $totalLatencyCount > 0 ? round($totalLatencySum / $totalLatencyCount, 1) : 0.0;
        $percentiles = self::percentilesFromHistogram($histogramTotals, $totalLatencyCount);

        return [
            ...$totals,
            'success_rate' => $successRate === null ? null : (float) max(0, min(100, $successRate)),
            'avg_latency_ms' => $avgLatency,
            'p50_ms' => $percentiles['p50'],
            'p95_ms' => $percentiles['p95'],
            'p99_ms' => $percentiles['p99'],
            'buffer_size' => 0,
            'hourly' => array_reverse($hourly),
            'alerts' => [],
        ];
    }

    public static function current(string $provider = self::DEFAULT_PROVIDER): array
    {
        $hourKey = self::hourKey();
        $prefix = self::prefix($provider);

        $purges = (int) Cache::get($prefix . 'purges:' . $hourKey, 0);
        $failures = (int) Cache::get($prefix . 'failures:' . $hourKey, 0);
        $rateLimits = (int) Cache::get($prefix . 'ratelimits:' . $hourKey, 0);
        
        // Finding #104: Fallback for attempt keys
        $attempts = (int) Cache::get($prefix . 'outbound_attempts:' . $hourKey, 0);
        if ($attempts === 0) {
            $attempts = (int) Cache::get($prefix . 'attempts:' . $hourKey, 0);
        }

        // Finding #102: Fallback for latency keys in current()
        $latencySum = (int) Cache::get($prefix . self::TYPE_OPERATIONAL . ':latency_sum:' . $hourKey, 0);
        $latencyCount = (int) Cache::get($prefix . self::TYPE_OPERATIONAL . ':latency_count:' . $hourKey, 0);

        if ($latencyCount === 0) {
            $latencySum = (int) Cache::get($prefix . 'latency_sum:' . $hourKey, 0);
            $latencyCount = (int) Cache::get($prefix . 'latency_count:' . $hourKey, 0);
        }

        $histogram = [];
        foreach (self::LATENCY_BUCKETS as $b) {
            $histogram[$b] = (int) Cache::get($prefix . 'latency_b' . $b . ':' . $hourKey, 0);
        }

        $successRate = $attempts > 0
            ? round((($attempts - $failures) / $attempts) * 100, 1)
            : null;

        $avgLatency = $latencyCount > 0 ? round($latencySum / $latencyCount, 1) : 0.0;
        $percentiles = self::percentilesFromHistogram($histogram, $latencyCount);

        return [
            'purges_1h' => $purges,
            'failures_1h' => $failures,
            'outbound_attempts_1h' => $attempts,
            'rate_limits_1h' => $rateLimits,
            'success_rate' => $successRate === null ? null : (float) max(0, min(100, $successRate)),
            'avg_latency_ms' => $avgLatency,
            'p95_ms' => $percentiles['p95'],
            'p99_ms' => $percentiles['p99'],
            'buffer_size' => 0,
            'alerts' => [],
        ];
    }

    private static function bucketFor(float $ms): int
    {
        foreach (self::LATENCY_BUCKETS as $bucket) {
            if ($ms <= $bucket) {
                return $bucket;
            }
        }

        return end(self::LATENCY_BUCKETS) ?: 5000;
    }

    private static function percentilesFromHistogram(array $histogram, int $totalCount): array
    {
        if ($totalCount === 0) {
            return ['p50' => 0, 'p95' => 0, 'p99' => 0];
        }

        $result = ['p50' => 0, 'p95' => 0, 'p99' => 0];
        $targets = ['p50' => 0.50, 'p95' => 0.95, 'p99' => 0.99];
        $cumulative = 0;

        foreach (self::LATENCY_BUCKETS as $bucket) {
            $cumulative += $histogram[$bucket] ?? 0;
            $ratio = $cumulative / $totalCount;

            foreach ($targets as $key => $threshold) {
                if ($result[$key] === 0 && $ratio >= $threshold) {
                    $result[$key] = $bucket;
                }
            }
        }

        foreach ($result as $key => $val) {
            if ($val === 0 && $totalCount > 0) {
                $lastBucket = end(self::LATENCY_BUCKETS);
                $result[$key] = $lastBucket ?: 5000;
            }
        }

        return $result;
    }

    private static function hourKey(): string
    {
        return Carbon::now()->format('Y-m-d-H');
    }

    /**
     * Finding #6: Probabilistic sampling check.
     */
    private static function shouldSample(): bool
    {
        if (self::SAMPLING_RATE >= 1.0) {
            return true;
        }

        return (mt_rand() / mt_getrandmax()) <= self::SAMPLING_RATE;
    }
}
