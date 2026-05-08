<?php

declare(strict_types=1);

namespace App\Support\CDN;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class CDNSignedUrl
{
    /**
     * Finding #5: Hardened Signed URL Implementation.
     * Uses HMAC SHA256 with path binding, timestamp, and a secure salt 
     * to prevent predictability and forgery.
     */
    public static function sign(string $path, int $ttlSeconds = 3600): string
    {
        $expires = Carbon::now()->addSeconds($ttlSeconds)->timestamp;
        $key = (string) Config::get('app.key');
        
        /**
         * We bind the hash to the path and expiry timestamp.
         * Using HMAC-SHA256 as recommended.
         */
        $signature = hash_hmac('sha256', "{$path}|{$expires}", $key);

        $separator = str_contains($path, '?') ? '&' : '?';

        return "{$path}{$separator}expires={$expires}&signature={$signature}";
    }

    /**
     * Verify the integrity and expiration of a signed URL.
     */
    public static function verify(string $path, int $expires, string $signature): bool
    {
        if (time() > $expires) {
            return false;
        }

        $key = (string) Config::get('app.key');
        $expected = hash_hmac('sha256', "{$path}|{$expires}", $key);

        return hash_equals($expected, $signature);
    }
}
