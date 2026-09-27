<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$user_id = (int)($_SESSION['user_id'] ?? 1);
$msg = '';

// Tự động kiểm tra và thêm các cột an toàn cho bảng users nếu chưa có
try {
    $cols = [
        'avatar' => "ALTER TABLE users ADD COLUMN `avatar` VARCHAR(255) DEFAULT NULL",
        'sdt' => "ALTER TABLE users ADD COLUMN `sdt` VARCHAR(50) DEFAULT NULL",
        'gioi_tinh' => "ALTER TABLE users ADD COLUMN `gioi_tinh` VARCHAR(20) DEFAULT 'Nam'",
        'bio' => "ALTER TABLE users ADD COLUMN `bio` TEXT DEFAULT NULL",
        'face_descriptor' => "ALTER TABLE users ADD COLUMN `face_descriptor` LONGTEXT DEFAULT NULL",
        'email' => "ALTER TABLE users ADD COLUMN `email` VARCHAR(150) DEFAULT NULL",
        'ho_ten' => "ALTER TABLE users ADD COLUMN `ho_ten` VARCHAR(150) DEFAULT 'Quản Trị Viên Hệ Thống'",
        'created_at' => "ALTER TABLE users ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];
    foreach ($cols as $colName => $alterSql) {
        $chk = @$db->query("SHOW COLUMNS FROM users LIKE '$colName'");
        if ($chk && $chk->num_rows === 0) {
            @$db->query($alterSql);
        }
    }
} catch (Throwable $e) {}

// Xử lý các Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];

    // 1. Cập nhật thông tin cơ bản & liên hệ
    if ($act === 'update_profile') {
        $ho_ten    = trim($_POST['ho_ten'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $sdt       = trim($_POST['sdt'] ?? '');
        $gioi_tinh = trim($_POST['gioi_tinh'] ?? 'Nam');
        $bio       = trim($_POST['bio'] ?? '');
        $username  = trim($_POST['username'] ?? '');

        if (empty($ho_ten)) {
            $msg = 'error:Họ và tên hiển thị không được để trống!';
        } elseif (empty($username)) {
            $msg = 'error:Tên đăng nhập không được để trống!';
        } elseif (strtolower($username) === 'admin' && !isSuperAdmin()) {
            $msg = 'error:Tên đăng nhập "admin" được bảo vệ vĩnh viễn cho Super Admin Lê Nhựt Khánh!';
        } else {
            // Kiểm tra username trùng lặp với người dùng khác
            $chkUser = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $chkUser->bind_param("si", $username, $user_id);
            $chkUser->execute();
            if ($chkUser->get_result()->num_rows > 0) {
                $msg = 'error:Tên đăng nhập này đã được sử dụng bởi tài khoản khác!';
            } else {
                $stmt = $db->prepare("UPDATE users SET ho_ten = ?, email = ?, sdt = ?, gioi_tinh = ?, bio = ?, username = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $ho_ten, $email, $sdt, $gioi_tinh, $bio, $username, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['ho_ten'] = $ho_ten;
                    $_SESSION['username'] = $username;
                    writeSystemLog("Cập nhật thông tin hồ sơ Quản trị viên");
                    $msg = 'success:Cập nhật thông tin hồ sơ quản trị viên thành công!';
                } else {
                    $msg = 'error:Lỗi hệ thống khi cập nhật thông tin: ' . $db->error;
                }
            }
        }
    }

    // 2. Tải lên Avatar mới
    if ($act === 'upload_avatar') {
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newFilename = "admin_" . $user_id . "_" . time() . "." . $ext;
                $destDir = __DIR__ . "/../assets/img/avatars/";
                if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $destDir . $newFilename)) {
                    $db->query("UPDATE users SET avatar = '$newFilename' WHERE id = $user_id");
                    writeSystemLog("Cập nhật ảnh đại diện Quản trị viên");
                    $msg = 'success:Tải lên ảnh đại diện mới thành công!';
                } else {
                    $msg = 'error:Không thể lưu tệp tin ảnh lên máy chủ.';
                }
            } else {
                $msg = 'error:Định dạng ảnh không hợp lệ (Chỉ chấp nhận JPG, PNG, GIF, WebP).';
            }
        } else {
            $msg = 'error:Vui lòng chọn một tệp ảnh hợp lệ để tải lên.';
        }
    }

    // 3. Chọn Avatar có sẵn (Preset)
    if ($act === 'select_preset_avatar') {
        $preset_url = trim($_POST['preset_avatar'] ?? '');
        if ($preset_url) {
            $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->bind_param("si", $preset_url, $user_id);
            if ($stmt->execute()) {
                writeSystemLog("Chọn ảnh đại diện mẫu cho Quản trị viên");
                $msg = 'success:Cập nhật ảnh đại diện mẫu thành công!';
            }
        }
    }

    // 4. Đổi mật khẩu bảo mật
    if ($act === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $msg = 'error:Vui lòng điền đầy đủ tất cả các trường mật khẩu!';
        } elseif (strlen($new_pass) < 6) {
            $msg = 'error:Mật khẩu mới phải có độ dài tối thiểu từ 6 ký tự trở lên!';
        } elseif ($new_pass !== $confirm_pass) {
            $msg = 'error:Mật khẩu nhập lại không khớp với mật khẩu mới!';
        } else {
            // Lấy mật khẩu hiện tại trong DB
            $chkPass = $db->prepare("SELECT password FROM users WHERE id = ?");
            $chkPass->bind_param("i", $user_id);
            $chkPass->execute();
            $currRow = $chkPass->get_result()->fetch_assoc();
            $dbHash = $currRow['password'] ?? '';

            if (password_verify($current_pass, $dbHash) || $dbHash === $current_pass || $dbHash === md5($current_pass)) {
                $newHash = password_hash($new_pass, PASSWORD_DEFAULT);
                $upPass = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upPass->bind_param("si", $newHash, $user_id);
                if ($upPass->execute()) {
                    writeSystemLog("Đổi mật khẩu tài khoản Quản trị viên thành công");
                    $msg = 'success:Đổi mật khẩu tài khoản thành công! Hãy ghi nhớ mật khẩu mới.';
                } else {
                    $msg = 'error:Không thể cập nhật mật khẩu mới!';
                }
            } else {
                $msg = 'error:Mật khẩu hiện tại không chính xác!';
            }
        }
    }

    // 5. Lưu dữ liệu khuôn mặt Face ID
    if ($act === 'save_face') {
        $desc = trim($_POST['face_descriptor'] ?? '');
        if ($desc) {
            $st = $db->prepare("UPDATE users SET face_descriptor = ? WHERE id = ?");
            $st->bind_param("si", $desc, $user_id);
            if ($st->execute()) {
                writeSystemLog("Đăng ký nhận diện khuôn mặt Face ID Quản trị viên");
                $msg = 'success:Đã lưu dữ liệu nhận diện khuôn mặt Face ID thành công!';
            }
        } else {
            $msg = 'error:Không nhận được dữ liệu khuôn mặt hợp lệ!';
        }
    }

    // 6. Xóa dữ liệu khuôn mặt Face ID
    if ($act === 'delete_face') {
        $st = $db->prepare("UPDATE users SET face_descriptor = NULL WHERE id = ?");
        $st->bind_param("i", $user_id);
        if ($st->execute()) {
            writeSystemLog("Xóa dữ liệu nhận diện Face ID Quản trị viên");
            $msg = 'success:Đã hủy và xóa dữ liệu khuôn mặt Face ID thành công!';
        }
    }
}

