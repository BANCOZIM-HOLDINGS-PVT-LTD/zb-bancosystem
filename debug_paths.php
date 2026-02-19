<?php
$paths = [
    'tests',
    'tests/Scripts',
    'scripts',
    'scripts/deploy',
    'docs',
    'docs/logs'
];

foreach ($paths as $p) {
    if (file_exists($p)) {
        echo "$p exists. ";
        if (is_dir($p)) echo "It is a DIRECTORY. ";
        if (is_file($p)) echo "It is a FILE. ";
        echo "\n";
        if (is_dir($p)) {
            $scan = scandir($p);
            echo "  Contents: " . implode(', ', array_slice($scan, 0, 10)) . "\n";
        }
    } else {
        echo "$p does NOT exist.\n";
    }
}
