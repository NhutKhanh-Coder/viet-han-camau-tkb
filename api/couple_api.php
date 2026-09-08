<?php
/**
 * Couple API - Góc Khoe Người Yêu 💜
 * Handles: get, save, upload_photo, upload_video, upload_avatar, delete_photo, delete_video
 * Storage: assets/uploads/ (Guaranteed writable) + MySQL system_settings + assets/uploads/couple_data.json
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user has admin rights
if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Chỉ quản trị viên mới có quyền truy cập!']);
    exit;
}

$db = getDB();

// Ensure uploads directory exists and is writable
$upload_dir = __DIR__ . '/../assets/uploads/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}
$json_file = $upload_dir . 'couple_data.json';

// Ensure system_settings table column is LONGTEXT
@$db->query("CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` LONGTEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@$db->query("ALTER TABLE `system_settings` MODIFY COLUMN `setting_value` LONGTEXT DEFAULT NULL");

$defaults = [
    'name' => 'Nguyễn Phương Anh',
    'badge' => 'My Everything',
    'message' => 'Cảm ơn em vì đã luôn ở đây, là động lực và ánh sáng trong cuộc sống của anh. 🌙',
    'date' => '17/02/2026',
    'date_label' => 'Ngày chúng ta bắt đầu ❤️',
    'quote' => 'Mỗi ngày bên em là một ngày tuyệt vời nhất.',
    'avatar' => '/tkb/assets/img/phuong_anh_avatar.png',
    'photos' => [
        '/tkb/assets/img/phuong_anh_1.png',
        '/tkb/assets/img/phuong_anh_2.png',
        '/tkb/assets/img/phuong_anh_3.png',
        '/tkb/assets/img/phuong_anh_4.png'
    ],
    'videos' => [
        ['thumb' => '/tkb/assets/img/anime_video_thumb.png', 'url' => '', 'title' => 'Video Kỷ Niệm']
    ]
];

function fetchCurrentCoupleData($db, $json_file, $defaults) {
    $data = null;
    // 1. From database
    $json = getSystemSetting('couple_data', '', $db);
    if ($json) {
        $parsed = @json_decode($json, true);
        if (is_array($parsed)) {
            $data = $parsed;
        }
    }
    // 2. From file
    if (!$data && file_exists($json_file)) {
        $content = @file_get_contents($json_file);
        if ($content) {
            $parsed = @json_decode($content, true);
            if (is_array($parsed)) {
                $data = $parsed;
            }
        }
    }
    // 3. Defaults
    if (!$data) {
        return $defaults;
    }
    foreach ($defaults as $k => $v) {
        if (!isset($data[$k])) {
            $data[$k] = $v;
        }
    }
    return $data;
}

function persistCoupleData($db, $json_file, $data) {
    $json_str = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    setSystemSetting('couple_data', $json_str, $db);
    @file_put_contents($json_file, $json_str);
    return true;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ===================== GET =====================
    case 'get':
        $data = fetchCurrentCoupleData($db, $json_file, $defaults);
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        break;

    // ===================== SAVE =====================
    case 'save':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !is_array($input)) {
            echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
            break;
        }

        $allowed_keys = ['name', 'badge', 'message', 'date', 'date_label', 'quote', 'avatar', 'photos', 'videos'];
        $current = fetchCurrentCoupleData($db, $json_file, $defaults);
        foreach ($allowed_keys as $key) {
            if (isset($input[$key])) {
                $current[$key] = $input[$key];
            }
        }

        persistCoupleData($db, $json_file, $current);
        writeSystemLog('Cập nhật Góc Khoe Người Yêu');
        echo json_encode(['success' => true, 'data' => $current, 'message' => 'Đã lưu thành công! 💜']);
        break;

    // ===================== UPLOAD AVATAR =====================
    case 'upload_avatar':
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $err = $_FILES['file']['error'] ?? 'No file';
            echo json_encode(['success' => false, 'error' => "Lỗi tải file avatar (Mã: $err)"]);
            break;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Chỉ chấp nhận ảnh JPG, PNG, GIF, WEBP']);
            break;
        }

        $filename = 'couple_avatar_' . time() . '.' . $ext;
        $target = $upload_dir . $filename;

        if (@move_uploaded_file($file['tmp_name'], $target)) {
            $url = '/tkb/assets/uploads/' . $filename;
            $current = fetchCurrentCoupleData($db, $json_file, $defaults);
            $current['avatar'] = $url;
            persistCoupleData($db, $json_file, $current);
            echo json_encode(['success' => true, 'url' => $url, 'data' => $current]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không thể lưu file vào thư mục']);
        }
        break;

    // ===================== UPLOAD PHOTO =====================
    case 'upload_photo':
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $err = $_FILES['file']['error'] ?? 'No file';
            echo json_encode(['success' => false, 'error' => "Lỗi tải ảnh (Mã: $err)"]);
            break;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Chỉ chấp nhận ảnh JPG, PNG, GIF, WEBP']);
            break;
        }

        $filename = 'couple_photo_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
        $target = $upload_dir . $filename;

        if (@move_uploaded_file($file['tmp_name'], $target)) {
            $url = '/tkb/assets/uploads/' . $filename;
            $current = fetchCurrentCoupleData($db, $json_file, $defaults);
            if (!isset($current['photos']) || !is_array($current['photos'])) {
                $current['photos'] = [];
            }
            $current['photos'][] = $url;
            persistCoupleData($db, $json_file, $current);
            echo json_encode(['success' => true, 'url' => $url, 'data' => $current]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không thể lưu file ảnh vào thư mục']);
        }
        break;

    // ===================== UPLOAD VIDEO =====================
    case 'upload_video':
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $err = $_FILES['file']['error'] ?? 'No file';
            $msg = "Lỗi tải video lên máy chủ (Mã: $err)";
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                $msg = 'Video vượt quá giới hạn tải lên của máy chủ (vui lòng chọn video < 10MB)';
            }
            echo json_encode(['success' => false, 'error' => $msg]);
            break;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['mp4', 'webm', 'ogg', 'mov'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Chỉ chấp nhận video MP4, WebM, OGG, MOV']);
            break;
        }

        $filename = 'couple_video_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
        $target = $upload_dir . $filename;

        if (@move_uploaded_file($file['tmp_name'], $target)) {
            $url = '/tkb/assets/uploads/' . $filename;
            $current = fetchCurrentCoupleData($db, $json_file, $defaults);
            
            // If the current list only has the initial placeholder video (url is empty), replace it
            if (isset($current['videos']) && count($current['videos']) === 1 && empty($current['videos'][0]['url'])) {
                $current['videos'] = [];
            }
            if (!isset($current['videos']) || !is_array($current['videos'])) {
                $current['videos'] = [];
            }

            $current['videos'][] = [
                'url' => $url,
                'thumb' => '',
                'title' => $file['name']
            ];

            persistCoupleData($db, $json_file, $current);
            echo json_encode(['success' => true, 'url' => $url, 'data' => $current]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không thể lưu video vào thư mục uploads']);
        }
        break;

    // ===================== DELETE PHOTO =====================
    case 'delete_photo':
        $idx = isset($_GET['index']) ? (int)$_GET['index'] : (isset($_POST['index']) ? (int)$_POST['index'] : -1);
        $current = fetchCurrentCoupleData($db, $json_file, $defaults);
        if ($idx >= 0 && isset($current['photos'][$idx])) {
            array_splice($current['photos'], $idx, 1);
            persistCoupleData($db, $json_file, $current);
        }
        echo json_encode(['success' => true, 'data' => $current]);
        break;

    // ===================== DELETE VIDEO =====================
    case 'delete_video':
        $idx = isset($_GET['index']) ? (int)$_GET['index'] : (isset($_POST['index']) ? (int)$_POST['index'] : -1);
        $current = fetchCurrentCoupleData($db, $json_file, $defaults);
        if ($idx >= 0 && isset($current['videos'][$idx])) {
            array_splice($current['videos'], $idx, 1);
            persistCoupleData($db, $json_file, $current);
        }
        echo json_encode(['success' => true, 'data' => $current]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
}

$db->close();
?>
