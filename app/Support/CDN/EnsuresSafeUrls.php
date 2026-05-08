<?php

declare(strict_types=1);

namespace App\Support\CDN;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

trait EnsuresSafeUrls
{
    /**
     * Assert that a URL is safe for internal requests (Warmup/Purge).
     * Prevents SSRF by blocking private ranges, reserved ranges, and non-http schemes.
     *
     * @throws InvalidArgumentException
     */
    protected function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException("Invalid URL");
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new InvalidArgumentException("Unsupported scheme: {$parts['scheme']}");
        }

        $host = strtolower($parts['host']);

        // Finding #11, #14 & #23: Strict host allowlist.
        $appUrl = config('app.url');
        $allowedHost = parse_url((string) $appUrl, PHP_URL_HOST);

        if (! is_string($allowedHost) || $allowedHost === '') {
            throw new InvalidArgumentException('Invalid APP_URL configuration detected.');
        }

        $allowedHost = strtolower($allowedHost);

        if ($host !== $allowedHost) {
            throw new InvalidArgumentException('Host not allowed');
        }

        // Basic host validation
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            throw new InvalidArgumentException("Localhost targets forbidden");
        }

        // DNS Rebinding Defense: Resolve ALL records (A and AAAA)
        $ips = [];

        // Check A records
        $aRecords = @dns_get_record($host, DNS_A);
        if ($aRecords === false) {
            Log::channel('cdn')->debug('[CDN] DNS A record resolution failed', ['host' => $host]);
        }
        foreach ($aRecords ?: [] as $record) {
            $ips[] = $record['ip'];
        }

        // Check AAAA records
        $aaaaRecords = @dns_get_record($host, DNS_AAAA);
        if ($aaaaRecords === false) {
            Log::channel('cdn')->debug('[CDN] DNS AAAA record resolution failed', ['host' => $host]);
        }
        foreach ($aaaaRecords ?: [] as $record) {
            $ips[] = $record['ipv6'];
        }

        if (empty($ips)) {
            $ip = gethostbyname($host);
            if ($ip !== $host) {
                $ips[] = $ip;
            }
        }

        if (empty($ips)) {
            throw new InvalidArgumentException("Could not resolve host");
        }

        foreach ($ips as $ip) {
            if (
                filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            ) {
                throw new InvalidArgumentException("Private or internal network targets forbidden");
            }
        }
    }

    /**
     * Finding #13: Sanitize URL for logging to prevent sensitive query param exposure.
     */
    protected function sanitizeUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return 'invalid-url';
        }

        $sanitized = $parts['scheme'] . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $sanitized .= ':' . $parts['port'];
        }

        if (isset($parts['path'])) {
            $sanitized .= $parts['path'];
        }

        return $sanitized;
    }
}
