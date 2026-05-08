<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case BreakingNews = 'breaking_news';
    case DailyDigest = 'daily_digest';
    case PersonalizedFeeds = 'personalized_feeds';
    case SecurityAlerts = 'security_alerts';
    case AccountUpdates = 'account_updates';
    case Newsletter = 'newsletter';
}
