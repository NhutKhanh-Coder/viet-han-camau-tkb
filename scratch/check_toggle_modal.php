<?php
$content = file_get_contents(__DIR__ . '/../admin/mmo_events.php');
if (preg_match('/function\s+toggleModal\([^\)]*\)\s*\{[^}]*\}/', $content, $m)) {
    echo "toggleModal:\n" . $m[0] . "\n";
} else {
    echo "toggleModal not matched\n";
}
preg_match_all('/id=["\']contestLeaderboardModal["\'][^>]*/', $content, $m2);
print_r($m2);