// Lấy thông tin chi tiết Admin hiện tại
$stAdmin = $db->prepare("SELECT * FROM users WHERE id = ?");
$stAdmin->bind_param("i", $user_id);
$stAdmin->execute();
$admin = $stAdmin->get_result()->fetch_assoc();
$hasFace = !empty($admin['face_descriptor']);

// Lấy số liệu thống kê hệ thống phục vụ hiển thị
$cnt_students = 0;
$cnt_teachers = 0;
$cnt_classes  = 0;
$cnt_subjects = 0;

$r1 = @$db->query("SELECT COUNT(*) AS c FROM students");
if ($r1 && $row = $r1->fetch_assoc()) $cnt_students = (int)$row['c'];

$r2 = @$db->query("SELECT COUNT(*) AS c FROM giang_vien");
if ($r2 && $row = $r2->fetch_assoc()) $cnt_teachers = (int)$row['c'];

$r3 = @$db->query("SELECT COUNT(*) AS c FROM lop");
if ($r3 && $row = $r3->fetch_assoc()) $cnt_classes = (int)$row['c'];

$r4 = @$db->query("SELECT COUNT(*) AS c FROM mon_hoc");
if ($r4 && $row = $r4->fetch_assoc()) $cnt_subjects = (int)$row['c'];

// Lấy 10 hoạt động gần nhất của admin này
$admin_user_esc = $db->real_escape_string($admin['username'] ?? 'admin');
$recent_logs = [];
$rLogs = @$db->query("SELECT * FROM system_logs WHERE user_id = $user_id OR username = '$admin_user_esc' ORDER BY id DESC LIMIT 10");
if ($rLogs) {
    while ($l = $rLogs->fetch_assoc()) {
        $recent_logs[] = $l;
    }
}

$db->close();

// Xử lý thông báo flash
$msgType = '';
$msgText = '';
if ($msg) {
    [$msgType, $msgText] = explode(':', $msg, 2);
}

// Xử lý Avatar URL
$avatar_display = '/tkb/assets/img/avatar_khanh.png';
if (!empty($admin['avatar'])) {
    if (strpos($admin['avatar'], '/') === 0 || strpos($admin['avatar'], 'http') === 0) {
        $avatar_display = $admin['avatar'];
    } else {
        $avatar_display = '/tkb/assets/img/avatars/' . $admin['avatar'];
    }
}

