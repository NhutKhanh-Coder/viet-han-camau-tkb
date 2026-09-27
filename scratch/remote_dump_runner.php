<?php
require_once __DIR__ . '/config.php';
$db = getDB();

$out = "=== DATABASE DEBUG DUMP ===\n";

$res = $db->query("SELECT id, tieu_de FROM quizzes");
if ($res) {
    while ($q = $res->fetch_assoc()) {
        $out .= "Quiz ID: {$q['id']} - {$q['tieu_de']}\n";
        
        // Count questions
        $res_qc = $db->query("SELECT COUNT(*) as c FROM quiz_questions WHERE quiz_id = {$q['id']}");
        $qc = $res_qc->fetch_assoc()['c'] ?? 0;
        $out .= "  Total questions in DB: $qc\n";

        // Sample questions
        $res_qs = $db->query("SELECT id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung FROM quiz_questions WHERE quiz_id = {$q['id']} LIMIT 5");
        while ($row = $res_qs->fetch_assoc()) {
            $out .= "  Q#{$row['id']}: '{$row['cau_hoi']}'\n";
            $out .= "    dap_an_dung = [" . var_export($row['dap_an_dung'], true) . "]\n";
            $out .= "    A: '{$row['dap_an_a']}'\n";
            $out .= "    B: '{$row['dap_an_b']}'\n";
            $out .= "    C: '{$row['dap_an_c']}'\n";
            $out .= "    D: '{$row['dap_an_d']}'\n";
        }
        
        // Check quiz_exam_codes
        $res_ec = $db->query("SELECT id, ma_de, LENGTH(matrix_data) as m_len FROM quiz_exam_codes WHERE quiz_id = {$q['id']}");
        $out .= "  Exam codes:\n";
        while ($ec = $res_ec->fetch_assoc()) {
            $out .= "    ID: {$ec['id']} | Ma de: {$ec['ma_de']} | matrix_len: {$ec['m_len']}\n";
        }
    }
} else {
    $out .= "Query quizzes failed: " . $db->error . "\n";
}

$out .= "\n=== LAST 5 ATTEMPTS ===\n";
$res_att = $db->query("SELECT * FROM quiz_attempts ORDER BY id DESC LIMIT 5");
if ($res_att) {
    while ($att = $res_att->fetch_assoc()) {
        $out .= "Attempt ID: {$att['id']} | Quiz: {$att['quiz_id']} | Student: {$att['student_id']} | Score: {$att['score']}/{$att['total_questions']} | Ma de: {$att['ma_de']} | At: {$att['attempted_at']}\n";
    }
}

file_put_contents(__DIR__ . '/scratch_dump.txt', $out);
echo "Wrote " . strlen($out) . " bytes to scratch_dump.txt\n";
