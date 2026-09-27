<?php
require_once __DIR__ . '/config.php';
$db = getDB();
echo "=== LESSONS ON REMOTE ===\n";
$res = $db->query("SELECT id, giang_vien_id, mon_hoc_id, tieu_de, video_url, created_at FROM lessons");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
