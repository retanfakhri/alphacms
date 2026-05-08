<?php

declare(strict_types=1);

namespace App\Services\CDN;

use App\Services\SettingsManagerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CDNCircuitBreaker
{
    private const CACHE_PREFIX_BASE = 'cdn:circuit:';

    private const PLAN_THRESHOLDS = [
        'free'       => 3,
        'pro'        => 5,
        'business'   => 7,
        'enterprise' => 10,
    ];

    private const COOLDOWN_SECONDS = 60;

    private const FAILURE_WINDOW_SECONDS = 300;

    public function __construct(
        private readonly SettingsManagerService $settings,
        private string $provider = 'global',
    ) {}

    public function forProvider(string $provider): self
    {
        $clone = clone $this;
        $clone->provider = $provider;

        return $clone;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function isAvailable(): bool
    {
        $state = $this->getState();

        if ($state === 'closed') {
            return true;
        }

        if ($state === 'open') {
            $openedAt = (int) Cache::get($this->key('opened_at'), 0);

            if (time() - $openedAt >= self::COOLDOWN_SECONDS) {
                // Single probe lock: only one worker can enter half-open
                if (Cache::add($this->key('half_open_probe'), true, 30)) {
                    $this->setState('half-open');
                    Log::channel('cdn')->info('[CDN] Circuit breaker -> half-open (probe started)', ['provider' => $this->provider]);

                    return true;
                }

                return false;
            }

            return false;
        }

        return true;
    }

    public function recordSuccess(): void
    {
        $state = $this->getState();
        Cache::forget($this->key('failures'));

        if ($state !== 'closed') {
            $this->setState('closed');
            Cache::forget($this->key('opened_at'));
            Log::channel('cdn')->info('[CDN] Circuit breaker -> closed', ['provider' => $this->provider]);
        }
    }

    public function recordFailure(): void
    {
        $failures = (int) Cache::get($this->key('failures'), 0) + 1;
        Cache::put($this->key('failures'), $failures, self::FAILURE_WINDOW_SECONDS);

        $threshold = $this->failureThreshold();

        Log::channel('cdn')->warning('[CDN] Circuit breaker failure recorded', [
            'provider' => $this->provider,
            'failures' => $failures,
            'threshold' => $threshold,
            'plan' => $this->currentPlan(),
        ]);

        if ($failures >= $threshold && $this->getState() !== 'open') {
            $this->trip();
        }
    }

    public function trip(): void
    {
        $this->setState('open');
        Cache::put($this->key('opened_at'), time(), self::COOLDOWN_SECONDS + 60);

        Log::channel('cdn')->error('[CDN] Circuit breaker OPEN', [
            'provider' => $this->provider,
            'cooldown' => self::COOLDOWN_SECONDS . 's',
        ]);
    }

    public function reset(): void
    {
        Cache::forget($this->key('state'));
        Cache::forget($this->key('failures'));
        Cache::forget($this->key('opened_at'));

        Log::channel('cdn')->info('[CDN] Circuit breaker manually reset', ['provider' => $this->provider]);
    }

    public function status(): array
    {
        $state = $this->getState();
        $failures = (int) Cache::get($this->key('failures'), 0);
        $threshold = $this->failureThreshold();
        $cooldownRemaining = null;

        if ($state === 'open') {
            $openedAt = (int) Cache::get($this->key('opened_at'), 0);
            $cooldownRemaining = max(0, self::COOLDOWN_SECONDS - (time() - $openedAt));
        }

        return [
            'state' => $state,
            'failures' => $failures,
            'threshold' => $threshold,
            'cooldown_remaining' => $cooldownRemaining,
            'provider' => $this->provider,
        ];
    }

    private function key(string $suffix): string
    {
        return self::CACHE_PREFIX_BASE . $this->provider . ':' . $suffix;
    }

    private function getState(): string
    {
        return Cache::get($this->key('state'), 'closed');
    }

    private function setState(string $state): void
    {
        Cache::put($this->key('state'), $state, self::COOLDOWN_SECONDS + 120);

        if ($state !== 'half-open') {
            Cache::forget($this->key('half_open_probe'));
        }
    }

    private const EMA_ALPHA = 0.3;

    private function emaKey(): string
    {
        return "cdn:circuit:{$this->provider}:ema_success_rate";
    }

    private function failureThreshold(): int
    {
        $base = self::PLAN_THRESHOLDS[$this->currentPlan()] ?? self::PLAN_THRESHOLDS['free'];

        $multiplier = 1.0;

        try {
            $stats = CDNStats::current($this->provider);
            $rawRate = (float) ($stats['success_rate'] ?? 100.0);
            $purges = (int) ($stats['purges_1h'] ?? 0);

            if ($purges >= 20) {
                $smoothed = $this->updateEma($rawRate);

                $multiplier = match (true) {
                    $smoothed >= 99 => 1.5,
                    $smoothed >= 95 => 1.0,
                    $smoothed >= 80 => 0.7,
                    default         => 0.5,
                };
            }
        } catch (\Throwable) {
        }

        return max(2, (int) round($base * $multiplier));
    }

    private function updateEma(float $sample): float
    {
        $previous = Cache::get($this->emaKey());

        $smoothed = $previous === null
            ? $sample
            : (self::EMA_ALPHA * $sample) + ((1 - self::EMA_ALPHA) * (float) $previous);

        Cache::put($this->emaKey(), $smoothed, 3600);

        return $smoothed;
    }

    private function currentPlan(): string
    {
        try {
            return (string) ($this->settings->getCDN()['cdn_plan'] ?? 'free');
        } catch (\Throwable) {
            return 'free';
        }
    }
}
