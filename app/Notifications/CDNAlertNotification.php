<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class CDNAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $level,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->routeNotificationFor('mail')) {
            $channels[] = 'mail';
        }

        if ($notifiable->routeNotificationFor('slack')) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isDanger = $this->level === 'danger' || $this->level === 'critical';
        $appName = config('app.name');

        return (new MailMessage())
            ->subject(($isDanger ? '[CRITICAL] ' : '[WARNING] ') . "CDN Alert — {$appName}")
            ->greeting($isDanger ? 'CDN Critical Alert' : 'CDN Warning')
            ->line($this->message)
            ->line('Time: ' . now()->toDateTimeString())
            ->action('Open CDN Dashboard', url('/admin/settings'));
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $color = ($this->level === 'danger' || $this->level === 'critical') ? 'danger' : 'warning';

        return (new SlackMessage())
            ->{$color}()
            ->content("*[CDN " . strtoupper($this->level) . "]* {$this->message}");
    }
}
