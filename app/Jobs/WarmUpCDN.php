<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\CDN\CDNQueues;
use App\Support\CDN\CDNRequestContext;
use App\Support\CDN\IntelligentRetry;
use App\Support\CDN\WarmupThrottle;
use App\Support\CDN\EnsuresSafeUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Warms up CDN edge caches by issuing GET requests after a purge.
 * Runs on its OWN bulkhead queue (cdn-warmup) so it cannot starve purges.
 *
 * Per-host throttle protects origin during cache-miss storms — when the
 * throttle is hit, deferred URLs are re-dispatched with a delay instead
 * of being dropped.
 */
class WarmUpCDN implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, EnsuresSafeUrls;

    public int $tries = 2;

    public int $timeout = 90;

    /** Concurrent warmup requests per pool batch */
    private const CHUNK_SIZE = 10;

    public ?string $requestId;

    public ?string $traceId;

    /**
     * @param  array<int, string>  $urls
     */
    public function __construct(public array $urls)
    {
        $this->onQueue(CDNQueues::WARMUP);
        $this->requestId = CDNRequestContext::requestId();
        $this->traceId = CDNRequestContext::traceId();
    }

    public function handle(WarmupThrottle $throttle): void
    {
        CDNRequestContext::set($this->requestId, $this->traceId);

        try {
            $urls = array_values(array_unique(array_filter($this->urls)));

            if ($urls === []) {
                return;
            }

            // Split into allowed (under throttle) vs deferred (host saturated)
            $allowed = [];
            $deferred = [];
            $maxDelay = 0;

            foreach ($urls as $url) {
                try {
                    $this->assertSafeUrl($url);

                    if ($throttle->allow($url)) {
                        $allowed[] = $url;
                    } else {
                        $deferred[] = $url;
                        $maxDelay = max($maxDelay, $throttle->suggestedDelay($url));
                    }
                } catch (\InvalidArgumentException $e) {
                    Log::channel('cdn')->warning('[CDN] Warmup skipped unsafe URL', [
                        'url' => $this->sanitizeUrl($url),
                        'reason' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            if ($deferred !== []) {
                Log::channel('cdn')->info('[CDN] Warmup deferring throttled URLs', array_merge([
                    'deferred' => count($deferred),
                    'allowed' => count($allowed),
                    'delay_seconds' => $maxDelay,
                ], CDNRequestContext::logContext()));

                self::dispatch($deferred)
                    ->onQueue(CDNQueues::WARMUP)
                    ->delay(now()->addSeconds(max(5, $maxDelay)));
            }

            if ($allowed === []) {
                return;
            }

            Log::channel('cdn')->info('[CDN] Warmup starting', array_merge([
                'count' => count($allowed),
            ], CDNRequestContext::logContext()));

            $ok = 0;
            $fail = 0;

            foreach (array_chunk($allowed, self::CHUNK_SIZE) as $chunk) {
                $responses = Http::pool(function ($pool) use ($chunk) {
                    $requests = [];

                    foreach ($chunk as $url) {
                        $requests[] = $pool
                            ->withHeaders(array_merge(
                                ['User-Agent' => 'CDN-Warmup/1.0'],
                                CDNRequestContext::httpHeaders(),
                            ))
                            ->connectTimeout(5)
                            ->timeout(15)
                            ->withoutRedirecting()
                            ->retry(2, IntelligentRetry::backoff())
                            ->get($url);
                    }

                    return $requests;
                });

                foreach ($responses as $response) {
                    if ($response instanceof \Throwable) {
                        $fail++;

                        continue;
                    }

                    if (method_exists($response, 'successful') && $response->successful()) {
                        $ok++;
                    } else {
                        $fail++;
                    }
                }
            }

            Log::channel('cdn')->info('[CDN] Warmup complete', array_merge([
                'ok' => $ok,
                'fail' => $fail,
                'total' => count($allowed),
            ], CDNRequestContext::logContext()));
        } finally {
            CDNRequestContext::reset();
        }
    }
}
