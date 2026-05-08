<?php

declare(strict_types=1);

namespace App\Support\CDN;

/**
 * Categorises CDN API failures so callers (failover, circuit breaker,
 * retry policy) can react appropriately.
 *
 * - TRANSIENT  → retry / failover is worthwhile (network blip, 5xx, 429, timeout)
 * - PERMANENT  → retry / failover is pointless (auth error, malformed request, 404 zone)
 * - UNKNOWN    → no information; treat as transient by default
 */
enum FailureType: string
{
    case TRANSIENT = 'transient';
    case PERMANENT = 'permanent';
    case UNKNOWN = 'unknown';

    public function shouldFailover(): bool
    {
        return $this === self::TRANSIENT || $this === self::UNKNOWN;
    }

    public function shouldOpenCircuit(): bool
    {
        // Permanent errors (bad token, missing zone) are config issues —
        // tripping the breaker won't help. Only count transient failures.
        return $this === self::TRANSIENT;
    }

    /**
     * Classify an HTTP status code.
     */
    public static function fromStatus(int $status): self
    {
        if ($status === 0) {
            return self::TRANSIENT; // network failure
        }

        if ($status === 429 || $status >= 500) {
            return self::TRANSIENT;
        }

        if ($status === 401 || $status === 403 || $status === 404 || $status === 400) {
            return self::PERMANENT;
        }

        return self::UNKNOWN;
    }

    /**
     * Classify an exception (network/timeout → transient, others → unknown).
     */
    public static function fromException(\Throwable $e): self
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'timeout')
            || str_contains($message, 'timed out')
            || str_contains($message, 'connection')
            || str_contains($message, 'could not resolve')
            || str_contains($message, 'cURL error')) {
            return self::TRANSIENT;
        }

        return self::UNKNOWN;
    }
}
