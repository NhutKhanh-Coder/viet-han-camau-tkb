<?php
$file = 'c:/xampp/htdocs/Tvy/index.html';
if (!file_exists($file)) {
    // Try lower case
    $file = 'c:/xampp/htdocs/tvy/index.html';
}

if (file_exists($file)) {
    echo "Found file: $file\n";
    $content = file_get_contents($file);
    echo "Size: " . strlen($content) . "\n";
    
    // Replace KERIA with TƯỜNG VY
    $modified = str_replace('KERIA', 'TƯỜNG VY', $content);
    $modified = str_replace('Keria', 'Tường Vy', $modified);
    $modified = str_replace('keria', 'tường vy', $modified);
    
    if ($modified !== $content) {
        file_put_contents($file, $modified);
        echo "SUCCESS: $file updated with TƯỜNG VY!\n";
    } else {
        echo "Already updated or not found.\n";
    }
} else {
    echo "File not found at $file\n";
}
