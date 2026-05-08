<?php

declare(strict_types=1);

if (! function_exists('cache_key')) {
    /**
     * Unified cache key prefixing for future multi-tenancy support.
     */
    function cache_key(string $key): string
    {
        if (! app()->bound('cache.keyPrefix')) {
            return $key;
        }

        $prefix = app('cache.keyPrefix');

        if (is_callable($prefix)) {
            $prefix = $prefix();
        }

        $prefix = trim((string) $prefix, ':');

        return $prefix !== '' ? "{$prefix}:{$key}" : $key;
    }
}

if (! function_exists('social_display_links')) {
    /**
     * Get only active social platforms for display.
     */
    function social_display_links(array $social): array
    {
        $out = [];
        $fb = trim((string) ($social['facebook'] ?? ''));
        $fbid = trim((string) ($social['fb_page_id'] ?? ''));
        if ($fb !== '') {
            $out['facebook'] = $fb;
        } elseif ($fbid !== '') {
            $out['facebook'] = (str_starts_with($fbid, 'http://') || str_starts_with($fbid, 'https://'))
                ? $fbid
                : 'https://www.facebook.com/'.ltrim($fbid, '/');
        }
        foreach (['twitter', 'instagram', 'linkedin', 'youtube', 'tiktok', 'snapchat'] as $k) {
            $v = trim((string) ($social[$k] ?? ''));
            if ($v !== '') {
                $out[$k] = $v;
            }
        }
        $wa = trim((string) ($social['whatsapp_number'] ?? ''));
        if ($wa !== '') {
            if (str_starts_with($wa, 'http://') || str_starts_with($wa, 'https://')) {
                $out['whatsapp_number'] = $wa;
            } else {
                $digits = preg_replace('/\D+/', '', $wa);
                if ($digits !== '') {
                    $out['whatsapp_number'] = 'https://wa.me/'.$digits;
                }
            }
        }
        $wac = trim((string) ($social['whatsapp_channel'] ?? ''));
        if ($wac !== '') {
            $out['whatsapp_channel'] = $wac;
        }

        return $out;
    }
}
