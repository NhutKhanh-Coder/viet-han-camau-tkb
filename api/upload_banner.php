<?php
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'] ?? 0;
$student_id = $_SESSION['student_id'] ?? 0;

// Ensure students table has banner column safely
$db->query("SHOW COLUMNS FROM students LIKE 'banner'");
if ($db->affected_rows == 0) {
    @$db->query("ALTER TABLE students ADD COLUMN banner LONGTEXT NULL");
}

$file = $_FILES['banner_file'] ?? $_FILES['banner'] ?? $_FILES['file'] ?? null;

if ($file && isset($file['tmp_name']) && !empty($file['tmp_name'])) {
    if ($file['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!$ext || !in_array($ext, $allowed)) {
            $ext = 'jpg';
        }
        
        $newFilename = "banner_" . ($student_id ?: $user_id) . "_" . time() . "." . $ext;
        $destDir = __DIR__ . "/../assets/img/banners/";
        if (!is_dir($destDir)) { 
            @mkdir($destDir, 0777, true); 
        }
        
        $savedPath = "";
        $bannerUrl = "";
        if (@move_uploaded_file($file['tmp_name'], $destDir . $newFilename)) {
            $savedPath = $newFilename;
            $bannerUrl = '/tkb/assets/img/banners/' . $newFilename;
        } else {
            // Fallback to assets/img/
            $fallbackDir = __DIR__ . "/../assets/img/";
            if (@move_uploaded_file($file['tmp_name'], $fallbackDir . $newFilename)) {
                $savedPath = $newFilename;
                $bannerUrl = '/tkb/assets/img/' . $newFilename;
            }
        }

        if (!empty($savedPath)) {
            $st_id = $student_id ?: $user_id;
            $existing_banners = [];
            $res = @$db->query("SELECT banner FROM students WHERE id=$st_id OR user_id=$st_id LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $raw = $row['banner'] ?? '';
                if (strpos($raw, '[') === 0) {
                    $decoded = @json_decode($raw, true);
                    if (is_array($decoded)) $existing_banners = array_values(array_filter($decoded));
                } else if (!empty($raw)) {
                    $existing_banners = [$raw];
                }
            }
            if (!in_array($bannerUrl, $existing_banners)) {
                $existing_banners[] = $bannerUrl;
            }
            $json_str = json_encode(array_values($existing_banners), JSON_UNESCAPED_SLASHES);
            $escaped = $db->real_escape_string($json_str);

            if ($st_id > 0) {
                @$db->query("UPDATE sinh_vien SET banner='$escaped' WHERE id=$st_id OR user_id=$st_id");
                @$db->query("UPDATE students SET banner='$escaped' WHERE id=$st_id OR user_id=$st_id");
            } else if ($user_id > 0) {
                @$db->query("UPDATE sinh_vien SET banner='$escaped' WHERE user_id=$user_id");
                @$db->query("UPDATE students SET banner='$escaped' WHERE user_id=$user_id");
            }
            $_SESSION['banner'] = $json_str;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Tải lên banner thành công!', 
                'banner_url' => $bannerUrl
            ]);
            $db->close();
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể lưu tệp tin banner. Vui lòng thử chọn tệp ảnh khác.']);
            $db->close();
            exit;
        }
    } else {
        $errCode = $file['error'];
        $msg = 'Lỗi khi tải tệp tin lên server (Mã lỗi: ' . $errCode . ')';
        if ($errCode === 1 || $errCode === 2) $msg = 'Dung lượng ảnh quá lớn! Vui lòng chọn tệp ảnh dưới 5MB.';
        echo json_encode(['success' => false, 'message' => $msg]);
        $db->close();
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Vui lòng chọn tệp ảnh hợp lệ.']);
$db->close();
exit;
