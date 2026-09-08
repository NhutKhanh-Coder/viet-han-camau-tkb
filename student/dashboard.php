<?php
require_once __DIR__ . '/../config.php';
requireStudent();

$db      = getDB();
$sv_id   = $_SESSION['student_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if (!$sv_id && $user_id > 0) {
    $check_sv = @$db->query("SELECT id, ho_ten, ma_sv, gioi_tinh FROM students WHERE user_id = $user_id LIMIT 1");
    if ($check_sv && method_exists($check_sv, 'fetch_assoc') && ($sv_row = $check_sv->fetch_assoc())) {
        $sv_id = (int)$sv_row['id'];
        $_SESSION['student_id'] = $sv_id;
        $_SESSION['ho_ten']     = $sv_row['ho_ten'];
        $_SESSION['ma_sv']      = $sv_row['ma_sv'];
        $_SESSION['gioi_tinh']   = $sv_row['gioi_tinh'] ?? 'Nam';
    }
}

if (!$sv_id && $user_id > 0) {
    $sv_id = $user_id;
    $_SESSION['student_id'] = $sv_id;
}

if (!$user_id) {
    header('Location: /tkb/login.php');
    exit;
}

function dashboardAppendToGallery($db, $field, $newValue, $st_id, $user_id) {
    if (empty($newValue)) return '';
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
    if (!in_array($newValue, $list)) {
        $list[] = $newValue;
    }
    $json_str = json_encode(array_values($list), JSON_UNESCAPED_SLASHES);
    $escaped = $db->real_escape_string($json_str);

    $st_target = $st_id ?: $user_id;
    if ($st_target > 0) {
        @$db->query("UPDATE sinh_vien SET {$field}='$escaped' WHERE id=$st_target OR user_id=$st_target");
        @$db->query("UPDATE students SET {$field}='$escaped' WHERE id=$st_target OR user_id=$st_target");
        $chk = @$db->query("SELECT id FROM students WHERE id=$st_target OR user_id=$st_target LIMIT 1");
        if ($chk && method_exists($chk, 'fetch_assoc') && $chk->fetch_assoc()) {
            $_SESSION[$field] = $json_str;
            return $json_str;
        }
    }
    if (!empty($_SESSION['username'])) {
        $u = $db->real_escape_string($_SESSION['username']);
        @$db->query("UPDATE sinh_vien SET {$field}='$escaped' WHERE ma_sv='$u' OR mssv='$u' OR username='$u'");
        @$db->query("UPDATE students SET {$field}='$escaped' WHERE ma_sv='$u' OR mssv='$u' OR username='$u'");
        $chk = @$db->query("SELECT id FROM students WHERE ma_sv='$u' OR mssv='$u' OR username='$u' LIMIT 1");
        if ($chk && method_exists($chk, 'fetch_assoc') && $chk->fetch_assoc()) {
            $_SESSION[$field] = $json_str;
            return $json_str;
        }
    }
    if ($user_id > 0) {
        $u_name = $db->real_escape_string($_SESSION['username'] ?? 'K24CDCNTT');
        @$db->query("INSERT INTO students (user_id, ma_sv, ho_ten, lop, khoa, {$field}) VALUES ($user_id, '$u_name', '$u_name', 'K24CDCNTT', 'Công nghệ thông tin', '$escaped')");
    }

    $_SESSION[$field] = $json_str;
    return $json_str;
}

function dashboardSaveGallery($db, $field, $cleanArray, $st_id, $user_id) {
    if (!is_array($cleanArray) || empty($cleanArray)) {
        $escaped = '';
        $json_str = '';
    } else {
        $json_str = json_encode(array_values($cleanArray), JSON_UNESCAPED_SLASHES);
        $escaped = $db->real_escape_string($json_str);
    }

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
        @$db->query("UPDATE students SET {$field}='$escaped' WHERE $where_str");
        @$db->query("UPDATE sinh_vien SET {$field}='$escaped' WHERE $where_str");
    }

    $_SESSION[$field] = $json_str;
    return $json_str;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_video_gallery') {
    $st_id = (int)$sv_id;
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $gallery_json = trim($_POST['gallery_json'] ?? '');
    $decoded = @json_decode($gallery_json, true);
    if (!is_array($decoded)) { $decoded = []; }
    $clean = array_values(array_filter($decoded, function($url) {
        return !empty($url) && is_string($url) && strpos($url, 'blob:') !== 0;
    }));

    dashboardSaveGallery($db, 'tiktok_video', $clean, $st_id, $user_id);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'gallery' => $clean]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_banner_gallery') {
    $st_id = (int)$sv_id;
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $gallery_json = trim($_POST['gallery_json'] ?? '');
    $decoded = @json_decode($gallery_json, true);
    if (!is_array($decoded)) { $decoded = []; }
    $clean = array_values(array_filter($decoded, function($url) {
        return !empty($url) && is_string($url) && strpos($url, 'blob:') !== 0;
    }));

    dashboardSaveGallery($db, 'banner', $clean, $st_id, $user_id);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'gallery' => $clean]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_banner') {
    $st_id = (int)$sv_id;
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $bannerValue = "";

    // 1. Try Base64 Data URL conversion to file first
    $base64 = $_POST['banner_base64'] ?? '';
    if (!empty($base64) && strpos($base64, 'data:image') === 0) {
        $data = substr($base64, strpos($base64, ',') + 1);
        $decoded = base64_decode($data);
        if ($decoded !== false) {
            $savedFilename = "banner_" . ($st_id ?: $user_id) . "_" . time() . ".jpg";
            $destDir = __DIR__ . "/../assets/img/banners/";
            if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
            if (@file_put_contents($destDir . $savedFilename, $decoded) !== false) {
                $bannerValue = "/tkb/assets/img/banners/" . $savedFilename;
            } else {
                $fallbackDir = __DIR__ . "/../assets/img/";
                if (@file_put_contents($fallbackDir . $savedFilename, $decoded) !== false) {
                    $bannerValue = "/tkb/assets/img/" . $savedFilename;
                } else {
                    $bannerValue = $base64;
                }
            }
        }
    }

    // 2. Fallback to standard $_FILES Upload if bannerValue is empty
    if (empty($bannerValue) && isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['banner_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $savedFilename = "banner_" . ($st_id ?: $user_id) . "_" . time() . "." . $ext;
            $destDir = __DIR__ . "/../assets/img/banners/";
            if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
            if (@move_uploaded_file($_FILES['banner_file']['tmp_name'], $destDir . $savedFilename)) {
                $bannerValue = "/tkb/assets/img/banners/" . $savedFilename;
            } else {
                $fallbackDir = __DIR__ . "/../assets/img/";
                if (@move_uploaded_file($_FILES['banner_file']['tmp_name'], $fallbackDir . $savedFilename)) {
                    $bannerValue = "/tkb/assets/img/" . $savedFilename;
                }
            }
        }
    }

    if (!empty($bannerValue)) {
        try {
            dashboardAppendToGallery($db, 'banner', $bannerValue, $st_id, $user_id);
        } catch (Throwable $e) {
            error_log("Upload banner error: " . $e->getMessage());
        }
        header('Location: /tkb/student/dashboard.php?upload=success');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_tiktok_video') {
    $st_id = (int)$sv_id;
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $video_val = trim($_POST['video_url'] ?? '');
    $upload_err = '';

    if (isset($_FILES['video_file'])) {
        $file = $_FILES['video_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['mp4', 'webm', 'ogg', 'mov', 'm4v', 'avi', 'mkv'];
            if (in_array($ext, $allowed)) {
                $destDir = __DIR__ . "/../assets/uploads/videos/";
                if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
                if (!is_writable($destDir)) {
                    $destDir = __DIR__ . "/../assets/uploads/";
                    if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
                }
                $filename = "video_" . ($st_id ?: time()) . "_" . time() . "." . $ext;
                if (@move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
                    if (strpos($destDir, 'videos') !== false) {
                        $video_val = "/tkb/assets/uploads/videos/" . $filename;
                    } else {
                        $video_val = "/tkb/assets/uploads/" . $filename;
                    }
                } else {
                    $upload_err = "Không thể di chuyển tệp video lên máy chủ.";
                }
            } else {
                $upload_err = "Định dạng tệp ." . $ext . " không được hỗ trợ.";
            }
        } else {
            $errCode = $file['error'];
            if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                $upload_err = "Tệp video quá lớn (Vượt giới hạn 10MB của máy chủ).";
            } else {
                $upload_err = "Lỗi tải tệp (Mã lỗi: " . $errCode . ").";
            }
        }
    }

    if (!empty($video_val)) {
        try {
            dashboardAppendToGallery($db, 'tiktok_video', $video_val, $st_id, $user_id);
        } catch (Throwable $e) {
            error_log("Save video error: " . $e->getMessage());
        }
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        if (!empty($video_val)) {
            echo json_encode(['success' => true, 'video_url' => $video_val]);
        } else {
            echo json_encode(['success' => false, 'error' => $upload_err ?: 'Không thể lưu video']);
        }
        exit;
    }

    header('Location: /tkb/student/dashboard.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_banner_pos') {
    $st_id = (int)$sv_id;
    $pos_val = trim($_POST['banner_pos'] ?? 'center center');
    $escaped = $db->real_escape_string($pos_val);

    $updated = false;
    if ($st_id > 0) {
        $res = @$db->query("UPDATE students SET banner_pos='$escaped' WHERE id=$st_id");
        if ($res && $db->affected_rows > 0) { $updated = true; }
    }
    if (!$updated && $user_id > 0) {
        $res = @$db->query("UPDATE students SET banner_pos='$escaped' WHERE user_id=$user_id");
        if ($res && $db->affected_rows > 0) { $updated = true; }
    }
    if (!$updated && $user_id > 0) {
        $u_name = $db->real_escape_string($_SESSION['username'] ?? 'K24CDCNTT');
        @$db->query("INSERT INTO students (user_id, ma_sv, ho_ten, lop, khoa, banner_pos) VALUES ($user_id, '$u_name', '$u_name', 'K24CDCNTT', 'Công nghệ thông tin', '$escaped')");
    }
    $_SESSION['banner_pos'] = $pos_val;

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'banner_pos' => $pos_val]);
        exit;
    }

    header('Location: /tkb/student/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_banner_fit') {
    $st_id = (int)$sv_id;
    $fit_val = trim($_POST['banner_fit'] ?? 'cover');
    $escaped = $db->real_escape_string($fit_val);

    $updated = false;
    if ($st_id > 0) {
        $res = @$db->query("UPDATE students SET banner_fit='$escaped' WHERE id=$st_id");
        if ($res && $db->affected_rows > 0) { $updated = true; }
    }
    if (!$updated && $user_id > 0) {
        $res = @$db->query("UPDATE students SET banner_fit='$escaped' WHERE user_id=$user_id");
        if ($res && $db->affected_rows > 0) { $updated = true; }
    }
    if (!$updated && $user_id > 0) {
        $u_name = $db->real_escape_string($_SESSION['username'] ?? 'K24CDCNTT');
        @$db->query("INSERT INTO students (user_id, ma_sv, ho_ten, lop, khoa, banner_fit) VALUES ($user_id, '$u_name', '$u_name', 'K24CDCNTT', 'Công nghệ thông tin', '$escaped')");
    }
    $_SESSION['banner_fit'] = $fit_val;

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'banner_fit' => $fit_val]);
        exit;
    }

    header('Location: /tkb/student/dashboard.php');
    exit;
}

$user_id_sess = (int)($_SESSION['user_id'] ?? 0);
$sv_id_sess   = (int)($_SESSION['student_id'] ?? 0);
$u_sess       = !empty($_SESSION['username']) ? $db->real_escape_string($_SESSION['username']) : '';

$sv = null;
if ($sv_id_sess > 0) {
    $check_sv = @$db->query("SELECT s.*, u.username FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = $sv_id_sess LIMIT 1");
    if ($check_sv && method_exists($check_sv, 'fetch_assoc')) {
        $sv = $check_sv->fetch_assoc();
    }
}
if (!$sv && $user_id_sess > 0) {
    $check_sv = @$db->query("SELECT s.*, u.username FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.user_id = $user_id_sess LIMIT 1");
    if ($check_sv && method_exists($check_sv, 'fetch_assoc')) {
        $sv = $check_sv->fetch_assoc();
    }
}
if (!$sv && !empty($u_sess)) {
    $check_sv = @$db->query("SELECT s.*, u.username FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.ma_sv = '$u_sess' OR s.username = '$u_sess' LIMIT 1");
    if ($check_sv && method_exists($check_sv, 'fetch_assoc')) {
        $sv = $check_sv->fetch_assoc();
    }
}

if (!$sv) {
    $sv = [
        'ho_ten'       => $_SESSION['ho_ten'] ?? $_SESSION['username'] ?? 'LÊ NHỰT KHÁNH',
        'ma_sv'        => $_SESSION['ma_sv'] ?? $_SESSION['username'] ?? 'K24CDCNTT',
        'lop'          => 'K24CDCNTT',
        'khoa'         => 'Công nghệ thông tin',
        'email'        => '',
        'avatar'       => '',
        'username'     => $_SESSION['username'] ?? 'lenhutkhanh',
        'gioi_tinh'    => $_SESSION['gioi_tinh'] ?? 'Nam',
        'banner'       => $_SESSION['banner'] ?? '',
        'tiktok_video' => $_SESSION['tiktok_video'] ?? ''
    ];
}

if (!empty($sv['gioi_tinh'])) {
    $_SESSION['gioi_tinh'] = $sv['gioi_tinh'];
}

// === Lấy lịch học hôm nay ===
$khoa = $sv['khoa'] ?? '';
$thu_hien_tai = (int)date('N') + 1;

$tkb_hom_nay = [];
if (!empty($khoa)) {
    $st2 = $db->prepare(
        "SELECT t.*, m.ten_mon, g.ho_ten AS ten_gv
         FROM thoi_khoa_bieu t
         JOIN mon_hoc m ON t.mon_hoc_id = m.id
         LEFT JOIN giang_vien g ON t.giang_vien_id = g.id
         WHERE t.khoa = ? AND t.thu = ?
         ORDER BY t.tiet_bat_dau ASC"
    );
    if ($st2) {
        $st2->bind_param("si", $khoa, $thu_hien_tai);
        if ($st2->execute() && ($res2 = $st2->get_result())) {
            if (method_exists($res2, 'fetch_all')) {
                $tkb_hom_nay = $res2->fetch_all(MYSQLI_ASSOC);
            } else {
                while ($row = $res2->fetch_assoc()) {
                    $tkb_hom_nay[] = $row;
                }
            }
        }
        $st2->close();
    }
}

// === Lấy thông báo mới nhất & Chế độ Lịch học/Lịch thi ===
$system_schedule_mode = getSystemSetting('schedule_mode', 'lich_hoc', $db);
$thong_bao = [];
$tb_res = @$db->query("SELECT * FROM thong_bao WHERE trang_thai='Đã xuất bản' ORDER BY ngay_dang DESC, id DESC LIMIT 10");
if ($tb_res) {
    if (method_exists($tb_res, 'fetch_all')) {
        $thong_bao = $tb_res->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $tb_res->fetch_assoc()) {
            $thong_bao[] = $row;
        }
    }
}

$is_female = (($sv['gioi_tinh'] ?? '') === 'Nữ' || ($_SESSION['gioi_tinh'] ?? '') === 'Nữ');
?>
<!DOCTYPE html>
<html lang="vi" data-mc-mode="<?= $is_female ? 'female' : 'dark' ?>" data-mc-theme="<?= $is_female ? 'female' : 'red' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<script>
(function() {
    try {
        localStorage.removeItem('st_user_banner_url');
        localStorage.removeItem('st_saved_tiktok_video');
        localStorage.removeItem('st_user_banner_ts');
        localStorage.removeItem('st_saved_banner_pos');
        localStorage.removeItem('st_saved_banner_fit');
        localStorage.removeItem('st_saved_video_fit');

        var svId = <?= json_encode($sv_id) ?>;
        var b = localStorage.getItem('st_user_banner_url_' + svId);
        if (!b) {
            var saved = localStorage.getItem('sf_banner_gallery_' + svId);
            if (saved) {
                try {
                    var items = JSON.parse(saved);
                    if (Array.isArray(items) && items.length > 0) {
                        b = items[items.length - 1];
                    }
                } catch(err) {}
            }
        }
        if (b && typeof b === 'string') {
            b = b.replace(/^["']+|["']+$|\\/g, '').trim();
            if (b && b.indexOf('http') !== 0 && b.indexOf('data:') !== 0 && b.indexOf('/') !== 0) {
                b = '/tkb/assets/img/banners/' + b;
            }
            if (b && b.indexOf('minecraft_hero.png') === -1 && b.indexOf('ponyo_banner.gif') === -1 && b.indexOf('1785338518') === -1 && b.indexOf('1785302601') === -1) {
                window.__PRELOADED_BANNER__ = b;
            }
        }
    } catch(e) {}
})();
</script>

<title><?= (($sv['gioi_tinh'] ?? '') === 'Nữ' || ($_SESSION['gioi_tinh'] ?? '') === 'Nữ') ? 'Cổng Sinh Viên - Hệ Thống Quản Lý Đào Tạo' : 'Cổng Sinh Viên - Minecraft Theme' ?></title>

<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Silkscreen:wght@400;700&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/minecraft_student.css?v=<?= time() ?>">
<style>
/* Viền LED Avatar 7 Màu Di Chuyển Xoay Tròn (RGB Rotating LED Avatar Border) */
.mc-led-avatar-7mau {
    position: relative;
    width: 66px;
    height: 66px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 3.5px !important;
    flex-shrink: 0;
    box-shadow: 0 0 16px rgba(255, 0, 85, 0.9), 0 0 25px rgba(0, 217, 255, 0.6);
    animation: mcLedAvatarGlow 2.5s infinite linear;
}

.mc-led-avatar-7mau::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: conic-gradient(
        from 0deg,
        #ff0055, 
        #ff5500, 
        #ffe600, 
        #00ff66, 
        #00d9ff, 
        #a855f7, 
        #ff00cc, 
        #ff0055
    );
    animation: mcSpinLed 2s linear infinite;
    z-index: 1;
}

.mc-led-avatar-7mau img {
    position: relative;
    z-index: 2;
    width: calc(100% - 7px) !important;
    height: calc(100% - 7px) !important;
    border-radius: 50% !important;
    object-fit: cover !important;
    display: block !important;
    border: 2px solid #0f172a !important;
    box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.6) !important;
}

