<?php

declare(strict_types=1);

namespace App\Contracts;

interface CDNInterface
{
    public function enabled(): bool;

    public function purge(array $urls): bool;

    public function purgeAll(): bool;

    public function purgeTags(array $tags): bool;

    public function testConnection(): array;

    public function name(): string;
}
