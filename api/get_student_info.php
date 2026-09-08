<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing ID']);
    exit;
}

$db = getDB();
$id = (int)$_GET['id'];

$st = $db->prepare("SELECT id, ma_sv, ho_ten, ngay_sinh, lop, khoa, email, sdt, avatar FROM students WHERE id = ?");
$st->bind_param("i", $id);
$st->execute();
$sv = $st->get_result()->fetch_assoc();

if (!$sv) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

echo json_encode([
    'sv' => $sv,
    'diem' => [],
    'dtb' => 0,
    'hoc_luc' => 'Chưa có',
    'so_mon' => 0
]);
$db->close();
