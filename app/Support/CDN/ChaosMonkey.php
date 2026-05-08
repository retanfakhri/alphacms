<?php

declare(strict_types=1);

namespace App\Support\CDN;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Chaos engineering harness for CDN call paths.
 *
 * Disabled by default. Enable per-environment via:
 *   CDN_CHAOS_ENABLED=true
 *   CDN_CHAOS_FAILURE_RATE=0.10        // 10% of CDN calls fail
 *   CDN_CHAOS_LATENCY_RATE=0.05        // 5% of CDN calls inject extra latency
 *   CDN_CHAOS_LATENCY_MS=1500          // amount of latency to inject
 *   CDN_CHAOS_TIMEOUT_RATE=0.02        // 2% of CDN calls simulate full timeout
 *
 * Or runtime-toggleable via Cache::put('cdn:chaos:enabled', true) for
 * controlled bursts during fire-drills.
 *
 * NEVER enable in production unless you know what you're doing.
 */
final class ChaosMonkey
{
    private const RUNTIME_KEY = 'cdn:chaos:enabled';

    public static function enabled(): bool
    {
        if (app()->environment('production') && ! env('CDN_CHAOS_ALLOW_PROD', false)) {
            return false;
        }

        if (Cache::get(self::RUNTIME_KEY) === true) {
            return true;
        }

        return (bool) env('CDN_CHAOS_ENABLED', false);
    }

    public static function enable(int $minutes = 5): void
    {
        Cache::put(self::RUNTIME_KEY, true, now()->addMinutes($minutes));
        Log::channel('cdn')->warning('[CHAOS] CDN chaos monkey ENABLED', ['minutes' => $minutes]);
    }

    public static function disable(): void
    {
        Cache::forget(self::RUNTIME_KEY);
        Log::channel('cdn')->info('[CHAOS] CDN chaos monkey disabled');
    }

    /**
     * Should this CDN call be force-failed? Use before returning success.
     */
    public static function shouldFail(): bool
    {
        if (! self::enabled()) {
            return false;
        }

        return self::roll((float) env('CDN_CHAOS_FAILURE_RATE', 0.0));
    }

    /**
     * Should this CDN call simulate a full timeout (sleep until client times out)?
     */
    public static function shouldTimeout(): bool
    {
        if (! self::enabled()) {
            return false;
        }

        return self::roll((float) env('CDN_CHAOS_TIMEOUT_RATE', 0.0));
    }

    /**
     * Inject extra latency before/after the call (ms).
     * Returns the actual ms slept.
     */
    public static function injectLatency(): int
    {
        if (! self::enabled()) {
            return 0;
        }

        $rate = (float) env('CDN_CHAOS_LATENCY_RATE', 0.0);

        if (! self::roll($rate)) {
            return 0;
        }

        $ms = (int) env('CDN_CHAOS_LATENCY_MS', 1000);
        usleep($ms * 1000);

        Log::channel('cdn')->warning('[CHAOS] Injected latency', ['ms' => $ms]);

        return $ms;
    }

    private static function roll(float $probability): bool
    {
        if ($probability <= 0) {
            return false;
        }

        if ($probability >= 1) {
            return true;
        }

        return mt_rand(1, 10000) <= ($probability * 10000);
    }
}
