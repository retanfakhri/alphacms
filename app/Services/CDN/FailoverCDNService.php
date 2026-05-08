<?php

declare(strict_types=1);

namespace App\Services\CDN;

use App\Contracts\CDNInterface;
use App\Contracts\ClassifiesFailures;
use App\Support\CDN\CDNRequestContext;
use App\Support\CDN\FailureType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Smart failover wrapper.
 *
 * Falls back to the secondary provider ONLY when the primary's failure is
 * transient (5xx, timeout, 429). Permanent failures (auth, missing zone,
 * malformed request) are NOT retried on the fallback because the same
 * symptom would be misleading — we surface the original error instead.
 *
 * If the primary doesn't implement ClassifiesFailures, we conservatively
 * treat any false return as UNKNOWN (which still falls over) — preserving
 * the original behaviour for legacy providers.
 */
class FailoverCDNService implements CDNInterface
{
    public function __construct(
        private readonly CDNInterface $primary,
        private readonly CDNInterface $fallback,
    ) {}

    public function enabled(): bool
    {
        return $this->primary->enabled() || $this->fallback->enabled();
    }

    public function purge(array $urls): bool
    {
        if ($this->primary->purge($urls)) {
            return true;
        }

        return $this->maybeFailover('purge', fn () => $this->fallback->purge($urls), ['count' => count($urls)]);
    }

    public function purgeAll(): bool
    {
        if ($this->primary->purgeAll()) {
            return true;
        }

        return $this->maybeFailover('purge-all', fn () => $this->fallback->purgeAll(), []);
    }

    public function purgeTags(array $tags): bool
    {
        if ($this->primary->purgeTags($tags)) {
            return true;
        }

        return $this->maybeFailover('tag-purge', fn () => $this->fallback->purgeTags($tags), ['count' => count($tags)]);
    }

    public function testConnection(): array
    {
        $primary = $this->primary->testConnection();
        $fallback = $this->fallback->testConnection();

        return [
            'ok' => ($primary['ok'] ?? false) || ($fallback['ok'] ?? false),
            'message' => "Primary ({$this->primary->name()}): {$primary['message']} | Fallback ({$this->fallback->name()}): {$fallback['message']}",
        ];
    }

    /** Anti-stampede lock TTL — short, just long enough to deduplicate the herd */
    private const FAILOVER_LOCK_TTL = 5;

    /**
     * Cache key TTL for the recent failover decision.
     * Kept very short (2s) so siblings reuse the leader's outcome
     * without acting on stale info if the primary recovers quickly.
     */
    private const RECENT_DECISION_TTL = 2;

    public function name(): string
    {
        return "failover[{$this->primary->name()}>{$this->fallback->name()}]";
    }

    /**
     * Decide whether to fail over.
     *
     * Anti-stampede: when the primary fails under load, every worker would
     * race into the fallback simultaneously. We coordinate via a Redis lock
     * so only one worker performs the fallback call AND caches the outcome
     * for a short window. Other workers reuse that decision instead of
     * piling onto the secondary provider.
     */
    private function maybeFailover(string $op, \Closure $fallbackCall, array $extra): bool
    {
        $failureType = $this->primaryFailureType();

        if (! $failureType->shouldFailover()) {
            Log::channel('cdn')->warning("[CDN] Primary {$op} failed PERMANENTLY — skipping fallback", array_merge([
                'primary' => $this->primary->name(),
                'fallback' => $this->fallback->name(),
                'failure_type' => $failureType->value,
            ], $extra, CDNRequestContext::logContext()));

            return false;
        }

        $lockKey = "cdn:failover:lock:{$op}";
        $decisionKey = "cdn:failover:decision:{$op}";

        // Short-circuit: did a sibling worker recently decide?
        $cached = Cache::get($decisionKey);

        if ($cached !== null) {
            Log::channel('cdn')->info("[CDN] Reusing recent fallback decision for {$op}", array_merge([
                'decision' => $cached ? 'success' : 'failed',
            ], CDNRequestContext::logContext()));

            return (bool) $cached;
        }

        $lock = Cache::lock($lockKey, self::FAILOVER_LOCK_TTL);

        // Try to win the lock; wait briefly for the leader's decision
        if (! $lock->get()) {
            // Another worker is performing the failover — poll for its decision.
            // Jittered sleep prevents thundering-herd polling: every worker waits
            // a slightly different interval so they don't synchronize on cache reads.
            $waited = 0;

            while ($waited < 1500) {
                $sleepMs = random_int(80, 200); // 80–200 ms jitter
                usleep($sleepMs * 1000);
                $waited += $sleepMs;

                $cached = Cache::get($decisionKey);

                if ($cached !== null) {
                    return (bool) $cached;
                }
            }

            // Leader still busy — let this caller proceed independently as a safety net
            Log::channel('cdn')->warning("[CDN] Failover lock timeout — proceeding solo", CDNRequestContext::logContext());

            return $fallbackCall();
        }

        try {
            Log::channel('cdn')->warning("[CDN] Primary {$op} failed (transient) → trying fallback (leader)", array_merge([
                'primary' => $this->primary->name(),
                'fallback' => $this->fallback->name(),
                'failure_type' => $failureType->value,
            ], $extra, CDNRequestContext::logContext()));

            $result = $fallbackCall();

            Cache::put($decisionKey, $result, self::RECENT_DECISION_TTL);

            return $result;
        } finally {
            $lock->release();
        }
    }

    private function primaryFailureType(): FailureType
    {
        if ($this->primary instanceof ClassifiesFailures) {
            return $this->primary->lastFailureType();
        }

        return FailureType::UNKNOWN;
    }
}
