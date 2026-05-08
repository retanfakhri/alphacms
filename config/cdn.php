<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default CDN Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default CDN driver that will be used by the
    | framework. You can use 'cloudflare', 'bunny', or 'null' (disabled).
    |
    */
    'default' => env('CDN_PROVIDER', 'cloudflare'),

    /*
    |--------------------------------------------------------------------------
    | CDN Purge Batching
    |--------------------------------------------------------------------------
    |
    | When true, purges are not sent immediately to the API but are buffered
    | in Redis and flushed in batches to minimize API roundtrips.
    |
    */
    'buffered' => env('CDN_BUFFERED', true),

    /*
    |--------------------------------------------------------------------------
    | Global Rate Limiter
    |--------------------------------------------------------------------------
    |
    | Controls the aggregate rate limit across all providers to protect
    | the origin server.
    |
    */
    'rate_limit' => [
        'max'    => (int) env('CDN_GLOBAL_RATE_MAX', 100),
        'burst'  => (int) env('CDN_GLOBAL_RATE_BURST', 150),
        'window' => (int) env('CDN_GLOBAL_RATE_WINDOW', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chaos Monkey (Testing Only)
    |--------------------------------------------------------------------------
    |
    | Simulates failures and latency in non-production environments.
    |
    */
    'chaos' => [
        'enabled'      => env('CDN_CHAOS_ENABLED', false),
        'failure_rate' => (float) env('CDN_CHAOS_FAILURE_RATE', 0.0),
        'latency_rate' => (float) env('CDN_CHAOS_LATENCY_RATE', 0.0),
        'latency_ms'   => (int) env('CDN_CHAOS_LATENCY_MS', 1500),
    ],
];
