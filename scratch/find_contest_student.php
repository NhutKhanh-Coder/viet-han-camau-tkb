<?php
$content = file_get_contents(__DIR__ . '/../student/events.php');
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (stripos($line, 'contest_triathlon') !== false || 
        stripos($line, 'contestArena') !== false ||
        stripos($line, 'Vòng 1') !== false ||
        stripos($line, 'round1') !== false ||
        stripos($line, 'contest_submit') !== false) {
        echo "Line " . ($i + 1) . ": " . trim(substr($line, 0, 120)) . "\n";
    }
}
