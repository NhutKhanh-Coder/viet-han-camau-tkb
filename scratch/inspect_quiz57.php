<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

header('Content-Type: text/plain; charset=utf-8');

echo "=== QUIZ 57 (18 questions) ===\n";
$res57 = $db->query("SELECT id, cau_hoi FROM quiz_questions WHERE quiz_id = 57 ORDER BY id ASC");
$idx = 1;
while ($r = $res57->fetch_assoc()) {
    echo "Q57 [$idx] ID {$r['id']}: " . mb_substr($r['cau_hoi'], 0, 70) . "\n";
    $idx++;
}

echo "\n=== QUIZ 55 (40 questions) ===\n";
$res55 = $db->query("SELECT id, cau_hoi FROM quiz_questions WHERE quiz_id = 55 ORDER BY id ASC");
$idx = 1;
while ($r = $res55->fetch_assoc()) {
    echo "Q55 [$idx] ID {$r['id']}: " . mb_substr($r['cau_hoi'], 0, 70) . "\n";
    $idx++;
}
