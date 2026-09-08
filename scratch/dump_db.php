<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$res = $db->query("SELECT id, username, banner, tiktok_video FROM students ORDER BY id DESC LIMIT 5");
$data = [];
while ($row = $res->fetch_assoc()) {
    $raw_video_val = $row['tiktok_video'];
    if (!empty($raw_video_val) && is_string($raw_video_val)) {
        $raw_video_val = trim($raw_video_val, '"\' ');
        if (strpos($raw_video_val, '[') === 0) {
            $decoded = @json_decode($raw_video_val, true);
            if (!is_array($decoded)) {
                $row['tiktok_video_error'] = json_last_error_msg();
                $row['tiktok_video_raw'] = $raw_video_val;
            } else {
                $row['tiktok_video_decoded'] = $decoded;
            }
        }
    }
    
    $raw_banner_val = $row['banner'];
    if (!empty($raw_banner_val) && is_string($raw_banner_val)) {
        $raw_banner_val = trim($raw_banner_val, '"\' ');
        if (strpos($raw_banner_val, '[') === 0) {
            $decoded = @json_decode($raw_banner_val, true);
            if (!is_array($decoded)) {
                $row['banner_error'] = json_last_error_msg();
                $row['banner_raw'] = $raw_banner_val;
            } else {
                $row['banner_decoded'] = $decoded;
            }
        }
    }
    
    $data[] = $row;
}
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
