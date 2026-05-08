<?php

declare(strict_types=1);

namespace App\Support\CDN;

use Illuminate\Support\Facades\Cache;

/**
 * Global rate limiter shared across ALL CDN providers (primary + fallback).
 *
 * The per-provider limiter inside each service handles plan-specific quotas;
 * this one caps the AGGREGATE rate so a fallback can't double the load on
 * the origin or upstream API gateway.
 */
final class GlobalRateLimiter
{
    private const KEY = 'cdn:global:rate:v2';

    public static function maxRequests(): int
    {
        return (int) (env('CDN_GLOBAL_RATE_MAX') ?: 100);
    }

    public static function burstRequests(): int
    {
        $burst = (int) (env('CDN_GLOBAL_RATE_BURST') ?: 150);

        return max($burst, self::maxRequests());
    }

    public static function windowSeconds(): int
    {
        return (int) (env('CDN_GLOBAL_RATE_WINDOW') ?: 10);
    }

    /**
     * Finding #39: Try to consume a slot. Returns a unique token (string) if successful,
     * or null if the limit is reached.
     */
    public static function acquire(): ?string
    {
        $limit = self::burstRequests();
        $window = self::windowSeconds();
        $now = microtime(true);
        $token = bin2hex(random_bytes(8)); // Finding #48: Stronger uniqueness for high concurrency

        try {
            $store = Cache::getStore();

            if ($store instanceof \Illuminate\Cache\RedisStore) {
                $lua = <<<'LUA'
                    local key = KEYS[1]
                    local now = tonumber(ARGV[1])
                    local window = tonumber(ARGV[2])
                    local limit = tonumber(ARGV[3])
                    local token = ARGV[4]

                    redis.call('ZREMRANGEBYSCORE', key, 0, now - window)
                    local count = redis.call('ZCARD', key)

                    if count >= limit then
                        return nil
                    end

                    redis.call('ZADD', key, now, token)
                    redis.call('EXPIRE', key, window + 5)
                    return token
LUA;

                $result = $store->connection()->eval(
                    $lua,
                    1,
                    self::KEY,
                    $now,
                    $window,
                    $limit,
                    $token
                );

                return $result ? (string) $result : null;
            }
        } catch (\Throwable) {
            // Fallback handled below
        }

        // Fallback for non-Redis stores
        return Cache::lock(self::KEY . ':lock', 2)->block(1, function () use ($limit, $window, $now, $token) {
            $key = self::KEY . ':fallback';
            $data = Cache::get($key, []);
            $cutoff = $now - $window;

            // Finding #49: Simpler filter callback
            $data = array_filter($data, fn ($t) => $t > $cutoff);

            if (count($data) >= $limit) {
                return null;
            }

            $data[$token] = $now;
            Cache::put($key, $data, $window + 5);

            return $token;
        });
    }

    /**
     * Finding #39: Release a previously acquired slot using its token.
     */
    public static function release(?string $token): void
    {
        if ($token === null || $token === '') {
            return;
        }

        try {
            $store = Cache::getStore();
            if ($store instanceof \Illuminate\Cache\RedisStore) {
                $store->connection()->zrem(self::KEY, $token);

                return;
            }
        } catch (\Throwable) {
            // Fallback
        }

        Cache::lock(self::KEY . ':lock', 2)->block(1, function () use ($token) {
            $key = self::KEY . ':fallback';
            $data = Cache::get($key, []);
            unset($data[$token]);
            Cache::put($key, $data, self::windowSeconds() + 5);
        });
    }

    /**
     * Finding #40: Re-implemented observability.
     */
    public static function usagePercent(): float
    {
        $current = self::currentCount();
        $max = self::burstRequests();

        return $max > 0 ? round(($current / $max) * 100, 1) : 0.0;
    }

    public static function inBurstZone(): bool
    {
        return self::currentCount() >= self::maxRequests();
    }

    private static function currentCount(): int
    {
        try {
            $store = Cache::getStore();
            if ($store instanceof \Illuminate\Cache\RedisStore) {
                // Prune old entries first for accurate count
                $now = microtime(true);
                $store->connection()->zremrangebyscore(self::KEY, 0, $now - self::windowSeconds());

                return (int) $store->connection()->zcard(self::KEY);
            }
        } catch (\Throwable) {
            // Fallback
        }

        $data = Cache::get(self::KEY . ':fallback', []);
        $cutoff = microtime(true) - self::windowSeconds();

        return count(array_filter($data, fn ($t) => $t > $cutoff));
    }

    public static function reset(): void
    {
        Cache::forget(self::KEY);
        Cache::forget(self::KEY . ':fallback');
        Cache::forget(self::KEY . ':lock');
    }
}