@keyframes mcSpinLed {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes mcLedAvatarGlow {
    0% { box-shadow: 0 0 16px rgba(255, 0, 85, 0.9), 0 0 26px rgba(255, 0, 85, 0.5); }
    20% { box-shadow: 0 0 16px rgba(255, 85, 0, 0.9), 0 0 26px rgba(255, 85, 0, 0.5); }
    40% { box-shadow: 0 0 16px rgba(255, 230, 0, 0.9), 0 0 26px rgba(255, 230, 0, 0.5); }
    60% { box-shadow: 0 0 16px rgba(0, 255, 102, 0.9), 0 0 26px rgba(0, 255, 102, 0.5); }
    80% { box-shadow: 0 0 16px rgba(0, 217, 255, 0.9), 0 0 26px rgba(0, 217, 255, 0.5); }
    100% { box-shadow: 0 0 16px rgba(255, 0, 204, 0.9), 0 0 26px rgba(255, 0, 204, 0.5); }
}
</style>
</head>
<body data-mc-mode="<?= $is_female ? 'female' : 'dark' ?>" data-mc-theme="<?= $is_female ? 'female' : 'red' ?>">
<div style="display:none;" id="debug_sv"><?= htmlspecialchars(json_encode(['sv' => $sv, 'session' => $_SESSION])) ?></div>
<?php include '../includes/student_nav.php'; ?>

        <?php 
        $is_female = (($sv['gioi_tinh'] ?? '') === 'Nữ' || ($_SESSION['gioi_tinh'] ?? '') === 'Nữ');
        $default_banner = '';
        
        function fixBannerUrl($url) {
            if (empty($url) || !is_string($url)) return '';
            $u = trim($url, "\"'\t\n\r\0\x0B\\ ");
            if (empty($u)) return '';
            $lower = strtolower($u);
            if ($lower === 'banner.jpg' || $lower === 'default.jpg' || $lower === 'default.png' || $lower === 'banner.png' || $lower === 'sample.jpg' || $lower === 'placeholder.jpg') {
                return '';
            }
            if (strpos($lower, 'banner.jpg') !== false && strpos($lower, 'banner_') === false) {
                return '';
            }
            if (strpos($lower, '1785338518') !== false || strpos($lower, '1785302601') !== false) {
                return '';
            }
            if (strpos($u, 'http://') === 0 || strpos($u, 'https://') === 0 || strpos($u, 'data:image') === 0 || strpos($u, '/') === 0) {
                if (rtrim($u, '/') === '/tkb/assets/img/banners' || rtrim($u, '/') === '/tkb/assets/img' || rtrim($u, '/') === '/tkb/assets/img/banners/banner.jpg') return '';
                return $u;
            }
            return '/tkb/assets/img/banners/' . $u;
        }

        function fixVideoUrl($url) {
            if (empty($url) || !is_string($url)) return '';
            $u = trim($url, "\"'\t\n\r\0\x0B\\ ");
            if (empty($u)) return '';
            $lower = strtolower($u);
            if ($lower === 'video.mp4' || $lower === 'default.mp4' || $lower === 'sample.mp4' || $lower === 'intro_video.mp4') return '';
            if (strpos($lower, 'video_original_backup.mp4') !== false) return '';
            if (strpos($u, 'http://') === 0 || strpos($u, 'https://') === 0 || strpos($u, 'data:video') === 0 || strpos($u, '/') === 0) {
                if (rtrim($u, '/') === '/tkb/assets/uploads/videos' || rtrim($u, '/') === '/tkb/assets/uploads') return '';
                return $u;
            }
            return '/tkb/assets/uploads/videos/' . $u;
        }

        $raw_banner_val = array_key_exists('banner', $sv) ? $sv['banner'] : ($_SESSION['banner'] ?? '');
        $banner_url = $default_banner;
        $banner_gallery_list = [];
        if (!empty($raw_banner_val) && is_string($raw_banner_val)) {
            $raw_banner_val = trim($raw_banner_val, '"\' ');
            $lower_raw = strtolower($raw_banner_val);
            if ($lower_raw === 'banner.jpg' || $lower_raw === 'default.jpg' || $lower_raw === 'default.png') {
                $raw_banner_val = '';
            }
            if (!empty($raw_banner_val)) {
                $decoded = @json_decode($raw_banner_val, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $banner_gallery_list = array_values(array_filter($decoded));
                } else {
                    $unslashed = stripslashes($raw_banner_val);
                    $decoded2 = @json_decode($unslashed, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded2)) {
                        $banner_gallery_list = array_values(array_filter($decoded2));
                    } else if (!empty($raw_banner_val)) {
                        $banner_gallery_list = [$raw_banner_val];
                    }
                }
            }
        }
        $banner_gallery_list = array_values(array_filter(array_map('fixBannerUrl', $banner_gallery_list)));
        if (!empty($banner_gallery_list)) {
            $banner_url = end($banner_gallery_list);
        }

        $raw_video_val = '';
        if (isset($sv['tiktok_video']) && is_string($sv['tiktok_video']) && trim($sv['tiktok_video']) !== '') {
            $raw_video_val = trim($sv['tiktok_video']);
        } else if (!empty($_SESSION['tiktok_video']) && is_string($_SESSION['tiktok_video'])) {
            $raw_video_val = trim($_SESSION['tiktok_video']);
        }
        $saved_tiktok_video = '';
        $video_gallery_list = [];
        if (!empty($raw_video_val)) {
            $raw_video_val = trim($raw_video_val, '"\' ');
            $decoded = @json_decode($raw_video_val, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $video_gallery_list = array_values(array_filter($decoded));
            } else {
                $unslashed = stripslashes($raw_video_val);
                $decoded2 = @json_decode($unslashed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded2)) {
                    $video_gallery_list = array_values(array_filter($decoded2));
                } else if (!empty($raw_video_val)) {
                    $video_gallery_list = [$raw_video_val];
                }
            }
        }
        $video_gallery_list = array_values(array_filter(array_map('fixVideoUrl', $video_gallery_list)));
        if (!empty($video_gallery_list)) {
            $saved_tiktok_video = end($video_gallery_list);
        } else {
            $_SESSION['tiktok_video'] = '';
        }

        $banner_pos = isset($sv['banner_pos']) ? $sv['banner_pos'] : ($_SESSION['banner_pos'] ?? 'center center');
        if (empty($banner_pos)) { $banner_pos = 'center center'; }
        $banner_fit = isset($sv['banner_fit']) ? $sv['banner_fit'] : ($_SESSION['banner_fit'] ?? 'cover');
        if (empty($banner_fit)) { $banner_fit = 'cover'; }
        ?>


        <?php if ($is_female) { ?>
        <style>
        /* Modern Soft UI - Female Specific Styles matching Image 2 */
        body, body[data-mc-mode="female"] {
            background: #f4f0f7 !important;
            font-family: 'Outfit', 'Inter', sans-serif !important;
            color: #1e293b !important;
        }
        .main-content { background: #f4f0f7 !important; }
        
        .top-header {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(16px) !important;
            border-bottom: 1px solid #f3e8ff !important;
            box-shadow: 0 4px 20px rgba(236, 72, 153, 0.04) !important;
        }

        .sf-hero-card {
            background: linear-gradient(135deg, #ede9fe 0%, #f3e8ff 100%) !important;
            border: 1.5px solid #e9d5ff !important;
            border-radius: 24px !important;
            padding: 20px 22px !important;
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.08) !important;
            display: grid !important;
            grid-template-columns: 1.4fr 1fr !important;
            gap: 16px !important;
            align-items: center !important;
            position: relative;
            overflow: hidden;
            height: 295px;
        }

        .sf-stat-box-small {
            background: #ffffff !important;
            border: 1px solid #f3e8ff !important;
            border-radius: 12px !important;
            padding: 8px 12px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.05) !important;
        }

        .sf-stat-val-small {
            font-size: 15px !important;
            font-weight: 900 !important;
            color: #0f172a !important;
        }

        .sf-stat-lbl-small {
            font-size: 9.5px !important;
            color: #64748b !important;
            font-weight: 600 !important;
        }

        .sf-btn-view-profile {
            background: #ffffff !important;
            color: #6d28d9 !important;
            border: 1px solid #ddd6fe !important;
            border-radius: 20px !important;
            padding: 7px 18px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            box-shadow: 0 4px 12px rgba(109, 40, 217, 0.1) !important;
            transition: all 0.2s ease !important;
            margin-top: 6px;
        }

        .sf-btn-view-profile:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 18px rgba(109, 40, 217, 0.2) !important;
        }

        .sf-video-card {
            background: #ffffff !important;
            border: 1.5px solid #f3e8ff !important;
            border-radius: 24px !important;
            padding: 16px !important;
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.06) !important;
            height: 295px;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }

        .sf-card-white {
            background: #ffffff !important;
            border: 1.5px solid #f3e8ff !important;
            border-radius: 20px !important;
            padding: 18px !important;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.05) !important;
            transition: all 0.3s ease !important;
        }

        .sf-card-white:hover {
            box-shadow: 0 12px 32px rgba(139, 92, 246, 0.12) !important;
            transform: translateY(-2px) !important;
        }

        /* Banner Clean Aesthetics: Controls show on hover */
        .sf-banner-box .sf-banner-overlay-item {
            opacity: 0 !important;
            visibility: hidden !important;
            transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .sf-banner-box:hover .sf-banner-overlay-item {
            opacity: 1 !important;
            visibility: visible !important;
        }
        </style>

        <div style="display: flex; flex-direction: column; gap: 20px; width: 100%;">
            <!-- Row 1: Hero Card (Welcome + Photo) & Video Gallery Box -->
            <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; align-items: stretch; width: 100%;">
                
                <!-- Left Hero Card -->
                <div class="sf-hero-card">
                    <!-- Left Details -->
                    <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; z-index: 2;">
                        <div>
                            <div style="font-size: 13px; font-weight: 600; color: #64748b;">Xin chào,</div>
                            <div id="sfHeroName" contenteditable="true" onblur="sfSaveHeroEdits()" title="✏️ Bấm vào đây để chỉnh sửa Tên của bạn" style="font-size: 22px; font-weight: 900; color: #4c1d95; margin-top: 2px; outline: none; border-radius: 6px; padding: 2px 4px; transition: all 0.2s; cursor: pointer; display: inline-block;">
                                <?= htmlspecialchars($sv['ho_ten'] ?? 'Vũ Nhật Tường Vi') ?> 💕
                            </div>
                            <br>
                            <div style="display: inline-block; background: #ddd6fe; color: #6d28d9; font-size: 10.5px; font-weight: 800; padding: 3px 10px; border-radius: 12px; margin-top: 4px;">
                                Mã SV: <?= htmlspecialchars($sv['ma_sv'] ?? 'leduykhanh') ?>
                            </div>
                            <div id="sfHeroQuote" contenteditable="true" onblur="sfSaveHeroEdits()" title="✏️ Bấm vào đây để chỉnh sửa châm ngôn" style="font-size: 11.5px; color: #6d28d9; font-style: italic; margin-top: 8px; outline: none; border-radius: 6px; padding: 2px 4px; transition: all 0.2s; cursor: pointer;">
                                Học tập là chìa khóa mở ra cánh cửa tương lai 🌸✨
                            </div>
                        </div>

                        <!-- 3 Social Media Link Boxes Grid -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px;">
                            <!-- Facebook Box -->
                            <div class="sf-stat-box-small" style="position: relative; cursor: pointer; transition: all 0.2s; padding: 6px 10px !important;" onclick="sfSocialBoxClick('facebook')" title="Bấm để mở Facebook (hoặc dán link nếu chưa có)">
                                <i class="fa-brands fa-facebook" style="color: #1877f2; font-size: 18px; flex-shrink: 0;"></i>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 11.5px; font-weight: 800; color: #1877f2;">Facebook</div>
                                    <div id="sfFbText" style="font-size: 9px; color: #64748b; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Gắn link FB</div>
                                </div>
                                <i class="fa-solid fa-pen" onclick="sfSocialPenClick('facebook', event)" style="font-size: 9px; color: #a855f7; padding: 4px; cursor: pointer;" title="Sửa link Facebook"></i>
                            </div>

                            <!-- TikTok Box -->
                            <div class="sf-stat-box-small" style="position: relative; cursor: pointer; transition: all 0.2s; padding: 6px 10px !important;" onclick="sfSocialBoxClick('tiktok')" title="Bấm để mở TikTok (hoặc dán link nếu chưa có)">
                                <i class="fa-brands fa-tiktok" style="color: #000000; font-size: 18px; flex-shrink: 0;"></i>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 11.5px; font-weight: 800; color: #0f172a;">TikTok</div>
                                    <div id="sfTiktokText" style="font-size: 9px; color: #64748b; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Gắn link TikTok</div>
                                </div>
                                <i class="fa-solid fa-pen" onclick="sfSocialPenClick('tiktok', event)" style="font-size: 9px; color: #a855f7; padding: 4px; cursor: pointer;" title="Sửa link TikTok"></i>
                            </div>

                            <!-- Instagram Box -->
                            <div class="sf-stat-box-small" style="position: relative; cursor: pointer; transition: all 0.2s; padding: 6px 10px !important;" onclick="sfSocialBoxClick('instagram')" title="Bấm để mở Instagram (hoặc dán link nếu chưa có)">
                                <i class="fa-brands fa-instagram" style="color: #e4405f; font-size: 18px; flex-shrink: 0;"></i>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 11.5px; font-weight: 800; color: #e4405f;">Instagram</div>
                                    <div id="sfIgText" style="font-size: 9px; color: #64748b; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Gắn link Insta</div>
                                </div>
                                <i class="fa-solid fa-pen" onclick="sfSocialPenClick('instagram', event)" style="font-size: 9px; color: #a855f7; padding: 4px; cursor: pointer;" title="Sửa link Instagram"></i>
                            </div>
                        </div>

                        <!-- Button -->
                        <div>
                            <a href="/tkb/student/profile.php" class="sf-btn-view-profile">
                                Xem hồ sơ của bạn &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Right Photo Box inside Hero Card -->
                    <div class="sf-banner-box" style="position: relative; height: 100%; border-radius: 18px; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.1); border: 2px solid #ffffff; cursor: pointer; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #fce7f3, #e0e7ff);" onclick="document.getElementById('sfBannerUploadMulti').click()" title="Bấm vào để chọn & thêm ảnh mới">
                        <img src="<?= htmlspecialchars($banner_url) ?>" id="ltHeroBannerImg" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: <?= $banner_url ? 'block' : 'none' ?>; z-index: 1;">
                        
                        <div id="ltHeroBannerPlaceholder" style="text-align: center; color: #a855f7; display: <?= $banner_url ? 'none' : 'block' ?>; z-index: 0;">
                            <i class="fa-solid fa-image" style="font-size: 32px; margin-bottom: 8px;"></i><br>
                            <span style="font-size: 14px; font-weight: 800;">Chưa có ảnh bìa</span><br>
                            <span style="font-size: 11px; font-weight: 600;">Bấm vào đây để tải lên</span>
                        </div>
                        
                        <span id="sfBannerCounter" class="sf-banner-overlay-item" style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 800; color: #ffffff; z-index: 5;"><?= !empty($banner_gallery_list) ? ('1 / ' . count($banner_gallery_list)) : '0 / 0' ?></span>

                        <div class="sf-banner-overlay-item" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.9); backdrop-filter: blur(8px); border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 800; color: #4c1d95; cursor: pointer; display: flex; align-items: center; gap: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 5;">
                            <i class="fa-solid fa-camera"></i> Đổi/Thêm ảnh
                        </div>

                        <button type="button" class="sf-banner-overlay-item sf-banner-prev-btn" onclick="sfBannerPrev(); event.stopPropagation();" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border-radius: 50%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); border: none; color: #fff; font-size: 12px; cursor: pointer; display: <?= count($banner_gallery_list) > 1 ? 'flex' : 'none' ?>; align-items: center; justify-content: center; z-index: 6;"><i class="fa-solid fa-chevron-left"></i></button>
                        <button type="button" class="sf-banner-overlay-item sf-banner-next-btn" onclick="sfBannerNext(); event.stopPropagation();" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border-radius: 50%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); border: none; color: #fff; font-size: 12px; cursor: pointer; display: <?= count($banner_gallery_list) > 1 ? 'flex' : 'none' ?>; align-items: center; justify-content: center; z-index: 6;"><i class="fa-solid fa-chevron-right"></i></button>
                        
                        <button type="button" class="sf-banner-overlay-item sf-banner-remove-btn" onclick="sfBannerRemoveCurrent(); event.stopPropagation();" style="position: absolute; bottom: 10px; right: 10px; background: rgba(239,68,68,0.85); backdrop-filter: blur(8px); border-radius: 10px; padding: 4px 8px; font-size: 10px; font-weight: bold; color: #fff; border: none; cursor: pointer; display: <?= !empty($banner_gallery_list) ? 'inline-flex' : 'none' ?>; z-index: 6;" title="Xóa banner này"><i class="fa-solid fa-trash-can"></i></button>
                        
                        <div id="sfBannerDots" class="sf-banner-overlay-item" style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 5px; align-items: center; z-index: 5;"></div>

                        <input type="file" id="sfBannerUploadMulti" accept="image/*" multiple style="display: none;" onchange="sfBannerAddFiles(this)">
                    </div>
                </div>

                <!-- Right Video Gallery Player Box -->
                <div class="sf-video-card">
                    <div style="font-size: 12px; font-weight: 800; color: #0f172a; display: flex; align-items: center; justify-content: space-between; border-bottom: 1.5px dashed #f3e8ff; padding-bottom: 6px;">
                        <span style="display: flex; align-items: center; gap: 6px; color: #8b5cf6;">
                            <i class="fa-solid fa-film"></i> BỘ SƯU TẬP VIDEO 
                            <span id="sfVideoHeaderCount" style="background: linear-gradient(135deg, #ec4899, #8b5cf6); color: #ffffff; font-size: 10.5px; font-weight: 800; padding: 2px 9px; border-radius: 12px; box-shadow: 0 2px 8px rgba(236,72,153,0.3); font-family: sans-serif; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-layer-group" style="font-size: 9.5px;"></i> <?= !empty($video_gallery_list) ? count($video_gallery_list) : 0 ?> Video
                            </span>
                        </span>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <span id="sfVideoCounter" style="background: #f3e8ff; color: #7c3aed; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 10px;"><?= !empty($video_gallery_list) ? ('1 / ' . count($video_gallery_list)) : '0 / 0' ?></span>
                            <button type="button" onclick="openVideoManagerModal()" style="background: #f3e8ff; border: 1px solid #ddd6fe; color: #7c3aed; padding: 2px 7px; border-radius: 6px; font-size: 9px; font-weight: 800; cursor: pointer;" title="Xem & quản lý danh sách tất cả video đã thêm">
                                📋 Danh sách
                            </button>
                            <button type="button" onclick="toggleTikTokVideoFitMode()" id="btnVideoFitToggle" style="background: #f3e8ff; border: 1px solid #ddd6fe; color: #7c3aed; padding: 2px 7px; border-radius: 6px; font-size: 9px; font-weight: 800; cursor: pointer;" title="Đổi chế độ xem vừa khung hoặc đầy khung">
                                🖼️ Vừa Khung
                            </button>
                            <a href="/tkb/student/music.php" style="font-size: 10.5px; font-weight: 700; color: #8b5cf6; text-decoration: none;">Xem tất cả</a>
                        </div>
                    </div>

                    <!-- Video Container -->
                    <script>
                    function autoFitVideo(v) {
                        if (!v || !v.videoWidth || !v.videoHeight) return;
                        var ratio = v.videoWidth / v.videoHeight;
                        if (ratio >= 1.25) {
                            v.style.objectFit = 'cover';
                            v.style.objectPosition = 'center center';
                        } else {
                            v.style.objectFit = 'contain';
                            v.style.objectPosition = 'center center';
                        }
                    }
                    </script>
                    <div style="position: relative; width: 100%; height: 195px; border-radius: 12px; overflow: hidden; background: #000; margin: 6px 0;">
                        <div id="tiktokFrameContainer" style="width: 100%; height: 100%;">
                            <?php if (!empty($saved_tiktok_video)): ?>
                                <video id="tiktokPlayerVideo" src="<?= htmlspecialchars($saved_tiktok_video) ?>" autoplay loop muted playsinline controls onloadedmetadata="autoFitVideo(this)" onloadeddata="autoFitVideo(this)" style="width: 100%; height: 100%; object-fit: contain; background: #000; display: block;"></video>
                            <?php else: ?>
                                <div style="text-align: center; padding: 20px; color: rgba(255,255,255,0.85); font-family: 'Outfit', sans-serif;">
                                    <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, rgba(236,72,153,0.2), rgba(139,92,246,0.2)); border: 1.5px solid rgba(236,72,153,0.4); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 22px; color: #ec4899;">
                                        <i class="fa-solid fa-film"></i>
                                    </div>
                                    <div style="font-weight: 800; font-size: 13.5px; margin-bottom: 4px; color: #ffffff;">Chưa có video trong bộ sưu tập</div>
                                    <div style="font-size: 10.5px; color: rgba(255,255,255,0.6); max-width: 220px; margin: 0 auto 12px; line-height: 1.4;">Dán link Video MP4 / YouTube hoặc bấm Tải Lên để thêm video!</div>
                                    <button onclick="document.getElementById('sfVideoUploadMulti') ? document.getElementById('sfVideoUploadMulti').click() : (document.getElementById('sfVideoUploadMultiMale') && document.getElementById('sfVideoUploadMultiMale').click())" style="background: linear-gradient(135deg, #ec4899, #8b5cf6); border: none; color: #fff; padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 4px 12px rgba(236,72,153,0.3);">
                                        <i class="fa-solid fa-plus"></i> Thêm Video Ngay
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Controls Bar: Move Prev/Next, Upload Video & Delete -->
                    <div style="display: flex; gap: 6px; align-items: center; justify-content: space-between; margin-top: 4px;">
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <button type="button" class="sf-video-prev-btn" onclick="sfVideoPrev()" style="background: #f1f5f9; border: 1.5px solid #cbd5e1; color: #475569; padding: 5px 12px; border-radius: 9px; font-size: 11px; font-weight: 800; cursor: pointer; display: <?= count($video_gallery_list) > 1 ? 'flex' : 'none' ?>; align-items: center; gap: 4px; transition: all 0.2s;" title="Xem video trước">
                                <i class="fa-solid fa-chevron-left"></i> Trước
                            </button>
                            <button type="button" class="sf-video-next-btn" onclick="sfVideoNext()" style="background: #f1f5f9; border: 1.5px solid #cbd5e1; color: #475569; padding: 5px 12px; border-radius: 9px; font-size: 11px; font-weight: 800; cursor: pointer; display: <?= count($video_gallery_list) > 1 ? 'flex' : 'none' ?>; align-items: center; gap: 4px; transition: all 0.2s;" title="Xem video sau">
                                Sau <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>

                        <div style="display: flex; gap: 6px; align-items: center;">
                            <label for="sfVideoUploadMulti" style="background: linear-gradient(135deg, #ec4899, #8b5cf6); border: none; color: #fff; padding: 5px 12px; border-radius: 9px; font-size: 11px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 8px rgba(236,72,153,0.25);" title="Tải video mới lên từ máy tính">
                                <i class="fa-solid fa-upload"></i> Tải Lên Video
                            </label>
                            <input type="file" id="sfVideoUploadMulti" accept="video/*" multiple style="display: none;" onchange="sfVideoAddFiles(this)">
                            
                            <button type="button" class="sf-video-remove-btn" onclick="sfVideoRemoveCurrent()" style="background: #fff1f2; border: 1.5px solid #fecdd3; color: #f43f5e; padding: 5px 10px; border-radius: 9px; font-size: 11px; font-weight: 800; cursor: pointer; display: <?= !empty($video_gallery_list) ? 'inline-flex' : 'none' ?>; align-items: center; gap: 4px;" title="Xóa video đang phát">
                                <i class="fa-solid fa-trash-can"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: 4 Columns Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1.1fr; gap: 20px; align-items: stretch; width: 100%;">
                <!-- Col 1: Góc Khoe Người Yêu -->
                <div class="sf-card-white" style="display: flex; flex-direction: column; justify-content: space-between; border-color: #fce7f3 !important;">
                    <div style="font-size: 12px; font-weight: 800; color: #ec4899; display: flex; align-items: center; justify-content: space-between; border-bottom: 1.5px dashed #fce7f3; padding-bottom: 8px;">
                        <span><i class="fa-solid fa-heart"></i> GÓC KHOE NGƯỜI YÊU</span>
                        <span style="font-size: 9px; background: #fdf2f8; color: #ec4899; padding: 3px 8px; border-radius: 10px; font-weight: bold;">COUPLE 💕</span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: center; gap: 14px; margin: 12px 0;">
                        <div style="text-align: center;">
                            <img src="<?= $st_av ?>" id="coupleMyAvatar" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid #ec4899; object-fit: cover;">
                            <div style="font-size: 10.5px; font-weight: 700; color: #0f172a; margin-top: 4px;" id="coupleMyName"><?= htmlspecialchars($sv['ho_ten'] ?? 'Vũ Nhật Tường Vi') ?></div>
                        </div>

                        <div style="text-align: center;">
                            <i class="fa-solid fa-heart" style="font-size: 18px; color: #ec4899;"></i>
                            <div style="font-size: 9.5px; color: #db2777; font-weight: bold; margin-top: 2px;" id="coupleDays">365 Ngày</div>
                        </div>

                        <div style="text-align: center;">
                            <img src="/tkb/assets/img/avatar_khanh.png" id="couplePartnerAvatar" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid #ec4899; object-fit: cover;">
                            <div style="font-size: 10.5px; font-weight: 700; color: #0f172a; margin-top: 4px;" id="couplePartnerName">Lê Nhựt Khánh 💕</div>
                        </div>
                    </div>

                    <div style="background: #fdf2f8; border: 1px dashed #fbcfe8; border-radius: 10px; padding: 6px 8px; text-align: center; font-size: 10px; color: #db2777; font-style: italic; margin-bottom: 8px;" id="coupleStatus">
                        "Cùng nhau học tập &amp; khám phá thế giới Minecraft! 🎮✨"
                    </div>

                    <button onclick="openCoupleEditModal()" style="width: 100%; background: linear-gradient(135deg, #ec4899, #8b5cf6); border: none; color: #fff; padding: 8px; border-radius: 10px; font-size: 11px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(236, 72, 153, 0.25);">
                        <i class="fa-solid fa-heart"></i> Cập nhật góc khoe người yêu
                    </button>
                </div>

                <!-- Col 2: Mã Định Danh Sinh Viên -->
                <div class="sf-card-white" style="display: flex; flex-direction: column; justify-content: space-between; border-color: #e0f2fe !important;">
                    <div style="font-size: 12px; font-weight: 800; color: #0ea5e9; border-bottom: 1.5px dashed #e0f2fe; padding-bottom: 8px;">
                        <i class="fa-solid fa-id-card"></i> MÃ ĐỊNH DANH SINH VIÊN
                    </div>

                    <div style="text-align: center; font-size: 10px; font-weight: 800; color: #0284c7; margin-top: 4px;">TRƯỜNG CAO ĐẲNG CÀ MAU</div>

                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 6px 0;">
                        <img src="<?= $st_av ?>" style="width: 42px; height: 42px; border-radius: 50%; border: 2px solid #0ea5e9; object-fit: cover;">
                        <div>
                            <div style="font-size: 12px; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($sv['ho_ten'] ?? 'Vũ Nhật Tường Vi') ?></div>
                            <div style="font-size: 10px; color: #0284c7; font-weight: 700; background: #e0f2fe; padding: 1px 8px; border-radius: 8px; display: inline-block; margin-top: 2px;">MSV: <?= htmlspecialchars($sv['ma_sv'] ?? 'leduykhanh') ?></div>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode(($sv['ma_sv'] ?? '') ?: 'leduykhanh') ?>" style="width: 60px; height: 60px; border-radius: 6px; border: 1px solid #e0f2fe;">
                        <div style="font-size: 9px; color: #64748b; margin-top: 3px;">Quét mã để truy cập nhanh</div>
                    </div>

                    <button onclick="window.open('https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode(($sv['ma_sv'] ?? '') ?: 'leduykhanh') ?>')" style="width: 100%; background: #f0f9ff; border: 1px solid #bae6fd; color: #0284c7; padding: 7px; border-radius: 10px; font-size: 11px; font-weight: 800; cursor: pointer;">
                        <i class="fa-solid fa-download"></i> Tải mã QR &rarr;
                    </button>
                </div>

                <!-- Col 3: Live Clock & Stats (4 Boxes) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 12px; text-align: center;">
                        <i class="fa-regular fa-clock" style="color: #2563eb; font-size: 14px; margin-bottom: 2px;"></i>
                        <div style="font-size: 15px; font-weight: 900; color: #2563eb;" id="ltLiveClockFemale"><?= date('H:i:s') ?></div>
                        <div style="font-size: 9.5px; font-weight: 700; color: #3b82f6; margin-top: 2px;">Giờ hiện tại</div>
                    </div>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 12px; text-align: center;">
                        <i class="fa-regular fa-calendar-days" style="color: #16a34a; font-size: 14px; margin-bottom: 2px;"></i>
                        <div style="font-size: 11.5px; font-weight: 900; color: #16a34a;" id="ltLiveDateFemale"><?= 'Thứ ' . (date('N') == 7 ? 'Nhật' : (date('N')+1)) . ', ' . date('d/m') ?></div>
                        <div style="font-size: 9.5px; font-weight: 700; color: #22c55e; margin-top: 2px;">Hôm nay</div>
                    </div>
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 14px; padding: 12px; text-align: center;">
                        <i class="fa-solid fa-book-open" style="color: #d97706; font-size: 14px; margin-bottom: 2px;"></i>
                        <div style="font-size: 15px; font-weight: 900; color: #d97706;"><?= count($tkb_hom_nay) ?> Môn</div>
                        <div style="font-size: 9.5px; font-weight: 700; color: #f59e0b; margin-top: 2px;">Lịch học</div>
                    </div>
                    <div style="background: #f3e8ff; border: 1px solid #ddd6fe; border-radius: 14px; padding: 12px; text-align: center;">
                        <i class="fa-solid fa-hourglass-half" style="color: #7c3aed; font-size: 14px; margin-bottom: 2px;"></i>
                        <div style="font-size: 15px; font-weight: 900; color: #7c3aed;" id="ltPomodoroFemale">25:00</div>
                        <div style="font-size: 9.5px; font-weight: 700; color: #8b5cf6; margin-top: 2px;">Đếm giờ học</div>
                    </div>
                </div>

                <!-- Col 4: Lịch Học / Lịch Thi & Thông Báo -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- Lịch học / Lịch thi -->
                    <div class="sf-card-white" style="padding: 12px 14px !important;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #8b5cf6; display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span id="stFemaleSchedTitle">
                                <?php if ($system_schedule_mode === 'lich_thi'): ?>
                                    <i class="fa-solid fa-file-pen" style="color: #ef4444;"></i> LỊCH THI HÔM NAY
                                <?php else: ?>
                                    <i class="fa-solid fa-calendar-week"></i> LỊCH HỌC HÔM NAY
                                <?php endif; ?>
                            </span>
                            <div style="display: flex; gap: 4px;">
                                <button onclick="setStudentSchedMode('hoc')" id="btnTabHocFemale" style="font-size: 8.5px; padding: 2px 6px; border-radius: 6px; border: 1px solid #c084fc; cursor: pointer; font-weight: 700; background: <?= $system_schedule_mode !== 'lich_thi' ? '#8b5cf6' : '#fff' ?>; color: <?= $system_schedule_mode !== 'lich_thi' ? '#fff' : '#8b5cf6' ?>;">📅 Lịch học</button>
                                <button onclick="setStudentSchedMode('thi')" id="btnTabThiFemale" style="font-size: 8.5px; padding: 2px 6px; border-radius: 6px; border: 1px solid #f87171; cursor: pointer; font-weight: 700; background: <?= $system_schedule_mode === 'lich_thi' ? '#ef4444' : '#fff' ?>; color: <?= $system_schedule_mode === 'lich_thi' ? '#fff' : '#ef4444' ?>;">📝 Lịch thi</button>
                            </div>
                        </div>

                        <!-- Content Lịch Học -->
                        <div id="stSchedHocContentFemale" style="display: <?= $system_schedule_mode !== 'lich_thi' ? 'flex' : 'none' ?>; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; font-size: 10px;">
                                <div><strong style="color: #7c3aed;">07:30</strong> Lập trình Web nâng cao</div>
                                <span style="background: #f3e8ff; color: #7c3aed; padding: 1px 6px; border-radius: 6px; font-weight: bold; font-size: 8.5px;">Đang diễn ra</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 10px;">
                                <div><strong style="color: #0ea5e9;">10:00</strong> Cơ sở dữ liệu</div>
                                <span style="background: #e0f2fe; color: #0284c7; padding: 1px 6px; border-radius: 6px; font-weight: bold; font-size: 8.5px;">Sắp diễn ra</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 10px;">
                                <div><strong style="color: #0ea5e9;">13:30</strong> Thiết kế giao diện</div>
                                <span style="background: #e0f2fe; color: #0284c7; padding: 1px 6px; border-radius: 6px; font-weight: bold; font-size: 8.5px;">Sắp diễn ra</span>
                            </div>
                        </div>

                        <!-- Content Lịch Thi -->
                        <div id="stSchedThiContentFemale" style="display: <?= $system_schedule_mode === 'lich_thi' ? 'flex' : 'none' ?>; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; font-size: 10px; background: #fef2f2; padding: 4px 6px; border-radius: 6px;">
                                <div><strong style="color: #dc2626;">07:30</strong> Lập trình Web - <span style="color:#b91c1c; font-weight:700;">Thi Cuối Kỳ (Phòng A301)</span></div>
                                <span style="background: #fee2e2; color: #991b1b; padding: 1px 6px; border-radius: 6px; font-weight: bold; font-size: 8.5px;">Đang diễn ra</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 10px; background: #fff7ed; padding: 4px 6px; border-radius: 6px;">
                                <div><strong style="color: #ea580c;">10:00</strong> Cơ sở dữ liệu - <span style="color:#c2410c; font-weight:700;">Thi Lý Thuyết (Phòng B205)</span></div>
                                <span style="background: #ffedd5; color: #9a3412; padding: 1px 6px; border-radius: 6px; font-weight: bold; font-size: 8.5px;">Sắp diễn ra</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 10px; background: #fff7ed; padding: 4px 6px; border-radius: 6px;">
                                <div><strong style="color: #ea580c;">13:30</strong> Thiết kế giao diện - <span style="color:#c2410c; font-weight:700;">Thi Thực Hành (Phòng A201)</span></div>
                                <span style="background: #ffedd5; color: #9a3412; padding: 1px 6px; border-radius: 6px; font-weight: bold; font-size: 8.5px;">Sắp diễn ra</span>
                            </div>
                        </div>
                    </div>

                    <!-- Thông báo -->
                    <div class="sf-card-white" style="padding: 12px 14px !important;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #ec4899; display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span><i class="fa-solid fa-bell"></i> THÔNG BÁO MỚI</span>
                            <span style="font-size: 9.5px; color: #ec4899; font-weight:700; cursor:pointer;" onclick="document.getElementById('annModalStudent').style.display='flex'">Xem tất cả</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 6px; font-size: 10px;">
                            <?php if (empty($thong_bao)): ?>
                                <div style="color: #94a3b8; font-size: 9.5px; text-align: center;">Chưa có thông báo nào.</div>
                            <?php else: foreach (array_slice($thong_bao, 0, 3) as $tb): 
                                $is_thi = (($tb['loai'] ?? '') === 'Lịch thi') || (mb_strpos($tb['tieu_de'], 'Lịch thi') !== false);
                            ?>
                                <div onclick='openAnnModal(<?= json_encode($tb, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; padding: 3px 0; border-bottom: 1px dashed #fce7f3;">
                                    <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 75%;">
                                        <?php if ($is_thi): ?>
                                            <i class="fa-solid fa-file-pen" style="color: #ef4444;"></i> <strong style="color: #e11d48;"><?= htmlspecialchars($tb['tieu_de']) ?></strong>
                                        <?php else: ?>
                                            <i class="fa-solid fa-bullhorn" style="color: #ec4899;"></i> <?= htmlspecialchars($tb['tieu_de']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <span style="color: #94a3b8; font-size: 8.5px; flex-shrink: 0;"><?= date('d/m', strtotime($tb['ngay_dang'] ?? 'now')) ?></span>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Tiến độ học tập & Truy cập nhanh -->
            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; align-items: stretch; width: 100%;">
                <!-- Left: Tiến độ học tập -->
                <div class="sf-card-white" style="position: relative; overflow: hidden;">
                    <div style="font-size: 12px; font-weight: 800; color: #8b5cf6; margin-bottom: 12px; border-bottom: 1.5px dashed #f3e8ff; padding-bottom: 8px;">
                        <i class="fa-solid fa-chart-line"></i> TIẾN ĐỘ HỌC TẬP
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px; max-width: 65%;">
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 3px;">
                                <span>Lập trình Web nâng cao</span><span>85%</span>
                            </div>
                            <div style="height: 7px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: 85%; height: 100%; background: linear-gradient(90deg, #a855f7, #7c3aed); border-radius: 10px;"></div>
                            </div>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 3px;">
                                <span>Cơ sở dữ liệu</span><span>90%</span>
                            </div>
                            <div style="height: 7px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: 90%; height: 100%; background: linear-gradient(90deg, #3b82f6, #1d4ed8); border-radius: 10px;"></div>
                            </div>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 3px;">
                                <span>Thiết kế giao diện</span><span>75%</span>
                            </div>
                            <div style="height: 7px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: 75%; height: 100%; background: linear-gradient(90deg, #ec4899, #db2777); border-radius: 10px;"></div>
                            </div>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 3px;">
                                <span>Tiếng Anh chuyên ngành</span><span>80%</span>
                            </div>
                            <div style="height: 7px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: 80%; height: 100%; background: linear-gradient(90deg, #f59e0b, #b45309); border-radius: 10px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Anime Girl Illustration -->
                    <img src="/tkb/assets/img/female_study.png" style="position: absolute; right: 10px; bottom: 0px; width: 140px; pointer-events: none;">
                </div>

                <!-- Right: Truy cập nhanh -->
                <div class="sf-card-white">
                    <div style="font-size: 12px; font-weight: 800; color: #8b5cf6; margin-bottom: 12px; border-bottom: 1.5px dashed #f3e8ff; padding-bottom: 8px;">
                        <i class="fa-solid fa-bolt"></i> TRUY CẬP NHANH
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;">
                        <a href="/tkb/student/hoc_bai.php" style="text-decoration: none; text-align: center;">
                            <div style="width: 42px; height: 42px; border-radius: 14px; background: #f3e8ff; color: #8b5cf6; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; font-size: 16px;">
                                <i class="fa-solid fa-box-archive"></i>
                            </div>
                            <div style="font-size: 10.5px; font-weight: 700; color: #0f172a;">Thư viện số</div>
                        </a>

                        <a href="/tkb/student/tien_do.php" style="text-decoration: none; text-align: center;">
                            <div style="width: 42px; height: 42px; border-radius: 14px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; font-size: 16px;">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                            <div style="font-size: 10.5px; font-weight: 700; color: #0f172a;">Tra cứu điểm</div>
                        </a>

                        <a href="/tkb/student/quiz.php" style="text-decoration: none; text-align: center;">
                            <div style="width: 42px; height: 42px; border-radius: 14px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; font-size: 16px;">
                                <i class="fa-solid fa-chart-column"></i>
                            </div>
                            <div style="font-size: 10.5px; font-weight: 700; color: #0f172a;">Cổng khảo sát</div>
                        </a>

                        <a href="/tkb/student/ai.php" style="text-decoration: none; text-align: center;">
                            <div style="width: 42px; height: 42px; border-radius: 14px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; font-size: 16px;">
                                <i class="fa-solid fa-headset"></i>
                            </div>
                            <div style="font-size: 10.5px; font-weight: 700; color: #0f172a;">Hỗ trợ sinh viên</div>
                        </a>

                        <a href="/tkb/student/baitap.php" style="text-decoration: none; text-align: center;">
                            <div style="width: 42px; height: 42px; border-radius: 14px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; font-size: 16px;">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div style="font-size: 10.5px; font-weight: 700; color: #0f172a;">Biểu mẫu</div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div style="margin-top: 10px; padding: 16px 0; border-top: 1px solid #e9d5ff; display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #64748b;">
                <div>© 2026 Trường Cao Đẳng Cà Mau. Tất cả quyền được bảo lưu.</div>
                <div style="display: flex; gap: 16px;">
                    <a href="#" style="color: #64748b; text-decoration: none;">Chính sách bảo mật</a>
                    <a href="#" style="color: #64748b; text-decoration: none;">Liên hệ</a>
                </div>
        </div>
        <?php } else { ?>
        <!-- =========================================================
             GIAO DIỆN SINH VIÊN NAM (PREMIUM LIGHT PURPLE THEME)
             ========================================================= -->
        <style>
        body, body[data-mc-mode="male"], body:not([data-mc-mode="female"]) {
            background: #f5f3ff !important;
            color: #0f172a !important;
            font-family: 'Outfit', sans-serif !important;
        }
        .main-content { background: #f5f3ff !important; }
        .top-header {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px) !important;
            border-bottom: 1px solid #e9d5ff !important;
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.05) !important;
        }
        .sidebar {
            background: linear-gradient(180deg, #1d1744 0%, #120e2e 100%) !important;
            border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        .sidebar-logo-area { background: transparent !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important; }
        
        .nav-link { color: #94a3b8 !important; border: 1px solid transparent !important; transition: all 0.3s ease !important; }
        .nav-link:hover { background: rgba(124, 58, 237, 0.1) !important; color: #a855f7 !important; border-color: rgba(124, 58, 237, 0.2) !important; transform: translateX(4px) !important; }
        .nav-link.active { background: linear-gradient(90deg, rgba(124, 58, 237, 0.1), transparent) !important; color: #8b5cf6 !important; font-weight: 700 !important; border-left: 3px solid #8b5cf6 !important; border-radius: 0 8px 8px 0 !important; }
        .nav-link.active .nav-icon-female { color: #8b5cf6 !important; }
        .nav-link:hover .nav-icon-female { color: #a855f7 !important; }
        
        .header-left { color: #1e293b !important; font-weight: 900 !important; }
        .header-left i { color: #8b5cf6 !important; }
        .user-profile { background: #ffffff !important; border: 1px solid #e9d5ff !important; box-shadow: 0 4px 15px rgba(124, 58, 237, 0.05) !important; }
        .u-name { color: #4c1d95 !important; font-weight: 800 !important; }
        .u-id { color: #64748b !important; }
        .btn-theme-switcher { background: #faf5ff !important; border: 1px solid #e9d5ff !important; color: #8b5cf6 !important; }
        .btn-theme-switcher i { color: #8b5cf6 !important; }
        .header-bell { background: #faf5ff !important; border: 1px solid #e9d5ff !important; color: #8b5cf6 !important; }
        .header-bell i { color: #8b5cf6 !important; }

        .mc-card-male {
            background: #ffffff !important;
            border: 1.5px solid #f3e8ff !important;
            border-radius: 16px !important;
            padding: 20px !important;
            box-shadow: 0 8px 32px rgba(124, 58, 237, 0.05) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative;
            overflow: hidden;
        }
        .mc-card-male:hover {
            box-shadow: 0 8px 32px rgba(124, 58, 237, 0.15), 0 0 20px rgba(124, 58, 237, 0.1) !important;
            border-color: #ddd6fe !important;
            transform: translateY(-3px) !important;
        }

        .mc-title {
            font-size: 13px; font-weight: 800; color: #8b5cf6; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 16px; border-bottom: 1.5px dashed #f3e8ff; padding-bottom: 8px;
        }
        .mc-title-pink { color: #ec4899; border-bottom-color: #fce7f3; }
        .mc-title-blue { color: #0ea5e9; border-bottom-color: #e0f2fe; }

        .mc-stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .mc-stat-purple { background: #f3e8ff; color: #8b5cf6; }
        .mc-stat-blue { background: #dbeafe; color: #3b82f6; }
        .mc-stat-green { background: #dcfce7; color: #22c55e; }
        .mc-stat-yellow { background: #fef3c7; color: #f59e0b; }
        .mc-stat-pink { background: #fce7f3; color: #ec4899; }

        .mc-input {
            background: #f8fafc !important; border: 1.5px solid #e2e8f0 !important; color: #0f172a !important; border-radius: 8px !important; padding: 8px 12px !important; outline: none !important; font-size: 11px !important; font-weight: 600 !important; width: 100%;
        }
        .mc-input::placeholder { color: #94a3b8 !important; }
        .mc-input:focus { border-color: #8b5cf6 !important; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important; }

        .mc-btn {
            background: #f1f5f9 !important; border: none !important; color: #475569 !important; border-radius: 8px !important; padding: 8px 14px !important; font-size: 11px !important; font-weight: 800 !important; cursor: pointer !important; transition: all 0.2s !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px !important; text-decoration: none !important;
        }
        .mc-btn:hover { background: #e2e8f0 !important; transform: scale(1.02) !important; }
        .mc-btn-purple { background: linear-gradient(135deg, #a855f7, #7c3aed) !important; color: #ffffff !important; box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3) !important; }
        .mc-btn-purple:hover { box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4) !important; }
        .mc-btn-pink { background: #fff1f2 !important; color: #e11d48 !important; }
        .mc-btn-pink:hover { background: #ffe4e6 !important; }
        
        .mc-glow-text { color: #0f172a !important; font-weight: 900 !important; font-size: 18px !important; }
        .mc-sub-text { color: #64748b !important; font-size: 11px !important; font-weight: 600 !important; }
        </style>

        <div style="display: flex; flex-direction: column; gap: 20px; width: 100%;">
            <!-- Row 1: Hero Banner + Video Gallery -->
            <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; align-items: stretch; width: 100%;">
                
                <!-- Left Hero Welcome Banner -->
                <div class="sf-banner-box" style="position: relative; border-radius: 20px; overflow: hidden; height: 280px; border: 1.5px solid #e9d5ff; box-shadow: 0 10px 30px rgba(124, 58, 237, 0.15); background: #000; cursor: pointer; display: flex; align-items: center; justify-content: center;" onclick="document.getElementById('sfBannerUploadMultiMale').click()" title="Bấm vào để chọn & thêm ảnh Banner mới">
                    <span id="sfBannerCounter" class="sf-banner-overlay-item" style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); border-radius: 12px; padding: 4px 10px; font-size: 11px; font-weight: 800; color: #ffffff; z-index: 5;"><?= !empty($banner_gallery_list) ? ('1 / ' . count($banner_gallery_list)) : '0 / 0' ?></span>

                    <div class="sf-banner-overlay-item" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.9); backdrop-filter: blur(8px); border-radius: 12px; padding: 4px 12px; font-size: 11px; font-weight: 800; color: #7c3aed; cursor: pointer; display: flex; align-items: center; gap: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); z-index: 5;">
                        <i class="fa-solid fa-camera"></i> Đổi/Thêm ảnh
                    </div>

                    <button type="button" class="sf-banner-overlay-item sf-banner-prev-btn" onclick="sfBannerPrev(); event.stopPropagation();" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 32px; height: 32px; border-radius: 50%; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); border: none; color: #ffffff; font-size: 13px; font-weight: bold; cursor: pointer; display: <?= count($banner_gallery_list) > 1 ? 'flex' : 'none' ?>; align-items: center; justify-content: center; z-index: 6;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="sf-banner-overlay-item sf-banner-next-btn" onclick="sfBannerNext(); event.stopPropagation();" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 32px; height: 32px; border-radius: 50%; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); border: none; color: #ffffff; font-size: 13px; font-weight: bold; cursor: pointer; display: <?= count($banner_gallery_list) > 1 ? 'flex' : 'none' ?>; align-items: center; justify-content: center; z-index: 6;"><i class="fa-solid fa-chevron-right"></i></button>
                    
                    <button type="button" class="sf-banner-overlay-item sf-banner-remove-btn" onclick="sfBannerRemoveCurrent(); event.stopPropagation();" style="position: absolute; bottom: 10px; right: 10px; background: rgba(239,68,68,0.85); backdrop-filter: blur(8px); border-radius: 10px; padding: 4px 10px; font-size: 11px; font-weight: bold; color: #fff; border: none; cursor: pointer; display: <?= !empty($banner_gallery_list) ? 'inline-flex' : 'none' ?>; z-index: 6;" title="Xóa banner này"><i class="fa-solid fa-trash-can"></i></button>

                    <div id="sfBannerDots" class="sf-banner-overlay-item" style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; align-items: center; z-index: 5;"></div>

                    <div id="ltHeroBannerPlaceholder" style="text-align: center; color: rgba(255,255,255,0.7); display: <?= $banner_url ? 'none' : 'block' ?>; z-index: 0;">
                        <i class="fa-solid fa-image" style="font-size: 32px; margin-bottom: 8px;"></i><br>
                        <span style="font-size: 14px; font-weight: 800;">Chưa có ảnh bìa</span><br>
                        <span style="font-size: 11px; font-weight: 600;">Bấm vào đây để tải lên</span>
                    </div>

                    <script>
                    if (window.__PRELOADED_BANNER__) {
                        document.write('<img id="ltHeroBannerImg" src="' + window.__PRELOADED_BANNER__ + '" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: <?= htmlspecialchars($banner_pos) ?>; display: block; transition: opacity 0.35s ease; z-index: 1;">');
                    } else {
                        document.write('<img id="ltHeroBannerImg" src="<?= htmlspecialchars($banner_url) ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: <?= htmlspecialchars($banner_pos) ?>; display: <?= $banner_url ? 'block' : 'none' ?>; transition: opacity 0.35s ease; z-index: 1;">');
                    }
                    </script>
                    <input type="file" id="sfBannerUploadMultiMale" accept="image/*" multiple style="display: none;" onchange="sfBannerAddFiles(this)">
                </div>

                <!-- Right Video Gallery Player Box -->
                <div class="mc-card-male" style="display: flex; flex-direction: column; justify-content: space-between; padding: 16px; height: 280px;">
                    <div class="mc-title" style="margin-bottom: 8px;">
                        <i class="fa-solid fa-film"></i> BỘ SƯU TẬP VIDEO
                        <span id="sfVideoHeaderCountMale" style="background: linear-gradient(135deg, #a855f7, #7c3aed); color: #ffffff; font-size: 10.5px; font-weight: 800; padding: 2px 9px; border-radius: 12px; margin-left: 4px; display: inline-flex; align-items: center; gap: 4px; text-transform: none;">
                            <i class="fa-solid fa-layer-group" style="font-size: 9.5px;"></i> <?= !empty($video_gallery_list) ? count($video_gallery_list) : 0 ?> Video
                        </span>
                        <div style="margin-left: auto; display: flex; align-items: center; gap: 8px;">
                            <span id="sfVideoCounter" style="background: #fff1f2; color: #f43f5e; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 12px;"><?= !empty($video_gallery_list) ? ('1 / ' . count($video_gallery_list)) : '0 / 0' ?></span>
                            <button type="button" onclick="openVideoManagerModal()" class="mc-btn" style="padding: 4px 8px; font-size: 10px;" title="Xem & quản lý danh sách tất cả video đã thêm">
                                📋 Danh sách
                            </button>
                            <button type="button" onclick="toggleTikTokVideoFitMode()" id="btnVideoFitToggle" class="mc-btn" style="padding: 4px 8px; font-size: 10px;" title="Chuyển chế độ xem vừa khung hoặc đầy khung">
                                🖼️ Vừa Khung
                            </button>
                        </div>
                    </div>

                    <!-- Video Container Box -->
                    <div style="position: relative; width: 100%; height: 185px; border-radius: 12px; overflow: hidden; background: #000; box-shadow: inset 0 0 10px rgba(0,0,0,0.5);">
                        <div id="tiktokFrameContainer" style="width: 100%; height: 100%;">
                            <?php if (!empty($saved_tiktok_video)): ?>
                                <video id="tiktokPlayerVideo" src="<?= htmlspecialchars($saved_tiktok_video) ?>" autoplay loop muted playsinline controls onloadedmetadata="autoFitVideo(this)" onloadeddata="autoFitVideo(this)" style="width: 100%; height: 100%; object-fit: contain; background: #000; display: block;"></video>
                            <?php else: ?>
                                <div style="text-align: center; padding: 20px; color: rgba(255,255,255,0.85); font-family: 'Outfit', sans-serif;">
                                    <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, rgba(168,85,247,0.2), rgba(124,58,237,0.2)); border: 1.5px solid rgba(168,85,247,0.4); display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-size: 20px; color: #a855f7;">
                                        <i class="fa-solid fa-film"></i>
                                    </div>
                                    <div style="font-weight: 800; font-size: 13px; margin-bottom: 4px; color: #ffffff;">Chưa có video trong bộ sưu tập</div>
                                    <div style="font-size: 10.5px; color: rgba(255,255,255,0.6); max-width: 220px; margin: 0 auto 10px; line-height: 1.4;">Dán link Video MP4 / YouTube hoặc bấm Tải Lên để thêm video!</div>
                                    <button onclick="var inp = document.getElementById('sfVideoUploadMultiMale') || document.getElementById('sfVideoUploadMulti'); if(inp) inp.click();" style="background: linear-gradient(135deg, #a855f7, #7c3aed); border: none; color: #fff; padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 4px 12px rgba(124,58,237,0.3);">
                                        <i class="fa-solid fa-plus"></i> Thêm Video Ngay
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; gap: 6px; align-items: center; justify-content: space-between; margin-top: 10px;">
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <button type="button" onclick="sfVideoPrev()" class="mc-btn sf-video-prev-btn" style="display: <?= count($video_gallery_list) > 1 ? 'inline-flex' : 'none' ?>;" title="Xem video trước">
                                <i class="fa-solid fa-chevron-left"></i> Trước
                            </button>
                            <button type="button" onclick="sfVideoNext()" class="mc-btn sf-video-next-btn" style="display: <?= count($video_gallery_list) > 1 ? 'inline-flex' : 'none' ?>;" title="Xem video sau">
                                Sau <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>

                        <div style="display: flex; gap: 6px; align-items: center;">
                            <button type="button" onclick="document.getElementById('sfVideoUploadMultiMale').click()" class="mc-btn mc-btn-purple" title="Tải video lên từ máy tính">
                                <i class="fa-solid fa-upload"></i> Tải Lên Video
                            </button>
                            <input type="file" id="sfVideoUploadMultiMale" accept="video/*" multiple style="display: none;" onchange="sfVideoAddFiles(this)">
                            
                            <button type="button" onclick="sfVideoRemoveCurrent()" class="mc-btn mc-btn-pink sf-video-remove-btn" style="display: <?= !empty($video_gallery_list) ? 'inline-flex' : 'none' ?>;" title="Xóa video đang phát">
                                <i class="fa-solid fa-trash-can"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: 5 Statistics Pills Strip -->
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; width: 100%;">
                <div class="mc-card-male" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px;">
                    <div class="mc-stat-icon mc-stat-purple"><i class="fa-solid fa-book-open"></i></div>
                    <div><div class="mc-glow-text">8</div><div class="mc-sub-text">Môn học</div></div>
                </div>
                <div class="mc-card-male" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px;">
                    <div class="mc-stat-icon mc-stat-blue"><i class="fa-solid fa-file-lines"></i></div>
                    <div><div class="mc-glow-text">15</div><div class="mc-sub-text">Bài tập</div></div>
                </div>
                <div class="mc-card-male" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px;">
                    <div class="mc-stat-icon mc-stat-green"><i class="fa-solid fa-star"></i></div>
                    <div><div class="mc-glow-text">1250</div><div class="mc-sub-text">Điểm tích lũy</div></div>
                </div>
                <div class="mc-card-male" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px;">
                    <div class="mc-stat-icon mc-stat-yellow"><i class="fa-solid fa-trophy"></i></div>
                    <div><div class="mc-glow-text">Lv.12</div><div class="mc-sub-text">Cấp độ</div></div>
                </div>
                <div class="mc-card-male" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px;">
                    <div class="mc-stat-icon mc-stat-purple"><i class="fa-solid fa-gem"></i></div>
                    <div><div class="mc-glow-text">256</div><div class="mc-sub-text">Huy hiệu</div></div>
                </div>
            </div>

            <!-- Row 3: 4 Columns Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1.1fr; gap: 20px; align-items: stretch; width: 100%;">
                <!-- Col 1: Góc Khoe Người Yêu -->
                <div class="mc-card-male" style="display: flex; flex-direction: column; justify-content: space-between; border-color: #fce7f3 !important;">
                    <div class="mc-title mc-title-pink">
                        <i class="fa-solid fa-heart" style="color: #ec4899;"></i> GÓC KHOE NGƯỜI YÊU
                        <span style="margin-left: auto; font-size: 9px; background: #fdf2f8; color: #ec4899; padding: 3px 8px; border-radius: 10px; font-weight: bold;">COUPLE 💕</span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: center; gap: 14px; margin: 12px 0;">
                        <div style="text-align: center;">
                            <img src="<?= $st_av ?>" id="coupleMyAvatar" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid #ec4899; object-fit: cover;">
                            <div style="font-size: 10.5px; font-weight: 700; color: #0f172a; margin-top: 4px;" id="coupleMyName"><?= htmlspecialchars($sv['ho_ten'] ?? 'Lê Nhựt Khánh') ?></div>
                        </div>

                        <div style="text-align: center;">
                            <i class="fa-solid fa-heart" style="font-size: 18px; color: #ec4899;"></i>
                            <div style="font-size: 9.5px; color: #db2777; font-weight: bold; margin-top: 2px;" id="coupleDays">365 Ngày bên nhau</div>
                        </div>

                        <div style="text-align: center;">
                            <img src="/tkb/assets/img/avatar_khanh.png" id="couplePartnerAvatar" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid #ec4899; object-fit: cover;">
                            <div style="font-size: 10.5px; font-weight: 700; color: #0f172a; margin-top: 4px;" id="couplePartnerName">Vũ Nhật Tường Vi 💕</div>
                        </div>
                    </div>

                    <div style="background: #fdf2f8; border: 1px dashed #fbcfe8; border-radius: 10px; padding: 6px 8px; text-align: center; font-size: 10.5px; color: #db2777; font-style: italic; margin-bottom: 8px;" id="coupleStatus">
                        "Cùng nhau học tập &amp; khám phá thế giới Minecraft! 🎮✨"
                    </div>

                    <button onclick="openCoupleEditModal()" class="mc-btn" style="width: 100%; background: linear-gradient(135deg, #ec4899, #8b5cf6) !important; color: #fff !important;">
                        <i class="fa-solid fa-heart"></i> Cập nhật góc khoe người yêu
                    </button>
                </div>

                <!-- Col 2: Mã Định Danh Sinh Viên -->
                <div class="mc-card-male" style="display: flex; flex-direction: column; justify-content: space-between; border-color: #e0f2fe !important;">
                    <div class="mc-title mc-title-blue">
                        <i class="fa-solid fa-id-card"></i> MÃ ĐỊNH DANH SINH VIÊN
                    </div>

                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 8px 0;">
                        <img src="<?= $st_av ?>" style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid #0ea5e9; object-fit: cover;">
                        <div>
                            <div style="font-size: 12px; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($sv['ho_ten'] ?? 'Lê Nhựt Khánh') ?></div>
                            <div style="font-size: 10px; color: #0284c7; font-weight: 700;">MSV: <?= htmlspecialchars($sv['ma_sv'] ?? 'leduykhanh') ?></div>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode(($sv['ma_sv'] ?? '') ?: 'leduykhanh') ?>" style="width: 65px; height: 65px; border-radius: 6px; border: 1px solid #e0f2fe;">
                        <div style="font-size: 9px; color: #64748b; margin-top: 4px;">Quét mã để truy cập nhanh</div>
                    </div>

                    <button onclick="window.open('https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode(($sv['ma_sv'] ?? '') ?: 'leduykhanh') ?>')" class="mc-btn" style="width: 100%; background: #f0f9ff !important; border: 1px solid #bae6fd !important; color: #0284c7 !important;">
                        <i class="fa-solid fa-download"></i> Tải mã QR &rarr;
                    </button>
                </div>

                <!-- Col 3: Live Clock & Stats 2x2 Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 12px; text-align: center;">
                        <div style="font-size: 16px; font-weight: 900; color: #2563eb;" id="ltLiveClockMale2"><?= date('H:i:s') ?></div>
                        <div style="font-size: 10px; font-weight: 700; color: #3b82f6; margin-top: 2px;">Giờ hiện tại</div>
                    </div>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 12px; text-align: center;">
                        <div style="font-size: 12px; font-weight: 900; color: #16a34a;" id="ltLiveDateMale2"><?= 'Thứ ' . (date('N') == 7 ? 'Nhật' : (date('N')+1)) . ', ' . date('d/m') ?></div>
                        <div style="font-size: 10px; font-weight: 700; color: #22c55e; margin-top: 2px;">Hôm nay</div>
                    </div>
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 14px; padding: 12px; text-align: center;">
                        <div style="font-size: 15px; font-weight: 900; color: #d97706;"><?= count($tkb_hom_nay) ?> Môn</div>
                        <div style="font-size: 10px; font-weight: 700; color: #f59e0b; margin-top: 2px;">Lịch học</div>
                    </div>
                    <div style="background: #f3e8ff; border: 1px solid #ddd6fe; border-radius: 14px; padding: 12px; text-align: center;">
                        <div style="font-size: 15px; font-weight: 900; color: #7c3aed;" id="ltPomodoroMale">25:00</div>
                        <div style="font-size: 10px; font-weight: 700; color: #8b5cf6; margin-top: 2px;">Đếm giờ học</div>
                    </div>
                </div>

                <!-- Col 4: Lịch Học / Lịch Thi Hôm Nay -->
                <div class="mc-card-male" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="mc-title" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span id="stMaleSchedTitle">
                            <?php if ($system_schedule_mode === 'lich_thi'): ?>
                                <i class="fa-solid fa-file-pen" style="color: #ef4444;"></i> LỊCH THI HÔM NAY
                            <?php else: ?>
                                <i class="fa-solid fa-calendar-week"></i> LỊCH HỌC HÔM NAY
                            <?php endif; ?>
                        </span>
                        <div style="display: flex; gap: 6px; margin-left: auto;">
                            <button onclick="setStudentSchedMode('hoc')" id="btnTabHocMale" style="font-size: 9.5px; padding: 3px 8px; border-radius: 8px; border: 1px solid #7c3aed; cursor: pointer; font-weight: 700; background: <?= $system_schedule_mode !== 'lich_thi' ? '#7c3aed' : 'transparent' ?>; color: <?= $system_schedule_mode !== 'lich_thi' ? '#fff' : '#7c3aed' ?>;">📅 Lịch học</button>
                            <button onclick="setStudentSchedMode('thi')" id="btnTabThiMale" style="font-size: 9.5px; padding: 3px 8px; border-radius: 8px; border: 1px solid #ef4444; cursor: pointer; font-weight: 700; background: <?= $system_schedule_mode === 'lich_thi' ? '#ef4444' : 'transparent' ?>; color: <?= $system_schedule_mode === 'lich_thi' ? '#fff' : '#ef4444' ?>;">📝 Lịch thi</button>
                        </div>
                    </div>

                    <!-- Content Lịch Học (Male) -->
                    <div id="stSchedHocContentMale" style="display: <?= $system_schedule_mode !== 'lich_thi' ? 'flex' : 'none' ?>; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #f3e8ff;">
                            <div>
                                <span style="font-size: 11px; font-weight: 800; color: #7c3aed;">07:30</span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #0f172a; margin-left: 6px;">Lập trình Web nâng cao</span>
                                <div style="font-size: 9.5px; color: #64748b; margin-left: 42px;">Phòng A301</div>
                            </div>
                            <span style="font-size: 9px; background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 8px; font-weight: bold;">Đang diễn ra</span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #f3e8ff;">
                            <div>
                                <span style="font-size: 11px; font-weight: 800; color: #3b82f6;">10:00</span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #0f172a; margin-left: 6px;">Cơ sở dữ liệu</span>
                                <div style="font-size: 9.5px; color: #64748b; margin-left: 42px;">Phòng B205</div>
                            </div>
                            <span style="font-size: 9px; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 8px; font-weight: bold;">Sắp diễn ra</span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0;">
                            <div>
                                <span style="font-size: 11px; font-weight: 800; color: #f59e0b;">13:30</span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #0f172a; margin-left: 6px;">Thiết kế giao diện</span>
                                <div style="font-size: 9.5px; color: #64748b; margin-left: 42px;">Phòng A201</div>
                            </div>
                            <span style="font-size: 9px; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 8px; font-weight: bold;">Sắp diễn ra</span>
                        </div>
                    </div>

                    <!-- Content Lịch Thi (Male) -->
                    <div id="stSchedThiContentMale" style="display: <?= $system_schedule_mode === 'lich_thi' ? 'flex' : 'none' ?>; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: #fef2f2; border-radius: 10px; border: 1px solid #fecaca;">
                            <div>
                                <span style="font-size: 11px; font-weight: 800; color: #dc2626;">07:30</span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #991b1b; margin-left: 6px;">Lập trình Web nâng cao</span>
                                <div style="font-size: 9.5px; color: #b91c1c; margin-left: 42px;">Phòng A301 • Thi Cuối Kỳ</div>
                            </div>
                            <span style="font-size: 9px; background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 8px; font-weight: bold;">Đang thi</span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: #fff7ed; border-radius: 10px; border: 1px solid #fed7aa;">
                            <div>
                                <span style="font-size: 11px; font-weight: 800; color: #ea580c;">10:00</span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #9a3412; margin-left: 6px;">Cơ sở dữ liệu</span>
                                <div style="font-size: 9.5px; color: #c2410c; margin-left: 42px;">Phòng B205 • Thi Lý Thuyết</div>
                            </div>
                            <span style="font-size: 9px; background: #ffedd5; color: #9a3412; padding: 2px 8px; border-radius: 8px; font-weight: bold;">Sắp thi</span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: #fff7ed; border-radius: 10px; border: 1px solid #fed7aa;">
                            <div>
                                <span style="font-size: 11px; font-weight: 800; color: #ea580c;">13:30</span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #9a3412; margin-left: 6px;">Thiết kế giao diện</span>
                                <div style="font-size: 9.5px; color: #c2410c; margin-left: 42px;">Phòng A201 • Thi Thực Hành</div>
                            </div>
                            <span style="font-size: 9px; background: #ffedd5; color: #9a3412; padding: 2px 8px; border-radius: 8px; font-weight: bold;">Sắp thi</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 4: 2 Columns Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: stretch; width: 100%;">
                <!-- Left: Tiến Độ Học Tập -->
                <div class="mc-card-male" style="position: relative; overflow: hidden;">
                    <div class="mc-title">
                        <i class="fa-solid fa-chart-line"></i> TIẾN ĐỘ HỌC TẬP
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px; max-width: 70%;">
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 4px;">
                                <span>Lập trình Web nâng cao</span><span>85%</span>
                            </div>
                            <div style="height: 7px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: 85%; height: 100%; background: linear-gradient(90deg, #a855f7, #7c3aed); border-radius: 10px;"></div>
                            </div>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 4px;">
                                <span>Cơ sở dữ liệu</span><span>90%</span>
                            </div>
                            <div style="height: 7px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: 90%; height: 100%; background: linear-gradient(90deg, #3b82f6, #1d4ed8); border-radius: 10px;"></div>
                            </div>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 4px;">
                                <span>Thiết kế giao diện</span><span>75%</span>
                            </div>
                            <div style="height: 7px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: 75%; height: 100%; background: linear-gradient(90deg, #10b981, #047857); border-radius: 10px;"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 4px;">
                                <span>Tiếng Anh chuyên ngành</span><span>80%</span>
                            </div>
                            <div style="height: 7px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: 80%; height: 100%; background: linear-gradient(90deg, #f59e0b, #b45309); border-radius: 10px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Minecraft Character Graphic -->
                    <img src="/tkb/assets/img/my_minecraft_skin.png" style="position: absolute; right: 10px; bottom: 0px; width: 135px; filter: drop-shadow(0 8px 16px rgba(124, 58, 237, 0.25)); pointer-events: none; border-radius: 12px;">
                </div>

                <!-- Right: Thông Báo Mới -->
                <div class="mc-card-male">
                    <div class="mc-title mc-title-pink" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span><i class="fa-solid fa-bell"></i> THÔNG BÁO MỚI</span>
                        <span style="margin-left: auto; font-size: 10.5px; font-weight: 700; color: #7c3aed; cursor: pointer;" onclick="document.getElementById('annModalStudent').style.display='flex'">Xem tất cả</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php if (empty($thong_bao)): ?>
                            <div style="color: #64748b; font-size: 12px; text-align: center; padding: 15px;">Chưa có thông báo mới nào.</div>
                        <?php else: foreach (array_slice($thong_bao, 0, 4) as $tb):
                            $is_thi = (($tb['loai'] ?? '') === 'Lịch thi') || (mb_strpos($tb['tieu_de'], 'Lịch thi') !== false);
                            $icon_bg = $is_thi ? '#ffe4e6' : (($tb['loai'] ?? '') === 'Nghỉ học' ? '#fee2e2' : '#f3e8ff');
                            $icon_color = $is_thi ? '#e11d48' : (($tb['loai'] ?? '') === 'Nghỉ học' ? '#dc2626' : '#7c3aed');
                            $icon_class = $is_thi ? 'fa-file-pen' : (($tb['loai'] ?? '') === 'Nghỉ học' ? 'fa-bullhorn' : 'fa-award');
                        ?>
                            <div onclick='openAnnModal(<?= json_encode($tb, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' style="display: flex; align-items: center; gap: 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; cursor: pointer;">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: <?= $icon_bg ?>; display: flex; align-items: center; justify-content: center; color: <?= $icon_color ?>; font-size: 15px;">
                                    <i class="fa-solid <?= $icon_class ?>"></i>
                                </div>
                                <div style="flex: 1; overflow: hidden;">
                                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        <?= htmlspecialchars($tb['tieu_de']) ?>
                                    </div>
                                    <div style="font-size: 10.5px; color: #64748b; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        <?= htmlspecialchars(mb_strimwidth(strip_tags($tb['noi_dung']), 0, 50, '...')) ?>
                                    </div>
                                </div>
                                <span style="font-size: 10px; color: #94a3b8; font-weight: 600; flex-shrink: 0;"><?= date('d/m', strtotime($tb['ngay_dang'] ?? 'now')) ?></span>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- Row 5: Truy Cập Nhanh (5 Action Cards Grid) -->
            <div style="width: 100%;">
                <div class="mc-title">
                    <i class="fa-solid fa-bolt"></i> TRUY CẬP NHANH
                </div>

                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px;">
                    <a href="/tkb/student/hoc_bai.php" class="mc-card-male" style="text-decoration: none; text-align: center; padding: 16px 10px;">
                        <div class="mc-stat-icon mc-stat-purple" style="margin: 0 auto 8px; width: 44px; height: 44px;"><i class="fa-solid fa-box-archive"></i></div>
                        <div style="font-size: 11.5px; font-weight: 700; color: #0f172a;">Thư viện số</div>
                    </a>

                    <a href="/tkb/student/tien_do.php" class="mc-card-male" style="text-decoration: none; text-align: center; padding: 16px 10px;">
                        <div class="mc-stat-icon mc-stat-purple" style="margin: 0 auto 8px; width: 44px; height: 44px;"><i class="fa-solid fa-file-invoice"></i></div>
                        <div style="font-size: 11.5px; font-weight: 700; color: #0f172a;">Tra cứu điểm</div>
                    </a>

                    <a href="/tkb/student/quiz.php" class="mc-card-male" style="text-decoration: none; text-align: center; padding: 16px 10px;">
                        <div class="mc-stat-icon mc-stat-blue" style="margin: 0 auto 8px; width: 44px; height: 44px;"><i class="fa-solid fa-chart-column"></i></div>
                        <div style="font-size: 11.5px; font-weight: 700; color: #0f172a;">Cổng khảo sát</div>
                    </a>

                    <a href="/tkb/student/ai.php" class="mc-card-male" style="text-decoration: none; text-align: center; padding: 16px 10px;">
                        <div class="mc-stat-icon mc-stat-yellow" style="margin: 0 auto 8px; width: 44px; height: 44px;"><i class="fa-solid fa-headset"></i></div>
                        <div style="font-size: 11.5px; font-weight: 700; color: #0f172a;">Hỗ trợ sinh viên</div>
                    </a>

                    <a href="/tkb/student/baitap.php" class="mc-card-male" style="text-decoration: none; text-align: center; padding: 16px 10px;">
                        <div class="mc-stat-icon mc-stat-pink" style="margin: 0 auto 8px; width: 44px; height: 44px;"><i class="fa-solid fa-file-signature"></i></div>
                        <div style="font-size: 11.5px; font-weight: 700; color: #0f172a;">Biểu mẫu</div>
                    </a>
                </div>
            </div>

            <!-- Footer Section -->
            <div style="margin-top: 10px; padding: 16px 0; border-top: 1px solid #e9d5ff; display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #64748b;">
                <div>© 2026 Trường Cao Đẳng Cà Mau. Tất cả quyền được bảo lưu.</div>
                <div style="display: flex; gap: 16px;">
                    <a href="#" style="color: #64748b; text-decoration: none;">Chính sách bảo mật</a>
                    <a href="#" style="color: #64748b; text-decoration: none;">Liên hệ</a>
                </div>
            </div>
        </div>
        <?php } ?>
    </div><!-- end .content-pad -->
</div><!-- end .main-content -->


        <!-- Couple Edit Modal -->
        <div class="mc-theme-modal-overlay" id="coupleEditOverlay" onclick="closeCoupleEditModal()"></div>
        <div class="mc-theme-modal" id="coupleEditModal" style="max-width: 500px; background: #ffffff !important; border-radius: 24px !important; border: 2px solid #fda4af !important; box-shadow: 0 25px 60px rgba(244, 63, 94, 0.3) !important; overflow: hidden !important; padding: 0 !important;">
            <div style="background: linear-gradient(135deg, #f43f5e, #e11d48); padding: 20px 24px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="color: #ffffff; margin: 0; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-heart" style="color: #ffe4e6; animation: mcHeartPulse 1.2s infinite ease-in-out;"></i> TÙY CHỈNH GÓC KHỎE NGƯỜI YÊU
                    </h3>
                    <p style="margin: 4px 0 0; font-size: 12px; color: rgba(255,255,255,0.9); font-weight: 500;">Nhập thông tin nửa kia của bạn để cùng khoe nhé!</p>
                </div>
                <button onclick="closeCoupleEditModal()" style="background: rgba(255,255,255,0.25); border: none; color: #ffffff; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>
            </div>
            <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px; background: #ffffff;">
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 800; color: #1e293b; margin-bottom: 6px;">Tên người yêu 💕</label>
                    <input type="text" id="inputPartnerName" value="Lê Anh Thư" placeholder="Nhập tên người yêu..." style="width: 100%; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 11px 14px; color: #0f172a; font-size: 13.5px; font-weight: 600; outline: none; transition: all 0.2s;">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 800; color: #1e293b; margin-bottom: 6px;">Ảnh đại diện người yêu (URL / Tải ảnh)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="inputPartnerAvatar" placeholder="Dán link ảnh tại đây..." style="flex: 1; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 11px 14px; color: #0f172a; font-size: 13.5px; font-weight: 600; outline: none; transition: all 0.2s;">
                        <button type="button" onclick="document.getElementById('filePartnerAvatar').click()" style="background: #fff1f2; border: 1.5px solid #fda4af; color: #e11d48; padding: 0 16px; border-radius: 12px; font-size: 12.5px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 6px; white-space: nowrap; transition: all 0.2s; box-shadow: 0 2px 8px rgba(244,63,94,0.15);">
                            <i class="fa-solid fa-upload" style="color: #f43f5e;"></i> Chọn ảnh
                        </button>
                    </div>
                    <input type="file" id="filePartnerAvatar" accept="image/*" style="display: none;" onchange="handlePartnerImageUpload(this)">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 800; color: #1e293b; margin-bottom: 6px;">📅 Chọn ngày bắt đầu yêu (Tự động đếm ngày 💖)</label>
                    <input type="date" id="inputCoupleStartDate" onchange="calculateCoupleDaysFromDate()" style="width: 100%; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 11px 14px; color: #0f172a; font-size: 13.5px; font-weight: 600; outline: none; color-scheme: light; transition: all 0.2s;">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 800; color: #1e293b; margin-bottom: 6px;">Số ngày yêu nhau / Kỷ niệm (Tự động cập nhật)</label>
                    <input type="text" id="inputCoupleDays" value="365 Ngày" placeholder="Ví dụ: 365 Ngày" style="width: 100%; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 11px 14px; color: #0f172a; font-size: 13.5px; font-weight: 600; outline: none; transition: all 0.2s;">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 800; color: #1e293b; margin-bottom: 6px;">Lời chúc / Status tình yêu ✨</label>
                    <input type="text" id="inputCoupleStatus" value="Cùng nhau học tập &amp; khám phá thế giới Minecraft! 🚀✨" placeholder="Nhập lời chúc hay status..." style="width: 100%; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 11px 14px; color: #0f172a; font-size: 13.5px; font-weight: 600; outline: none; transition: all 0.2s;">
                </div>
                <button onclick="saveCoupleInfo()" style="width: 100%; margin-top: 8px; background: linear-gradient(135deg, #f43f5e, #be123c); border: none; color: #ffffff; padding: 13px; border-radius: 14px; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 20px rgba(244, 63, 94, 0.35); display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.25s;">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu Thay Đổi
                </button>
            </div>
        </div>


        <!-- Avatar Preview Lightbox Modal -->
        <div class="mc-theme-modal-overlay" id="avatarPreviewOverlay" onclick="closeAvatarPreviewModal()" style="z-index: 999990;"></div>
        <div class="mc-theme-modal" id="avatarPreviewModal" style="max-width: 360px; text-align: center; padding: 24px; border: 2.5px solid #00d9ff; box-shadow: 0 0 40px rgba(0, 217, 255, 0.6); z-index: 999991; border-radius: 20px; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(20px);">
            <button class="mc-theme-close" onclick="closeAvatarPreviewModal()" style="position: absolute; top: 12px; right: 16px; font-size: 24px; color: #ffffff; background: none; border: none; cursor: pointer;">&times;</button>
            <div style="font-size: 11px; font-weight: 800; color: #00d9ff; letter-spacing: 1px; margin-bottom: 16px; text-transform: uppercase;">
                <i class="fa-solid fa-expand"></i> ÁNH ĐẠI DIỆN HD
            </div>
            <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 16px;">
                <div class="mc-led-avatar-7mau" style="width: 210px; height: 210px; padding: 6px;">
                    <img src="" id="avatarPreviewImg" style="width: calc(100% - 10px) !important; height: calc(100% - 10px) !important; border-radius: 50%; object-fit: cover;">
                </div>
            </div>
            <div id="avatarPreviewTitle" style="font-size: 18px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px;"></div>
            <div id="avatarPreviewSub" style="font-size: 12px; color: #fda4af; margin-top: 6px; font-style: italic;"></div>
        </div>

        <script>
        var svId = <?= json_encode($sv_id) ?>;
        function handleTikTokFileUpload(input) {
            if (input.files && input.files[0]) {
                var file = input.files[0];
                var videoUrl = URL.createObjectURL(file);
                
                var containers = [document.getElementById('tiktokFrameContainer'), document.getElementById('tiktokFrameContainerMale')];
                containers.forEach(function(container) {
                    if (container) {
                        renderVideoInContainer(container, videoUrl);
                    }
                });

                var formData = new FormData();
                formData.append('action', 'save_tiktok_video');
                formData.append('is_ajax', '1');
                formData.append('video_file', file);
                fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.video_url) {
                        try { localStorage.setItem('st_saved_tiktok_video_' + svId, d.video_url); } catch(e){}
                    }
                })
                .catch(e => console.error(e));
            }
        }

        function changeTikTokVideo() {
            var urlInput = document.getElementById('inputTikTokUrl');
            if (!urlInput) return;
            var url = urlInput.value.trim();
            if (!url) return;

            var containers = [document.getElementById('tiktokFrameContainer'), document.getElementById('tiktokFrameContainerMale')];
            containers.forEach(function(container) {
                if (container) {
                    renderVideoInContainer(container, url);
                }
            });

            try { localStorage.setItem('st_saved_tiktok_video_' + svId, url); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_tiktok_video');
            formData.append('is_ajax', '1');
            formData.append('video_url', url);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function changeTikTokVideoMale() {
            var urlInput = document.getElementById('inputTikTokUrlMale');
            if (!urlInput) return;
            var url = urlInput.value.trim();
            if (!url) return;

            var containers = [document.getElementById('tiktokFrameContainer'), document.getElementById('tiktokFrameContainerMale')];
            containers.forEach(function(container) {
                if (container) {
                    renderVideoInContainer(container, url);
                }
            });

            try { localStorage.setItem('st_saved_tiktok_video_' + svId, url); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_tiktok_video');
            formData.append('is_ajax', '1');
            formData.append('video_url', url);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function toggleTikTokVideoFitMode() {
            var currentFit = localStorage.getItem('st_saved_video_fit_' + svId) || 'contain';
            var newFit = (currentFit === 'contain') ? 'cover' : 'contain';

            var vids = [document.getElementById('tiktokPlayerVideo'), document.getElementById('tiktokPlayerVideoMale')];
            vids.forEach(function(v) {
                if (v) v.style.objectFit = newFit;
            });

            var btns = [document.getElementById('btnVideoFitToggle'), document.getElementById('btnVideoFitToggleMale')];
            btns.forEach(function(b) {
                if (b) b.innerHTML = (newFit === 'contain') ? '🖼️ Vừa Khung' : '🔍 Lấp Đầy';
            });

            try { localStorage.setItem('st_saved_video_fit_' + svId, newFit); } catch(e){}
        }

        function renderVideoInContainer(container, url) {
            if (!container || !url) return;
            var fitMode = localStorage.getItem('st_saved_video_fit_' + svId) || 'cover';
            var vidId = (container.id === 'tiktokFrameContainerMale') ? 'tiktokPlayerVideoMale' : 'tiktokPlayerVideo';

            if (url.match(/\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i) || url.indexOf('blob:') === 0 || url.indexOf('data:video') === 0 || url.indexOf('/uploads/videos/') !== -1) {
                container.innerHTML = '<video id="' + vidId + '" src="' + url + '" autoplay loop muted playsinline controls style="width:100%; height:100%; max-height:100%; object-fit:' + fitMode + '; border-radius:14px; display:block; background:#000;"></video>';
                return;
            }

            var ttMatch = url.match(/\/video\/(\d+)/) || url.match(/\/v\/(\d+)/) || url.match(/modal_id=(\d+)/);
            if (ttMatch && ttMatch[1]) {
                var videoId = ttMatch[1];
                container.innerHTML = '<iframe src="https://www.tiktok.com/embed/v2/' + videoId + '" style="width:100%; height:100%; max-height:100%; border:none; overflow:hidden; border-radius:14px;" scrolling="no" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture"></iframe>';
                return;
            }

            var ytMatch = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/);
            if (ytMatch && ytMatch[1]) {
                var ytId = ytMatch[1];
                container.innerHTML = '<iframe src="https://www.youtube.com/embed/' + ytId + '?autoplay=1&rel=0" style="width:100%; height:100%; max-height:100%; border:none; overflow:hidden; border-radius:14px;" scrolling="no" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture"></iframe>';
                return;
            }

            container.innerHTML = '<video id="' + vidId + '" src="' + url + '" autoplay loop muted playsinline controls style="width:100%; height:100%; max-height:100%; object-fit:' + fitMode + '; border-radius:14px; display:block; background:#000;"></video>';
        }


        function toggleBannerFitMode() {
            var img = document.getElementById('ltHeroBannerImg');
            var btn = document.getElementById('btnBannerFitToggle');
            if (!img) return;

            var currentFit = img.style.objectFit || 'cover';
            var newFit = (currentFit === 'contain') ? 'cover' : 'contain';

            img.style.objectFit = newFit;
            if (btn) {
                btn.innerHTML = (newFit === 'contain') ? '🖼️ Vừa Khung (Full)' : '🔍 Lấp Đầy (Cover)';
            }

            try { localStorage.setItem('st_saved_banner_fit_' + svId, newFit); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_banner_fit');
            formData.append('is_ajax', '1');
            formData.append('banner_fit', newFit);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function setBannerPosPreset(pos) {
            var img = document.getElementById('ltHeroBannerImg');
            if (img) {
                img.style.objectPosition = pos;
            }
            try { localStorage.setItem('st_saved_banner_pos_' + svId, pos); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_banner_pos');
            formData.append('is_ajax', '1');
            formData.append('banner_pos', pos);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function updateLiveClockDisplay() {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            var seconds = String(now.getSeconds()).padStart(2, '0');
            var timeString = hours + ':' + minutes + ':' + seconds;

            var days = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
            var dayName = days[now.getDay()];
            var dateNum = String(now.getDate()).padStart(2, '0');
            var monthNum = String(now.getMonth() + 1).padStart(2, '0');
            var dateString = dayName + ', ' + dateNum + '/' + monthNum;

            var clockEls = [document.getElementById('ltLiveClock'), document.getElementById('ltLiveClockMale')];
            clockEls.forEach(function(el) {
                if (el) el.textContent = timeString;
            });

            var dateEls = [document.getElementById('ltLiveDate'), document.getElementById('ltLiveDateMale')];
            dateEls.forEach(function(el) {
                if (el) el.textContent = dateString;
            });
        }

        function sfSaveHeroEdits() {
            try {
                var nameEl = document.getElementById('sfHeroName');
                var quoteEl = document.getElementById('sfHeroQuote');
                var monEl = document.getElementById('sfStatMon');
                var baiEl = document.getElementById('sfStatBai');
                var diemEl = document.getElementById('sfStatDiem');

                if (nameEl) localStorage.setItem('sf_hero_name_' + <?= json_encode($sv_id) ?>, nameEl.innerHTML);
                if (quoteEl) localStorage.setItem('sf_hero_quote_' + <?= json_encode($sv_id) ?>, quoteEl.innerHTML);
                if (monEl) localStorage.setItem('sf_stat_mon_' + <?= json_encode($sv_id) ?>, monEl.innerText);
                if (baiEl) localStorage.setItem('sf_stat_bai_' + <?= json_encode($sv_id) ?>, baiEl.innerText);
                if (diemEl) localStorage.setItem('sf_stat_diem_' + <?= json_encode($sv_id) ?>, diemEl.innerText);
            } catch(e){}
        }

        function sfLoadHeroEdits() {
            try {
                var svId = <?= json_encode($sv_id) ?>;
                var name = localStorage.getItem('sf_hero_name_' + svId);
                var quote = localStorage.getItem('sf_hero_quote_' + svId);
                var mon = localStorage.getItem('sf_stat_mon_' + svId);
                var bai = localStorage.getItem('sf_stat_bai_' + svId);
                var diem = localStorage.getItem('sf_stat_diem_' + svId);

                var nameEl = document.getElementById('sfHeroName');
                var quoteEl = document.getElementById('sfHeroQuote');
                var monEl = document.getElementById('sfStatMon');
                var baiEl = document.getElementById('sfStatBai');
                var diemEl = document.getElementById('sfStatDiem');

                if (name && nameEl) nameEl.innerHTML = name;
                if (quote && quoteEl) quoteEl.innerHTML = quote;
                if (mon && monEl) monEl.innerText = mon;
                if (bai && baiEl) baiEl.innerText = bai;
                if (diem && diemEl) diemEl.innerText = diem;
            } catch(e){}
        }

        window.sfSocialBoxClick = function(platform) {
            var svId = <?= json_encode($sv_id) ?>;
            var key = 'sf_social_link_' + platform + '_' + svId;
            var currentLink = localStorage.getItem(key) || '';

            if (!currentLink) {
                var name = platform === 'facebook' ? 'Facebook' : (platform === 'tiktok' ? 'TikTok' : 'Instagram');
                var newLink = prompt('Dán đường dẫn trang ' + name + ' của bạn vào đây:', '');
                if (newLink !== null && newLink.trim() !== '') {
                    newLink = newLink.trim();
                    if (newLink.indexOf('http') !== 0) newLink = 'https://' + newLink;
                    localStorage.setItem(key, newLink);
                    sfLoadSocialLinks();
                    if (typeof sfShowToast === 'function') sfShowToast('✅ Đã gắn link ' + name + '!');
                }
            } else {
                window.open(currentLink, '_blank');
            }
        };

        window.sfSocialPenClick = function(platform, e) {
            if (e) e.stopPropagation();
            var svId = <?= json_encode($sv_id) ?>;
            var key = 'sf_social_link_' + platform + '_' + svId;
            var currentLink = localStorage.getItem(key) || '';
            var name = platform === 'facebook' ? 'Facebook' : (platform === 'tiktok' ? 'TikTok' : 'Instagram');
            var newLink = prompt('Sửa đường dẫn ' + name + ' của bạn:', currentLink);
            if (newLink !== null) {
                newLink = newLink.trim();
                if (newLink !== '' && newLink.indexOf('http') !== 0) newLink = 'https://' + newLink;
                localStorage.setItem(key, newLink);
                sfLoadSocialLinks();
                if (typeof sfShowToast === 'function') sfShowToast('✅ Đã cập nhật link ' + name + '!');
            }
        };

        window.openSocialLinksModal = function(platform) {
            var modal = document.getElementById('socialLinksModal');
            var svId = <?= json_encode($sv_id) ?>;
            var fb = localStorage.getItem('sf_social_link_facebook_' + svId) || '';
            var tiktok = localStorage.getItem('sf_social_link_tiktok_' + svId) || '';
            var ig = localStorage.getItem('sf_social_link_instagram_' + svId) || '';

            var inFb = document.getElementById('inputSocialFb');
            var inTiktok = document.getElementById('inputSocialTiktok');
            var inIg = document.getElementById('inputSocialIg');

            if (inFb) inFb.value = fb;
            if (inTiktok) inTiktok.value = tiktok;
            if (inIg) inIg.value = ig;

            var btnFb = document.getElementById('btnGoFb');
            var btnTt = document.getElementById('btnGoTiktok');
            var btnIg = document.getElementById('btnGoIg');
            if (btnFb) { btnFb.style.display = fb ? 'inline-block' : 'none'; btnFb.href = fb; }
            if (btnTt) { btnTt.style.display = tiktok ? 'inline-block' : 'none'; btnTt.href = tiktok; }
            if (btnIg) { btnIg.style.display = ig ? 'inline-block' : 'none'; btnIg.href = ig; }

            if (modal) {
                modal.style.display = 'flex';
                setTimeout(function() {
                    if (platform === 'facebook' && inFb) inFb.focus();
                    else if (platform === 'tiktok' && inTiktok) inTiktok.focus();
                    else if (platform === 'instagram' && inIg) inIg.focus();
                }, 100);
            }
        };

        window.closeSocialLinksModal = function() {
            var modal = document.getElementById('socialLinksModal');
            if (modal) modal.style.display = 'none';
        };

        window.saveSocialLinksFromModal = function() {
            var svId = <?= json_encode($sv_id) ?>;
            var fb = (document.getElementById('inputSocialFb')?.value || '').trim();
            var tiktok = (document.getElementById('inputSocialTiktok')?.value || '').trim();
            var ig = (document.getElementById('inputSocialIg')?.value || '').trim();

            if (fb && fb.indexOf('http') !== 0) fb = 'https://' + fb;
            if (tiktok && tiktok.indexOf('http') !== 0) tiktok = 'https://' + tiktok;
            if (ig && ig.indexOf('http') !== 0) ig = 'https://' + ig;

            localStorage.setItem('sf_social_link_facebook_' + svId, fb);
            localStorage.setItem('sf_social_link_tiktok_' + svId, tiktok);
            localStorage.setItem('sf_social_link_instagram_' + svId, ig);

            sfLoadSocialLinks();
            closeSocialLinksModal();
            if (typeof sfShowToast === 'function') sfShowToast('✅ Đã lưu thành công các liên kết Mạng Xã Hội!');
        };

        window.sfLoadSocialLinks = function() {
            try {
                var svId = <?= json_encode($sv_id) ?>;
                var fb = localStorage.getItem('sf_social_link_facebook_' + svId);
                var tiktok = localStorage.getItem('sf_social_link_tiktok_' + svId);
                var ig = localStorage.getItem('sf_social_link_instagram_' + svId);

                var fbEl = document.getElementById('sfFbText');
                var ttEl = document.getElementById('sfTiktokText');
                var igEl = document.getElementById('sfIgText');

                if (fbEl) fbEl.textContent = fb ? 'Đã gắn link' : 'Gắn link FB';
                if (ttEl) ttEl.textContent = tiktok ? 'Đã gắn link' : 'Gắn link TikTok';
                if (igEl) igEl.textContent = ig ? 'Đã gắn link' : 'Gắn link Insta';
            } catch(e){}
        };

        document.addEventListener('DOMContentLoaded', function() {
            sfLoadHeroEdits();
            sfLoadSocialLinks();
            updateLiveClockDisplay();
            setInterval(updateLiveClockDisplay, 1000);

            try {
                var savedVid = localStorage.getItem('st_saved_tiktok_video_' + svId);
                if (savedVid) {
                    var maleContainer = document.getElementById('tiktokFrameContainerMale');
                    if (maleContainer) {
                        renderVideoInContainer(maleContainer, savedVid);
                    }
                }
            } catch(e){}


            // Kéo Rê Chuột Căn Chỉnh Vị Trí Ảnh Banner Trực Tiếp
            var img = document.getElementById('ltHeroBannerImg');
            if (img) {
                var savedPos = localStorage.getItem('st_saved_banner_pos_' + svId);
                if (savedPos) {
                    img.style.objectPosition = savedPos;
                }
                var savedFit = localStorage.getItem('st_saved_banner_fit_' + svId);
                if (savedFit) {
                    img.style.objectFit = savedFit;
                    var btn = document.getElementById('btnBannerFitToggle');
                    if (btn) {
                        btn.innerHTML = (savedFit === 'contain') ? '🖼️ Vừa Khung (Full)' : '🔍 Lấp Đầy (Cover)';
                    }
                }

                var isDragging = false;
                var startY = 0;
                var currentYPercent = 50;

                var currentPosStr = img.style.objectPosition || 'center center';
                var parts = currentPosStr.split(' ');
                if (parts.length >= 2 && parts[1].indexOf('%') !== -1) {
                    currentYPercent = parseFloat(parts[1]) || 50;
                } else if (parts[1] === 'top') {
                    currentYPercent = 0;
                } else if (parts[1] === 'bottom') {
                    currentYPercent = 100;
                }

                var startX = 0;
                var hasMoved = false;

                img.addEventListener('mousedown', function(e) {
                    isDragging = true;
                    hasMoved = false;
                    startY = e.clientY;
                    startX = e.clientX;
                    img.style.cursor = 'grabbing';
                });

                window.addEventListener('mousemove', function(e) {
                    if (!isDragging) return;
                    var dx = Math.abs(e.clientX - startX);
                    var dy = Math.abs(e.clientY - startY);
                    if (dx > 5 || dy > 5) {
                        hasMoved = true;
                    }
                    var deltaY = e.clientY - startY;
                    var newY = Math.max(0, Math.min(100, currentYPercent - (deltaY * 0.35)));
                    img.style.objectPosition = 'center ' + newY.toFixed(1) + '%';
                });

                window.addEventListener('mouseup', function(e) {
                    if (isDragging) {
                        isDragging = false;
                        img.style.cursor = 'pointer';
                        if (!hasMoved) {
                            var fileInput = document.getElementById('sfBannerUploadMulti');
                            if (fileInput) fileInput.click();
                        } else {
                            var pos = img.style.objectPosition;
                            var parts = pos.split(' ');
                            if (parts.length >= 2 && parts[1].indexOf('%') !== -1) {
                                currentYPercent = parseFloat(parts[1]) || 50;
                            }
                            setBannerPosPreset(pos);
                        }
                    }
                });
            }
        });

        function openAvatarPreview(src, title, sub) {
            var modal = document.getElementById('avatarPreviewModal');
            var overlay = document.getElementById('avatarPreviewOverlay');
            var img = document.getElementById('avatarPreviewImg');
            var titleEl = document.getElementById('avatarPreviewTitle');
            var subEl = document.getElementById('avatarPreviewSub');

            if (img) img.src = src;
            if (titleEl) titleEl.innerText = title || 'Ảnh Đại Diện';
            if (subEl) subEl.innerText = sub || 'Góc Khoe Người Yêu 💕';

            if (modal && overlay) {
                modal.classList.add('active');
                overlay.classList.add('active');
            }
        }

        function closeAvatarPreviewModal() {
            var modal = document.getElementById('avatarPreviewModal');
            var overlay = document.getElementById('avatarPreviewOverlay');
            if (modal && overlay) {
                modal.classList.remove('active');
                overlay.classList.remove('active');
            }
        }

        // =========================================================
        // BANNER MANAGER MODAL JS HANDLERS
        // =========================================================
        var pendingModalFile = null;

        function openBannerManagerModal() {
            var modal = document.getElementById('bannerManagerModal');
            var overlay = document.getElementById('bannerManagerOverlay');
            if (modal && overlay) {
                modal.style.display = 'block';
                overlay.style.display = 'block';
            }
        }

        function closeBannerManagerModal() {
            var modal = document.getElementById('bannerManagerModal');
            var overlay = document.getElementById('bannerManagerOverlay');
            if (modal && overlay) {
                modal.style.display = 'none';
                overlay.style.display = 'none';
            }
        }

        function setModalBannerFit(fit) {
            var mainImg = document.getElementById('ltHeroBannerImg');
            var modalImg = document.getElementById('modalBannerImg');
            if (mainImg) mainImg.style.objectFit = fit;
            if (modalImg) modalImg.style.objectFit = fit;

            var btnContain = document.getElementById('modalBtnFitContain');
            var btnCover = document.getElementById('modalBtnFitCover');
            if (btnContain && btnCover) {
                if (fit === 'contain') {
                    btnContain.style.background = '#e0f2fe';
                    btnContain.style.borderColor = '#0284c7';
                    btnContain.style.color = '#0369a1';

                    btnCover.style.background = '#ffffff';
                    btnCover.style.borderColor = '#cbd5e1';
                    btnCover.style.color = '#475569';
                } else {
                    btnCover.style.background = '#e0f2fe';
                    btnCover.style.borderColor = '#0284c7';
                    btnCover.style.color = '#0369a1';

                    btnContain.style.background = '#ffffff';
                    btnContain.style.borderColor = '#cbd5e1';
                    btnContain.style.color = '#475569';
                }
            }
            try { localStorage.setItem('st_saved_banner_fit_' + svId, fit); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_banner_fit');
            formData.append('is_ajax', '1');
            formData.append('banner_fit', fit);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function setModalBannerPos(pos) {
            setBannerPosPreset(pos);
            var modalImg = document.getElementById('modalBannerImg');
            if (modalImg) modalImg.style.objectPosition = pos;
        }

        function previewModalUploadedBanner(input) {
            if (input.files && input.files[0]) {
                var file = input.files[0];
                pendingModalFile = file;
                var url = URL.createObjectURL(file);
                var modalImg = document.getElementById('modalBannerImg');
                var modalBlur = document.getElementById('modalBannerBlurBg');
                var mainImg = document.getElementById('ltHeroBannerImg');
                var mainBlur = document.getElementById('ltHeroBannerBlurBg');
                var label = document.getElementById('modalFileNameLabel');

                if (modalImg) modalImg.src = url;
                if (modalBlur) modalBlur.src = url;
                if (mainImg) mainImg.src = url;
                if (mainBlur) mainBlur.src = url;
                if (label) label.innerText = '📁 Đã chọn tệp: ' + file.name;

                var reader = new FileReader();
                reader.onload = function(e) {
                    var base64El = document.getElementById('modalBannerBase64');
                    if (base64El) base64El.value = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function saveBannerManagerSettings() {
            var fileInput = document.getElementById('modalBannerFileInput');
            var form = document.getElementById('modalBannerUploadForm');
            var base64El = document.getElementById('modalBannerBase64');

            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                sfBannerAddFiles(fileInput);
                closeBannerManagerModal();
                return;
            }

            if (pendingModalFile || (base64El && base64El.value)) {
                if (form) form.submit();
                return;
            }

            closeBannerManagerModal();
            sfShowToast('✅ Đã lưu cài đặt Banner!');
        }

        </script>

        <!-- ============ GALLERY CAROUSEL JS — Banner & Video Multi-Upload ============ -->
        <script>
        (function() {
            // ═══════════ BANNER GALLERY ═══════════
            var sfBannerItems = [];
            var sfBannerIdx = 0;
            var sfBannerAutoTimer = null;

            // Default banner and DB server banners from PHP
            var sfDefaultBanner = <?= json_encode($banner_url) ?>;
            var sfServerBannerItems = <?= json_encode($banner_gallery_list) ?>;

            function sfBannerLoad() {
                var serverItems = Array.isArray(sfServerBannerItems) ? sfServerBannerItems : [];
                var localItems = [];
                try {
                    var svId = <?= json_encode($sv_id) ?>;
                    var saved = localStorage.getItem('sf_banner_gallery_' + svId);
                    if (saved) {
                        var parsed = JSON.parse(saved);
                        if (Array.isArray(parsed)) localItems = parsed;
                    }
                } catch(e) {}

                var combined = serverItems.concat(localItems);
                var unique = [];
                combined.forEach(function(item) {
                    if (!item || typeof item !== 'string') return;
                    var u = item.trim();
                    if (u === '' || u.indexOf('blob:') === 0) return;
                    var lower = u.toLowerCase();
                    if (lower === 'banner.jpg' || lower === 'default.jpg' || lower === 'default.png' || lower === 'sample.jpg') return;
                    if (lower.indexOf('banner.jpg') !== -1 && lower.indexOf('banner_') === -1) return;
                    if (lower === '/tkb/assets/img/banners/' || lower === '/tkb/assets/img/banners/banner.jpg') return;
                    if (unique.indexOf(u) === -1) {
                        unique.push(u);
                    }
                });

                sfBannerItems = unique;
                if (sfBannerItems.length === 0) {
                    try {
                        var svId = <?= json_encode($sv_id) ?>;
                        localStorage.removeItem('sf_banner_gallery_' + svId);
                        localStorage.removeItem('st_user_banner_url_' + svId);
                    } catch(e){}
                }

                sfBannerIdx = sfBannerItems.length > 0 ? sfBannerItems.length - 1 : 0;
                sfBannerShow();
                sfBannerResetAuto();
            }

            function sfBannerSave() {
                try {
                    var clean = sfBannerItems.filter(function(item) {
                        if (!item || typeof item !== 'string' || item.indexOf('blob:') === 0) return false;
                        var lower = item.toLowerCase().trim();
                        if (lower === 'banner.jpg' || lower === 'default.jpg' || lower === 'default.png') return false;
                        if (lower.indexOf('banner.jpg') !== -1 && lower.indexOf('banner_') === -1) return false;
                        return true;
                    });
                    if (clean.length > 0) {
                        localStorage.setItem('st_user_banner_url_' + <?= json_encode($sv_id) ?>, clean[clean.length - 1]);
                        localStorage.setItem('st_user_banner_ts_' + <?= json_encode($sv_id) ?>, Math.floor(Date.now() / 1000).toString());
                    } else {
                        localStorage.removeItem('st_user_banner_url_' + <?= json_encode($sv_id) ?>);
                    }
                    localStorage.setItem('sf_banner_gallery_' + <?= json_encode($sv_id) ?>, JSON.stringify(clean));

                    // Sync to MySQL Database so banner list NEVER dies on logout!
                    var formData = new FormData();
                    formData.append('action', 'save_banner_gallery');
                    formData.append('gallery_json', JSON.stringify(clean));
                    fetch('/tkb/api/upload_video.php', { method: 'POST', body: formData }).catch(function(){});
                } catch(e) {}
            }

            function sfBannerShow() {
                var img = document.getElementById('ltHeroBannerImg');
                var placeholder = document.getElementById('ltHeroBannerPlaceholder');
                var blur = document.getElementById('ltHeroBannerBlurBg');
                
                var counters = document.querySelectorAll('#sfBannerCounter');
                var prevBtns = document.querySelectorAll('.sf-banner-prev-btn');
                var nextBtns = document.querySelectorAll('.sf-banner-next-btn');
                var removeBtns = document.querySelectorAll('.sf-banner-remove-btn');
                var dotsEls = document.querySelectorAll('#sfBannerDots');

                var totalCount = (sfBannerItems && Array.isArray(sfBannerItems)) ? sfBannerItems.length : 0;
                counters.forEach(function(c) {
                    c.textContent = totalCount > 0 ? ((sfBannerIdx + 1) + ' / ' + totalCount) : '0 / 0';
                });

                if (totalCount === 0 || !sfBannerItems[0]) {
                    if (img) img.style.display = 'none';
                    if (placeholder) placeholder.style.display = 'block';
                    removeBtns.forEach(function(b){ b.style.display = 'none'; });
                    prevBtns.forEach(function(b){ b.style.display = 'none'; });
                    nextBtns.forEach(function(b){ b.style.display = 'none'; });
                    dotsEls.forEach(function(d){ d.innerHTML = ''; });
                    return;
                }

                if (sfBannerIdx < 0) sfBannerIdx = totalCount - 1;
                if (sfBannerIdx >= totalCount) sfBannerIdx = 0;

                removeBtns.forEach(function(b){ b.style.display = 'inline-flex'; });
                if (totalCount > 1) {
                    prevBtns.forEach(function(b){ b.style.display = 'flex'; });
                    nextBtns.forEach(function(b){ b.style.display = 'flex'; });
                } else {
                    prevBtns.forEach(function(b){ b.style.display = 'none'; });
                    nextBtns.forEach(function(b){ b.style.display = 'none'; });
                }

                var src = sfBannerItems[sfBannerIdx];
                if (img) {
                    img.onerror = function() {
                        this.style.display = 'none';
                        if (placeholder) placeholder.style.display = 'block';
                        if (sfBannerItems && sfBannerItems.length > 0) {
                            sfBannerItems.splice(sfBannerIdx, 1);
                            sfBannerIdx = Math.max(0, sfBannerItems.length - 1);
                            sfBannerSave();
                            sfBannerShow();
                        }
                    };
                    if (src && src.trim() !== '') {
                        img.style.display = 'block';
                        if (placeholder) placeholder.style.display = 'none';
                        img.style.opacity = '0'; 
                        img.style.transition = 'opacity 0.35s ease'; 
                        setTimeout(function() { img.src = src; img.style.opacity = '1'; }, 50); 
                    } else {
                        img.style.display = 'none';
                        if (placeholder) placeholder.style.display = 'block';
                    }
                }
                if (blur && src) blur.src = src;

                sfBannerRenderDots();
            }

            function sfBannerRenderDots() {
                var dotsEl = document.getElementById('sfBannerDots');
                if (!dotsEl) return;
                if (sfBannerItems.length <= 1) { dotsEl.innerHTML = ''; return; }
                var maxDots = Math.min(sfBannerItems.length, 8);
                var html = '';
                for (var i = 0; i < maxDots; i++) {
                    var isActive = (i === sfBannerIdx);
                    html += '<span onclick="sfBannerGoTo(' + i + ')" style="width: ' + (isActive ? '20px' : '8px') + '; height: 8px; border-radius: 4px; background: ' + (isActive ? 'rgba(236,72,153,0.9)' : 'rgba(255,255,255,0.5)') + '; cursor: pointer; transition: all 0.3s; box-shadow: ' + (isActive ? '0 0 8px rgba(236,72,153,0.5)' : 'none') + ';"></span>';
                }
                if (sfBannerItems.length > maxDots) {
                    html += '<span style="color: rgba(255,255,255,0.6); font-size: 10px; font-weight: bold;">+' + (sfBannerItems.length - maxDots) + '</span>';
                }
                dotsEl.innerHTML = html;
            }

            window.sfBannerGoTo = function(idx) {
                sfBannerIdx = idx;
                sfBannerShow();
                sfBannerResetAuto();
            };

            window.sfBannerPrev = function() {
                sfBannerIdx--;
                sfBannerShow();
                sfBannerResetAuto();
            };

            window.sfBannerNext = function() {
                sfBannerIdx++;
                sfBannerShow();
                sfBannerResetAuto();
            };

            window.sfBannerAddFiles = function(input) {
                if (!input.files || input.files.length === 0) return;
                var files = Array.from(input.files);

                sfShowToast('⏳ Đang tải ' + files.length + ' ảnh banner...');

                files.forEach(function(file) {
                    var formData = new FormData();
                    formData.append('banner_file', file);

                    fetch('/tkb/api/upload_banner.php', { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d && d.success) {
                            if (Array.isArray(d.gallery) && d.gallery.length > 0) {
                                sfBannerItems = d.gallery.slice();
                            } else if (d.banner_url && sfBannerItems.indexOf(d.banner_url) === -1) {
                                sfBannerItems.push(d.banner_url);
                            }
                            sfBannerIdx = sfBannerItems.length - 1;
                            sfBannerSave();
                            sfBannerShow();
                            sfBannerResetAuto();
                            sfShowToast('🖼️ Đã thêm ảnh banner!');
                        } else {
                            sfShowToast('⚠️ ' + (d ? (d.message || d.error) : 'Không thể tải ảnh banner!'));
                        }
                    })
                    .catch(function(err) {
                        console.warn('Banner upload fallback to DataURL:', err);
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            if (sfBannerItems.indexOf(e.target.result) === -1) {
                                sfBannerItems.push(e.target.result);
                            }
                            sfBannerIdx = sfBannerItems.length - 1;
                            sfBannerSave();
                            sfBannerShow();
                            sfBannerResetAuto();
                            sfShowToast('🖼️ Đã thêm ảnh banner!');
                        };
                        reader.readAsDataURL(file);
                    });
                });
                input.value = '';
            };

            window.sfBannerRemoveCurrent = function() {
                if (!sfBannerItems || sfBannerItems.length === 0) {
                    sfShowToast('⚠️ Chưa có ảnh banner nào trong bộ sưu tập!');
                    return;
                }
                sfBannerItems.splice(sfBannerIdx, 1);
                if (sfBannerIdx >= sfBannerItems.length) sfBannerIdx = sfBannerItems.length - 1;
                if (sfBannerIdx < 0) sfBannerIdx = 0;
                sfBannerSave();
                sfBannerShow();
                sfShowToast('🗑️ Đã xóa ảnh banner!');
            };

            function sfBannerResetAuto() {
                if (sfBannerAutoTimer) clearInterval(sfBannerAutoTimer);
                if (sfBannerItems.length > 1) {
                    sfBannerAutoTimer = setInterval(function() {
                        sfBannerIdx++;
                        sfBannerShow();
                    }, 6000);
                }
            }
            function saveVideoFileToIDB(key, fileBlob, callback) {
                try {
                    var req = indexedDB.open('TkbMediaDB', 1);
                    req.onupgradeneeded = function(e) {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('videos')) {
                            db.createObjectStore('videos');
                        }
                    };
                    req.onsuccess = function(e) {
                        var db = e.target.result;
                        var tx = db.transaction('videos', 'readwrite');
                        var store = tx.objectStore('videos');
                        var pReq = store.put(fileBlob, key);
                        pReq.onsuccess = function() { if (callback) callback(true); };
                        pReq.onerror = function() { if (callback) callback(false); };
                    };
                    req.onerror = function() { if (callback) callback(false); };
                } catch(err) { if (callback) callback(false); }
            }

            function loadVideosFromIDB(svId, callback) {
                if (!svId) {
                    if (callback) callback([]);
                    return;
                }
                try {
                    var req = indexedDB.open('TkbMediaDB', 1);
                    req.onupgradeneeded = function(e) {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('videos')) {
                            db.createObjectStore('videos');
                        }
                    };
                    req.onsuccess = function(e) {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('videos')) {
                            if (callback) callback([]);
                            return;
                        }
                        var tx = db.transaction('videos', 'readonly');
                        var store = tx.objectStore('videos');
                        var cursorReq = store.openCursor();
                        var items = [];
                        var prefix = 'idb_vid_' + svId + '_';
                        cursorReq.onsuccess = function(ev) {
                            var cursor = ev.target.result;
                            if (cursor) {
                                if (typeof cursor.key === 'string' && cursor.key.indexOf(prefix) === 0 && cursor.value) {
                                    try {
                                        var blobUrl = URL.createObjectURL(cursor.value);
                                        items.push(blobUrl);
                                    } catch(err){}
                                }
                                cursor.continue();
                            } else {
                                if (callback) callback(items);
                            }
                        };
                        cursorReq.onerror = function() { if (callback) callback([]); };
                    };
                    req.onerror = function() { if (callback) callback([]); };
                } catch(err) { if (callback) callback([]); }
            }

            function clearVideosFromIDB(svId) {
                if (!svId) return;
                try {
                    var req = indexedDB.open('TkbMediaDB', 1);
                    req.onsuccess = function(e) {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('videos')) return;
                        var tx = db.transaction('videos', 'readwrite');
                        var store = tx.objectStore('videos');
                        var cursorReq = store.openCursor();
                        var prefix = 'idb_vid_' + svId + '_';
                        cursorReq.onsuccess = function(ev) {
                            var cursor = ev.target.result;
                            if (cursor) {
                                if (typeof cursor.key === 'string' && cursor.key.indexOf(prefix) === 0) {
                                    cursor.delete();
                                }
                                cursor.continue();
                            }
                        };
                    };
                } catch(err) {}
            }

            // ═══════════ VIDEO GALLERY ═══════════
            var sfVideoItems = [];
            var sfVideoIdx = 0;

            var sfDefaultVideo = <?= json_encode($saved_tiktok_video) ?>;
            var sfServerVideoItems = <?= json_encode($video_gallery_list) ?>;

            function sfVideoLoad() {
                var serverItems = Array.isArray(sfServerVideoItems) ? sfServerVideoItems.slice() : [];
                var localItems = [];
                var svId = <?= json_encode($sv_id) ?>;

                // Clear legacy un-namespaced keys to avoid sharing across accounts
                try {
                    localStorage.removeItem('st_saved_tiktok_video');
                    localStorage.removeItem('sf_video_gallery');
                    localStorage.removeItem('st_video_items');
                } catch(e){}

                try {
                    var saved = localStorage.getItem('sf_video_gallery_' + svId);
                    if (saved) {
                        var parsed = JSON.parse(saved);
                        if (Array.isArray(parsed)) localItems = parsed;
                    }
                    var singleSaved = localStorage.getItem('st_saved_tiktok_video_' + svId);
                    if (singleSaved && localItems.indexOf(singleSaved) === -1) {
                        localItems.push(singleSaved);
                    }
                } catch(e) {}

                loadVideosFromIDB(svId, function(idbItems) {
                    var combined = [];
                    if (serverItems.length > 0) {
                        combined = serverItems.concat(localItems);
                    } else if (localItems.length > 0) {
                        combined = localItems;
                    } else if (idbItems && idbItems.length > 0 && Array.isArray(sfServerVideoItems) && sfServerVideoItems.length > 0) {
                        combined = idbItems;
                    }

                    var clean = combined.filter(function(item) {
                        if (!item || typeof item !== 'string') return false;
                        var u = item.trim();
                        if (u === '') return false;
                        var lower = u.toLowerCase();
                        if (lower === 'video.mp4' || lower === 'default.mp4' || lower === 'sample.mp4' || lower === 'intro_video.mp4') return false;
                        return true;
                    });

                    var unique = [];
                    clean.forEach(function(item) {
                        if (unique.indexOf(item) === -1) {
                            unique.push(item);
                        }
                    });

                    sfVideoItems = unique;
                    if (sfVideoItems.length === 0) {
                        try {
                            localStorage.removeItem('sf_video_gallery_' + svId);
                            localStorage.removeItem('st_saved_tiktok_video_' + svId);
                            clearVideosFromIDB(svId);
                        } catch(e){}
                    }

                    sfVideoIdx = sfVideoItems.length > 0 ? sfVideoItems.length - 1 : 0;
                    sfVideoShow();
                });
            }

            function sfVideoSave() {
                try {
                    var svId = <?= json_encode($sv_id) ?>;
                    var cleanItems = sfVideoItems.filter(function(item) {
                        return item && typeof item === 'string' &&
                               item.indexOf('blob:') !== 0 &&
                               item.trim() !== '';
                    });
                    if (cleanItems.length > 0) {
                        localStorage.setItem('sf_video_gallery_' + svId, JSON.stringify(cleanItems));
                        localStorage.setItem('st_saved_tiktok_video_' + svId, cleanItems[cleanItems.length - 1]);
                    } else {
                        localStorage.removeItem('sf_video_gallery_' + svId);
                        localStorage.removeItem('st_saved_tiktok_video_' + svId);
                        clearVideosFromIDB(svId);
                    }

                    // Always sync to MySQL database in both dashboard.php and api/upload_video.php
                    var formData = new FormData();
                    formData.append('action', 'save_video_gallery');
                    formData.append('gallery_json', JSON.stringify(cleanItems));
                    fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData, credentials: 'same-origin' }).catch(function(){});
                    fetch('/tkb/api/upload_video.php', { method: 'POST', body: formData, credentials: 'same-origin' }).catch(function(){});
                } catch(e) {
                    console.warn('sfVideoSave warning:', e);
                }
            }


            function sfVideoShow() {
                var container = document.getElementById('tiktokFrameContainer');
                var counters = document.querySelectorAll('#sfVideoCounter');
                var hCountFemale = document.getElementById('sfVideoHeaderCount');
                var hCountMale = document.getElementById('sfVideoHeaderCountMale');
                var prevBtns = document.querySelectorAll('.sf-video-prev-btn');
                var nextBtns = document.querySelectorAll('.sf-video-next-btn');
                var removeBtns = document.querySelectorAll('.sf-video-remove-btn');

                var totalCount = (sfVideoItems && Array.isArray(sfVideoItems)) ? sfVideoItems.length : 0;
                if (hCountFemale) hCountFemale.innerHTML = '<i class="fa-solid fa-layer-group" style="font-size: 9.5px;"></i> ' + totalCount + ' Video';
                if (hCountMale) hCountMale.innerHTML = '<i class="fa-solid fa-layer-group" style="font-size: 9.5px;"></i> ' + totalCount + ' Video';

                counters.forEach(function(c) {
                    c.textContent = totalCount > 0 ? ((sfVideoIdx + 1) + ' / ' + totalCount) : '0 / 0';
                });

                if (!sfVideoItems || totalCount === 0) {
                    removeBtns.forEach(function(b){ b.style.display = 'none'; });
                    prevBtns.forEach(function(b){ b.style.display = 'none'; });
                    nextBtns.forEach(function(b){ b.style.display = 'none'; });

                    if (container) {
                        var isFem = <?= json_encode($is_female) ?>;
                        var grad = isFem ? 'linear-gradient(135deg, #ec4899, #8b5cf6)' : 'linear-gradient(135deg, #a855f7, #7c3aed)';
                        var iconColor = isFem ? '#ec4899' : '#a855f7';
                        var uploadInputId = isFem ? 'sfVideoUploadMulti' : 'sfVideoUploadMultiMale';
                        container.innerHTML = '<div style="text-align: center; padding: 20px; color: rgba(255,255,255,0.85); font-family: \'Outfit\', sans-serif;">' +
                            '<div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, rgba(236,72,153,0.2), rgba(139,92,246,0.2)); border: 1.5px solid rgba(236,72,153,0.4); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 22px; color: ' + iconColor + ';">' +
                                '<i class="fa-solid fa-film"></i>' +
                            '</div>' +
                            '<div style="font-weight: 800; font-size: 13.5px; margin-bottom: 4px; color: #ffffff;">Chưa có video trong bộ sưu tập</div>' +
                            '<div style="font-size: 10.5px; color: rgba(255,255,255,0.6); max-width: 220px; margin: 0 auto 12px; line-height: 1.4;">Dán link Video MP4 / YouTube hoặc bấm Tải Lên để thêm video!</div>' +
                            '<button onclick="var inp = document.getElementById(\'' + uploadInputId + '\') || document.getElementById(\'sfVideoUploadMulti\') || document.getElementById(\'sfVideoUploadMultiMale\'); if(inp) inp.click();" style="background: ' + grad + '; border: none; color: #fff; padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 4px 12px rgba(236,72,153,0.3);">' +
                                '<i class="fa-solid fa-plus"></i> Thêm Video Ngay' +
                            '</button>' +
                        '</div>';
                    }
                    return;
                }

                removeBtns.forEach(function(b){ b.style.display = 'inline-flex'; });
                if (totalCount > 1) {
                    prevBtns.forEach(function(b){ b.style.display = 'inline-flex'; });
                    nextBtns.forEach(function(b){ b.style.display = 'inline-flex'; });
                } else {
                    prevBtns.forEach(function(b){ b.style.display = 'none'; });
                    nextBtns.forEach(function(b){ b.style.display = 'none'; });
                }

                if (sfVideoIdx < 0) sfVideoIdx = totalCount - 1;
                if (sfVideoIdx >= totalCount) sfVideoIdx = 0;

                var src = sfVideoItems[sfVideoIdx];
                if (container) {
                    sfRenderVideoItem(container, src);
                }
            }

            function sfRenderVideoItem(container, src) {
                if (!container || !src) return;
                var fitMode = localStorage.getItem('st_saved_video_fit_' + <?= json_encode($sv_id) ?>) || 'cover';
                var isSingle = (!sfVideoItems || sfVideoItems.length <= 1);
                var loopAttr = isSingle ? ' loop ' : '';

                // YouTube & YouTube Shorts Embed
                var ytMatch = src.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/);
                if (ytMatch && ytMatch[1]) {
                    var ytId = ytMatch[1];
                    container.innerHTML = '<iframe src="https://www.youtube.com/embed/' + ytId + '?autoplay=1&loop=1&playlist=' + ytId + '&rel=0&enablejsapi=1" style="width:100%; height:100%; max-height:100%; border:none; overflow:hidden; border-radius:12px;" scrolling="no" allowfullscreen referrerpolicy="no-referrer" allow="autoplay; encrypted-media; picture-in-picture"></iframe>';
                    return;
                }

                // Native Video Player for ALL direct videos & uploaded files (MP4, WebM, MOV, DataURL, etc.)
                container.innerHTML = '<video id="tiktokPlayerVideo" src="' + src + '" autoplay ' + loopAttr + ' playsinline controls style="width:100%; height:100%; max-height:100%; object-fit:' + fitMode + '; border-radius:12px; display:block; background:#000;"></video>';

                setTimeout(function() {
                    var vid = document.getElementById('tiktokPlayerVideo');
                    if (vid) {
                        vid.muted = false;
                        vid.volume = 1.0;

                        var playPromise = vid.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(function(e) {
                                // Fallback to muted autoplay if browser restricts unmuted autoplay on initial load
                                vid.muted = true;
                                vid.play().catch(function(){});
                            });
                        }

                        // Continuous Infinite Playback Loop (Switch next video or replay single video)
                        vid.onended = function() {
                            if (sfVideoItems && sfVideoItems.length > 1) {
                                sfVideoNext();
                            } else {
                                vid.currentTime = 0;
                                vid.play().catch(function(){});
                            }
                        };
                    }
                }, 50);
            }

            // Auto unmute audio on first user click if muted by browser
            document.addEventListener('click', function() {
                var vid = document.getElementById('tiktokPlayerVideo');
                if (vid && vid.muted) {
                    vid.muted = false;
                    vid.volume = 1.0;
                }
            }, { once: true });

            window.sfVideoPrev = function() {
                if (!sfVideoItems || sfVideoItems.length === 0) return;
                sfVideoIdx--;
                if (sfVideoIdx < 0) sfVideoIdx = sfVideoItems.length - 1;
                sfVideoShow();
            };

            window.sfVideoNext = function() {
                if (!sfVideoItems || sfVideoItems.length === 0) return;
                sfVideoIdx++;
                if (sfVideoIdx >= sfVideoItems.length) sfVideoIdx = 0;
                sfVideoShow();
            };

            window.sfVideoAddUrl = function() {
                var input = document.getElementById('inputTikTokUrl');
                if (!input) return;
                var url = input.value.trim();
                if (!url) return;

                sfVideoItems.push(url);
                sfVideoIdx = sfVideoItems.length - 1;
                sfVideoSave();
                sfVideoShow();
                input.value = '';
                sfShowToast('🎬 Đã thêm video vào bộ sưu tập!');

                // Also save to server
                var formData = new FormData();
                formData.append('action', 'save_tiktok_video');
                formData.append('is_ajax', '1');
                formData.append('video_url', url);
                fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
            };

            window.sfVideoAddFiles = function(input) {
                if (!input.files || input.files.length === 0) return;
                var files = Array.from(input.files);
                var svId = <?= json_encode($sv_id) ?>;

                files.forEach(function(file) {
                    sfShowToast('⏳ Đang xử lý video "' + file.name + '"...');

                    // 1. Instantly create Blob URL & Save File to IndexedDB (100% Permanent in Browser storage!)
                    var blobUrl = URL.createObjectURL(file);
                    var idbKey = 'idb_vid_' + svId + '_' + Date.now() + '_' + file.name;
                    saveVideoFileToIDB(idbKey, file);

                    if (sfVideoItems.indexOf(blobUrl) === -1) {
                        sfVideoItems.push(blobUrl);
                    }
                    sfVideoIdx = sfVideoItems.length - 1;
                    sfVideoShow();
                    sfShowToast('🎬 Đã lưu video thành công!');

                    // 2. Try server upload if supported by host
                    var formData = new FormData();
                    formData.append('video_file', file);

                    fetch('/tkb/api/upload_video.php', { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(function(r) {
                        return r.text().then(function(text) {
                            try { return JSON.parse(text); }
                            catch(e) { return null; }
                        });
                    })
                    .then(function(d) {
                        if (d && d.success && d.video_url) {
                            var idx = sfVideoItems.indexOf(blobUrl);
                            if (idx !== -1) {
                                sfVideoItems[idx] = d.video_url;
                            } else if (sfVideoItems.indexOf(d.video_url) === -1) {
                                sfVideoItems.push(d.video_url);
                            }
                            sfVideoSave();
                            sfVideoShow();
                        }
                    })
                    .catch(function(err) {});
                });
                input.value = '';
            };







            window.sfVideoRemoveCurrent = function() {
                if (!sfVideoItems || sfVideoItems.length === 0) {
                    sfShowToast('⚠️ Chưa có video nào trong bộ sưu tập!');
                    return;
                }
                var svId = <?= json_encode($sv_id) ?>;
                sfVideoItems.splice(sfVideoIdx, 1);
                if (sfVideoIdx >= sfVideoItems.length) sfVideoIdx = sfVideoItems.length - 1;
                if (sfVideoIdx < 0) sfVideoIdx = 0;
                if (sfVideoItems.length === 0) {
                    clearVideosFromIDB(svId);
                    try {
                        localStorage.removeItem('sf_video_gallery_' + svId);
                        localStorage.removeItem('st_saved_tiktok_video_' + svId);
                    } catch(e){}
                }
                sfVideoSave();
                sfVideoShow();
                sfShowToast('🗑️ Đã xóa video khỏi bộ sưu tập!');
            };

            // ═══════════ VIDEO MANAGER MODAL JS ═══════════
            window.openVideoManagerModal = function() {
                var modal = document.getElementById('videoManagerModal');
                var overlay = document.getElementById('videoManagerOverlay');
                var listEl = document.getElementById('videoManagerList');

                if (!modal || !overlay || !listEl) return;

                if (!sfVideoItems || sfVideoItems.length === 0) {
                    listEl.innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8; font-size:13px; font-weight:600;">Chưa có video nào trong danh sách. Hãy thêm video mới!</div>';
                } else {
                    var html = '';
                    sfVideoItems.forEach(function(url, idx) {
                        var isCurrent = (idx === sfVideoIdx);
                        var displayTitle = url.length > 50 ? url.substring(0, 50) + '...' : url;
                        if (url.indexOf('/uploads/videos/') !== -1) {
                            var filename = url.split('/').pop();
                            displayTitle = '📹 ' + filename;
                        } else if (url.match(/youtube|youtu\.be/)) {
                            displayTitle = '🔴 YouTube: ' + displayTitle;
                        }

                        html += '<div style="display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:' + (isCurrent ? '#fdf2f8' : '#f8fafc') + '; border:1.5px solid ' + (isCurrent ? '#ec4899' : '#e2e8f0') + '; border-radius:12px; gap:10px;">' +
                            '<div style="flex:1; min-width:0;">' +
                                '<div style="font-size:12px; font-weight:700; color:' + (isCurrent ? '#ec4899' : '#1e293b') + '; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + (idx + 1) + '. ' + displayTitle + '</div>' +
                                (isCurrent ? '<div style="font-size:10px; color:#ec4899; font-weight:800; margin-top:2px;">▶ Đang phát</div>' : '') +
                            '</div>' +
                            '<div style="display:flex; gap:6px; flex-shrink:0;">' +
                                '<button type="button" onclick="sfVideoSelectIndex(' + idx + ')" style="background:' + (isCurrent ? '#ec4899' : '#8b5cf6') + '; color:#fff; border:none; padding:5px 12px; border-radius:8px; font-size:11px; font-weight:700; cursor:pointer;">▶ Xem</button>' +
                                '<button type="button" onclick="sfVideoRemoveIndex(' + idx + ')" style="background:#fff1f2; color:#e11d48; border:1px solid #fecdd3; padding:5px 10px; border-radius:8px; font-size:11px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-trash-can"></i> Xóa</button>' +
                            '</div>' +
                        '</div>';
                    });
                    listEl.innerHTML = html;
                }

                modal.style.display = 'block';
                overlay.style.display = 'block';
            };

            window.closeVideoManagerModal = function() {
                var modal = document.getElementById('videoManagerModal');
                var overlay = document.getElementById('videoManagerOverlay');
                if (modal && overlay) {
                    modal.style.display = 'none';
                    overlay.style.display = 'none';
                }
            };

            window.sfVideoSelectIndex = function(idx) {
                sfVideoIdx = idx;
                sfVideoShow();
                openVideoManagerModal();
                sfShowToast('▶ Đã phát Video ' + (idx + 1));
            };

            window.sfVideoRemoveIndex = function(idx) {
                if (idx >= 0 && idx < sfVideoItems.length) {
                    var svId = <?= json_encode($sv_id) ?>;
                    sfVideoItems.splice(idx, 1);
                    if (sfVideoIdx >= sfVideoItems.length) sfVideoIdx = sfVideoItems.length - 1;
                    if (sfVideoIdx < 0) sfVideoIdx = 0;
                    if (sfVideoItems.length === 0) {
                        clearVideosFromIDB(svId);
                        try {
                            localStorage.removeItem('sf_video_gallery_' + svId);
                            localStorage.removeItem('st_saved_tiktok_video_' + svId);
                        } catch(e){}
                    }
                    sfVideoSave();
                    sfVideoShow();
                    openVideoManagerModal();
                    sfShowToast('🗑️ Đã xóa video khỏi danh sách!');
                }
            };


            // ═══════════ TOAST NOTIFICATION ═══════════
            window.sfShowToast = function(msg) {
                var toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#fff;color:#1e293b;border:1.5px solid #e2e8f0;padding:12px 20px;border-radius:14px;font-family:"Outfit",sans-serif;font-weight:700;font-size:13px;box-shadow:0 8px 32px rgba(0,0,0,0.12);z-index:999999;transition:all 0.35s cubic-bezier(0.34,1.56,0.64,1);opacity:0;transform:translateY(-16px) scale(0.95);display:flex;align-items:center;gap:8px;';
                toast.innerHTML = msg;
                document.body.appendChild(toast);
                requestAnimationFrame(function() {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0) scale(1)';
                });
                setTimeout(function() {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-16px) scale(0.95)';
                    setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
                }, 2500);
            };

            // ═══════════ INIT ON DOM READY ═══════════
            function sfInitAll() {
                sfBannerLoad();
                sfVideoLoad();
                sfBannerResetAuto();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', sfInitAll);
            } else {
                sfInitAll();
            }
        })();
        </script>



        <!-- =========================================================
             MODAL QUẢN LÝ DANH SÁCH VIDEO ĐÃ THÊM
             ========================================================= -->
        <div id="videoManagerOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(6px); z-index: 99998;" onclick="closeVideoManagerModal()"></div>
        <div id="videoManagerModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 520px; background: #ffffff; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); z-index: 99999; padding: 24px; font-family: 'Outfit', 'Inter', sans-serif;">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 16px;">
                <div style="font-weight: 800; font-size: 16px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-film" style="color: #ec4899;"></i> DANH SÁCH VIDEO ĐÃ THÊM
                </div>
                <button type="button" onclick="closeVideoManagerModal()" style="background: none; border: none; font-size: 22px; color: #64748b; cursor: pointer;">&times;</button>
            </div>

            <div id="videoManagerList" style="max-height: 320px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding-right: 4px;">
                <!-- JS renders list here -->
            </div>

            <div style="margin-top: 18px; pt-3; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
                <button type="button" onclick="closeVideoManagerModal()" style="background: #f1f5f9; border: none; color: #475569; padding: 8px 18px; border-radius: 10px; font-weight: 700; font-size: 12px; cursor: pointer;">Đóng</button>
            </div>
        </div>



        <!-- =========================================================
             MODAL CÀI ĐẶT & CĂN CHỈNH BANNER TRANG CHỦ
             ========================================================= -->
        <div id="bannerManagerOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(6px); z-index: 99998;" onclick="closeBannerManagerModal()"></div>

        <div id="bannerManagerModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 680px; background: #ffffff; border-radius: 24px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25); z-index: 99999; overflow: hidden; font-family: sans-serif;">
            <div style="background: linear-gradient(135deg, #0284c7, #0369a1); padding: 18px 24px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                <span style="font-weight: 800; font-size: 15px; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-image" style="font-size: 18px; color: #7dd3fc;"></i> CÀI ĐẶT & CĂN CHỈNH BANNER TRANG CHỦ
                </span>
                <button onclick="closeBannerManagerModal()" style="background: rgba(255,255,255,0.2); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; font-size: 16px; font-weight: bold; cursor: pointer;">&times;</button>
            </div>

            <div style="padding: 24px;">
                <!-- Live Preview Banner Box -->
                <div style="margin-bottom: 18px;">
                    <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 8px; display: block;">
                        <i class="fa-solid fa-eye" style="color: #0284c7;"></i> Xem Trước & Kéo Rê Ảnh Để Căn Vị Trí:
                    </label>
                    <div id="modalBannerPreviewBox" style="width: 100%; height: 220px; border-radius: 16px; overflow: hidden; position: relative; border: 1.5px solid #cbd5e1; background: #0f172a;">
                        <img src="<?= htmlspecialchars($banner_url) ?>" id="modalBannerBlurBg" alt="Blur Bg" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; filter:blur(20px) brightness(0.6); opacity:0.8; transform:scale(1.15); pointer-events:none;">
                        <img src="<?= htmlspecialchars($banner_url) ?>" id="modalBannerImg" alt="Main Preview" style="position: relative; z-index: 2; width:100%; height:100%; object-fit: <?= htmlspecialchars($banner_fit) ?>; object-position: <?= htmlspecialchars($banner_pos) ?>; cursor: grab; user-select: none;">
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 6px; font-style: italic;">
                        💡 Dùng chuột giữ và kéo rê ảnh lên/xuống trực tiếp trên khung để chỉnh góc nhìn đẹp nhất.
                    </div>
                </div>

                <!-- Action Controls Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <!-- Chế độ Khung Ảnh -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px;">
                        <div style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-expand" style="color: #0284c7;"></i> CHẾ ĐỘ HIỂN THỊ ẢNH
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="setModalBannerFit('contain')" id="modalBtnFitContain" style="flex:1; padding: 8px 10px; border-radius: 10px; font-size: 11px; font-weight: 700; cursor: pointer; border: 1.5px solid <?= ($banner_fit === 'contain') ? '#0284c7' : '#cbd5e1' ?>; background: <?= ($banner_fit === 'contain') ? '#e0f2fe' : '#ffffff' ?>; color: <?= ($banner_fit === 'contain') ? '#0369a1' : '#475569' ?>;">
                                🖼️ Vừa Khung (Full)
                            </button>
                            <button type="button" onclick="setModalBannerFit('cover')" id="modalBtnFitCover" style="flex:1; padding: 8px 10px; border-radius: 10px; font-size: 11px; font-weight: 700; cursor: pointer; border: 1.5px solid <?= ($banner_fit === 'cover') ? '#0284c7' : '#cbd5e1' ?>; background: <?= ($banner_fit === 'cover') ? '#e0f2fe' : '#ffffff' ?>; color: <?= ($banner_fit === 'cover') ? '#0369a1' : '#475569' ?>;">
                                🔍 Lấp Đầy (Cover)
                            </button>
                        </div>
                    </div>

                    <!-- Căn Góc Nhanh -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px;">
                        <div style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-arrows-up-down" style="color: #0284c7;"></i> CĂN GÓC CHỤP NÓNG
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" onclick="setModalBannerPos('center top')" style="flex:1; padding: 8px; border-radius: 10px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; cursor: pointer;">⬆️ Trên</button>
                            <button type="button" onclick="setModalBannerPos('center center')" style="flex:1; padding: 8px; border-radius: 10px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; cursor: pointer;">🎯 Giữa</button>
                            <button type="button" onclick="setModalBannerPos('center bottom')" style="flex:1; padding: 8px; border-radius: 10px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; cursor: pointer;">⬇️ Dưới</button>
                        </div>
                    </div>
                </div>

                <!-- Upload New File Form -->
                <form id="modalBannerUploadForm" action="/tkb/student/dashboard.php" method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="upload_banner">
                    <input type="hidden" name="banner_base64" id="modalBannerBase64">
                    <input type="file" id="modalBannerFileInput" name="banner_file" accept="image/*" style="display: none;" onchange="previewModalUploadedBanner(this)">
                    
                    <div style="background: #eff6ff; border: 1.5px dashed #93c5fd; border-radius: 16px; padding: 14px; text-align: center;">
                        <button type="button" onclick="document.getElementById('modalBannerFileInput').click()" style="background: linear-gradient(135deg, #0284c7, #0369a1); border: none; color: #ffffff; padding: 10px 20px; border-radius: 12px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);">
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size: 15px;"></i> Chọn & Tải Ảnh Banner Mới Từ Máy Tính
                        </button>
                        <span id="modalFileNameLabel" style="display: block; font-size: 11px; color: #0284c7; font-weight: 600; margin-top: 6px;"></span>
                    </div>
                </form>

                <!-- Footer Submit Buttons -->
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeBannerManagerModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 10px 20px; border-radius: 12px; font-size: 12px; font-weight: 700; cursor: pointer;">Hủy Bỏ</button>
                    <button type="button" onclick="saveBannerManagerSettings()" style="background: linear-gradient(135deg, #10b981, #059669); border: none; color: #ffffff; padding: 10px 24px; border-radius: 12px; font-size: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-check"></i> Lưu Cài Đặt Banner
                    </button>
                </div>
            </div>
        </div>

        <script>
        function openCoupleEditModal() {
            var modal = document.getElementById('coupleEditModal');
            var overlay = document.getElementById('coupleEditOverlay');
            if (modal && overlay) {
                modal.classList.add('active');
                overlay.classList.add('active');
            }
        }

        function closeCoupleEditModal() {
            var modal = document.getElementById('coupleEditModal');
            var overlay = document.getElementById('coupleEditOverlay');
            if (modal && overlay) {
                modal.classList.remove('active');
                overlay.classList.remove('active');
            }
        }

        function handlePartnerImageUpload(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('inputPartnerAvatar').value = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function calculateCoupleDaysFromDate() {
            var dateInput = document.getElementById('inputCoupleStartDate');
            var daysInput = document.getElementById('inputCoupleDays');
            if (dateInput && dateInput.value && daysInput) {
                var start = new Date(dateInput.value);
                var now = new Date();
                var diffTime = now.getTime() - start.getTime();
                var diffDays = Math.floor(diffTime / (1000 * 3600 * 24));
                if (!isNaN(diffDays) && diffDays >= 0) {
                    daysInput.value = diffDays + ' Ngày';
                }
            }
        }

        function saveCoupleInfo() {
            var pName = document.getElementById('inputPartnerName').value.trim() || 'Chưa thêm tên người yêu';
            var pAv = document.getElementById('inputPartnerAvatar').value.trim();
            var startDate = document.getElementById('inputCoupleStartDate').value;
            var cDays = document.getElementById('inputCoupleDays').value.trim() || '0 Ngày';
            var cStatus = document.getElementById('inputCoupleStatus').value.trim() || 'Hãy cập nhật Góc Khoe Người Yêu!';

            localStorage.setItem('student_couple_name', pName);
            if (pAv) localStorage.setItem('student_couple_av', pAv);
            if (startDate) localStorage.setItem('student_couple_start_date', startDate);
            localStorage.setItem('student_couple_days', cDays);
            localStorage.setItem('student_couple_status', cStatus);

            loadCoupleInfo();
            closeCoupleEditModal();

            var toast = document.createElement('div');
            toast.className = 'mc-theme-toast';
            toast.innerHTML = '<i class="fa-solid fa-heart" style="color: #f43f5e;"></i> 🎉 Đã cập nhật Góc Khoe Người Yêu!';
            document.body.appendChild(toast);
            setTimeout(function() { toast.classList.add('show'); }, 10);
            setTimeout(function() {
                toast.classList.remove('show');
                setTimeout(function() { toast.remove(); }, 300);
            }, 2000);
        }

        function loadCoupleInfo() {
            var pName = localStorage.getItem('student_couple_name') || 'Chưa thêm tên người yêu';
            var pAv = localStorage.getItem('student_couple_av');
            var startDate = localStorage.getItem('student_couple_start_date');
            var cDays = localStorage.getItem('student_couple_days') || '0 Ngày';
            var cStatus = localStorage.getItem('student_couple_status') || '"Hãy cập nhật Góc Khoe Người Yêu!"';

            var elName = document.getElementById('couplePartnerName');
            var elAv = document.getElementById('couplePartnerAvatar');
            var elDays = document.getElementById('coupleDays');
            var elStatus = document.getElementById('coupleStatus');

            if (elName) elName.innerText = pName;
            if (elAv && pAv) {
                elAv.src = pAv;
                elAv.style.filter = 'none';
            }

            // Tự động tính số ngày từ Ngày bắt đầu yêu
            if (startDate) {
                var start = new Date(startDate);
                var now = new Date();
                var diffTime = now.getTime() - start.getTime();
                var diffDays = Math.floor(diffTime / (1000 * 3600 * 24));
                if (!isNaN(diffDays) && diffDays >= 0) {
                    cDays = diffDays + ' Ngày';
                }
            }

            if (elDays) {
                var displayDays = cDays.startsWith('💖') ? cDays.replace('💖', '').trim() : cDays;
                elDays.innerHTML = '💖 ' + displayDays;
                elDays.title = startDate ? 'Bắt đầu yêu từ: ' + startDate : 'Số ngày kỷ niệm';
            }
            if (elStatus) elStatus.innerText = cStatus.startsWith('"') ? cStatus : '"' + cStatus + '"';

            var inputN = document.getElementById('inputPartnerName');
            var inputA = document.getElementById('inputPartnerAvatar');
            var inputDate = document.getElementById('inputCoupleStartDate');
            var inputD = document.getElementById('inputCoupleDays');
            var inputS = document.getElementById('inputCoupleStatus');

            if (inputN) inputN.value = pName;
            if (inputA && pAv) inputA.value = pAv;
            if (inputDate && startDate) inputDate.value = startDate;
            if (inputD) inputD.value = cDays;
            if (inputS) inputS.value = cStatus.replace(/^"|"$/g, '');
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadCoupleInfo();
            setInterval(loadCoupleInfo, 60000);
        });
        </script>
        </div>

        <?php if ($is_female): ?>
        <!-- Footer Soft UI — Sinh Viên Nữ -->
        <footer class="sf-footer">
            <div class="sf-footer-emoji">
                <span>🌸</span>
                <span>✨</span>
                <span>📚</span>
                <span>💖</span>
                <span>🎓</span>
            </div>
            <div>© 2026 — Cổng thông tin sinh viên | Hệ thống quản lý đào tạo</div>
            <div class="sf-footer-text">Học tập, sáng tạo & tỏa sáng mỗi ngày ✨</div>
        </footer>
        <?php endif; ?>

        <?php if (!$is_female): ?>
        <!-- Footer Nether / Minecraft Strip (Image 1 Bottom) -->
        <footer class="mc-footer">
            <div class="mc-footer-strip">
                <span style="color: var(--mc-red);">🔥</span>
                <span style="color: var(--mc-gold);">📦</span>
                <span style="color: var(--mc-emerald);">💎</span>
                <span style="color: var(--mc-diamond);">🛠️</span>
                <span style="color: var(--mc-red);">🔥</span>
            </div>
            <div>© 2026 - Cổng thông tin sinh viên | Hệ thống quản lý đào tạo</div>
            <div class="mc-footer-text">Học tập như chơi game - Chinh phục mọi thử thách!</div>
        </footer>
        <?php endif; ?>
    </div>
</div>

<!-- Floating AI Chatbot Widget -->
<style>
.cfab{position:fixed;bottom:24px;right:24px;width:58px;height:58px;border-radius:50%;background:var(--mc-red);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:9999;box-shadow:0 0 20px var(--mc-red-glow);border:2px solid #ffffff;transition:transform 0.25s, box-shadow 0.25s}
.cfab:hover{transform:scale(1.1) rotate(5deg);box-shadow:0 0 30px var(--mc-red)}
.cfab svg{width:26px;height:26px;fill:#fff}
.cbox{position:fixed;bottom:94px;right:24px;width:365px;background:var(--mc-card-bg);backdrop-filter:blur(30px);border:2px solid var(--mc-red);border-radius:20px;display:none;flex-direction:column;z-index:9998;box-shadow:0 16px 48px rgba(0,0,0,0.8);overflow:hidden;max-height:540px;animation:cslide .25s cubic-bezier(0.34, 1.56, 0.64, 1)}
@keyframes cslide{from{opacity:0;transform:translateY(18px) scale(.97)}to{opacity:1;transform:none}}
.cbox.open{display:flex}
.chdr{display:flex;align-items:center;gap:10px;padding:16px 20px;background:linear-gradient(90deg, var(--mc-red-dark), var(--mc-red));border-bottom:2px solid var(--mc-border)}
.chdr-ic{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center}
.chdr-ic svg{width:17px;height:17px;fill:#fff}
.chdr-info{flex:1}
.chdr-info p{font-size:13px;font-weight:700;color:#fff;margin:0;font-family:var(--mc-pixel-font);font-size:10px}
.chdr-info span{font-size:11px;color:rgba(255,255,255,0.8)}
.cclose{background:none;border:none;color:rgba(255,255,255,0.8);cursor:pointer;font-size:18px;padding:0 4px}
.cclose:hover{color:#fff}
.cmsgs{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;min-height:240px;max-height:350px;background:#0d0912}
.cmsg{display:flex;flex-direction:column;max-width:84%}
.cmsg.user{align-self:flex-end;align-items:flex-end}
.cmsg.bot{align-self:flex-start;align-items:flex-start}
.cbubble{padding:10px 14px;border-radius:14px;font-size:13px;line-height:1.55;word-break:break-word}
.cmsg.user .cbubble{background:var(--mc-red);color:#fff;border-bottom-right-radius:4px}
.cmsg.bot .cbubble{background:var(--mc-card-inner);color:#f8fafc;border:1px solid var(--mc-border);border-bottom-left-radius:4px}
.cinrow{display:flex;gap:8px;padding:12px 16px;border-top:2px solid var(--mc-border);background:#14101a}
.cinput{flex:1;background:#0d0912;border:1.5px solid var(--mc-border);border-radius:10px;padding:10px 14px;color:#fff;font-size:13px;outline:none;resize:none}
.csend{width:40px;height:40px;border-radius:10px;background:var(--mc-red);border:none;display:flex;align-items:center;justify-content:center;cursor:pointer}
.csend svg{width:16px;height:16px;fill:#fff}
</style>

<button class="cfab" onclick="cToggle()">
  <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
</button>

<div class="cbox" id="cbox">
  <div class="chdr">
    <div class="chdr-ic"><svg viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73C8.4 5.39 8 4.74 8 4a2 2 0 0 1 2-2h2z"/></svg></div>
    <div class="chdr-info">
      <p>TRỢ LÝ AI MINECRAFT</p>
      <span>Luôn sẵn sàng hỗ trợ bạn 24/7</span>
    </div>
    <button class="cclose" onclick="cToggle()">×</button>
  </div>
  <div class="cmsgs" id="cmsgs">
    <div class="cmsg bot">
      <div class="cbubble">Xin chào <?= htmlspecialchars($sv['ho_ten']) ?>! Mình là Trợ lý AI Minecraft. Bạn cần mình giúp gì hôm nay? ⚔️</div>
    </div>
  </div>
  <div class="cinrow">
    <textarea class="cinput" id="cinput" rows="1" placeholder="Nhập tin nhắn..." onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();cSend();}"></textarea>
    <button class="csend" id="csend" onclick="cSend()"><svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg></button>
  </div>
</div>

<script>
function cToggle() {
    document.getElementById('cbox').classList.toggle('open');
}
async function cSend() {
    var input = document.getElementById('cinput');
    var txt = input.value.trim();
    if (!txt) return;
    input.value = '';
    var msgs = document.getElementById('cmsgs');
    msgs.innerHTML += '<div class="cmsg user"><div class="cbubble">' + escapeHtml(txt) + '</div></div>';
    msgs.scrollTop = msgs.scrollHeight;
    
    var botMsg = document.createElement('div');
    botMsg.className = 'cmsg bot';
    botMsg.innerHTML = '<div class="cbubble"><i>Đang suy nghĩ...</i></div>';
    msgs.appendChild(botMsg);
    msgs.scrollTop = msgs.scrollHeight;

    try {
        var res = await fetch('/tkb/api/login.php?groq', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({messages: [{role: 'user', content: txt}]})
        });
        var data = await res.json();
        var reply = data.choices && data.choices[0] ? data.choices[0].message.content : 'Xin lỗi, không nhận được phản hồi.';
        botMsg.querySelector('.cbubble').innerHTML = reply;
    } catch(e) {
        botMsg.querySelector('.cbubble').innerHTML = 'Lỗi kết nối AI!';
    }
    msgs.scrollTop = msgs.scrollHeight;
}
function escapeHtml(t) { return t.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"); }

function submitQuickBanner() {
    var input = document.getElementById('quickBannerInput');
    if (!input.files || !input.files[0]) return;
    
    var file = input.files[0];
    var btn = document.querySelector('.mc-btn-change-banner');
    if (btn) btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang nén & tải...';

    var reader = new FileReader();
    reader.onload = function(e) {
        var img = new Image();
        img.onerror = function() {
            document.getElementById('quickBannerForm').submit();
        };
        img.onload = function() {
            var canvas = document.createElement('canvas');
            var maxW = 1920;
            var width = img.width;
            var height = img.height;

            if (width > maxW) {
                height = Math.round(height * (maxW / width));
                width = maxW;
            }

            canvas.width = width;
            canvas.height = height;
            var ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, 0, 0, width, height);

            var base64 = canvas.toDataURL('image/jpeg', 0.92);
            try {
                localStorage.setItem('student_banner_cache', base64);
                var fullImg = document.getElementById('mcBannerFullImg');
                var blurBg = document.getElementById('mcBannerBlurBg');
                if (fullImg) fullImg.src = base64;
                if (blurBg) blurBg.src = base64;
            } catch(err) {}
            var base64El = document.getElementById('quickBannerBase64');
            if (base64El) base64El.value = base64;
            document.getElementById('quickBannerForm').submit();
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

document.addEventListener('DOMContentLoaded', function() {
    var cached = localStorage.getItem('student_banner_cache');
    var fullImg = document.getElementById('mcBannerFullImg');
    var blurBg = document.getElementById('mcBannerBlurBg');
    if (cached && fullImg) {
        fullImg.src = cached;
        if (blurBg) blurBg.src = cached;
    }
});

if (window.location.search.indexOf('upload=success') !== -1) {
    if (history.replaceState) {
        history.replaceState(null, null, window.location.pathname);
    }
    var toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#1e293b;color:#4ade80;border:2px solid #22c55e;padding:14px 24px;border-radius:8px;font-family:sans-serif;font-weight:bold;font-size:15px;box-shadow:0 10px 25px rgba(0,0,0,0.5);z-index:99999;transition:all 0.4s ease;opacity:0;transform:translateY(-20px);';
    toast.innerHTML = '<i class="fa-solid fa-circle-check"></i> 🎉 Cập nhật Banner trang chủ mới thành công!';
    document.body.appendChild(toast);
    setTimeout(function() { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; }, 50);
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 400);
function updateLhLiveClock() {
    const now = new Date();
    const hrs = String(now.getHours()).padStart(2, '0');
    const mins = String(now.getMinutes()).padStart(2, '0');
    const secs = String(now.getSeconds()).padStart(2, '0');
    
    const clockEl = document.getElementById('ltLiveClock');
    if (clockEl) {
        clockEl.innerText = `${hrs}:${mins}:${secs}`;
    }

    const days = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    const dayName = days[now.getDay()];
    const dateStr = String(now.getDate()).padStart(2, '0');
    const monthStr = String(now.getMonth() + 1).padStart(2, '0');

    const dateEl = document.getElementById('ltLiveDate');
    if (dateEl) {
        dateEl.innerText = `${dayName}, ${dateStr}/${monthStr}`;
    }
}
setInterval(updateLhLiveClock, 1000);
updateLhLiveClock();
</script>
<!-- Modal Cấu Hình Link Mạng Xã Hội -->
<div id="socialLinksModal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,23,42,0.65); backdrop-filter: blur(6px); z-index: 999999; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: #ffffff; width: 100%; max-width: 420px; border-radius: 24px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); position: relative; border: 1.5px solid #f3e8ff;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px dashed #f3e8ff; padding-bottom: 12px; margin-bottom: 16px;">
            <div style="font-size: 16px; font-weight: 900; color: #4c1d95; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-share-nodes" style="color: #8b5cf6;"></i> Cài Đặt Link Mạng Xã Hội
            </div>
            <button type="button" onclick="closeSocialLinksModal()" style="background: #f1f5f9; border: none; width: 28px; height: 28px; border-radius: 50%; color: #64748b; font-weight: bold; cursor: pointer;">&times;</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
            <div>
                <label style="font-size: 12px; font-weight: 800; color: #1877f2; display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <span><i class="fa-brands fa-facebook" style="font-size: 16px;"></i> Link Facebook:</span>
                    <a id="btnGoFb" href="#" target="_blank" style="font-size: 10.5px; color: #1877f2; font-weight: 800; text-decoration: none; display: none;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Mở trang FB</a>
                </label>
                <input type="text" id="inputSocialFb" placeholder="Dán link https://facebook.com/..." style="width: 100%; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 8px 12px; font-size: 11.5px; outline: none; font-family: sans-serif; box-sizing: border-box;">
            </div>

            <div>
                <label style="font-size: 12px; font-weight: 800; color: #0f172a; display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <span><i class="fa-brands fa-tiktok" style="font-size: 16px;"></i> Link TikTok:</span>
                    <a id="btnGoTiktok" href="#" target="_blank" style="font-size: 10.5px; color: #0f172a; font-weight: 800; text-decoration: none; display: none;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Mở TikTok</a>
                </label>
                <input type="text" id="inputSocialTiktok" placeholder="Dán link https://tiktok.com/@..." style="width: 100%; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 8px 12px; font-size: 11.5px; outline: none; font-family: sans-serif; box-sizing: border-box;">
            </div>

            <div>
                <label style="font-size: 12px; font-weight: 800; color: #e4405f; display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <span><i class="fa-brands fa-instagram" style="font-size: 16px;"></i> Link Instagram:</span>
                    <a id="btnGoIg" href="#" target="_blank" style="font-size: 10.5px; color: #e4405f; font-weight: 800; text-decoration: none; display: none;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Mở Insta</a>
                </label>
                <input type="text" id="inputSocialIg" placeholder="Dán link https://instagram.com/..." style="width: 100%; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 8px 12px; font-size: 11.5px; outline: none; font-family: sans-serif; box-sizing: border-box;">
            </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="button" onclick="closeSocialLinksModal()" style="flex: 1; background: #f1f5f9; border: none; color: #64748b; padding: 10px; border-radius: 12px; font-size: 12px; font-weight: 800; cursor: pointer;">
                Hủy
            </button>
            <button type="button" onclick="saveSocialLinksFromModal()" style="flex: 2; background: linear-gradient(135deg, #8b5cf6, #ec4899); border: none; color: #fff; padding: 10px; border-radius: 12px; font-size: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(139,92,246,0.3);">
                <i class="fa-solid fa-floppy-disk"></i> Lưu Liên Kết Ngay
            </button>
        </div>
    </div>
</div>

<!-- Modal Chi tiết thông báo cho sinh viên -->
<div id="annModalStudent" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #fff; border-radius: 16px; width: 90%; max-width: 550px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); position: relative; animation: modalFadeIn 0.2s ease;">
        <button onclick="document.getElementById('annModalStudent').style.display='none'" style="position: absolute; top: 16px; right: 18px; background: none; border: none; font-size: 22px; color: #64748b; cursor: pointer;">&times;</button>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
            <span id="stModalAnnBadge" style="background: #ffe4e6; color: #e11d48; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 8px;">Thông báo</span>
            <span id="stModalAnnDate" style="font-size: 11.5px; color: #94a3b8; font-weight: 600;"></span>
        </div>
        <h3 id="stModalAnnTitle" style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 12px 0; line-height: 1.4;"></h3>
        <div id="stModalAnnContent" style="font-size: 13px; color: #334155; line-height: 1.6; max-height: 300px; overflow-y: auto; background: #f8fafc; padding: 14px; border-radius: 10px; border: 1px solid #e2e8f0; white-space: pre-line;"></div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px;">
            <span id="stModalAnnAuthor" style="font-size: 11px; color: #64748b; font-style: italic;"></span>
            <button onclick="document.getElementById('annModalStudent').style.display='none'" style="background: linear-gradient(90deg, #7c3aed, #a855f7); color: #fff; border: none; padding: 8px 20px; border-radius: 8px; font-weight: 700; cursor: pointer;">Đóng</button>
        </div>
    </div>
</div>

<script>
function setStudentSchedMode(mode) {
    const isThi = (mode === 'thi');
    
    // Female theme elements
    const titleF = document.getElementById('stFemaleSchedTitle');
    const hocF = document.getElementById('stSchedHocContentFemale');
    const thiF = document.getElementById('stSchedThiContentFemale');
    const btnHocF = document.getElementById('btnTabHocFemale');
    const btnThiF = document.getElementById('btnTabThiFemale');

    if (titleF) {
        titleF.innerHTML = isThi ? '<i class="fa-solid fa-file-pen" style="color: #ef4444;"></i> LỊCH THI HÔM NAY' : '<i class="fa-solid fa-calendar-week"></i> LỊCH HỌC HÔM NAY';
    }
    if (hocF) hocF.style.display = isThi ? 'none' : 'flex';
    if (thiF) thiF.style.display = isThi ? 'flex' : 'none';
    if (btnHocF) {
        btnHocF.style.background = isThi ? '#fff' : '#8b5cf6';
        btnHocF.style.color = isThi ? '#8b5cf6' : '#fff';
    }
    if (btnThiF) {
        btnThiF.style.background = isThi ? '#ef4444' : '#fff';
        btnThiF.style.color = isThi ? '#fff' : '#ef4444';
    }

    // Male theme elements
    const titleM = document.getElementById('stMaleSchedTitle');
    const hocM = document.getElementById('stSchedHocContentMale');
    const thiM = document.getElementById('stSchedThiContentMale');
    const btnHocM = document.getElementById('btnTabHocMale');
    const btnThiM = document.getElementById('btnTabThiMale');

    if (titleM) {
        titleM.innerHTML = isThi ? '<i class="fa-solid fa-file-pen" style="color: #ef4444;"></i> LỊCH THI HÔM NAY' : '<i class="fa-solid fa-calendar-week"></i> LỊCH HỌC HÔM NAY';
    }
    if (hocM) hocM.style.display = isThi ? 'none' : 'flex';
    if (thiM) thiM.style.display = isThi ? 'flex' : 'none';
    if (btnHocM) {
        btnHocM.style.background = isThi ? 'transparent' : '#7c3aed';
        btnHocM.style.color = isThi ? '#7c3aed' : '#fff';
    }
    if (btnThiM) {
        btnThiM.style.background = isThi ? '#ef4444' : 'transparent';
        btnThiM.style.color = isThi ? '#fff' : '#ef4444';
    }
}

function openAnnModal(tb) {
    if (!tb) return;
    document.getElementById('stModalAnnTitle').innerText = tb.tieu_de || '';
    document.getElementById('stModalAnnContent').innerText = tb.noi_dung || '';
    document.getElementById('stModalAnnDate').innerText = tb.ngay_dang ? ('Ngày đăng: ' + tb.ngay_dang) : '';
    document.getElementById('stModalAnnBadge').innerText = tb.loai || 'Thông báo';
    document.getElementById('stModalAnnAuthor').innerText = tb.tac_gia ? ('Đăng bởi: ' + tb.tac_gia) : '';

    if (tb.loai === 'Lịch thi' || (tb.tieu_de && tb.tieu_de.includes('Lịch thi'))) {
        setStudentSchedMode('thi');
    }

    document.getElementById('annModalStudent').style.display = 'flex';
}
</script>
</body>
</html>