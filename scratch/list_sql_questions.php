<?php
$sql = file_get_contents('c:/xampp/htdocs/tkb/api/db_sync_data.sql');
preg_match_all("/INSERT INTO `quiz_questions` .*? VALUES \('(\d+)', '14', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '([A-D])'\);/u", $sql, $matches, PREG_SET_ORDER);

echo "Total questions for quiz 14 in sql: " . count($matches) . "\n";
foreach ($matches as $idx => $m) {
    echo "[" . ($idx + 1) . "] ID {$m[1]}: " . mb_substr($m[2], 0, 50) . "\n";
}
