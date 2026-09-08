<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isStudentLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Bạn chưa đăng nhập!']);
    exit;
}

$st_id = (int)($_SESSION['student_id'] ?? ($_SESSION['sv_id'] ?? ($_SESSION['user_id'] ?? 0)));
$user_id = (int)($_SESSION['user_id'] ?? ($_SESSION['id'] ?? 0));
$db = getDB();

if ($st_id === 0 && !empty($_SESSION['username'])) {
    $u = $db->real_escape_string($_SESSION['username']);
    $res = @$db->query("SELECT id FROM students WHERE username='$u' OR mssv='$u' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $st_id = (int)$row['id'];
    }
}

// Schema initialization should be done safely with SHOW COLUMNS check, but to prevent fatal exceptions on every run:
@$db->query("ALTER TABLE students MODIFY COLUMN banner LONGTEXT NULL");
@$db->query("ALTER TABLE students MODIFY COLUMN tiktok_video LONGTEXT NULL");
@$db->query("ALTER TABLE sinh_vien MODIFY COLUMN banner LONGTEXT NULL");
@$db->query("ALTER TABLE sinh_vien MODIFY COLUMN tiktok_video LONGTEXT NULL");

function updateStudentField($db, $field, $escapedValue, $st_id, $user_id) {
    $where = [];
    if ($st_id > 0) $where[] = "id = $st_id";
    if ($user_id > 0) $where[] = "user_id = $user_id";
    if (!empty($_SESSION['username'])) {
        $u_esc = $db->real_escape_string($_SESSION['username']);
        $where[] = "ma_sv = '$u_esc'";
        $where[] = "username = '$u_esc'";
    }
    if (!empty($_SESSION['ma_sv'])) {
        $msv_esc = $db->real_escape_string($_SESSION['ma_sv']);
        $where[] = "ma_sv = '$msv_esc'";
    }
    $where_str = implode(' OR ', $where);
    if (!empty($where_str)) {
        @$db->query("UPDATE students SET {$field}='$escapedValue' WHERE $where_str");
        @$db->query("UPDATE sinh_vien SET {$field}='$escapedValue' WHERE $where_str");
    }
}



function getExistingStudentGallery($db, $field, $st_id, $user_id) {
    $raw = '';
    $st_target = $st_id ?: $user_id;
    if ($st_target > 0) {
        $res = @$db->query("SELECT {$field} FROM students WHERE id=$st_target OR user_id=$st_target LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $raw = $row[$field] ?? '';
        }
    }
    if (empty($raw) && !empty($_SESSION['username'])) {
        $u = $db->real_escape_string($_SESSION['username']);
        $res = @$db->query("SELECT {$field} FROM students WHERE username='$u' OR mssv='$u' OR ma_sv='$u' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $raw = $row[$field] ?? '';
        }
    }
    $list = [];
    if (!empty($raw)) {
        $raw_trimmed = trim($raw, '"\' ');
        $decoded = @json_decode($raw_trimmed, true);
        if (is_array($decoded)) {
            $list = array_values(array_filter($decoded));
        } else {
            $unslashed = stripslashes($raw_trimmed);
            $decoded2 = @json_decode($unslashed, true);
            if (is_array($decoded2)) {
                $list = array_values(array_filter($decoded2));
            } else if (!empty($raw_trimmed) && strpos($raw_trimmed, 'blob:') !== 0) {
                $list = [$raw_trimmed];
            }
        }
    }
    return $list;
}

// Action 1: Save full video gallery JSON array to DB
if (isset($_POST['action']) && $_POST['action'] === 'save_video_gallery') {
    $gallery_json = trim($_POST['gallery_json'] ?? '');
    if (isset($_POST['gallery_json'])) {
        $decoded = @json_decode($gallery_json, true);
        if (!is_array($decoded)) { $decoded = []; }
        
        $clean = array_values(array_filter($decoded, function($url) {
            return !empty($url) && is_string($url) && strpos($url, 'blob:') !== 0;
        }));
        
        if (empty($clean)) {
            updateStudentField($db, 'tiktok_video', '', $st_id, $user_id);
            $_SESSION['tiktok_video'] = '';
        } else {
            $json_str = json_encode($clean, JSON_UNESCAPED_SLASHES);
            $escaped = $db->real_escape_string($json_str);
            updateStudentField($db, 'tiktok_video', $escaped, $st_id, $user_id);
            $_SESSION['tiktok_video'] = $json_str;
        }
        $db->close();
        echo json_encode(['success' => true, 'gallery' => $clean]);
        exit;
    }
    $db->close();
    echo json_encode(['success' => false, 'error' => 'Dữ liệu bộ sưu tập video không hợp lệ.']);
    exit;
}

