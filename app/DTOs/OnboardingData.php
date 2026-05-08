<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\PreferredTopic;
use App\Enums\NotificationType;

readonly class OnboardingData
{
    /**
     * @param array<string, mixed> $readingPreferences
     * @param PreferredTopic[] $preferredTopics
     * @param NotificationType[] $notificationPreferences
     */
    public function __construct(
        public array $readingPreferences = [],
        public ?string $preferredLocale = null,
        public array $preferredTopics = [],
        public array $notificationPreferences = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            readingPreferences: $data['reading_preferences'] ?? [],
            preferredLocale: $data['preferred_locale'] ?? null,
            preferredTopics: array_values(array_filter(array_map(fn($topic) => PreferredTopic::tryFrom($topic), $data['preferred_topics'] ?? []))),
            notificationPreferences: array_values(array_filter(array_map(fn($type) => NotificationType::tryFrom($type), $data['notification_preferences'] ?? []))),
        );
    }
}
