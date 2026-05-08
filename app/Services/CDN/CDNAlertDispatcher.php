<?php

declare(strict_types=1);

namespace App\Services\CDN;

use App\Notifications\CDNAlertNotification;
use App\Settings\CDNSettings;
use App\Support\CDN\CDNRequestContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Alert dispatcher with escalation policy.
 *
 * Levels (low → high): info, warning, danger, critical
 *
 * Escalation matrix per level:
 *
 *   Level     | Log     | Email | Slack | Cooldown | Auto-escalate
 *   ----------|---------|-------|-------|----------|----------------
 *   info      | info    | no    | no    | 30 min   | no
 *   warning   | warning | yes   | no    | 15 min   | →danger after 3 in 30min
 *   danger    | error   | yes   | yes   |  5 min   | →critical after 3 in 30min
 *   critical  | crit    | yes   | yes   |  2 min   | repeats every cooldown
 *
 * Tracking is keyed by message hash so distinct alerts escalate independently.
 */
class CDNAlertDispatcher
{
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_DANGER = 'danger';
    public const LEVEL_CRITICAL = 'critical';

    private const POLICY = [
        self::LEVEL_INFO => [
            'log' => 'info', 'mail' => false, 'slack' => false,
            'cooldown' => 1800, 'escalate_to' => null, 'escalate_count' => 0,
        ],
        self::LEVEL_WARNING => [
            'log' => 'warning', 'mail' => true, 'slack' => false,
            'cooldown' => 900, 'escalate_to' => self::LEVEL_DANGER, 'escalate_count' => 3,
        ],
        self::LEVEL_DANGER => [
            'log' => 'error', 'mail' => true, 'slack' => true,
            'cooldown' => 300, 'escalate_to' => self::LEVEL_CRITICAL, 'escalate_count' => 3,
        ],
        self::LEVEL_CRITICAL => [
            'log' => 'critical', 'mail' => true, 'slack' => true,
            'cooldown' => 120, 'escalate_to' => null, 'escalate_count' => 0,
        ],
    ];

    /** Escalation tracking window */
    private const ESCALATION_WINDOW = 1800;

    private const COOLDOWN_PREFIX = 'cdn:alert:fired:';
    private const COUNT_PREFIX = 'cdn:alert:count:';

    /**
     * @param  array<int, array{level: string, message: string}>  $alerts
     */
    public static function dispatchMany(array $alerts): void
    {
        foreach ($alerts as $alert) {
            self::dispatch($alert['level'] ?? self::LEVEL_WARNING, $alert['message'] ?? '');
        }
    }

    public static function dispatch(string $level, string $message): void
    {
        if ($message === '') {
            return;
        }

        $level = self::normaliseLevel($level);
        $level = self::escalate($level, $message);

        $policy = self::POLICY[$level];
        $cooldownKey = self::COOLDOWN_PREFIX . md5("{$level}:{$message}");

        // Cache::add returns false if the key already exists → cooldown active
        if (! Cache::add($cooldownKey, true, $policy['cooldown'])) {
            return;
        }

        // Always log with the policy-mandated level
        Log::channel('cdn')->{$policy['log']}("[CDN-ALERT] {$message}", array_merge([
            'level' => $level,
        ], CDNRequestContext::logContext()));

        $recipients = self::recipients();

        if ($recipients === []) {
            return;
        }

        try {
            $mail = ($policy['mail'] && isset($recipients['mail'])) ? $recipients['mail'] : null;
            $slack = ($policy['slack'] && isset($recipients['slack'])) ? $recipients['slack'] : null;

            if ($mail === null && $slack === null) {
                return;
            }

            $route = $mail !== null
                ? Notification::route('mail', $mail)
                : Notification::route('slack', $slack);

            if ($mail !== null && $slack !== null) {
                $route = $route->route('slack', $slack);
            }

            $route->notify(new CDNAlertNotification($level, $message));
        } catch (\Throwable $e) {
            Log::channel('cdn')->error('[CDN] Failed to dispatch alert notification', array_merge([
                'error' => $e->getMessage(),
                'message' => $message,
            ], CDNRequestContext::logContext()));
        }
    }

    /**
     * If the same message fires N times within the escalation window,
     * promote it to the next level.
     */
    private static function escalate(string $level, string $message): string
    {
        $policy = self::POLICY[$level];

        if ($policy['escalate_to'] === null || $policy['escalate_count'] <= 0) {
            return $level;
        }

        $countKey = self::COUNT_PREFIX . md5("{$level}:{$message}");
        $count = (int) Cache::get($countKey, 0) + 1;
        Cache::put($countKey, $count, self::ESCALATION_WINDOW);

        if ($count >= $policy['escalate_count']) {
            Log::channel('cdn')->warning('[CDN-ALERT] Escalating', [
                'from' => $level,
                'to' => $policy['escalate_to'],
                'occurrences' => $count,
            ]);

            return $policy['escalate_to'];
        }

        return $level;
    }

    private static function normaliseLevel(string $level): string
    {
        return isset(self::POLICY[$level]) ? $level : self::LEVEL_WARNING;
    }

    /**
     * @return array{mail?: string|array, slack?: string}
     */
    private static function recipients(): array
    {
        $recipients = [];

        try {
            $settings = app(CDNSettings::class);
            $email = property_exists($settings, 'cdn_alert_email') ? (string) $settings->cdn_alert_email : '';
            $slack = property_exists($settings, 'cdn_alert_slack_webhook') ? (string) $settings->cdn_alert_slack_webhook : '';

            if ($email !== '') {
                $recipients['mail'] = $email;
            }

            if ($slack !== '') {
                $recipients['slack'] = $slack;
            }
        } catch (\Throwable) {
            // Settings not bound yet — fall through to env
        }

        if (! isset($recipients['mail']) && ($email = env('CDN_ALERT_EMAIL'))) {
            $recipients['mail'] = $email;
        }

        if (! isset($recipients['slack']) && ($slack = env('CDN_ALERT_SLACK_WEBHOOK'))) {
            $recipients['slack'] = $slack;
        }

        return $recipients;
    }
}
