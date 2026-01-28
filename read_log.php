<?php
$file = 'storage/logs/laravel.log';
$lines = 100;
$handle = fopen($file, "r");
$linecount = 0;
while(!feof($handle)){
  $line = fgets($handle);
  $linecount++;
}

fseek($handle, 0);
$pos = -2;
$tlines = 0;
// Simple tail implementation
$text = [];
while ($tlines < $lines && fseek($handle, $pos, SEEK_END) != -1) {
    echo "ERROR: File Seek Failed or Empty"; 
    // This simple tail is risky on windows with CRLF.
    // Let's just use array_slice on file() for simplicity since memory is likely fine for a log file or we yield.
    break; 
}
// Safer:
$data = file($file);
$slice = array_slice($data, -200); // Read last 200 lines
foreach($slice as $l) { 
    if (strpos($l, 'Account opening PDF generation failed') !== false) {
        echo $l; 
    }
}
