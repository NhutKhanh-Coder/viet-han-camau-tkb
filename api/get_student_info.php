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

$st2 = $db->prepare("SELECT d.*, m.ten_mon FROM diem d JOIN mon_hoc m ON d.mon_hoc_id = m.id WHERE d.student_id = ?");
$st2->bind_param("i", $id);
$st2->execute();
$diem_list = $st2->get_result()->fetch_all(MYSQLI_ASSOC);

$tong_diem = 0;
$so_mon = 0;

foreach ($diem_list as $d) {
    if ($d['diem_tong_ket'] !== null) {
        $tong_diem += (float)$d['diem_tong_ket'];
        $so_mon++;
    }
}

$dtb = $so_mon > 0 ? round($tong_diem / $so_mon, 2) : 0;
$hoc_luc = 'Chưa có';
if ($so_mon > 0) {
    if ($dtb >= 9.5) $hoc_luc = 'Xuất sắc';
    elseif ($dtb >= 8.0) $hoc_luc = 'Giỏi';
    elseif ($dtb >= 6.5) $hoc_luc = 'Khá';
    elseif ($dtb >= 5.0) $hoc_luc = 'Trung bình';
    else $hoc_luc = 'Yếu';
}

echo json_encode([
    'sv' => $sv,
    'diem' => $diem_list,
    'dtb' => $dtb,
    'hoc_luc' => $hoc_luc,
    'so_mon' => $so_mon
]);
$db->close();
