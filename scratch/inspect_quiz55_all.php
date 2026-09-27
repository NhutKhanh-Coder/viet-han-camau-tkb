<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

header('Content-Type: text/plain; charset=utf-8');

echo "=== QUIZ 55 QUESTIONS (Total count) ===\n";
$res55 = $db->query("SELECT id, cau_hoi FROM quiz_questions WHERE quiz_id = 55 ORDER BY id ASC");
$idx = 1;
while ($r = $res55->fetch_assoc()) {
    echo "[$idx] ID {$r['id']}: " . mb_substr($r['cau_hoi'], 0, 70) . "\n";
    $idx++;
}
