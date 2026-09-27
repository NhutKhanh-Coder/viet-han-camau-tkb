<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

$res = $db->query("SELECT id, tieu_de, mo_ta, thoi_gian_lam_bai FROM quizzes WHERE id = 57");
if ($res && $r = $res->fetch_assoc()) {
    echo "Quiz 57 Info:\n";
    echo "Title: {$r['tieu_de']}\n";
    echo "Mota: {$r['mo_ta']}\n";
}
