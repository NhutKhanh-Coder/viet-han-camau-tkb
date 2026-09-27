<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
$db = getDB();

echo "=== INSPECT QUIZ 67 ===\n";
$q = $db->query("SELECT * FROM quizzes WHERE id = 67");
if ($q && $row = $q->fetch_assoc()) {
    print_r($row);
} else {
    echo "Quiz 67 not found!\n";
}

echo "=== QUIZ 67 QUESTIONS ===\n";
$qq = $db->query("SELECT id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung FROM quiz_questions WHERE quiz_id = 67 ORDER BY id ASC");
if ($qq) {
    echo "Total questions: " . $qq->num_rows . "\n";
    $i = 1;
    while ($r = $qq->fetch_assoc()) {
        echo "Q" . $i++ . " (ID {$r['id']}): " . $r['cau_hoi'] . " [Ans: {$r['dap_an_dung']}]\n";
    }
} else {
    echo "Query error: " . $db->error . "\n";
}

echo "\n=== RECENT QUIZZES ===\n";
$rq = $db->query("SELECT id, tieu_de, created_at FROM quizzes ORDER BY id DESC LIMIT 10");
if ($rq) {
    while ($r = $rq->fetch_assoc()) {
        echo "#{$r['id']} - {$r['tieu_de']} ({$r['created_at']})\n";
    }
}
