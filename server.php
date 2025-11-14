<?php

/**
 * Laravel development server entry point
 *
 * This file serves as the entry point for the built-in PHP development server.
 * It handles routing and serves static files when available.
 */
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Check if the request is for a static file that exists
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

// Forward all other requests to the Laravel front controller
require_once __DIR__.'/public/index.php';
