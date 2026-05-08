<?php

declare(strict_types=1);

namespace App\Services\CDN;

use App\Support\CDN\CDNRequestContext;
use App\Support\CDN\CDNStats;
use App\Support\CDN\EnsuresSafeUrls;
use App\Support\CDN\FailureType;
use App\Support\CDN\GlobalRateLimiter;
use App\Support\CDN\IntelligentRetry;
use App\Support\Chaos\ChaosMonkey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CloudflareCDNService extends AbstractCDNService
{
    use EnsuresSafeUrls;

    private const API_BASE = 'https://api.cloudflare.com/client/v4';

    private const RATE_KEY = 'cdn:cloudflare:rate';

    private const COOLDOWN_KEY = 'cdn:cloudflare:cooldown';

    private const MAX_BATCH_SIZE = 100;

    /**
     * Finding #84: Explicit provider constant. 
     * Used by name() to provide the source of truth for metrics.
     */
    private const PROVIDER = 'cloudflare';

    private const DEFAULT_PLAN_CONFIG = [
        'batch_size' => 30,
        'max_requests' => 30,
        'window' => 10,
    ];

    private string $token;

    private string $zoneId;

    private string $plan;

    private array $planConfig;

    public function __construct()
    {
        parent::__construct();

        $this->token = (string) config('cdn.providers.cloudflare.token', '');
        $this->zoneId = (string) config('cdn.providers.cloudflare.zone_id', '');
        $this->plan = (string) config('cdn.providers.cloudflare.plan', 'free');
        
        $this->planConfig = (array) config("cdn.plans.cloudflare.{$this->plan}", self::DEFAULT_PLAN_CONFIG);
    }

    /**
     * Build a base HTTP client with token, timeouts, retry strategy AND
     * correlation headers for traceability across services.
     */
    private function http(bool $chaos = true): \Illuminate\Http\Client\PendingRequest
    {
        if ($chaos) {
            ChaosMonkey::injectLatency();

            if (ChaosMonkey::shouldTimeout()) {
                usleep(20_000_000);
            }
        }

        return Http::withToken($this->token)
            ->connectTimeout(5)
            ->timeout(10)
            /**
             * Finding #100: Simplified retry logic.
             * Since we handle HTTP status codes (like 429 and 500) manually 
             * and do NOT use ->throw(), the retry predicate only needs to 
             * handle transient network-level ConnectionExceptions.
             */
            ->retry(3, IntelligentRetry::backoff(), function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            })
            ->withHeaders([
                'X-CDN-Request-ID' => CDNRequestContext::id(),
                'X-CDN-Batch-ID' => CDNRequestContext::batchId(),
            ]);
    }

    /**
     * Dynamically adjust batch size based on recent network latency.
     */
    private function effectiveBatchSize(): int
    {
        $avgLatency = CDNStats::getAverageLatency($this->name());
        $baseBatch = (int) ($this->planConfig['batch_size'] ?? self::DEFAULT_PLAN_CONFIG['batch_size']);

        if ($avgLatency > 2000) {
            return max(5, (int) round($baseBatch * 0.5));
        }

        if ($avgLatency > 1000) {
            return max(5, (int) round($baseBatch * 0.7));
        }

        if ($avgLatency > 500) {
            return max(5, (int) round($baseBatch * 0.85));
        }

        return min($baseBatch, self::MAX_BATCH_SIZE);
    }

    public function purge(array $urls): bool
    {
        if (! $this->enabled() || $urls === []) {
            return false;
        }

        if (! $this->circuit()->isAvailable()) {
            Log::channel('cdn')->warning('[CDN] Circuit OPEN — skipping purge', ['count' => count($urls)]);

            return false;
        }

        $urls = array_filter(array_unique($urls), function ($url) {
            if (! is_string($url) || $url === '') {
                return false;
            }

            try {
                $this->assertSafeUrl($url);
                return true;
            } catch (\Throwable $e) {
                Log::channel('cdn')->warning('[CDN] SSRF guard blocked URL in purge', [
                    'url' => $this->sanitizeUrl($url),
                    'reason' => $e->getMessage()
                ]);
                return false;
            }
        });

        $urls = array_values($urls);

        if (empty($urls)) {
            return false;
        }

        $allOk = true;
        $batches = array_chunk($urls, $this->effectiveBatchSize());

        foreach ($batches as $batch) {
            $token = $this->acquireRateSlot();
            if ($token === null) {
                $allOk = false;
                continue;
            }

            try {
                CDNStats::recordOutboundAttempt($this->name());
                
                $start = microtime(true);

                $response = $this->http()
                    ->post(self::API_BASE . "/zones/{$this->zoneId}/purge_cache", [
                        'files' => $batch,
                    ]);

                CDNStats::recordLatency((microtime(true) - $start) * 1000, CDNStats::TYPE_OPERATIONAL, $this->name());

                if ($response->status() === 429) {
                    $this->recordRateLimitHit();
                    // recordFailure is handled centrally inside failWith()
                    $this->failWith(FailureType::TRANSIENT);
                    Log::channel('cdn')->warning('[CDN] Cloudflare 429 rate-limited', array_merge(['plan' => $this->plan], CDNRequestContext::logContext()));
                    $allOk = false;
                    continue;
                }

                if (! $response->json('success', false)) {
                    $this->failWith(FailureType::fromStatus($response->status()));
                    Log::channel('cdn')->warning('[CDN] Cloudflare purge failed', array_merge([
                        'urls' => array_map([$this, 'sanitizeUrl'], $batch),
                        'status' => $response->status(),
                        'errors' => $response->json('errors', []),
                    ], CDNRequestContext::logContext()));
                    $allOk = false;
                } else {
                    $this->recordSuccess();
                    CDNStats::recordPurge(urlCount: count($batch), provider: $this->name());
                    Log::channel('cdn')->info('[CDN] Cloudflare purge OK', array_merge(['count' => count($batch)], CDNRequestContext::logContext()));
                }
            } catch (\Throwable $e) {
                $this->failWith(FailureType::fromException($e));
                Log::channel('cdn')->error('[CDN] Cloudflare purge exception', array_merge([
                    'message' => $e->getMessage(),
                    'urls' => array_map([$this, 'sanitizeUrl'], $batch),
                ], CDNRequestContext::logContext()));
                $allOk = false;
            } finally {
                GlobalRateLimiter::release($token);
            }
        }

        return $allOk;
    }

    public function purgeAll(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if (! $this->circuit()->isAvailable()) {
            Log::channel('cdn')->warning('[CDN] Circuit OPEN — skipping purge-all');
            return false;
        }

        $token = $this->acquireRateSlot();
        if ($token === null) {
            Log::channel('cdn')->warning('[CDN] Rate limit reached (proactive), skipping purge-all', ['plan' => $this->plan]);
            return false;
        }

        try {
            CDNStats::recordOutboundAttempt($this->name());
            $start = microtime(true);
            
            $response = $this->http()
                ->post(self::API_BASE . "/zones/{$this->zoneId}/purge_cache", [
                    'purge_everything' => true,
                ]);

            CDNStats::recordLatency((microtime(true) - $start) * 1000, CDNStats::TYPE_OPERATIONAL, $this->name());

            if ($response->status() === 429) {
                $this->recordRateLimitHit();
                // recordFailure is handled centrally inside failWith()
                $this->failWith(FailureType::TRANSIENT);
                return false;
            }

            if ($response->json('success', false)) {
                $this->recordSuccess();
                CDNStats::recordPurge(provider: $this->name());
                Log::channel('cdn')->info('[CDN] Cloudflare purge-all OK', CDNRequestContext::logContext());

                return true;
            }

            // Finding #107: recordFailure is now handled centrally inside failWith()
            $this->failWith(FailureType::fromStatus($response->status()));

            return false;
        } catch (\Throwable $e) {
            // Finding #107: recordFailure is now handled centrally inside failWith()
            $this->failWith(FailureType::fromException($e));
            Log::channel('cdn')->error('[CDN] Cloudflare purge-all exception', array_merge(['message' => $e->getMessage()], CDNRequestContext::logContext()));

            return false;
        } finally {
            GlobalRateLimiter::release($token);
        }
    }

    public function purgeTags(array $tags): bool
    {
        if (! $this->enabled() || $tags === []) {
            return false;
        }

        if (! $this->circuit()->isAvailable()) {
            Log::channel('cdn')->warning('[CDN] Circuit OPEN — skipping tag purge', ['count' => count($tags)]);
            return false;
        }

        $tags = array_values(array_filter(
            array_unique($tags),
            fn ($tag) => is_string($tag)
                && trim((string)$tag) !== ''
                && mb_strlen((string)$tag, '8bit') <= 1024
        ));

        if (empty($tags)) {
            return true;
        }

        $allOk = true;
        $batches = array_chunk($tags, $this->effectiveBatchSize());

        foreach ($batches as $batch) {
            $token = $this->acquireRateSlot();
            if ($token === null) {
                $allOk = false;
                continue;
            }

            try {
                CDNStats::recordOutboundAttempt($this->name());
                $start = microtime(true);

                $response = $this->http()
                    ->post(self::API_BASE . "/zones/{$this->zoneId}/purge_cache", [
                        'tags' => $batch,
                    ]);

                CDNStats::recordLatency((microtime(true) - $start) * 1000, CDNStats::TYPE_OPERATIONAL, $this->name());

                if ($response->status() === 429) {
                    $this->recordRateLimitHit();
                    // recordFailure is handled centrally inside failWith()
                    $this->failWith(FailureType::TRANSIENT);
                    Log::channel('cdn')->warning('[CDN] Cloudflare tag purge 429 rate-limited', array_merge(['plan' => $this->plan], CDNRequestContext::logContext()));
                    $allOk = false;
                    continue;
                }

                if (! $response->json('success', false)) {
                    // Finding #107: recordFailure is now handled centrally inside failWith()
                    $this->failWith(FailureType::fromStatus($response->status()));
                    Log::channel('cdn')->warning('[CDN] Cloudflare tag purge failed', array_merge([
                        'tags_sha1' => array_map(fn ($t) => sha1((string) $t), $batch),
                        'status' => $response->status(),
                        'errors' => $response->json('errors', []),
                    ], CDNRequestContext::logContext()));
                    $allOk = false;
                } else {
                    $this->recordSuccess();
                    CDNStats::recordPurge(tagCount: count($batch), provider: $this->name());
                    Log::channel('cdn')->info('[CDN] Cloudflare tag purge OK', array_merge(['count' => count($batch)], CDNRequestContext::logContext()));
                }
            } catch (\Throwable $e) {
                // Finding #107: recordFailure is now handled centrally inside failWith()
                $this->failWith(FailureType::fromException($e));
                Log::channel('cdn')->error('[CDN] Cloudflare tag purge exception', array_merge([
                    'message' => $e->getMessage(),
                    'tags_sha1' => array_map(fn ($t) => sha1((string) $t), $batch),
                ], CDNRequestContext::logContext()));
                $allOk = false;
            } finally {
                GlobalRateLimiter::release($token);
            }
        }

        return $allOk;
    }

    public function testConnection(): array
    {
        if ($this->token === '' || $this->zoneId === '') {
            return ['ok' => false, 'message' => 'بيانات الإعدادات غير مكتملة'];
        }

        try {
            $start = microtime(true);
            
            $response = $this->http(chaos: false)
                ->get(self::API_BASE . "/zones/{$this->zoneId}");

            CDNStats::recordLatency((microtime(true) - $start) * 1000, CDNStats::TYPE_DIAGNOSTIC, $this->name());

            if ($response->json('success', false)) {
                $zoneName = $response->json('result.name', '');

                return ['ok' => true, 'message' => "متصل بنجاح — النطاق: {$zoneName}"];
            }

            // Finding #108 & #109: Diagnostic failures must not pollute metrics OR affect the circuit breaker.
            $this->failWith(
                FailureType::fromStatus($response->status()),
                recordTelemetry: false,
                affectCircuit: false
            );

            $errors = $response->json('errors', []);
            $code = $errors[0]['code'] ?? 0;
            
            $msg = match((int)$code) {
                6003 => 'بيانات الاتصال غير صالحة (Invalid Auth)',
                7003 => 'لم يتم العثور على النطاق (Zone Not Found)',
                default => 'فشل الاتصال بمزود الخدمة (تحقق من الإعدادات)',
            };

            return ['ok' => false, 'message' => $msg];
        } catch (\Throwable $e) {
            // Finding #108 & #109: Diagnostic failures must not pollute metrics OR affect the circuit breaker.
            $this->failWith(
                FailureType::fromException($e),
                recordTelemetry: false,
                affectCircuit: false
            );

            Log::channel('cdn')->error('[CDN] testConnection exception', array_merge([
                'message' => $e->getMessage()
            ], CDNRequestContext::logContext()));

            return ['ok' => false, 'message' => 'تعذر الاتصال بمزود الخدمة'];
        }
    }

    public function name(): string
    {
        return self::PROVIDER;
    }

    // ─── Proactive Rate Limiter ────────────────────────────────────────

    /**
     * Finding #88, #91, #96, #111, #112, #113, #114 & #115: Hybrid rate limiting strategy.
     * 
     * 1. GlobalRateLimiter: Acts as a concurrency guard (semaphore/token bucket). 
     *    Tokens are released after each batch to free up capacity for other workers.
     * 2. Local RateLimiter Facade: Acts as an atomic fixed-window request counter.
     * 3. Local Cooldown Key: Acts as an O(1) emergency block when a 429 is received.
     */
    private function acquireRateSlot(): ?string
    {
        $token = GlobalRateLimiter::acquire();

        if ($token === null) {
            Log::channel('cdn')->warning('[CDN] Global rate limiter saturated', CDNRequestContext::logContext());

            return null;
        }

        // Finding #115: Check for explicit cooldown block first (O(1)).
        $cooldownKey = self::COOLDOWN_KEY . ':' . $this->plan;
        if (Cache::has($cooldownKey)) {
            GlobalRateLimiter::release($token);
            Log::channel('cdn')->info('[CDN] Request blocked by active cooldown', ['plan' => $this->plan]);

            return null;
        }

        $key = self::RATE_KEY . ':' . $this->plan;
        $window = (int) ($this->planConfig['window'] ?? self::DEFAULT_PLAN_CONFIG['window']);
        $max = (int) ($this->planConfig['max_requests'] ?? self::DEFAULT_PLAN_CONFIG['max_requests']);

        $acquired = RateLimiter::attempt(
            $key,
            $max,
            fn () => true,
            $window
        );

        if (! $acquired) {
            GlobalRateLimiter::release($token);

            return null;
        }

        return $token;
    }

    /**
     * Finding #46: Clean helper to record successful operation and reset circuit breaker.
     */
    private function recordSuccess(): void
    {
        $this->lastFailureType = FailureType::UNKNOWN;
        $this->circuit()->recordSuccess();
    }

    /**
     * Finding #52, #94, #113 & #115: Efficient emergency block.
     */
    private function recordRateLimitHit(): void
    {
        $window = (int) ($this->planConfig['window'] ?? self::DEFAULT_PLAN_CONFIG['window']);
        
        // Finding #115: Use an O(1) cooldown key instead of O(N) loop-flooding.
        $cooldownKey = self::COOLDOWN_KEY . ':' . $this->plan;
        Cache::put($cooldownKey, true, $window);
        
        CDNStats::recordRateLimit($this->name());

        Log::channel('cdn')->warning('[CDN] Rate limit cooldown activated', [
            'plan' => $this->plan,
            'window_seconds' => $window,
        ]);
    }
}
