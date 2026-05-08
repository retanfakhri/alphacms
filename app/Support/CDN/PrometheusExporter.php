<?php

declare(strict_types=1);

namespace App\Support\CDN;

use App\Services\CDN\CDNCircuitBreaker;
use App\Services\CDN\CDNPurgeBuffer;
use App\Services\CDN\CDNStats;
use App\Services\SettingsManagerService;
use App\Support\Metrics\HlsMetrics;

/**
 * Renders CDN metrics in the Prometheus text exposition format
 * (https://prometheus.io/docs/instrumenting/exposition_formats/).
 *
 * Mounted at GET /metrics/cdn — scrape interval 15-60s recommended.
 *
 * Metrics emitted:
 *   cdn_purges_total{provider}                   counter
 *   cdn_purge_failures_total{provider}           counter
 *   cdn_urls_purged_total{provider}              counter
 *   cdn_tags_purged_total{provider}              counter
 *   cdn_rate_limits_total{provider}              counter
 *   cdn_success_rate{provider,window}            gauge   (0-100)
 *   cdn_latency_avg_ms{provider,window}          gauge
 *   cdn_latency_p95_ms{provider,window}          gauge
 *   cdn_latency_p99_ms{provider,window}          gauge
 *   cdn_buffer_size{priority}                    gauge
 *   cdn_circuit_state{provider}                  gauge   (0=closed,1=half-open,2=open)
 *   cdn_circuit_failures{provider}               gauge
 *   cdn_global_rate_used                         gauge   (0-100 %)
 *
 * HLS metrics:
 *   hls_requests_total{result}                   counter (result=hit/not_found/...)
 *   hls_requests_by_kind_total{kind}             counter (kind=playlist/segment/offload)
 *   hls_latency_ms{label,quantile}               gauge   (label=playlist/segment/offload/error, q=0.5/0.95/0.99)
 *   hls_latency_avg_ms{label}                    gauge
 */
final class PrometheusExporter
{
    private const CIRCUIT_STATE_VALUES = [
        'closed' => 0,
        'half-open' => 1,
        'open' => 2,
    ];

    public function render(): string
    {
        $providers = $this->knownProviders();
        $output = [];

        // Counters / gauges per provider
        foreach ($providers as $provider) {
            $hour = CDNStats::current($provider);
            $day = CDNStats::summary(24, $provider);

            $output[] = $this->counter('cdn_purges_total', 'Total CDN purge requests', $hour['purges_1h'], ['provider' => $provider, 'window' => '1h']);
            $output[] = $this->counter('cdn_purge_failures_total', 'Total CDN purge failures', $hour['failures_1h'], ['provider' => $provider, 'window' => '1h']);
            $output[] = $this->counter('cdn_urls_purged_total', 'Total URLs purged', (int) ($day['urls'] ?? 0), ['provider' => $provider, 'window' => '24h']);
            $output[] = $this->counter('cdn_tags_purged_total', 'Total tags purged', (int) ($day['tags'] ?? 0), ['provider' => $provider, 'window' => '24h']);
            $output[] = $this->counter('cdn_rate_limits_total', 'Total rate-limit hits', $hour['rate_limits_1h'], ['provider' => $provider, 'window' => '1h']);

            $output[] = $this->gauge('cdn_success_rate', 'CDN purge success rate (%)', $hour['success_rate'], ['provider' => $provider, 'window' => '1h']);
            $output[] = $this->gauge('cdn_latency_avg_ms', 'CDN average latency in ms', $hour['avg_latency_ms'], ['provider' => $provider, 'window' => '1h']);
            $output[] = $this->gauge('cdn_latency_p95_ms', 'CDN p95 latency in ms', $hour['p95_ms'], ['provider' => $provider, 'window' => '1h']);
            $output[] = $this->gauge('cdn_latency_p99_ms', 'CDN p99 latency in ms', $hour['p99_ms'], ['provider' => $provider, 'window' => '1h']);

            // Circuit breaker per provider
            $cb = app(CDNCircuitBreaker::class)->forProvider($provider)->status();
            $stateValue = self::CIRCUIT_STATE_VALUES[$cb['state']] ?? 0;
            $output[] = $this->gauge('cdn_circuit_state', 'Circuit state (0=closed,1=half-open,2=open)', $stateValue, ['provider' => $provider]);
            $output[] = $this->gauge('cdn_circuit_failures', 'Current circuit failure count', $cb['failures'], ['provider' => $provider]);
        }

        // Buffer sizes per priority lane
        $buffer = app(CDNPurgeBuffer::class);
        $output[] = $this->gauge('cdn_buffer_size', 'CDN buffer size by priority', $buffer->size(CDNPurgeBuffer::PRIORITY_HIGH), ['priority' => 'high']);
        $output[] = $this->gauge('cdn_buffer_size', 'CDN buffer size by priority', $buffer->size(CDNPurgeBuffer::PRIORITY_NORMAL), ['priority' => 'normal']);

        // Global rate limiter usage
        $output[] = $this->gauge('cdn_global_rate_used', 'Global cross-provider rate limiter usage (%)', GlobalRateLimiter::usagePercent(), []);

        // HLS streaming metrics (نافذة ساعة واحدة).
        foreach ($this->hlsCounters() as [$metric, $help, $value, $labels]) {
            $output[] = $this->counter($metric, $help, $value, $labels);
        }

        foreach ($this->hlsLatencyGauges() as [$metric, $help, $value, $labels]) {
            $output[] = $this->gauge($metric, $help, $value, $labels);
        }

        return implode("\n", $output) . "\n";
    }

