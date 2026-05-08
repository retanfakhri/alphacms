<?php

declare(strict_types=1);

namespace App\Support\CDN;

use Illuminate\Support\Str;

/**
 * Per-process correlation context for CDN operations.
 *
 * A correlation ID is generated once per HTTP request / queued job and
 * propagated through:
 *   - Outgoing CDN API requests   (X-Request-ID, X-Trace-ID headers)
 *   - All Log::channel('cdn') entries (via Log::withContext)
 *   - Queued jobs (constructor parameter so worker inherits the id)
 *
 * This makes it possible to grep logs for one news article's purge across
 * the listener → buffer → job → API hop chain.
 */
final class CDNRequestContext
{
    private static ?string $requestId = null;
    private static ?string $traceId = null;

    /**
     * Get (or lazily generate) the current request id.
     */
    public static function requestId(): string
    {
        return self::$requestId ??= (string) Str::uuid();
    }

    /**
     * Get (or lazily generate) the current trace id.
     * Trace id is meant to span multiple correlated requests (e.g.
     * news save → purge → warmup all share one trace).
     */
    public static function traceId(): string
    {
        return self::$traceId ??= substr(bin2hex(random_bytes(8)), 0, 16);
    }

    /**
     * Override (used by queued jobs to inherit caller's ids).
     */
    public static function set(?string $requestId, ?string $traceId): void
    {
        self::$requestId = $requestId;
        self::$traceId = $traceId;
    }

    /**
     * Reset (used between queued jobs).
     */
    public static function reset(): void
    {
        self::$requestId = null;
        self::$traceId = null;
    }

    /**
     * @return array{X-Request-ID: string, X-Trace-ID: string}
     */
    public static function httpHeaders(): array
    {
        return [
            'X-Request-ID' => self::requestId(),
            'X-Trace-ID' => self::traceId(),
        ];
    }

    /**
     * @return array{request_id: string, trace_id: string}
     */
    public static function logContext(): array
    {
        return [
            'request_id' => self::requestId(),
            'trace_id' => self::traceId(),
        ];
    }
}
