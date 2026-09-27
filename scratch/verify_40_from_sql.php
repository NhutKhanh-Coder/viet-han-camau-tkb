<?php
$sql = file_get_contents(__DIR__ . '/../api/db_sync_data.sql');
preg_match_all("/INSERT INTO `quiz_questions` .*? VALUES \('(?:[0-9]+)', '14', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)'\);/u", $sql, $matches, PREG_SET_ORDER);

echo "Found: " . count($matches) . " questions from Quiz 14 in db_sync_data.sql\n";
foreach ($matches as $i => $m) {
    echo ($i+1) . ". " . substr($m[1], 0, 40) . "... | Correct: " . $m[6] . "\n";
}
