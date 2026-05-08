<?php

declare(strict_types=1);

namespace App\Services\CDN;

use App\Contracts\CDNInterface;

/**
 * Null CDN provider — used when CDN is disabled.
 * All operations are no-ops.
 */
class NullCDNService implements CDNInterface
{
    public function enabled(): bool
    {
        return false;
    }

    public function purge(array $urls): bool
    {
        return true;
    }

    public function purgeAll(): bool
    {
        return true;
    }

    public function purgeTags(array $tags): bool
    {
        return true;
    }

    public function testConnection(): array
    {
        return ['ok' => false, 'message' => 'CDN غير مفعّل'];
    }

    public function name(): string
    {
        return 'null';
    }
}
