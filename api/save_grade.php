<?php
require_once '../config.php';
requireTeacher();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Phương thức yêu cầu không hợp lệ.']);
    exit;
}

$sub_id = intval($_POST['submission_id'] ?? 0);
$diem = isset($_POST['diem']) && $_POST['diem'] !== '' ? floatval($_POST['diem']) : null;
$nhan_xet = trim($_POST['nhan_xet'] ?? '');

if ($sub_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Thiếu thông tin bài nộp.']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("UPDATE practice_submissions SET diem = ?, nhan_xet = ? WHERE id = ?");
$stmt->bind_param("dsi", $diem, $nhan_xet, $sub_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Lỗi lưu cơ sở dữ liệu: ' . $db->error]);
}

$stmt->close();
$db->close();
