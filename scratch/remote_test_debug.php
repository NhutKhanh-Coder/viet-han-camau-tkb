<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing lam_bai_tap.php directly:\n";
try {
    require_once __DIR__ . '/config.php';
    echo "config.php loaded\n";
    $db = getDB();
    echo "db connected\n";
    
    $sv_id = 2;
    $sql = "SELECT a.id, a.mon_hoc_id, a.giang_vien_id, a.lop, a.tieu_de, a.mo_ta, a.han_nop, a.created_at, a.file_path as assign_file_path,
           m.ten_mon, m.ma_mon, g.ho_ten as gv_name, g.email as gv_email,
           sub.id as submission_id, sub.file_path as sub_file_path, sub.submission_text, sub.grade, sub.feedback, sub.submitted_at
    FROM assignments a
    JOIN mon_hoc m ON a.mon_hoc_id = m.id
    LEFT JOIN giang_vien g ON a.giang_vien_id = g.id
    LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
    WHERE a.lop = ? OR a.lop IN ('K24CDCNT1', 'CNTT24A') OR ? = ''
    ORDER BY a.id DESC";
    $st = $db->prepare($sql);
    if (!$st) {
        echo "PREPARE ERROR: " . $db->error . "\n";
    } else {
        echo "PREPARE SUCCESS!\n";
    }
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
}
