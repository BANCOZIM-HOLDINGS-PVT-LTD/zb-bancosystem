<?php
/**
 * Upload System Health Check for cPanel hosting
 */

echo "<h2>🔍 Upload System Health Check</h2>\n";

$checks = [
    'Storage Directory' => [
        'path' => 'storage/app/public/uploads',
        'required' => true,
        'type' => 'directory'
    ],
    'Public Storage' => [
        'path' => 'public/storage/uploads', 
        'required' => true,
        'type' => 'directory'
    ],
    'Storage Writable' => [
        'path' => 'storage/app/public/uploads',
        'required' => true,
        'type' => 'writable'
    ],
    'Upload .htaccess' => [
        'path' => 'public/storage/uploads/.htaccess',
        'required' => true,
        'type' => 'file'
    ],
    'Sync Script' => [
        'path' => 'sync-storage.php',
        'required' => true,
        'type' => 'file'
    ],
];

$allPassed = true;

foreach ($checks as $name => $check) {
    $path = __DIR__ . '/' . $check['path'];
    $passed = false;
    $message = '';
    
    switch ($check['type']) {
        case 'directory':
            $passed = is_dir($path);
            $message = $passed ? "✅ Exists" : "❌ Missing";
            break;
            
        case 'file':
            $passed = is_file($path);
            $message = $passed ? "✅ Exists" : "❌ Missing";
            break;
            
        case 'writable':
            $passed = is_dir($path) && is_writable($path);
            $message = $passed ? "✅ Writable" : "❌ Not writable";
            break;
    }
    
    if (!$passed && $check['required']) {
        $allPassed = false;
    }
    
    echo "<div style='padding: 5px; margin: 2px; background: " . 
         ($passed ? '#d4edda' : '#f8d7da') . "; border-radius: 3px;'>";
    echo "<strong>$name:</strong> $message ($path)</div>\n";
}

echo "\n<h3>" . ($allPassed ? "🎉 All checks passed!" : "⚠️ Some issues found") . "</h3>\n";

if (!$allPassed) {
    echo "<p><strong>To fix issues:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Run: <code>php sync-storage.php</code></li>\n";
    echo "<li>Set permissions: <code>chmod -R 755 storage/</code></li>\n";
    echo "<li>For uploads: <code>chmod -R 777 storage/app/public/uploads/</code></li>\n";
    echo "</ol>\n";
}

// Environment checks
echo "\n<h3>📋 Environment Configuration</h3>\n";

$envChecks = [
    'FILESYSTEM_DISK' => env('FILESYSTEM_DISK', 'not set'),
    'MAX_FILE_SIZE' => env('MAX_FILE_SIZE', 'not set'),
    'ALLOWED_FILE_TYPES' => env('ALLOWED_FILE_TYPES', 'not set'),
];

foreach ($envChecks as $key => $value) {
    $status = ($value !== 'not set') ? '✅' : '❌';
    echo "<div style='padding: 3px;'>$status <strong>$key:</strong> $value</div>\n";
}

// Laravel-specific function to get env values
function env($key, $default = null) {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        return $default;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($envKey, $envValue) = explode('=', $line, 2);
            if (trim($envKey) === $key) {
                return trim($envValue, '"\'');
            }
        }
    }
    
    return $default;
}

echo "\n<p><small>Last checked: " . date('Y-m-d H:i:s') . "</small></p>\n";
?>