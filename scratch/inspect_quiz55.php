<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

header('Content-Type: text/plain; charset=utf-8');

echo "=== QUIZ 55 QUESTIONS ===\n";
$res = $db->query("SELECT id, quiz_id, dap_an_dung, dap_an_a, dap_an_b, dap_an_c, dap_an_d FROM quiz_questions WHERE quiz_id = 55 ORDER BY id ASC LIMIT 10");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo "ID: {$r['id']} | dap_an_dung: " . var_export($r['dap_an_dung'], true) . " | A: " . mb_substr($r['dap_an_a'], 0, 15) . " | B: " . mb_substr($r['dap_an_b'], 0, 15) . "\n";
    }
} else {
    echo "Query error: " . $db->error . "\n";
}

echo "\n=== QUIZ 55 EXAM CODES ===\n";
$res_ec = $db->query("SELECT id, ma_de, LENGTH(matrix_data) as len FROM quiz_exam_codes WHERE quiz_id = 55");
if ($res_ec && $res_ec->num_rows > 0) {
    while ($r = $res_ec->fetch_assoc()) {
        echo "EC ID: {$r['id']} | ma_de: {$r['ma_de']} | len: {$r['len']}\n";
    }
} else {
    echo "NO exam codes for Quiz 55!\n";
}

echo "\n=== LAST ATTEMPTS FOR QUIZ 55 ===\n";
$res_at = $db->query("SELECT * FROM quiz_attempts WHERE quiz_id = 55 ORDER BY id DESC LIMIT 5");
if ($res_at) {
    while ($r = $res_at->fetch_assoc()) {
        echo "ATT ID: {$r['id']} | Student: {$r['student_id']} | Score: {$r['score']}/{$r['total_questions']} | ma_de: {$r['ma_de']} | at: {$r['attempted_at']}\n";
    }
}
