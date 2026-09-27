<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

header('Content-Type: text/plain; charset=utf-8');

echo "=== QUIZ EXAM CODES ===\n";
$res_ec = $db->query("SELECT id, quiz_id, ma_de, LENGTH(matrix_data) as m_len FROM quiz_exam_codes ORDER BY id DESC LIMIT 10");
if ($res_ec && $res_ec->num_rows > 0) {
    while ($r = $res_ec->fetch_assoc()) {
        echo "ID: {$r['id']} | Quiz ID: {$r['quiz_id']} | Ma de: {$r['ma_de']} | Matrix len: {$r['m_len']}\n";
    }
} else {
    echo "NO quiz_exam_codes found in DB!\n";
}

echo "\n=== LAST QUIZ ATTEMPTS ===\n";
$res_at = $db->query("SELECT * FROM quiz_attempts ORDER BY id DESC LIMIT 5");
if ($res_at) {
    while ($ra = $res_at->fetch_assoc()) {
        echo "Attempt ID: {$ra['id']} | Quiz: {$ra['quiz_id']} | Student: {$ra['student_id']} | Score: {$ra['score']} / {$ra['total_questions']} | Ma de: {$ra['ma_de']} | Time: {$ra['attempted_at']}\n";
        echo "Nhan xet: {$ra['nhan_xet']}\n\n";
    }
}

echo "\n=== FIRST 3 QUESTIONS OF QUIZ 1 ===\n";
$res_q = $db->query("SELECT id, quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung FROM quiz_questions ORDER BY id ASC LIMIT 3");
if ($res_q) {
    while ($rq = $res_q->fetch_assoc()) {
        echo "Q ID: {$rq['id']} | Dap an dung: '{$rq['dap_an_dung']}' | Cau hoi: {$rq['cau_hoi']}\n";
        echo "  A: {$rq['dap_an_a']}\n";
        echo "  B: {$rq['dap_an_b']}\n";
    }
}
