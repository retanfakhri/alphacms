<?php

declare(strict_types=1);

namespace App\Support\CDN;

/**
 * Intelligent retry strategy with exponential backoff + jitter.
 *
 * Designed to be passed into Laravel's Http::retry() callback signature:
 *   ->retry($attempts, sleepMilliseconds: IntelligentRetry::backoff(...))
 *
 * Or use IntelligentRetry::execute() to wrap an arbitrary closure.
 */
final class IntelligentRetry
{
    /** Base delay in ms */
    private const BASE_MS = 200;

    /** Maximum cap on a single delay (to avoid huge waits) */
    private const MAX_MS = 5000;

    /** Maximum jitter ratio applied (±20%) */
    private const JITTER_RATIO = 0.2;

    /**
     * Closure suitable for Http::retry($times, $sleepCallback).
     * Signature receives: int $attempt, ?\Throwable $exception
     */
    public static function backoff(): \Closure
    {
        return static function (int $attempt): int {
            return self::delayForAttempt($attempt);
        };
    }

    /**
     * Compute delay (ms) for a given retry attempt (1-indexed).
     * Formula: min(MAX, BASE * 2^(attempt-1)) ± JITTER
     */
    public static function delayForAttempt(int $attempt): int
    {
        $attempt = max(1, $attempt);
        $exp = self::BASE_MS * (2 ** ($attempt - 1));
        $exp = min($exp, self::MAX_MS);

        // Symmetric jitter: ±JITTER_RATIO of the computed delay
        $jitterRange = (int) round($exp * self::JITTER_RATIO);
        $jitter = random_int(-$jitterRange, $jitterRange);

        return max(50, $exp + $jitter);
    }

    /**
     * Wrap a closure with retries (when Http::retry is not appropriate).
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function execute(callable $callback, int $maxAttempts = 3): mixed
    {
        $attempt = 1;

        while (true) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                usleep(self::delayForAttempt($attempt) * 1000);
                $attempt++;
            }
        }
    }
}
