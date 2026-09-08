<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$res = $db->query('SELECT id, user_id, ma_sv, ho_ten, banner, tiktok_video FROM students');
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo "ID: {$r['id']} | UserID: {$r['user_id']} | MaSV: {$r['ma_sv']} | HoTen: {$r['ho_ten']} | Banner: {$r['banner']} | Video: {$r['tiktok_video']}\n";
    }
}
