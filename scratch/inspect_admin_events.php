<?php
$content = file_get_contents(__DIR__ . '/../admin/mmo_events.php');

// Find all occurrences of "contest"
preg_match_all('/.{0,50}contest.{0,50}/i', $content, $matches);
echo "Contest matches: " . count($matches[0]) . "\n";
foreach (array_slice($matches[0], 0, 10) as $m) {
    echo " - " . trim($m) . "\n";
}

// Find openContestLeaderboard
if (strpos($content, 'openContestLeaderboard') !== false) {
    echo "Found openContestLeaderboard!\n";
} else {
    echo "NOT found openContestLeaderboard!\n";
}

// Find what button is rendered for contest_triathlon
preg_match_all('/.{0,100}contest_triathlon.{0,100}/', $content, $m2);
foreach ($m2[0] as $line) {
    echo "Triathlon: " . trim($line) . "\n";
}
