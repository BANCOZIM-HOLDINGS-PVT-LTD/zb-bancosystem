<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for PDF generation logging and notifications.
    |
    */

    // Log channels for PDF operations
    'channels' => [
        'pdf_info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/pdf/info.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'pdf_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/pdf/error.log'),
            'level' => 'error',
            'days' => 30,
        ],
        'pdf_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/pdf/debug.log'),
            'level' => 'debug',
            'days' => 7,
        ],
    ],

    // Notification settings for critical PDF errors
    'notifications' => [
        'enabled' => env('PDF_ERROR_NOTIFICATIONS', false),
        'recipients' => [
            // List of email addresses to notify on critical errors
            // Example: 'admin@example.com'
        ],
        'channels' => [
            'mail',
            // 'slack', // Uncomment to enable Slack notifications
        ],
        'slack_webhook' => env('PDF_ERROR_SLACK_WEBHOOK', ''),
        'error_codes' => [
            // List of error codes that should trigger notifications
            'PDF_GENERATION_FAILED',
            'PDF_STORAGE_FAILED',
            'PDF_INCOMPLETE_DATA',
        ],
        'include_stack_trace' => env('PDF_ERROR_INCLUDE_STACK_TRACE', false),
        'throttle' => [
            'enabled' => true,
            'limit' => 5, // Maximum number of notifications per minute
            'per_error_code' => true, // Apply throttling per error code
        ],
    ],

    // Performance monitoring thresholds
    'performance' => [
        'slow_generation_threshold' => 5.0, // seconds
        'log_performance_metrics' => true,
    ],
];