// Preset avatars
$preset_avatars = [
    'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80',
    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&auto=format&fit=crop&q=80',
    'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=200&auto=format&fit=crop&q=80',
    'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=200&auto=format&fit=crop&q=80',
    'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=200&auto=format&fit=crop&q=80',
    'https://images.unsplash.com/photo-1501196354995-cbb51c65aaea?w=200&auto=format&fit=crop&q=80'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Quản Trị Viên - Hệ Thống Quản Trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">

    <style>
        :root {
            /* Default: Dark Lofi Theme */
            --adm-bg: #0c0717;
            --adm-card-bg: #140d27;
            --adm-border: rgba(168, 85, 247, 0.2);
            --adm-card-hover-border: rgba(192, 132, 252, 0.45);
            --adm-text-main: #ffffff;
            --adm-text-muted: #a79bb7;
            --adm-input-bg: #100922;
            --adm-input-border: rgba(168, 85, 247, 0.3);
            --adm-sub-card-bg: rgba(255, 255, 255, 0.04);
            --adm-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }

        body.adm-light-mode {
            /* Light Mode */
            --adm-bg: #f8fafc;
            --adm-card-bg: #ffffff;
            --adm-border: #e2e8f0;
            --adm-card-hover-border: #cbd5e1;
            --adm-text-main: #0f172a;
            --adm-text-muted: #64748b;
            --adm-input-bg: #f8fafc;
            --adm-input-border: #e2e8f0;
            --adm-sub-card-bg: #f8fafc;
            --adm-shadow: 0 2px 12px rgba(0,0,0,0.03);
        }

        body.admin-portal {
            background-color: var(--adm-bg) !important;
            color: var(--adm-text-main) !important;
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
            display: block !important;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .page-header {
            margin-bottom: 24px;
        }
        .page-title {
            color: var(--adm-text-main) !important;
            font-weight: 800;
        }
        .page-sub {
            color: var(--adm-text-muted) !important;
            font-size: 13.5px;
            margin-top: 4px;
        }

        /* Modern Profile Layout */
        .profile-wrapper {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 28px;
            align-items: start;
        }

        /* Identity Side Card */
        .profile-side-card {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-border);
            border-radius: 20px;
            box-shadow: var(--adm-shadow);
            overflow: hidden;
            position: sticky;
            top: 88px;
            transition: all 0.3s ease;
        }
        .profile-cover {
            height: 115px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
            position: relative;
        }
        .profile-avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: -60px auto 14px;
        }
        .profile-avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--adm-card-bg);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            background: var(--adm-card-bg);
            transition: transform 0.2s ease;
        }
        .profile-avatar-upload-btn {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
            color: #ffffff;
            border: 3px solid var(--adm-card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.35);
            transition: all 0.2s ease;
            font-size: 14px;
        }
        .profile-avatar-upload-btn:hover {
            transform: scale(1.1);
            background: #6d28d9;
        }

        .profile-identity-info {
            text-align: center;
            padding: 0 20px 20px;
        }
        .profile-name {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 19px;
            font-weight: 800;
            color: var(--adm-text-main);
            margin: 0 0 6px;
            letter-spacing: -0.2px;
            text-shadow: none;
        }
        .profile-user-id {
            font-size: 12px;
            font-weight: 700;
            color: var(--adm-text-muted);
            background: var(--adm-sub-card-bg);
            border: 1px solid var(--adm-border);
            padding: 4px 12px;
            border-radius: 999px;
            display: inline-block;
            margin-bottom: 12px;
        }
        .profile-badge-role {
            padding: 8px 14px;
            border-radius: 12px;
            font-size: 11.5px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        /* Stats in Left Card */
        .profile-stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 16px 20px;
            background: var(--adm-sub-card-bg);
            border-top: 1px solid var(--adm-border);
            border-bottom: 1px solid var(--adm-border);
        }
        .profile-stat-box {
            background: var(--adm-card-bg);
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--adm-border);
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }
        .profile-stat-val {
            font-size: 18px;
            font-weight: 800;
            color: var(--adm-text-main);
            line-height: 1.2;
            text-shadow: none;
        }
        .profile-stat-lbl {
            font-size: 11px;
            color: var(--adm-text-muted);
            font-weight: 600;
            margin-top: 2px;
        }

        /* Meta details list */
        .profile-meta-list {
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 13px;
        }
        .profile-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .profile-meta-label {
            color: var(--adm-text-muted);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .profile-meta-val {
            color: var(--adm-text-main);
            font-weight: 700;
            text-align: right;
        }

        /* Right Content: Tabs */
        .profile-tabs-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--adm-card-bg);
            padding: 8px 12px;
            border-radius: 16px;
            border: 1px solid var(--adm-border);
            margin-bottom: 24px;
            overflow-x: auto;
            box-shadow: var(--adm-shadow);
        }
        .profile-tab-btn {
            background: transparent;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            color: var(--adm-text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .profile-tab-btn i { font-size: 14px; }
        .profile-tab-btn:hover {
            color: var(--adm-text-main);
            background: var(--adm-sub-card-bg);
        }
        .profile-tab-btn.active {
            background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.25);
        }

        .tab-pane {
            display: none;
            animation: fadeInTab 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .tab-pane.active {
            display: block;
        }
        @keyframes fadeInTab {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Card and Forms */
        .card {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-border);
            border-radius: 18px;
            box-shadow: var(--adm-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .card-head {
            padding: 18px 24px;
            border-bottom: 1px solid var(--adm-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--adm-text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-body {
            padding: 24px;
        }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--adm-text-main);
            margin-bottom: 6px;
        }
        .form-control, .form-select, textarea.form-control {
            width: 100%;
            box-sizing: border-box;
            background: var(--adm-input-bg);
            border: 1.5px solid var(--adm-input-border);
            border-radius: 12px;
            padding: 10px 14px;
            color: var(--adm-text-main);
            font-size: 13.5px;
            font-family: inherit;
            transition: all 0.2s ease;
            outline: none;
        }
        .form-control:focus, .form-select:focus, textarea.form-control:focus {
            background: var(--adm-card-bg);
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.18);
        }

        /* Input formatting */
        .input-icon-group {
            position: relative;
        }
        .input-icon-group i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
        }
        .input-icon-group .form-control,
        .input-icon-group .form-input {
            padding-left: 40px !important;
        }
        .toggle-password-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            padding: 4px;
        }
        .toggle-password-btn:hover { color: #0f172a; }

        /* Password Strength Bar */
        .pass-strength-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
            margin-bottom: 6px;
        }
        .pass-strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background 0.3s ease;
        }
        .pass-strength-text {
            font-size: 11.5px;
            font-weight: 700;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }

        /* Face Recognition Camera Scanner */
        .face-scanner-box {
            position: relative;
            width: 100%;
            max-width: 380px;
            aspect-ratio: 4/3;
            background: #0f172a;
            border: 2px solid #cbd5e1;
            border-radius: 18px;
            overflow: hidden;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        #regVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        #regCanvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            transform: scaleX(-1);
        }
        .face-scanner-beam {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #a855f7;
            box-shadow: 0 0 15px #a855f7;
            animation: faceScanAnim 2s ease-in-out infinite;
            display: none;
        }
        .face-scanner-beam.active { display: block; }
        @keyframes faceScanAnim {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }

        .face-status-text {
            text-align: center;
            font-size: 13.5px;
            font-weight: 700;
            color: #475569;
            min-height: 24px;
            margin-bottom: 16px;
        }

        /* Preset Avatar Gallery Modal */
        .avatar-preset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 14px;
            margin-top: 14px;
        }
        .avatar-preset-item {
            width: 100%;
            aspect-ratio: 1/1;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .avatar-preset-item:hover {
            transform: scale(1.08);
            border-color: #7c3aed;
            box-shadow: 0 4px 16px rgba(124, 58, 237, 0.25);
        }

        @media (max-width: 992px) {
            .profile-wrapper {
                grid-template-columns: 1fr;
            }
            .profile-side-card {
                position: static;
            }
        }
    </style>
</head>
<body class="admin-portal <?= (isset($_COOKIE['adm_theme']) && $_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : '' ?>">
    <?php include '../includes/admin_nav.php'; ?>

    <div class="main-content">
        
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-id-card-clip"></i> Hồ Sơ Quản Trị Viên</h1>
                <p class="page-sub">Quản lý thông tin tài khoản, cấu hình bảo mật sinh trắc học Face ID, đổi mật khẩu và nhật ký kiểm toán</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="/tkb/admin/caidat.php" class="btn btn-ghost">
                    <i class="fa-solid fa-gears"></i> Cài đặt hệ thống
                </a>
                <a href="/tkb/admin/nhatky.php" class="btn btn-ghost">
                    <i class="fa-solid fa-clock-rotate-left"></i> Toàn bộ nhật ký
                </a>
            </div>
        </div>

        <!-- Alert messages -->
        <?php if ($msgText): ?>
            <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>">
                <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <div><?= htmlspecialchars($msgText) ?></div>
            </div>
        <?php endif; ?>

        <!-- Main Profile Grid -->
        <div class="profile-wrapper">
            
            <!-- Left Side: Identity & Meta Card -->
            <div class="profile-side-card">
                <div class="profile-cover"></div>
                
                <div class="profile-avatar-container">
                    <img src="<?= htmlspecialchars($avatar_display) ?>" class="profile-avatar-img" id="profileAvatarPreview" alt="Avatar Admin">
                    <button type="button" class="profile-avatar-upload-btn" onclick="document.getElementById('avatarFileInput').click()" title="Thay đổi ảnh đại diện">
                        <i class="fa-solid fa-camera"></i>
                    </button>
                    <!-- Hidden File Upload Form -->
                    <form id="avatarUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
                        <input type="hidden" name="action" value="upload_avatar">
                        <input type="file" name="avatar_file" id="avatarFileInput" accept="image/jpeg,image/png,image/gif,image/webp" onchange="document.getElementById('avatarUploadForm').submit();">
                    </form>
                </div>

                <div class="profile-identity-info">
                    <h2 class="profile-name"><?= htmlspecialchars($admin['ho_ten'] ?? 'Quản Trị Viên') ?></h2>
                    <span class="profile-user-id"><i class="fa-solid fa-hashtag"></i> ID: <?= htmlspecialchars($admin['username']) ?></span>
                    
                    <div class="profile-badge-role" style="<?= isSuperAdmin() ? 'background:#fefce8; color:#b45309; border:1px solid #fef08a;' : 'background:#f0f9ff; color:#0284c7; border:1px solid #bae6fd;' ?>">
                        <i class="fa-solid <?= isSuperAdmin() ? 'fa-crown' : 'fa-shield-halved' ?>"></i> 
                        <?= isSuperAdmin() ? 'SUPER ADMIN • LÊ NHỰT KHÁNH (NGƯỜI SÁNG LẬP)' : 'QUẢN TRỊ VIÊN PHỤ (SUB-ADMIN • DƯỚI QUYỀN LÊ NHỰT KHÁNH)' ?>
                    </div>

                    <div style="display: flex; gap: 8px; justify-content: center; margin-bottom: 4px;">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="openPresetModal()" style="font-size: 11.5px; padding: 6px 12px;">
                            <i class="fa-regular fa-images"></i> Chọn ảnh mẫu
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('avatarFileInput').click()" style="font-size: 11.5px; padding: 6px 12px;">
                            <i class="fa-solid fa-arrow-up-from-bracket"></i> Tải ảnh lên
                        </button>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="profile-stat-grid">
                    <div class="profile-stat-box">
                        <div class="profile-stat-val"><?= number_format($cnt_students) ?></div>
                        <div class="profile-stat-lbl">Sinh viên</div>
                    </div>
                    <div class="profile-stat-box">
                        <div class="profile-stat-val"><?= number_format($cnt_teachers) ?></div>
                        <div class="profile-stat-lbl">Giảng viên</div>
                    </div>
                    <div class="profile-stat-box">
                        <div class="profile-stat-val"><?= number_format($cnt_classes) ?></div>
                        <div class="profile-stat-lbl">Lớp học</div>
                    </div>
                    <div class="profile-stat-box">
                        <div class="profile-stat-val"><?= number_format($cnt_subjects) ?></div>
                        <div class="profile-stat-lbl">Môn học</div>
                    </div>
                </div>

                <!-- Meta detail list -->
                <div class="profile-meta-list">
                    <div class="profile-meta-row">
                        <span class="profile-meta-label"><i class="fa-regular fa-calendar-check" style="color:#6366f1;"></i> Ngày khởi tạo:</span>
                        <span class="profile-meta-val"><?= !empty($admin['created_at']) ? date('d/m/Y', strtotime($admin['created_at'])) : 'Hệ thống gốc' ?></span>
                    </div>
                    <div class="profile-meta-row">
                        <span class="profile-meta-label"><i class="fa-solid fa-fingerprint" style="color:#10b981;"></i> Bảo mật Face ID:</span>
                        <span class="profile-meta-val">
                            <?php if ($hasFace): ?>
                                <span style="color:#10b981; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Đã kích hoạt</span>
                            <?php else: ?>
                                <span style="color:#f59e0b; font-weight:700;"><i class="fa-solid fa-circle-exclamation"></i> Chưa đăng ký</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="profile-meta-row">
                        <span class="profile-meta-label"><i class="fa-solid fa-network-wired" style="color:#0284c7;"></i> Địa chỉ IP phiên:</span>
                        <span class="profile-meta-val" style="font-family:monospace;"><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></span>
                    </div>
                    <div class="profile-meta-row">
                        <span class="profile-meta-label"><i class="fa-solid fa-signal" style="color:#10b981;"></i> Trạng thái phiên:</span>
                        <span class="profile-meta-val" style="color:#10b981;"><span class="adm-status-dot" style="display:inline-block; vertical-align:middle; margin-right:4px;"></span> Đang hoạt động</span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Navigation Tabs & Forms -->
            <div>
                
                <!-- Tab Navigation Buttons -->
                <div class="profile-tabs-nav">
                    <button type="button" class="profile-tab-btn active" onclick="switchTab('tab-info', this)">
                        <i class="fa-regular fa-user"></i> Thông tin cá nhân
                    </button>
                    <button type="button" class="profile-tab-btn" onclick="switchTab('tab-security', this)">
                        <i class="fa-solid fa-key"></i> Đổi mật khẩu & Bảo mật
                    </button>
                    <button type="button" class="profile-tab-btn" onclick="switchTab('tab-faceid', this)">
                        <i class="fa-solid fa-camera-rotate"></i> Nhận diện Face ID AI
                        <?php if ($hasFace): ?>
                            <span style="background:rgba(16,185,129,0.2); color:#10b981; font-size:10px; padding:2px 6px; border-radius:10px;">Bật</span>
                        <?php endif; ?>
                    </button>
                    <button type="button" class="profile-tab-btn" onclick="switchTab('tab-logs', this)">
                        <i class="fa-solid fa-clock-rotate-left"></i> Hoạt động gần đây
                    </button>
                </div>

                <!-- TAB 1: THÔNG TIN CÁ NHÂN -->
                <div id="tab-info" class="tab-pane active">
                    <div class="card">
                        <div class="card-head">
                            <span class="card-title"><i class="fa-solid fa-pen-to-square"></i> Cập nhật thông tin định danh & liên hệ</span>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Họ và tên hiển thị *</label>
                                        <div class="input-icon-group">
                                            <i class="fa-solid fa-signature"></i>
                                            <input type="text" name="ho_ten" class="form-control" value="<?= htmlspecialchars($admin['ho_ten'] ?? '') ?>" placeholder="Nhập họ và tên đầy đủ..." required>
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Tên đăng nhập (Username) *</label>
                                        <div class="input-icon-group">
                                            <i class="fa-solid fa-user-shield"></i>
                                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($admin['username'] ?? '') ?>" placeholder="Tên tài khoản admin..." required>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Địa chỉ Email quản trị</label>
                                        <div class="input-icon-group">
                                            <i class="fa-regular fa-envelope"></i>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" placeholder="admin@domain.edu.vn">
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Số điện thoại liên hệ</label>
                                        <div class="input-icon-group">
                                            <i class="fa-solid fa-phone"></i>
                                            <input type="text" name="sdt" class="form-control" value="<?= htmlspecialchars($admin['sdt'] ?? '') ?>" placeholder="09xxxxxxxx">
                                        </div>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Giới tính</label>
                                        <div class="input-icon-group">
                                            <i class="fa-solid fa-venus-mars"></i>
                                            <select name="gioi_tinh" class="form-select" style="padding-left: 40px !important;">
                                                <option value="Nam" <?= ($admin['gioi_tinh'] ?? 'Nam') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                                <option value="Nữ" <?= ($admin['gioi_tinh'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                                <option value="Khác" <?= ($admin['gioi_tinh'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Vai trò quyền hạn</label>
                                        <div class="input-icon-group">
                                            <i class="fa-solid fa-award"></i>
                                            <input type="text" class="form-control" value="<?= isSuperAdmin() ? 'Quản trị viên Cấp cao (Super Admin • Toàn quyền)' : 'Quản trị viên Phụ (Sub-Admin • Dưới quyền Lê Nhựt Khánh)' ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 24px;">
                                    <label class="form-label">Giới thiệu ngắn / Chức danh quản trị</label>
                                    <textarea name="bio" rows="3" class="form-control" placeholder="Mô tả chức năng, nhiệm vụ hoặc ghi chú cá nhân của quản trị viên..."><?= htmlspecialchars($admin['bio'] ?? '') ?></textarea>
                                </div>

                                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi hồ sơ
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ĐỔI MẬT KHẨU & BẢO MẬT -->
                <div id="tab-security" class="tab-pane">
                    <div class="card">
                        <div class="card-head">
                            <span class="card-title"><i class="fa-solid fa-lock"></i> Đổi mật khẩu tài khoản quản trị</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="changePasswordForm">
                                <input type="hidden" name="action" value="change_password">

                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label class="form-label">Mật khẩu hiện tại *</label>
                                    <div class="input-icon-group">
                                        <i class="fa-solid fa-key"></i>
                                        <input type="password" name="current_password" id="curPassInput" class="form-control" placeholder="Nhập mật khẩu hiện tại..." required>
                                        <button type="button" class="toggle-password-btn" onclick="togglePassView('curPassInput', this)">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 14px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Mật khẩu mới *</label>
                                        <div class="input-icon-group">
                                            <i class="fa-solid fa-lock"></i>
                                            <input type="password" name="new_password" id="newPassInput" class="form-control" placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)..." required oninput="checkPasswordStrength(this.value)">
                                            <button type="button" class="toggle-password-btn" onclick="togglePassView('newPassInput', this)">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Xác nhận mật khẩu mới *</label>
                                        <div class="input-icon-group">
                                            <i class="fa-solid fa-shield-check"></i>
                                            <input type="password" name="confirm_password" id="confPassInput" class="form-control" placeholder="Nhập lại mật khẩu mới..." required>
                                            <button type="button" class="toggle-password-btn" onclick="togglePassView('confPassInput', this)">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Password Strength Indicator -->
                                <div style="margin-bottom: 24px; background:#f8fafc; padding:14px; border-radius:12px; border:1px solid #e2e8f0;">
                                    <div class="pass-strength-text">
                                        <span>Độ mạnh mật khẩu: <span id="passStrengthLabel" style="font-weight:800; color:#94a3b8;">Chưa nhập</span></span>
                                        <span id="passStrengthHint" style="font-weight:600; color:#64748b;">Nên gồm chữ hoa, chữ thường, số & ký tự đặc biệt</span>
                                    </div>
                                    <div class="pass-strength-bar">
                                        <div class="pass-strength-fill" id="passStrengthBar"></div>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: flex-end;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-shield-halved"></i> Cập nhật mật khẩu mới
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: NHẬN DIỆN KHUÔN MẶT FACE ID AI -->
                <div id="tab-faceid" class="tab-pane">
                    <div class="card">
                        <div class="card-head">
                            <span class="card-title"><i class="fa-solid fa-camera-retro"></i> Sinh trắc học & Đăng nhập khuôn mặt (Face ID AI)</span>
                            <?php if ($hasFace): ?>
                                <span style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid #10b981; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:800; display:flex; align-items:center; gap:6px;">
                                    <i class="fa-solid fa-circle-check"></i> Đã đăng ký Face ID
                                </span>
                            <?php else: ?>
                                <span style="background:rgba(225,29,72,0.1); color:#e11d48; border:1px solid rgba(225,29,72,0.3); padding:4px 10px; border-radius:999px; font-size:12px; font-weight:800; display:flex; align-items:center; gap:6px;">
                                    <i class="fa-solid fa-circle-xmark"></i> Chưa đăng ký Face ID
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <p style="font-size: 13.5px; color: #64748b; line-height: 1.6; margin-top: 0; margin-bottom: 20px;">
                                Hệ thống tích hợp mô hình học sâu <strong>Face-API AI</strong> giúp nhận diện chính xác các điểm đặc trưng khuôn mặt (Landmarks & 128D Descriptors). Sau khi đăng ký, bạn có thể đăng nhập tức thì vào cổng Quản trị viên mà không cần gõ mật khẩu thủ công.
                            </p>

                            <!-- Camera scanner box -->
                            <div class="face-scanner-box">
                                <video id="regVideo" autoplay muted playsinline></video>
                                <canvas id="regCanvas"></canvas>
                                <div class="face-scanner-beam" id="scannerBeam"></div>
                            </div>

                            <div class="face-status-text" id="faceStatus">
                                <i class="fa-solid fa-circle-info" style="color:#6366f1;"></i> Nhấn <strong>"Mở Camera AI"</strong> và nhìn thẳng vào ống kính để bắt đầu quét.
                            </div>

                            <!-- Hidden Face ID Form -->
                            <form method="POST" id="saveFaceForm" style="display: none;">
                                <input type="hidden" name="action" value="save_face">
                                <input type="hidden" name="face_descriptor" id="faceDescInput">
                            </form>

                            <!-- Control buttons -->
                            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 16px;">
                                <button type="button" class="btn btn-primary" id="btnStartCam" onclick="startCamera()">
                                    <i class="fa-solid fa-camera"></i> Mở Camera AI
                                </button>
                                <button type="button" class="btn" id="btnCaptureFace" style="background:#6366f1; color:#fff; display:none;" onclick="captureFace()">
                                    <i class="fa-solid fa-expand"></i> Chụp & Nhận diện
                                </button>
                                <button type="button" class="btn" id="btnSaveFace" style="background:#10b981; color:#fff; display:none;" onclick="saveFaceData()">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Lưu khuôn mặt
                                </button>
                                <button type="button" class="btn btn-ghost" id="btnStopCam" style="display:none;" onclick="stopCamera()">
                                    <i class="fa-solid fa-video-slash"></i> Tắt Camera
                                </button>

                                <?php if ($hasFace): ?>
                                    <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy và xóa dữ liệu Face ID của tài khoản này?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_face">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fa-solid fa-trash-can"></i> Xóa Face ID đã lưu
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: NHẬT KÝ HOẠT ĐỘNG GẦN ĐÂY -->
                <div id="tab-logs" class="tab-pane">
                    <div class="card">
                        <div class="card-head">
                            <span class="card-title"><i class="fa-solid fa-list-check"></i> 10 hoạt động kiểm toán gần nhất</span>
                            <a href="/tkb/admin/nhatky.php" class="btn btn-ghost btn-sm">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">ID</th>
                                        <th>Hành động thực hiện</th>
                                        <th>Địa chỉ IP</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_logs)): ?>
                                        <?php foreach ($recent_logs as $log): ?>
                                            <tr>
                                                <td><span style="font-weight:700; color:#64748b;">#<?= $log['id'] ?></span></td>
                                                <td>
                                                    <div style="font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                                        <i class="fa-solid fa-circle-dot" style="color: #e11d48; font-size: 8px;"></i>
                                                        <?= htmlspecialchars($log['hanh_dong']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span style="background:#f1f5f9; color:#475569; padding:3px 8px; border-radius:6px; font-family:monospace; font-size:12px; font-weight:700;">
                                                        <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="color:#64748b; font-size:12.5px; font-weight:600;">
                                                        <i class="fa-regular fa-clock" style="margin-right:4px;"></i>
                                                        <?= date('H:i:s d/m/Y', strtotime($log['created_at'])) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center; padding: 32px; color: #94a3b8;">
                                                <i class="fa-regular fa-folder-open" style="font-size: 28px; display:block; margin-bottom:8px;"></i>
                                                Chưa có dữ liệu nhật ký cho phiên làm việc này.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Preset Avatar Picker Modal -->
    <div class="modal-overlay" id="presetAvatarModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-box" style="width: 100%; max-width: 480px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="modal-title" style="margin: 0;"><i class="fa-regular fa-images" style="color:#e11d48;"></i> Chọn ảnh đại diện mẫu</h3>
                <button type="button" class="btn-ghost" onclick="closePresetModal()" style="border:none; background:none; cursor:pointer; font-size:18px; color:#64748b;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <p style="font-size: 13px; color: #64748b; margin-top: 0; margin-bottom: 16px;">Chọn một trong các ảnh phong cách mẫu được thiết kế sẵn cho Quản trị viên:</p>
            
            <form method="POST" id="presetForm">
                <input type="hidden" name="action" value="select_preset_avatar">
                <input type="hidden" name="preset_avatar" id="selectedPresetInput">

                <div class="avatar-preset-grid">
                    <?php foreach ($preset_avatars as $pUrl): ?>
                        <img src="<?= htmlspecialchars($pUrl) ?>" class="avatar-preset-item" onclick="choosePresetAvatar('<?= htmlspecialchars($pUrl) ?>')" alt="Avatar preset">
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 24px; gap: 10px;">
                    <button type="button" class="btn btn-ghost" onclick="closePresetModal()">Đóng</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts for Face API and Interactive Features -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        // Tab switching
        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.profile-tab-btn').forEach(el => el.classList.remove('active'));
            
            const target = document.getElementById(tabId);
            if (target) target.classList.add('active');
            if (btn) btn.classList.add('active');
        }

        // Toggle password view
        function togglePassView(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength meter
        function checkPasswordStrength(pass) {
            const bar = document.getElementById('passStrengthBar');
            const label = document.getElementById('passStrengthLabel');
            if (!pass) {
                bar.style.width = '0%';
                bar.style.background = '#e2e8f0';
                label.textContent = 'Chưa nhập';
                label.style.color = '#94a3b8';
                return;
            }

            let score = 0;
            if (pass.length >= 6) score += 20;
            if (pass.length >= 10) score += 20;
            if (/[A-Z]/.test(pass)) score += 20;
            if (/[0-9]/.test(pass)) score += 20;
            if (/[^A-Za-z0-9]/.test(pass)) score += 20;

            bar.style.width = score + '%';

            if (score <= 40) {
                bar.style.background = '#ef4444';
                label.textContent = 'Yếu';
                label.style.color = '#ef4444';
            } else if (score <= 60) {
                bar.style.background = '#f59e0b';
                label.textContent = 'Khá';
                label.style.color = '#f59e0b';
            } else if (score <= 80) {
                bar.style.background = '#3b82f6';
                label.textContent = 'Mạnh';
                label.style.color = '#3b82f6';
            } else {
                bar.style.background = '#10b981';
                label.textContent = 'Rất mạnh (Tối ưu)';
                label.style.color = '#10b981';
            }
        }

        // Modal Preset Avatars
        function openPresetModal() {
            const m = document.getElementById('presetAvatarModal');
            m.style.display = 'flex';
        }
        function closePresetModal() {
            const m = document.getElementById('presetAvatarModal');
            m.style.display = 'none';
        }
        function choosePresetAvatar(url) {
            document.getElementById('selectedPresetInput').value = url;
            document.getElementById('presetForm').submit();
        }

        // Biometric Face ID via Face API
        let videoStream = null;
        let modelsLoaded = false;
        let capturedDescriptor = null;
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';

        async function startCamera() {
            const status = document.getElementById('faceStatus');
            status.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color:#6366f1;"></i> Đang khởi tạo mô hình AI Face API...';

            if (!modelsLoaded) {
                try {
                    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                    modelsLoaded = true;
                } catch (e) {
                    status.innerHTML = '<span style="color:#ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Lỗi nạp model AI: ' + e.message + '</span>';
                    return;
                }
            }

            try {
                videoStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }
                });
                const video = document.getElementById('regVideo');
                video.srcObject = videoStream;
                document.getElementById('scannerBeam').classList.add('active');

                status.innerHTML = '<span style="color:#10b981;"><i class="fa-solid fa-circle-check"></i> Camera đã sẵn sàng! Hãy nhìn thẳng vào khung hình và nhấn "Chụp & Nhận diện".</span>';
                document.getElementById('btnStartCam').style.display = 'none';
                document.getElementById('btnCaptureFace').style.display = 'inline-flex';
                document.getElementById('btnStopCam').style.display = 'inline-flex';
            } catch (err) {
                status.innerHTML = '<span style="color:#ef4444;"><i class="fa-solid fa-video-slash"></i> Không thể truy cập Camera: ' + err.message + '</span>';
            }
        }

        async function captureFace() {
            const video = document.getElementById('regVideo');
            const canvas = document.getElementById('regCanvas');
            const status = document.getElementById('faceStatus');

            status.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color:#6366f1;"></i> Đang phân tích sinh trắc khuôn mặt...';
            
            const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
            const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks().withFaceDescriptor();

            if (!detection) {
                status.innerHTML = '<span style="color:#ef4444;"><i class="fa-solid fa-circle-xmark"></i> Không tìm thấy khuôn mặt rõ ràng. Vui lòng căn chỉnh lại góc nhìn và đủ ánh sáng.</span>';
                return;
            }

            const dims = faceapi.matchDimensions(canvas, video, true);
            const resized = faceapi.resizeResults(detection, dims);
            faceapi.draw.drawDetections(canvas, resized);
            faceapi.draw.drawFaceLandmarks(canvas, resized);

            capturedDescriptor = Array.from(detection.descriptor);
            status.innerHTML = '<span style="color:#10b981; font-weight:800;"><i class="fa-solid fa-circle-check"></i> Đã trích xuất 128D Face Descriptor thành công! Nhấn "Lưu khuôn mặt" để hoàn tất.</span>';
            document.getElementById('btnSaveFace').style.display = 'inline-flex';
        }

        function saveFaceData() {
            if (!capturedDescriptor) return;
            document.getElementById('faceDescInput').value = JSON.stringify(capturedDescriptor);
            document.getElementById('saveFaceForm').submit();
        }

        function stopCamera() {
            if (videoStream) {
                videoStream.getTracks().forEach(t => t.stop());
                videoStream = null;
            }
            document.getElementById('scannerBeam').classList.remove('active');
            document.getElementById('faceStatus').innerHTML = '<i class="fa-solid fa-circle-info" style="color:#64748b;"></i> Camera đã tắt.';
            document.getElementById('btnStartCam').style.display = 'inline-flex';
            document.getElementById('btnCaptureFace').style.display = 'none';
            document.getElementById('btnSaveFace').style.display = 'none';
            document.getElementById('btnStopCam').style.display = 'none';

            const canvas = document.getElementById('regCanvas');
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    </script>
</body>
</html>
