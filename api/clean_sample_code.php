<?php
require_once '../config.php';
$db = getDB();

$res = $db->query("DELETE FROM student_code_storage WHERE ten_du_an LIKE 'Dự án: keria%' OR ten_du_an LIKE 'Bài Tập Lớn:%' OR ten_du_an LIKE 'Cấu Trúc Dữ Liệu%'");

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Đã xóa toàn bộ bài làm mẫu khỏi CSDL thành công!'
]);
