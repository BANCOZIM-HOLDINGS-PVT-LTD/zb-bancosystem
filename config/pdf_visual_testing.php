<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Visual Testing Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for PDF visual comparison testing.
    |
    */

    // Default threshold for visual comparison (percentage difference allowed)
    'default_threshold' => env('PDF_VISUAL_THRESHOLD', 5.0),
    
    // Template-specific thresholds
    'template_thresholds' => [
        'zb_account_opening' => env('PDF_VISUAL_THRESHOLD_ZB', 5.0),
        'ssb' => env('PDF_VISUAL_THRESHOLD_SSB', 5.0),
        'sme_account_opening' => env('PDF_VISUAL_THRESHOLD_SME', 5.0),
        'account_holders' => env('PDF_VISUAL_THRESHOLD_ACCOUNT_HOLDERS', 5.0),
    ],
    
    // Directory paths
    'paths' => [
        'design_templates' => public_path('design'),
        'temp_directory' => storage_path('app/temp/pdf-visual-tests'),
        'reports_directory' => storage_path('app/temp/pdf-visual-tests/reports'),
    ],
    
    // Report settings
    'reports' => [
        'generate_html' => true,
        'keep_diff_images' => true,
        'retention_days' => 7, // How many days to keep reports before cleanup
    ],
    
    // Testing settings
    'testing' => [
        'edge_cases' => [
            'enabled' => true,
            'long_text_test' => true,
            'special_chars_test' => true,
            'numeric_limits_test' => true,
        ],
        'batch_testing' => [
            'enabled' => true,
            'parallel' => false, // Whether to run tests in parallel
            'max_concurrent' => 2, // Maximum number of concurrent tests if parallel is true
        ],
    ],
];