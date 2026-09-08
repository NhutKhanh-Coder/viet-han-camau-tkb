<?php
$content = file_get_contents(__DIR__ . '/../admin/mmo_events.php');
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'openContestLeaderboard') !== false || strpos($line, 'contestLeaderboardModal') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
