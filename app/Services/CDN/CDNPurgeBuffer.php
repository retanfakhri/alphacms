<?php

declare(strict_types=1);

namespace App\Services\CDN;

use App\Support\CDN\CdnCacheTagNormalizer;
use App\Support\CDN\CDNQueues;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Redis-backed buffer that aggregates purge URLs & tags across events.
 * A scheduled job flushes the buffer periodically → single API call.
 *
 * Supports priority lanes:
 *   - high   → critical purges (homepage, breaking news) → cdn-high queue
 *   - normal → standard purges (regular updates)         → cdn-low queue
 *
 * Flow: Event → Listener → Buffer (Redis, lane) → Batch Job → CDN API
 */
class CDNPurgeBuffer
{
    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_NORMAL = 'normal';

    private const PRIORITIES = [self::PRIORITY_HIGH, self::PRIORITY_NORMAL];

    private const QUEUE_MAP = [
        self::PRIORITY_HIGH => CDNQueues::PURGE_HIGH,
        self::PRIORITY_NORMAL => CDNQueues::PURGE_NORMAL,
    ];

    private const BASE_KEY = 'cdn:buffer';

    private const LOCK_KEY = 'cdn:buffer:lock';

    private const TTL_NORMAL = 30;

    private const TTL_HIGH = 10;

    private const HIGH_TRAFFIC_THRESHOLD = 30;

    private const FLUSH_THRESHOLD = 50;

    private const MAX_BUFFER_SIZE = 500;

    private const TYPE_TAGS = ['type:news', 'type:articles', 'type:videos', 'type:live-streams', 'type:ads', 'type:pages', 'type:categories', 'type:feeds'];

    /**
     * Add URLs and tags to the buffer in the given priority lane.
     */
    public function add(array $urls, array $tags, array $warmUpUrls = [], string $priority = self::PRIORITY_NORMAL): void
    {
        $priority = in_array($priority, self::PRIORITIES, true) ? $priority : self::PRIORITY_NORMAL;
        $cacheKey = $this->keyFor($priority);
        $lockKey = self::LOCK_KEY . ':' . $priority;

        $typeTagsForCheck = CdnCacheTagNormalizer::normalize(self::TYPE_TAGS);

        Cache::lock($lockKey, 2)->block(1, function () use ($urls, $tags, $warmUpUrls, $cacheKey, $priority, $typeTagsForCheck) {
            $data = Cache::get($cacheKey, $this->emptyBuffer());

            $currentSize = count($data['urls']) + count($data['tags']);

            if ($currentSize >= self::MAX_BUFFER_SIZE) {
                Log::channel('cdn')->warning('[CDN] Buffer overflow — force flushing', [
                    'priority' => $priority,
                    'size' => $currentSize,
                ]);
                Cache::forget($cacheKey);
                $this->dispatchProcessJob($priority);
                $data = $this->emptyBuffer();
            }

            $data['tags'] = CdnCacheTagNormalizer::normalize(array_merge($data['tags'], $tags));

            $hasTypeTags = array_intersect($data['tags'], $typeTagsForCheck) !== [];

            if (! $hasTypeTags) {
                $urlMap = array_fill_keys($data['urls'], true);
                foreach ($urls as $url) {
                    $urlMap[$url] = true;
                }
                $data['urls'] = array_keys($urlMap);
                unset($urlMap);
            }

            if ($warmUpUrls !== []) {
                $warmMap = array_fill_keys($data['warmUpUrls'], true);
                foreach ($warmUpUrls as $wu) {
                    $warmMap[$wu] = true;
                }
                $data['warmUpUrls'] = array_keys($warmMap);
                unset($warmMap);
            }

            $ttl = count($data['urls']) + count($data['tags']) >= self::HIGH_TRAFFIC_THRESHOLD
                ? self::TTL_HIGH
                : self::TTL_NORMAL;

            Cache::put($cacheKey, $data, $ttl);
        });

        // High priority dispatches immediately; normal waits for scheduler unless threshold hit.
        if ($priority === self::PRIORITY_HIGH || $this->shouldFlushNow($priority)) {
            $this->dispatchProcessJob($priority);
        }
    }

    /**
     * Convenience: add to high-priority lane (dispatches immediately).
     */
    public function addHighPriority(array $urls, array $tags, array $warmUpUrls = []): void
    {
        $this->add($urls, $tags, $warmUpUrls, self::PRIORITY_HIGH);
    }

    /**
     * Flush a specific priority lane and return aggregated data.
     *
     * @return array{urls: array, tags: array, warmUpUrls: array}
     */
    public function flush(string $priority = self::PRIORITY_NORMAL): array
    {
        $priority = in_array($priority, self::PRIORITIES, true) ? $priority : self::PRIORITY_NORMAL;
        $cacheKey = $this->keyFor($priority);
        $lockKey = self::LOCK_KEY . ':' . $priority;
        $runningKey = 'cdn:flush:running:' . $priority;

        if (! Cache::add($runningKey, true, 5)) {
            Log::channel('cdn')->debug('[CDN] Flush already running, skipping', ['priority' => $priority]);

            return $this->emptyBuffer();
        }

        try {
            $lock = Cache::lock($lockKey, 2);

            if ($lock->get()) {
                try {
                    return Cache::pull($cacheKey, $this->emptyBuffer());
                } finally {
                    $lock->release();
                }
            }

            Log::channel('cdn')->debug('[CDN] Buffer flush lock unavailable, reading without lock', ['priority' => $priority]);

            return Cache::pull($cacheKey, $this->emptyBuffer());
        } finally {
            Cache::forget($runningKey);
        }
    }

    public function size(string $priority = self::PRIORITY_NORMAL): int
    {
        $data = Cache::get($this->keyFor($priority), $this->emptyBuffer());

        return count($data['urls']) + count($data['tags']);
    }

    public function totalSize(): int
    {
        return $this->size(self::PRIORITY_HIGH) + $this->size(self::PRIORITY_NORMAL);
    }

    public function shouldFlushNow(string $priority = self::PRIORITY_NORMAL): bool
    {
        return $this->size($priority) >= self::FLUSH_THRESHOLD;
    }

    public function isEmpty(?string $priority = null): bool
    {
        if ($priority !== null) {
            return $this->size($priority) === 0;
        }

        return $this->totalSize() === 0;
    }

    public function estimatedCost(string $priority = self::PRIORITY_NORMAL): int
    {
        $data = Cache::get($this->keyFor($priority), $this->emptyBuffer());

        return count($data['tags']) * 5 + count($data['urls']);
    }

    public static function queueFor(string $priority): string
    {
        return self::QUEUE_MAP[$priority] ?? self::QUEUE_MAP[self::PRIORITY_NORMAL];
    }

    private function dispatchProcessJob(string $priority): void
    {
        dispatch(new \App\Jobs\ProcessCDNPurgeBatch($priority))
            ->onQueue(self::queueFor($priority));
    }

    private function keyFor(string $priority): string
    {
        return self::BASE_KEY . ':' . $priority;
    }

    private function emptyBuffer(): array
    {
        return ['urls' => [], 'tags' => [], 'warmUpUrls' => []];
    }
}
