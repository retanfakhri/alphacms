<?php

declare(strict_types=1);

namespace App\Support\Metrics;

use Illuminate\Support\Facades\Cache;

/**
 * HLS streaming performance metrics.
 *
 * Tracks hits, misses, signature failures, and latencies for HLS segments
 * and playlists across a sliding window (Redis-backed).
 */
final class HlsMetrics
{
    public const EVENT_HIT = 'hit';
    public const EVENT_NOT_FOUND = 'not_found';
    public const EVENT_SIGNATURE_FAIL = 'sig_fail';
    public const EVENT_EXPIRED = 'expired';
    public const EVENT_RATE_LIMITED = 'rate_limit';
    public const EVENT_PATH_ESCAPE = 'path_escape';
    public const EVENT_NOT_READY = 'not_ready';
    public const EVENT_PLAYLIST = 'playlist';
    public const EVENT_SEGMENT = 'segment';
    public const EVENT_OFFLOAD = 'offload';

    private const KEY_PREFIX = 'metrics:hls:';

    public static function recordEvent(string $event): void
    {
        $key = self::KEY_PREFIX . 'events:' . $event . ':' . date('Y-m-d-H-i');
        Cache::increment($key);
        Cache::expire($key, 3600 * 25);
    }

    public static function recordLatency(float $ms, string $label = 'general'): void
    {
        $key = self::KEY_PREFIX . 'latency:' . $label . ':' . date('Y-m-d-H-i');
        $data = Cache::get($key, ['sum' => 0.0, 'count' => 0, 'values' => []]);
        $data['sum'] += $ms;
        $data['count']++;
        if (count($data['values']) < 100) {
            $data['values'][] = $ms;
        }
        Cache::put($key, $data, 3600 * 25);
    }

    public static function sum(string $event, int $minutes = 60): int
    {
        $total = 0;
        $now = time();
        for ($i = 0; $i < $minutes; $i++) {
            $key = self::KEY_PREFIX . 'events:' . $event . ':' . date('Y-m-d-H-i', $now - ($i * 60));
            $total += (int) Cache::get($key, 0);
        }
        return $total;
    }

    public static function latencyAvg(string $label = 'general', int $minutes = 60): float
    {
        $sum = 0.0;
        $count = 0;
        $now = time();
        for ($i = 0; $i < $minutes; $i++) {
            $key = self::KEY_PREFIX . 'latency:' . $label . ':' . date('Y-m-d-H-i', $now - ($i * 60));
            $data = Cache::get($key);
            if ($data) {
                $sum += $data['sum'];
                $count += $data['count'];
            }
        }
        return $count > 0 ? round($sum / $count, 2) : 0.0;
    }

    public static function latencyPercentile(int $percentile, string $label = 'general', int $minutes = 15): float
    {
        $values = [];
        $now = time();
        for ($i = 0; $i < $minutes; $i++) {
            $key = self::KEY_PREFIX . 'latency:' . $label . ':' . date('Y-m-d-H-i', $now - ($i * 60));
            $data = Cache::get($key);
            if ($data && ! empty($data['values'])) {
                $values = array_merge($values, $data['values']);
            }
        }

        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $index = (int) ceil(($percentile / 100) * count($values)) - 1;
        return (float) $values[max(0, $index)];
    }
}
