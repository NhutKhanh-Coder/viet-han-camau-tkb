<?php
require_once 'config.php';
$db = getDB();

echo "=== QUIZZES ===\n";
$res = $db->query("SELECT id, tieu_de, mon_hoc_id FROM quizzes");
while ($r = $res->fetch_assoc()) {
    echo "Quiz ID: {$r['id']} | Title: {$r['tieu_de']}\n";
    
    // Check questions
    $res_q = $db->query("SELECT id, cau_hoi, dap_an_dung FROM quiz_questions WHERE quiz_id = {$r['id']} LIMIT 3");
    while ($rq = $res_q->fetch_assoc()) {
        echo "   Q ID: {$rq['id']} | Correct: '{$rq['dap_an_dung']}' | Content: " . mb_substr($rq['cau_hoi'], 0, 30) . "\n";
    }
    
    // Check exam codes
    $res_ec = $db->query("SELECT id, ma_de, LENGTH(matrix_data) as m_len FROM quiz_exam_codes WHERE quiz_id = {$r['id']}");
    while ($rec = $res_ec->fetch_assoc()) {
        echo "   ExamCode ID: {$rec['id']} | Ma de: {$rec['ma_de']} | Len: {$rec['m_len']}\n";
    }
}

echo "=== LAST ATTEMPTS ===\n";
$res_att = $db->query("SELECT * FROM quiz_attempts ORDER BY id DESC LIMIT 5");
if ($res_att) {
    while ($ra = $res_att->fetch_assoc()) {
        echo "Attempt ID: {$ra['id']} | Quiz ID: {$ra['quiz_id']} | Student ID: {$ra['student_id']} | Score: {$ra['score']}/{$ra['total_questions']} | Time: {$ra['attempted_at']}\n";
    }
}
