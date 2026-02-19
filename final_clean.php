<?php
$map = [
    'scripts/deploy/download_fly.ps1' => 'download_fly.ps1',
    'scripts/deploy/install_fly.ps1' => 'install_fly.ps1',
    'scripts/deploy/install_fly_retry.ps1' => 'install_fly_retry.ps1',
    'scripts/deploy/set_secrets.ps1' => 'set_secrets.ps1',
    'scripts/deploy/deploy_app.ps1' => 'deploy_app.ps1',
    'tests/Scripts/list_construction_items.php' => 'list_construction_items.php',
    'tests/Scripts/read_excel.php' => 'read_excel.php',
    'tests/Scripts/read_excel.py' => 'read_excel.py',
    'tests/Scripts/read_log.php' => 'read_log.php',
    'tests/Scripts/remove_construction_items.php' => 'remove_construction_items.php',
    'tests/Scripts/temp_ssb_enum.php' => 'temp_ssb_enum.php',
    'tests/Scripts/temp_zb_enum.php' => 'temp_zb_enum.php',
    'tests/Scripts/test_sender_id_variations.php' => 'test_sender_id_variations.php',
    'docs/logs/temp_fly_logs.txt' => 'temp_fly_logs.txt',
    'docs/logs/test_output.txt' => 'test_output.txt',
    'docs/logs/verify_output.txt' => 'verify_output.txt',
    'tests/Fixtures/test_payload.json' => 'test_payload.json'
];

foreach ($map as $new => $old) {
    if (file_exists($new)) {
        if (file_exists($old)) {
            if (unlink($old)) {
                echo "Deleted root file: $old (confirmed exists at $new)\n";
            } else {
                echo "Failed to delete root file: $old\n";
            }
        } else {
            echo "Root file already gone: $old\n";
        }
    } else {
        echo "WARNING: New file missing: $new. NOT deleting $old\n";
    }
}
