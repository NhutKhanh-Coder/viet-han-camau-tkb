<?php
require_once file_exists(__DIR__ . '/config.php') ? __DIR__ . '/config.php' : __DIR__ . '/../config.php';
$db = getDB();

echo "Starting DB sync for lessons and tai_lieu...\n";

// 1. Check if document exists in mon_hoc_id = 9 as well
$check_doc = $db->query("SELECT id FROM tai_lieu WHERE mon_hoc_id = 9 AND ten_tai_lieu LIKE '%KỊCH BẢN VIDEO%'");
if ($check_doc && $check_doc->num_rows === 0) {
    // Insert copy for subject 9 so both 9 and 13 have this document
    $stmt = $db->prepare("INSERT INTO tai_lieu (giang_vien_id, mon_hoc_id, ten_tai_lieu, link_download, created_at) VALUES (1, 9, 'KỊCH BẢN VIDEO THUYẾT TRÌNH DỰ ÁN', '/tkb/assets/uploads/documents/1789441087_K___CH_B___N_VIDEO_THUY___T_TR__NH_D______N.docx', NOW())");
    if ($stmt && $stmt->execute()) {
        echo "Inserted document for mon_hoc_id = 9\n";
    }
} else {
    echo "Document already exists for mon_hoc_id = 9\n";
}

// 2. Ensure lessons exist for mon_hoc_id = 9 (Web Development)
$check_lessons_9 = $db->query("SELECT id FROM lessons WHERE mon_hoc_id = 9");
if ($check_lessons_9 && $check_lessons_9->num_rows === 0) {
    // Copy lessons 1..4 to mon_hoc_id = 9
    $res = $db->query("SELECT * FROM lessons WHERE mon_hoc_id = 13 ORDER BY id ASC");
    if ($res) {
        while ($l = $res->fetch_assoc()) {
            $st = $db->prepare("INSERT INTO lessons (giang_vien_id, mon_hoc_id, tieu_de, video_url, noi_dung, created_at) VALUES (?, 9, ?, ?, ?, ?)");
            $gv = (int)($l['giang_vien_id'] ?: 1);
            $st->bind_param("issss", $gv, $l['tieu_de'], $l['video_url'], $l['noi_dung'], $l['created_at']);
            $st->execute();
            echo "Copied lesson '{$l['tieu_de']}' to mon_hoc_id = 9\n";
        }
    }
} else {
    echo "Lessons already exist for mon_hoc_id = 9 (count: {$check_lessons_9->num_rows})\n";
}

// 3. Ensure teacher name is associated with giang_vien_id in tai_lieu and lessons
$db->query("UPDATE tai_lieu SET giang_vien_id = 1 WHERE giang_vien_id = 0");
$db->query("UPDATE lessons SET giang_vien_id = 1 WHERE giang_vien_id = 0");

// 4. Ensure thoi_khoa_bieu has entry for mon_hoc_id = 13 for Công Nghệ Thông Tin
$check_tkb_13 = $db->query("SELECT id FROM thoi_khoa_bieu WHERE mon_hoc_id = 13");
if ($check_tkb_13 && $check_tkb_13->num_rows === 0) {
    $db->query("INSERT INTO thoi_khoa_bieu (mon_hoc_id, giang_vien_id, khoa, thu, tiet_bat_dau, tiet_ket_thuc, phong_hoc, hoc_ky, nam_hoc) VALUES (13, 1, 'Công Nghệ Thông Tin', 4, 1, 4, 'Lab 01', 'HK1', '2026-2027')");
    echo "Added schedule row for mon_hoc_id = 13\n";
} else {
    echo "Schedule row for mon_hoc_id = 13 already exists\n";
}

echo "DB Sync completed successfully!\n";
