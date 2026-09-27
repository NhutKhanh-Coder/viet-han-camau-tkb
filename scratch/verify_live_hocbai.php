<?php
// Test verification script
require_once __DIR__ . '/config.php';
$db = getDB();

// Test student 19 (Vũ Nhật Tường Vi, CNTT)
$sv_id = 19;
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $sv_id);
$stmt->execute();
$sv = $stmt->get_result()->fetch_assoc();
$khoa = $sv['khoa'] ?? '';

echo "Student: {$sv['ho_ten']}, Khoa: {$khoa}\n";

// Run subject query
$stmt_mon = $db->prepare("
    SELECT DISTINCT m.id, m.ten_mon, m.ma_mon,
           (SELECT COUNT(*) FROM lessons l WHERE l.mon_hoc_id = m.id) as total_lessons,
           (SELECT COUNT(*) FROM tai_lieu d WHERE d.mon_hoc_id = m.id) as total_docs
    FROM mon_hoc m
    WHERE LOWER(m.khoa) = LOWER(?)
       OR m.id IN (SELECT mon_hoc_id FROM thoi_khoa_bieu WHERE LOWER(khoa) = LOWER(?))
       OR m.id IN (SELECT DISTINCT mon_hoc_id FROM lessons)
       OR m.id IN (SELECT DISTINCT mon_hoc_id FROM tai_lieu)
    ORDER BY m.id ASC
");
$stmt_mon->bind_param("ss", $khoa, $khoa);
$stmt_mon->execute();
$subjects = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);

echo "Subjects count: " . count($subjects) . "\n";
foreach ($subjects as $s) {
    echo " - ID {$s['id']}: {$s['ten_mon']} (Lessons: {$s['total_lessons']}, Docs: {$s['total_docs']})\n";
}

// Documents query
$res_doc = $db->query("
    SELECT d.*, COALESCE(NULLIF(g.ho_ten, ''), 'Phan Ngọc Tuyến') as gv_name, m.ten_mon
    FROM tai_lieu d
    LEFT JOIN giang_vien g ON d.giang_vien_id = g.id
    LEFT JOIN mon_hoc m ON d.mon_hoc_id = m.id
    ORDER BY d.id DESC
");
$docs = $res_doc->fetch_all(MYSQLI_ASSOC);
echo "\nTotal documents available: " . count($docs) . "\n";
foreach ($docs as $d) {
    echo " * Doc ID {$d['id']}: '{$d['ten_tai_lieu']}' | Subject: {$d['ten_mon']} | GV: {$d['gv_name']} | Link: {$d['link_download']}\n";
}

// Lessons query
$res_less = $db->query("
    SELECT l.*, COALESCE(NULLIF(g.ho_ten, ''), 'Phan Ngọc Tuyến') as gv_name, m.ten_mon
    FROM lessons l
    LEFT JOIN giang_vien g ON l.giang_vien_id = g.id
    LEFT JOIN mon_hoc m ON l.mon_hoc_id = m.id
    ORDER BY l.mon_hoc_id ASC, l.id ASC
");
$lessons = $res_less->fetch_all(MYSQLI_ASSOC);
echo "\nTotal lessons available: " . count($lessons) . "\n";
foreach ($lessons as $l) {
    echo " > Lesson ID {$l['id']}: '{$l['tieu_de']}' | Subject: {$l['ten_mon']} | Video: {$l['video_url']}\n";
}
