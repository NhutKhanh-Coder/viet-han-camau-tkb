<?php
require_once __DIR__ . '/config.php';
$db = getDB();
$db->query("DELETE FROM mmo_coupons WHERE code IN ('VKC20', 'SINHVIEN10')");
echo json_encode(['status' => 'success', 'deleted' => true]);
