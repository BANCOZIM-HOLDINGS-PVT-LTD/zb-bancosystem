<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the system monitoring
    | service including thresholds, alert settings, and data retention.
    |
    */

    'pdf_generation' => [
        'slow_threshold_seconds' => env('MONITORING_PDF_SLOW_THRESHOLD', 30),
        'failure_rate_threshold' => env('MONITORING_PDF_FAILURE_RATE_THRESHOLD', 0.3),
        'memory_threshold_mb' => env('MONITORING_PDF_MEMORY_THRESHOLD', 256),
    ],

    'system_health' => [
        'disk_usage_warning_threshold' => env('MONITORING_DISK_WARNING_THRESHOLD', 80),
        'disk_usage_critical_threshold' => env('MONITORING_DISK_CRITICAL_THRESHOLD', 90),
        'memory_usage_warning_threshold' => env('MONITORING_MEMORY_WARNING_THRESHOLD', 80),
        'memory_usage_critical_threshold' => env('MONITORING_MEMORY_CRITICAL_THRESHOLD', 90),
        'database_response_threshold_ms' => env('MONITORING_DB_RESPONSE_THRESHOLD', 1000),
    ],

    'alerts' => [
        'enabled' => env('MONITORING_ALERTS_ENABLED', true),
        'channels' => [
            'log' => true,
            'email' => env('MONITORING_EMAIL_ALERTS', false),
            'slack' => env('MONITORING_SLACK_ALERTS', false),
        ],
        'email' => [
            'recipients' => explode(',', env('MONITORING_EMAIL_RECIPIENTS', '')),
            'from' => env('MONITORING_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
        ],
        'slack' => [
            'webhook_url' => env('MONITORING_SLACK_WEBHOOK_URL'),
            'channel' => env('MONITORING_SLACK_CHANNEL', '#alerts'),
        ],
    ],

    'data_retention' => [
        'metrics_cache_hours' => env('MONITORING_METRICS_CACHE_HOURS', 24),
        'hourly_stats_days' => env('MONITORING_HOURLY_STATS_DAYS', 7),
        'alerts_days' => env('MONITORING_ALERTS_DAYS', 7),
        'cleanup_frequency_hours' => env('MONITORING_CLEANUP_FREQUENCY', 24),
    ],

    'dashboard' => [
        'refresh_interval_seconds' => env('MONITORING_DASHBOARD_REFRESH', 30),
        'metrics_history_hours' => env('MONITORING_DASHBOARD_HISTORY', 24),
        'show_detailed_errors' => env('MONITORING_SHOW_DETAILED_ERRORS', false),
    ],
];