    /**
     * @return array<int, array{0:string,1:string,2:int|float,3:array<string,string>}>
     */
    private function hlsCounters(): array
    {
        $resultEvents = [
            HlsMetrics::EVENT_HIT,
            HlsMetrics::EVENT_NOT_FOUND,
            HlsMetrics::EVENT_SIGNATURE_FAIL,
            HlsMetrics::EVENT_EXPIRED,
            HlsMetrics::EVENT_RATE_LIMITED,
            HlsMetrics::EVENT_PATH_ESCAPE,
            HlsMetrics::EVENT_NOT_READY,
        ];

        $out = [];
        foreach ($resultEvents as $event) {
            $out[] = [
                'hls_requests_total',
                'HLS requests by result (1h window)',
                HlsMetrics::sum($event, 60),
                ['result' => $event, 'window' => '1h'],
            ];
        }

        $kindEvents = [
            HlsMetrics::EVENT_PLAYLIST,
            HlsMetrics::EVENT_SEGMENT,
            HlsMetrics::EVENT_OFFLOAD,
        ];

        foreach ($kindEvents as $event) {
            $out[] = [
                'hls_requests_by_kind_total',
                'HLS requests by content kind (1h window)',
                HlsMetrics::sum($event, 60),
                ['kind' => $event, 'window' => '1h'],
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{0:string,1:string,2:int|float,3:array<string,string>}>
     */
    private function hlsLatencyGauges(): array
    {
        $labels = ['playlist', 'segment', 'offload', 'error'];
        $out = [];

        foreach ($labels as $label) {
            $out[] = ['hls_latency_avg_ms', 'HLS response avg latency (ms)', HlsMetrics::latencyAvg($label), ['label' => $label]];
            $out[] = ['hls_latency_ms', 'HLS response latency quantile (ms)', HlsMetrics::latencyPercentile(50, $label), ['label' => $label, 'quantile' => '0.5']];
            $out[] = ['hls_latency_ms', 'HLS response latency quantile (ms)', HlsMetrics::latencyPercentile(95, $label), ['label' => $label, 'quantile' => '0.95']];
            $out[] = ['hls_latency_ms', 'HLS response latency quantile (ms)', HlsMetrics::latencyPercentile(99, $label), ['label' => $label, 'quantile' => '0.99']];
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    private function knownProviders(): array
    {
        try {
            $cdn = app(SettingsManagerService::class)->getCDN();
            $providers = [];

            if (! empty($cdn['cdn_provider'])) {
                $providers[] = (string) $cdn['cdn_provider'];
            }

            if (! empty($cdn['cdn_fallback_provider']) && $cdn['cdn_fallback_provider'] !== ($cdn['cdn_provider'] ?? null)) {
                $providers[] = (string) $cdn['cdn_fallback_provider'];
            }

            return $providers !== [] ? array_unique($providers) : ['cloudflare'];
        } catch (\Throwable) {
            return ['cloudflare'];
        }
    }

    private function counter(string $name, string $help, int|float $value, array $labels): string
    {
        return $this->renderMetric($name, 'counter', $help, $value, $labels);
    }

    private function gauge(string $name, string $help, int|float $value, array $labels): string
    {
        return $this->renderMetric($name, 'gauge', $help, $value, $labels);
    }

    private function renderMetric(string $name, string $type, string $help, int|float $value, array $labels): string
    {
        $labelStr = '';

        if ($labels !== []) {
            $parts = [];
            foreach ($labels as $k => $v) {
                $escaped = str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], (string) $v);
                $parts[] = "{$k}=\"{$escaped}\"";
            }
            $labelStr = '{' . implode(',', $parts) . '}';
        }

        return "# HELP {$name} {$help}\n# TYPE {$name} {$type}\n{$name}{$labelStr} {$value}";
    }
}
