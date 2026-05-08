<?php

declare(strict_types=1);

namespace App\Support\CDN;

class CDNUrlNormalizer
{
    /**
     * Finding #2: URL Normalization to prevent Cache Poisoning and 
     * Cache Fragmentation (Origin Shielding).
     * 
     * - Removes common tracking parameters (utm_*, gclid, etc.)
     * - Sorts remaining query parameters alphabetically.
     * - Ensures consistent casing for paths.
     */
    public static function normalize(string $url): string
    {
        $parts = parse_url($url);
        if (! $parts || ! isset($parts['path'])) {
            return $url;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = strtolower($parts['path']); // Normalize path case
        
        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            
            // Remove junk/tracking params that pollute cache
            $junk = [
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'gclid', 'fbclid', '_ga', '_ke', 'mc_cid', 'mc_eid'
            ];
            
            foreach ($junk as $param) {
                unset($query[$param]);
            }
            
            // Sort keys to ensure /path?a=1&b=2 is the same as /path?b=2&a=1
            ksort($query);
        }

        $queryString = ! empty($query) ? '?' . http_build_query($query) : '';

        return "{$scheme}{$host}{$port}{$path}{$queryString}";
    }
}
