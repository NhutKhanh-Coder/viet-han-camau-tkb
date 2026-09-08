<?php
$content = file_get_contents(__DIR__ . '/../admin/mmo_events.php');
preg_match_all('/id="([^"]*Modal)"/', $content, $m);
echo "Modals found:\n";
print_r($m[1]);

// Let's check how addEventModal is styled/opened
if (preg_match('/<div[^>]*id="addEventModal"[^>]*>/', $content, $m2)) {
    echo "addEventModal tag: " . $m2[0] . "\n";
}
if (preg_match('/<div[^>]*id="contestLeaderboardModal"[^>]*>/', $content, $m3)) {
    echo "contestLeaderboardModal tag: " . $m3[0] . "\n";
}
