<?php
$base = dirname(__DIR__);

$files = [
    $base . '/admin/mmo_events.php',
    $base . '/admin/quanly_ai_accounts.php',
    $base . '/teacher/mmo.php',
    $base . '/student/shop_ai.php',
    $base . '/student/events.php',
    $base . '/api/claim_mmo_event.php',
    $base . '/api/mmo_chat.php',
    $base . '/clear_and_delete.php',
];

foreach ($files as $f) {
    if (file_exists($f)) {
        if (@unlink($f)) {
            echo "Deleted file: " . basename($f) . "\n";
        } else {
            echo "FAILED to delete file: " . basename($f) . "\n";
        }
    } else {
        echo "File already absent: " . basename($f) . "\n";
    }
}

// Delete folder uploads/mmo_banners
$bannerDir = $base . '/uploads/mmo_banners';
if (is_dir($bannerDir)) {
    $scan = glob($bannerDir . '/*');
    foreach ($scan as $sub) {
        if (is_file($sub)) @unlink($sub);
    }
    if (@rmdir($bannerDir)) {
        echo "Deleted directory: uploads/mmo_banners\n";
    } else {
        echo "FAILED to delete directory: uploads/mmo_banners\n";
    }
} else {
    echo "Directory already absent: uploads/mmo_banners\n";
}
