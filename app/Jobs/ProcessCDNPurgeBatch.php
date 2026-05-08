<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\CDN\CDNManager;
use App\Services\CDN\CDNPurgeBuffer;
use App\Support\CDN\CDNQueues;
use App\Support\CDN\CDNRequestContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Drains the buffer for a given priority lane and pushes purges to the CDN.
 *
 * Triggered by:
 *   - CDNPurgeBuffer::add() when high priority or threshold hit
 *   - Scheduler (every minute) for normal priority
 *
 * Carries the caller's request_id / trace_id so log entries from the
 * worker can be correlated with the originating HTTP request.
 */
class ProcessCDNPurgeBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public int $timeout = 60;

    public ?string $requestId;

    public ?string $traceId;

    public function __construct(
        public string $priority = CDNPurgeBuffer::PRIORITY_NORMAL,
    ) {
        $this->onQueue(CDNPurgeBuffer::queueFor($priority));

        // Capture caller's correlation ids so the worker can re-establish them
        $this->requestId = CDNRequestContext::requestId();
        $this->traceId = CDNRequestContext::traceId();
    }

    public function handle(CDNPurgeBuffer $buffer, CDNManager $cdn): void
    {
        // Re-establish correlation in the worker process
        CDNRequestContext::set($this->requestId, $this->traceId);

        try {
            $payload = $buffer->flush($this->priority);

            $urls = $payload['urls'] ?? [];
            $tags = $payload['tags'] ?? [];
            $warmUp = $payload['warmUpUrls'] ?? [];

            if ($urls === [] && $tags === [] && $warmUp === []) {
                return;
            }

            Log::channel('cdn')->info('[CDN] Processing purge batch', array_merge([
                'priority' => $this->priority,
                'urls' => count($urls),
                'tags' => count($tags),
                'warmup' => count($warmUp),
            ], CDNRequestContext::logContext()));

            $provider = $cdn->provider();

            if ($tags !== []) {
                $provider->purgeTags($tags);
            }

            if ($urls !== []) {
                $provider->purge($urls);
            }

            // Warmup runs on isolated queue so it can't starve future purges.
            if ($warmUp !== []) {
                WarmUpCDN::dispatch($warmUp)
                    ->onQueue(CDNQueues::WARMUP)
                    ->delay(now()->addSeconds(5));
            }
        } finally {
            CDNRequestContext::reset();
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('cdn')->error('[CDN] ProcessCDNPurgeBatch failed permanently', array_merge([
            'priority' => $this->priority,
            'error' => $e->getMessage(),
        ], CDNRequestContext::logContext()));
    }
}
