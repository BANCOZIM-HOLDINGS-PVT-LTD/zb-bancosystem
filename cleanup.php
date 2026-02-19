<?php
$files = [
    'download_fly.ps1' => 'scripts/deploy/download_fly.ps1',
    'install_fly.ps1' => 'scripts/deploy/install_fly.ps1',
    'install_fly_retry.ps1' => 'scripts/deploy/install_fly_retry.ps1',
    'set_secrets.ps1' => 'scripts/deploy/set_secrets.ps1',
    'list_construction_items.php' => 'tests/Scripts/list_construction_items.php',
    'read_excel.php' => 'tests/Scripts/read_excel.php',
    'read_excel.py' => 'tests/Scripts/read_excel.py',
    'read_log.php' => 'tests/Scripts/read_log.php',
    'remove_construction_items.php' => 'tests/Scripts/remove_construction_items.php',
    'temp_ssb_enum.php' => 'tests/Scripts/temp_ssb_enum.php',
    'temp_zb_enum.php' => 'tests/Scripts/temp_zb_enum.php',
    'test_sender_id_variations.php' => 'tests/Scripts/test_sender_id_variations.php',
    'temp_fly_logs.txt' => 'docs/logs/temp_fly_logs.txt',
    'test_output.txt' => 'docs/logs/test_output.txt',
    'verify_output.txt' => 'docs/logs/verify_output.txt'
];

foreach ($files as $src => $dest) {
    if (file_exists($src)) {
        // Ensure dir exists
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        if (copy($src, $dest)) {
            unlink($src);
            echo "Moved $src to $dest\n";
        } else {
            echo "Failed to move $src to $dest\n";
        }
    } else {
        echo "File $src does not exist\n";
    }
}
echo "Cleanup script finished.\n";
