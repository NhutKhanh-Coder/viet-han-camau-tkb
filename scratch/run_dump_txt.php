<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

$out = "=== QUIZ 57 QUESTIONS ===\n";
$res57 = $db->query("SELECT id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung FROM quiz_questions WHERE quiz_id = 57 ORDER BY id ASC");
$idx = 1;
while ($r = $res57->fetch_assoc()) {
    $out .= "[$idx] ID {$r['id']}: {$r['cau_hoi']}\n";
    $out .= "  A: {$r['dap_an_a']}\n";
    $out .= "  B: {$r['dap_an_b']}\n";
    $out .= "  C: {$r['dap_an_c']}\n";
    $out .= "  D: {$r['dap_an_d']}\n";
    $out .= "  DAP_AN_DUNG: {$r['dap_an_dung']}\n\n";
    $idx++;
}

$out .= "\n=== QUIZ 55 QUESTIONS ===\n";
$res55 = $db->query("SELECT id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung FROM quiz_questions WHERE quiz_id = 55 ORDER BY id ASC");
$idx = 1;
while ($r = $res55->fetch_assoc()) {
    $out .= "[$idx] ID {$r['id']}: {$r['cau_hoi']}\n";
    $idx++;
}

file_put_contents(__DIR__ . '/dump_quiz_data.txt', $out);
echo "Written dump_quiz_data.txt successfully (" . strlen($out) . " bytes)\n";