// Action 2: Save full banner gallery JSON array to DB
if (isset($_POST['action']) && $_POST['action'] === 'save_banner_gallery') {
    $gallery_json = trim($_POST['gallery_json'] ?? '');
    if (isset($_POST['gallery_json'])) {
        $decoded = @json_decode($gallery_json, true);
        if (!is_array($decoded)) { $decoded = []; }

        $clean = array_values(array_filter($decoded, function($url) {
            return !empty($url) && is_string($url) && strpos($url, 'blob:') !== 0;
        }));
        
        if (empty($clean)) {
            updateStudentField($db, 'banner', '', $st_id, $user_id);
            $_SESSION['banner'] = '';
        } else {
            $json_str = json_encode($clean, JSON_UNESCAPED_SLASHES);
            $escaped = $db->real_escape_string($json_str);
            updateStudentField($db, 'banner', $escaped, $st_id, $user_id);
            $_SESSION['banner'] = $json_str;
        }
        $db->close();
        echo json_encode(['success' => true, 'gallery' => $clean]);
        exit;
    }
    $db->close();
    echo json_encode(['success' => false, 'error' => 'Dữ liệu bộ sưu tập banner không hợp lệ.']);
    exit;
}

// Action 3: Upload new banner image file (Immediately updates MySQL DB on server side!)
if (isset($_FILES['banner_file'])) {
    $file = $_FILES['banner_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $db->close();
        echo json_encode(['success' => false, 'error' => 'Lỗi tải tệp ảnh banner lên máy chủ.']);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        $db->close();
        echo json_encode(['success' => false, 'error' => 'Định dạng ảnh .' . $ext . ' không được hỗ trợ.']);
        exit;
    }

    $destDir = __DIR__ . "/../assets/img/banners/";
    if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
    if (!is_writable($destDir)) {
        $destDir = __DIR__ . "/../assets/uploads/";
        if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
    }

    $filename = "banner_" . ($st_id ?: $user_id ?: time()) . "_" . time() . "." . $ext;
    $targetPath = $destDir . $filename;

    if (@move_uploaded_file($file['tmp_name'], $targetPath)) {
        $banner_url = (strpos($destDir, 'banners') !== false) ? "/tkb/assets/img/banners/" . $filename : "/tkb/assets/uploads/" . $filename;
        
        // Immediate server-side MySQL update for multi-banner gallery mode
        $existing = getExistingStudentGallery($db, 'banner', $st_id, $user_id);
        if (!in_array($banner_url, $existing)) {
            $existing[] = $banner_url;
        }
        $json_str = json_encode(array_values($existing), JSON_UNESCAPED_SLASHES);
        $escaped = $db->real_escape_string($json_str);
        updateStudentField($db, 'banner', $escaped, $st_id, $user_id);
        $_SESSION['banner'] = $json_str;

        $db->close();
        echo json_encode(['success' => true, 'banner_url' => $banner_url, 'gallery' => array_values($existing)]);
        exit;
    }
    $db->close();
    echo json_encode(['success' => false, 'error' => 'Không thể di chuyển tệp ảnh banner vào thư mục lưu trữ.']);
    exit;
}

// Action 4: Upload new video file (Immediately updates MySQL DB on server side!)
if (isset($_FILES['video_file'])) {
    $file = $_FILES['video_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errCode = $file['error'];
        $msg = "Lỗi tải tệp lên server (Mã: $errCode)";
        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $msg = "Tệp video quá dung lượng cho phép của server (Tối đa 5MB)!";
        }
        $db->close();
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['mp4', 'webm', 'ogg', 'mov', 'm4v', 'avi', 'mkv'];

    if (!in_array($ext, $allowed)) {
        $db->close();
        echo json_encode(['success' => false, 'error' => 'Định dạng tệp .' . $ext . ' không được hỗ trợ.']);
        exit;
    }

    $destDir = __DIR__ . "/../assets/uploads/videos/";
    if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
    if (!is_writable($destDir)) {
        $destDir = __DIR__ . "/../assets/uploads/";
        if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
    }

    $filename = "video_" . ($st_id ?: $user_id ?: time()) . "_" . time() . "." . $ext;
    $targetPath = $destDir . $filename;

    if (@move_uploaded_file($file['tmp_name'], $targetPath)) {
        $video_url = (strpos($destDir, 'videos') !== false) ? "/tkb/assets/uploads/videos/" . $filename : "/tkb/assets/uploads/" . $filename;

        // Immediate server-side MySQL update!
        $existing = getExistingStudentGallery($db, 'tiktok_video', $st_id, $user_id);
        if (!in_array($video_url, $existing)) {
            $existing[] = $video_url;
        }
        $json_str = json_encode(array_values($existing), JSON_UNESCAPED_SLASHES);
        $escaped = $db->real_escape_string($json_str);
        updateStudentField($db, 'tiktok_video', $escaped, $st_id, $user_id);
        $_SESSION['tiktok_video'] = $json_str;

        $db->close();
        echo json_encode(['success' => true, 'video_url' => $video_url, 'gallery' => $existing]);
        exit;
    }
    $db->close();
    echo json_encode(['success' => false, 'error' => 'Không thể di chuyển tệp video vào thư mục lưu trữ.']);
    exit;
}

$db->close();
echo json_encode(['success' => false, 'error' => 'Không tìm thấy yêu cầu hợp lệ.']);
