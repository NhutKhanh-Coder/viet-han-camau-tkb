<?php
require_once __DIR__ . '/../config.php';
requireStudent();
$db   = getDB();
$sv_id = $_SESSION['student_id'];
$msg  = '';

// Tự động kiểm tra và thêm cột gioi_tinh & banner nếu chưa có
$db->query("SHOW COLUMNS FROM students LIKE 'gioi_tinh'");
if ($db->affected_rows == 0) {
    $db->query("ALTER TABLE students ADD COLUMN `gioi_tinh` VARCHAR(20) DEFAULT 'Nam'");
}

$db->query("SHOW COLUMNS FROM students LIKE 'banner'");
if ($db->affected_rows == 0) {
    $db->query("ALTER TABLE students ADD COLUMN `banner` VARCHAR(255) DEFAULT NULL");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_face') {
        $desc = trim($_POST['face_descriptor'] ?? '');
        if ($desc) {
            $st = $db->prepare("UPDATE students SET face_descriptor=? WHERE id=?");
            $st->bind_param("si", $desc, $sv_id);
            $st->execute();
            $msg = 'success:Đã lưu dữ liệu khuôn mặt thành công!';
        } else { $msg = 'error:Không có dữ liệu khuôn mặt!'; }
    }
    if ($_POST['action'] === 'update_info') {
        $email = trim($_POST['email'] ?? '');
        $sdt   = trim($_POST['sdt'] ?? '');
        $dia_chi = trim($_POST['dia_chi'] ?? '');
        $gioi_tinh = trim($_POST['gioi_tinh'] ?? 'Nam');

        $st = $db->prepare("UPDATE students SET email=?, sdt=?, gioi_tinh=? WHERE id=?");
        $st->bind_param("sssi", $email, $sdt, $gioi_tinh, $sv_id);
        if ($st->execute()) {
            $_SESSION['gioi_tinh'] = $gioi_tinh;
            $msg = 'success:Cập nhật thông tin cá nhân & Giới tính thành công!';
        } else {
            $msg = 'error:Lỗi cập nhật dữ liệu: ' . $db->error;
        }
    }
    if ($_POST['action'] === 'select_preset_avatar') {
        $avatar_url = trim($_POST['avatar_url'] ?? '');
        if ($avatar_url) {
            $st = $db->prepare("UPDATE students SET avatar=? WHERE id=?");
            $st->bind_param("si", $avatar_url, $sv_id);
            if ($st->execute()) {
                $_SESSION['avatar'] = $avatar_url;
                $msg = 'success:Đã đổi ảnh đại diện (Ảnh động/Video) thành công!';
            }
        }
    }
    if ($_POST['action'] === 'change_password') {
        $curr_pass = $_POST['current_password'] ?? '';
        $new_pass  = $_POST['new_password'] ?? '';
        $conf_pass = $_POST['confirm_new_password'] ?? '';
        
        if (strlen($new_pass) < 6) {
            $msg = 'error:Mật khẩu mới phải có ít nhất 6 ký tự!';
        } elseif ($new_pass !== $conf_pass) {
            $msg = 'error:Mật khẩu nhập lại không khớp!';
        } else {
            $st_uid = $db->prepare("SELECT user_id FROM students WHERE id = ?");
            $st_uid->bind_param("i", $sv_id);
            $st_uid->execute();
            $user_row = $st_uid->get_result()->fetch_assoc();
            
            if ($user_row) {
                $uid = $user_row['user_id'];
                
                $st_pass = $db->prepare("SELECT password FROM users WHERE id = ?");
                $st_pass->bind_param("i", $uid);
                $st_pass->execute();
                $db_pass = $st_pass->get_result()->fetch_assoc()['password'] ?? '';
                
                if (password_verify($curr_pass, $db_pass)) {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $st_up = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $st_up->bind_param("si", $hashed, $uid);
                    if ($st_up->execute()) {
                        $msg = 'success:Đổi mật khẩu thành công!';
                    } else {
                        $msg = 'error:Lỗi hệ thống khi cập nhật mật khẩu!';
                    }
                } else {
                    $msg = 'error:Mật khẩu hiện tại không chính xác!';
                }
            } else {
                $msg = 'error:Không tìm thấy tài khoản liên kết!';
            }
        }
    }
    if ($_POST['action'] === 'upload_avatar') {
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newFilename = "avatar_" . $sv_id . "_" . time() . "." . $ext;
                $destDir = "../assets/img/avatars/";
                if (!is_dir($destDir)) { mkdir($destDir, 0777, true); }
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $destDir . $newFilename)) {
                    $db->query("UPDATE students SET avatar='$newFilename' WHERE id=$sv_id");
                    $_SESSION['avatar'] = $newFilename;
                    $msg = 'success:Tải ảnh đại diện thành công!';
                } else { $msg = 'error:Không thể lưu tệp tin.'; }
            } else { $msg = 'error:Định dạng ảnh không hợp lệ.'; }
        } else { $msg = 'error:Lỗi khi tải lên.'; }
    }
    if ($_POST['action'] === 'upload_banner') {
        if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['banner_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newFilename = "banner_" . $sv_id . "_" . time() . "." . $ext;
                $destDir = "../assets/img/banners/";
                if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
                if (move_uploaded_file($_FILES['banner_file']['tmp_name'], $destDir . $newFilename)) {
                    $bannerUrl = '/tkb/assets/img/banners/' . $newFilename;
                    $existing = [];
                    $res = @$db->query("SELECT banner FROM students WHERE id=$sv_id OR user_id=$sv_id LIMIT 1");
                    if ($res && $row = $res->fetch_assoc()) {
                        $raw = $row['banner'] ?? '';
                        if (strpos($raw, '[') === 0) {
                            $decoded = @json_decode($raw, true);
                            if (is_array($decoded)) $existing = array_values(array_filter($decoded));
                        } else if (!empty($raw)) {
                            $existing = [$raw];
                        }
                    }
                    if (!in_array($bannerUrl, $existing)) {
                        $existing[] = $bannerUrl;
                    }
                    $json_str = json_encode(array_values($existing), JSON_UNESCAPED_SLASHES);
                    $escaped = $db->real_escape_string($json_str);
                    @$db->query("UPDATE sinh_vien SET banner='$escaped' WHERE id=$sv_id OR user_id=$sv_id");
                    @$db->query("UPDATE students SET banner='$escaped' WHERE id=$sv_id OR user_id=$sv_id");
                    $_SESSION['banner'] = $json_str;
                    $msg = 'success:Tải ảnh Banner trang chủ thành công!';
                } else { $msg = 'error:Không thể lưu tệp tin banner.'; }
            } else { $msg = 'error:Định dạng ảnh không hợp lệ.'; }
        } else { $msg = 'error:Lỗi khi tải lên ảnh banner.'; }
    }
}

$st2 = $db->prepare("SELECT * FROM students WHERE id=?");
$st2->bind_param("i", $sv_id);
$st2->execute();
$sv = $st2->get_result()->fetch_assoc();
$hasFace = !empty($sv['face_descriptor']);
$current_gender = $sv['gioi_tinh'] ?? 'Nam';
$db->close();

$msgType = ''; $msgText = '';
if ($msg) { [$msgType, $msgText] = explode(':', $msg, 2); }

// Xử lý avatar URL
$raw_av = $sv['avatar'] ?? '';
if (empty($raw_av)) {
    $avatar_url = '/tkb/assets/img/logo_vkc.jpg';
} elseif (strpos($raw_av, 'http') === 0 || strpos($raw_av, '/') === 0) {
    $avatar_url = $raw_av;
} else {
    $avatar_url = '/tkb/assets/img/avatars/' . $raw_av;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Hồ Sơ Cá Nhân — Chọn Giới Tính & Ảnh Đại Diện Động</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
.video-box{position:relative;width:100%;max-width:300px;aspect-ratio:4/3;background:var(--bg);border:1px solid var(--border);border-radius:var(--r-sm);overflow:hidden;margin:0 auto 16px;}
#regVideo{width:100%;height:100%;object-fit:cover;}
#regCanvas{position:absolute;inset:0;width:100%;height:100%;}
.scan-anim{position:absolute;top:0;left:0;right:0;height:3px;background:var(--success);animation:scan 2s linear infinite;display:none;}
.scan-anim.on{display:block;}
@keyframes scan{0%{top:0}100%{top:100%}}
.face-status{text-align:center;font-size:13px;color:var(--text2);margin-bottom:12px;min-height:20px;font-weight:600;}
.face-captured{background:rgba(16, 185, 129, 0.1);border:1px solid var(--success);color:var(--success);border-radius:10px;padding:10px 14px;font-size:13px;margin-bottom:12px;display:none;text-align:center;font-weight:600;}

/* Custom Profile CSS */
.profile-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 30px; }
.prof-card { background: var(--bg2); border-radius: var(--r-md); padding: 30px; box-shadow: var(--shadow-md); border: 1px solid var(--border); }
.prof-avatar-wrap { text-align: center; margin-bottom: 20px; position: relative; display: inline-block; left: 50%; transform: translateX(-50%); }
.prof-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #ef4444; box-shadow: 0 6px 20px rgba(239,68,68,0.3); }
.prof-avatar-btn { position: absolute; bottom: 5px; right: 15px; background: #ef4444; color: #fff; width: 38px; height: 38px; border-radius: 50%; border: 2px solid #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.3); transition: transform 0.2s, background 0.2s; }
.prof-avatar-btn:hover { transform: scale(1.1); background: #dc2626; }
.prof-name { text-align: center; font-size: 24px; font-weight: 800; margin-bottom: 5px; color: var(--text); }
.prof-msv { text-align: center; font-size: 11px; font-weight: 700; color: var(--text2); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
.prof-pill-blue { background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-align: center; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid rgba(239, 68, 68, 0.2); }
.prof-pill-gray { background: var(--bg3); color: var(--text2); padding: 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-align: center; block; letter-spacing: 0.5px; }
.prof-divider { height: 1px; background: var(--border); margin: 25px 0; }
.prof-meta-item { margin-bottom: 15px; }
.prof-meta-label { font-size: 10px; color: var(--text2); font-weight: 700; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.5px; }
.prof-meta-val { font-size: 14px; color: var(--text); font-weight: 600; }

.prof-title { font-size: 18px; font-weight: 800; color: var(--text); margin-bottom: 30px; text-transform: uppercase; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
.prof-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 25px; }
.prof-input-wrap { position: relative; }
.prof-input-wrap i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text2); }
.prof-input { width: 100%; padding: 15px 15px 15px 45px; border: 1.5px solid var(--border) !important; border-radius: var(--r-sm); background: var(--bg) !important; font-size: 14px; color: var(--text) !important; outline: none; transition: 0.2s; font-family: 'Outfit', sans-serif; }
.prof-input:focus { border-color: #ef4444 !important; background: var(--bg2) !important; box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1); }
.prof-textarea { padding-left: 45px; min-height: 100px; resize: vertical; top: 15px; transform: none; }
.prof-textarea-icon { top: 20px !important; transform: none !important; }
.prof-hint { font-size: 11px; color: var(--text2); margin-top: 8px; }

.prof-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border); }
.prof-notice { font-size: 11px; color: var(--text2); display: flex; align-items: flex-start; gap: 8px; max-width: 60%; line-height: 1.5; }
.prof-btn-save { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; border: none; padding: 12px 25px; border-radius: var(--r-sm); font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; font-family: 'Outfit', sans-serif; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25); }
.prof-btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35); }

.badge-face-yes { background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; float: right; border: 1px solid var(--success); }
.badge-face-no { background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; float: right; border: 1px solid rgba(239, 68, 68, 0.15); }

/* GENDER SELECT RADIO PILLS */
.gender-radio-group {
    display: flex;
    gap: 12px;
    margin-top: 6px;
}
.gender-pill-option {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border: 2px solid var(--border);
    border-radius: 12px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    transition: all 0.2s ease;
    background: var(--bg);
}
.gender-pill-option:hover { border-color: #ef4444; }
.gender-pill-option.selected-nam {
    border-color: #3b82f6;
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}
.gender-pill-option.selected-nu {
    border-color: #ec4899;
    background: rgba(236, 72, 153, 0.12);
    color: #ec4899;
}
.gender-pill-option.selected-khac {
    border-color: #a855f7;
    background: rgba(168, 85, 247, 0.12);
    color: #a855f7;
}

/* MODAL CHỌN ẢNH ĐẠI DIỆN ĐỘNG / VIDEO */
.av-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(6px);
    display: flex; align-items: center; justify-content: center;
    z-index: 99999; opacity: 0; pointer-events: none; transition: opacity 0.25s;
}
.av-modal-backdrop.active { opacity: 1; pointer-events: auto; }

.av-modal-box {
    background: #ffffff;
    border-radius: 24px;
    width: 92%; max-width: 580px;
    padding: 26px 28px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.3);
    position: relative;
    max-height: 90vh; overflow-y: auto;
    font-family: 'Outfit', sans-serif;
    color: #1e293b;
}

.av-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
}
.av-modal-title { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; }
.av-modal-close {
    width: 36px; height: 36px; border-radius: 50%;
    background: #f1f5f9; border: 1px solid #cbd5e1; color: #64748b;
    font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
}
.av-modal-close:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

.av-tab-row { display: flex; gap: 10px; margin-bottom: 20px; }
.av-tab-btn {
    padding: 10px 20px; border-radius: 50px;
    font-size: 14px; font-weight: 700; cursor: pointer; border: 1px solid #e2e8f0;
    background: #f8fafc; color: #64748b; display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.2s;
}
.av-tab-btn.active {
    background: #bae6fd; color: #0284c7; border-color: #38bdf8;
    box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2);
}

.av-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;
}
.av-item-card {
    aspect-ratio: 16 / 10; border-radius: 18px; overflow: hidden; position: relative;
    border: 3px solid transparent; cursor: pointer; background: #0f172a;
    transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.av-item-card:hover { transform: scale(1.05); border-color: #38bdf8; box-shadow: 0 8px 20px rgba(56, 189, 248, 0.4); }
.av-item-card img { width: 100%; height: 100%; object-fit: cover; }

.av-footer-note {
    text-align: center; font-size: 13px; font-weight: 600; color: #64748b;
    padding: 12px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;
}
.av-footer-note a { color: #0284c7; text-decoration: none; font-weight: 800; }
.av-footer-note a:hover { text-decoration: underline; }
</style>
</head>
<body>
<?php include '../includes/student_nav.php'; ?>
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-user-pen" style="color:#ef4444"></i> Hồ Sơ Cá Nhân & Chọn Giới Tính</h1></div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
  <?php endif; ?>

  <div class="profile-grid">
    <!-- Left Column: Avatar & Meta -->
    <div class="prof-card">
        <div class="prof-avatar-wrap">
            <img src="<?= htmlspecialchars($avatar_url) ?>" class="prof-avatar" id="currentProfAvatar" alt="Avatar">
            <button class="prof-avatar-btn" onclick="openAvatarModal()" title="Chọn ảnh đại diện động / video / tĩnh"><i class="fa-solid fa-camera"></i></button>
            <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="action" value="upload_avatar">
                <input type="file" name="avatar_file" id="avatarInput" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
            </form>
        </div>
        <div class="prof-name"><?= htmlspecialchars($sv['ho_ten']) ?></div>
        <div class="prof-msv">MSV: <?= $sv['ma_sv'] ?></div>
        
        <div class="prof-pill-blue"><i class="fa-solid fa-graduation-cap"></i> <?= htmlspecialchars($sv['khoa']) ?></div>
        <div class="prof-pill-gray">
            GIỚI TÍNH: 
            <?php if ($current_gender === 'Nữ'): ?>
                <strong style="color:#ec4899;"><i class="fa-solid fa-venus"></i> NỮ (PASTEL)</strong>
            <?php elseif ($current_gender === 'Khác'): ?>
                <strong style="color:#a855f7;"><i class="fa-solid fa-genderless"></i> KHÁC</strong>
            <?php else: ?>
                <strong style="color:#3b82f6;"><i class="fa-solid fa-mars"></i> NAM</strong>
            <?php endif; ?>
        </div>
        
        <div class="prof-divider"></div>
        
        <div class="prof-meta-item">
            <div class="prof-meta-label">NGÀNH HỌC</div>
            <div class="prof-meta-val">Công Nghệ Thông Tin</div>
        </div>
        <div class="prof-meta-item">
            <div class="prof-meta-label">NGÀY NHẬP HỌC</div>
            <div class="prof-meta-val">28/03/2026</div>
        </div>
    </div>

    <!-- Right Column: Form & Face -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Contact Form & Gender Picker -->
        <div class="prof-card">
            <div class="prof-title">CHỈNH SỬA THÔNG TIN LIÊN LẠC & GIỚI TÍNH</div>
            <form method="POST">
                <input type="hidden" name="action" value="update_info">
                
                <!-- CHỌN GIỚI TÍNH (NỮ / NAM / KHÁC) -->
                <div style="margin-bottom: 24px; background: var(--bg); border: 1px solid var(--border); padding: 18px; border-radius: 16px;">
                    <div class="prof-meta-label" style="font-size: 12px; color: #ef4444; margin-bottom: 8px;">
                        <i class="fa-solid fa-venus-mars"></i> CHỌN GIỚI TÍNH CỦA BẠN <span style="color:red;">*</span>
                    </div>
                    <div class="gender-radio-group">
                        <label class="gender-pill-option <?= $current_gender === 'Nam' ? 'selected-nam' : '' ?>" onclick="selectGender('Nam')">
                            <input type="radio" name="gioi_tinh" value="Nam" <?= $current_gender === 'Nam' ? 'checked' : '' ?> style="display:none;">
                            <i class="fa-solid fa-mars" style="color:#3b82f6;"></i> 👦 Sinh viên Nam
                        </label>
                        
                        <label class="gender-pill-option <?= $current_gender === 'Nữ' ? 'selected-nu' : '' ?>" onclick="selectGender('Nữ')">
                            <input type="radio" name="gioi_tinh" value="Nữ" <?= $current_gender === 'Nữ' ? 'checked' : '' ?> style="display:none;">
                            <i class="fa-solid fa-venus" style="color:#ec4899;"></i> 👧 Sinh viên Nữ
                        </label>
                        
                        <label class="gender-pill-option <?= $current_gender === 'Khác' ? 'selected-khac' : '' ?>" onclick="selectGender('Khác')">
                            <input type="radio" name="gioi_tinh" value="Khác" <?= $current_gender === 'Khác' ? 'checked' : '' ?> style="display:none;">
                            <i class="fa-solid fa-genderless" style="color:#a855f7;"></i> ✨ Khác
                        </label>
                    </div>
                    <div class="prof-hint" style="margin-top:10px;">
                        💡 <strong>Mẹo:</strong> Khi chọn <strong>Sinh viên Nữ</strong>, bạn có thể chuyển đổi nhanh sang Giao diện Pastel Luyentu.com rực rỡ!
                    </div>
                </div>

                <div class="prof-form-row">
                    <div>
                        <div class="prof-meta-label">ĐỊA CHỈ EMAIL</div>
                        <div class="prof-input-wrap">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" name="email" class="prof-input" value="<?= htmlspecialchars($sv['email'] ?? '') ?>" placeholder="your@email.com">
                        </div>
                        <div class="prof-hint">Dùng để nhận thông báo chính thức.</div>
                    </div>
                    <div>
                        <div class="prof-meta-label">SỐ ĐIỆN THOẠI</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-mobile-screen"></i>
                            <input type="text" name="sdt" class="prof-input" value="<?= htmlspecialchars($sv['sdt'] ?? '') ?>" placeholder="0919495912">
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="prof-meta-label">ĐỊA CHỈ THƯỜNG TRÚ</div>
                    <div class="prof-input-wrap">
                        <i class="fa-solid fa-location-dot prof-textarea-icon"></i>
                        <textarea name="dia_chi" class="prof-input prof-textarea" placeholder="Số nhà, đường, phường/xã..."></textarea>
                    </div>
                </div>

                <div class="prof-footer">
                    <div class="prof-notice">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Các thông tin mang tính định danh chính thức chỉ có thể được thay đổi bởi Phòng Đào tạo.</span>
                    </div>
                    <button type="submit" class="prof-btn-save"><i class="fa-solid fa-download"></i> LƯU THAY ĐỔI</button>
                </div>
            </form>
        </div>

        <!-- Tùy Chỉnh Banner Trang Chủ -->
        <div class="prof-card">
            <div class="prof-title" style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-image" style="color: #ef4444;"></i> TÙY CHỈNH BANNER TRANG CHỦ (KERIA RED GLOW STYLE)
            </div>
            <?php $current_banner = !empty($sv['banner']) ? '/tkb/assets/img/banners/' . $sv['banner'] : ''; ?>
            <div style="margin-bottom: 16px; border-radius: 16px; overflow: hidden; border: 2px solid #ef4444; height: 130px; background: linear-gradient(90deg, rgba(15,23,42,0.95), rgba(69,10,10,0.85)), url('<?= htmlspecialchars($current_banner) ?>') no-repeat center center; background-size: cover; display: flex; align-items: center; justify-content: space-between; padding: 20px; box-shadow: inset 0 0 30px rgba(239,68,68,0.5);">
                <div>
                    <div style="font-size: 20px; font-weight: 900; color: #fff; text-shadow: 0 0 10px #ef4444;">
                        KERIA MINECRAFT GLOW
                    </div>
                    <div style="font-size: 12px; color: #fca5a5;">Banner tùy chỉnh dành cho giao diện trang chủ sinh viên</div>
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 12px; align-items: center;">
                <input type="hidden" name="action" value="upload_banner">
                <input type="file" name="banner_file" accept="image/*" required class="prof-input" style="padding: 10px; background: rgba(0,0,0,0.4);">
                <button type="submit" class="prof-btn-save" style="white-space: nowrap; flex-shrink: 0; background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fa-solid fa-upload"></i> TẢI BANNER MỚI
                </button>
            </form>
        </div>

        <!-- Đổi Mật Khẩu -->
        <div class="prof-card">
            <div class="prof-title">ĐỔI MẬT KHẨU</div>
            <form method="POST" onsubmit="return validateProfilePasswords()">
                <input type="hidden" name="action" value="change_password">
                <div class="prof-form-row">
                    <div>
                        <div class="prof-meta-label">Mật khẩu hiện tại</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" id="current_password" name="current_password" class="prof-input" placeholder="Nhập mật khẩu hiện tại" required>
                        </div>
                    </div>
                    <div>
                        <div class="prof-meta-label">Mật khẩu mới</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-key"></i>
                            <input type="password" id="new_password" name="new_password" class="prof-input" placeholder="Tối thiểu 6 ký tự" required>
                        </div>
                    </div>
                </div>
                <div class="prof-form-row" style="margin-bottom: 0;">
                    <div>
                        <div class="prof-meta-label">Nhập lại mật khẩu mới</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-key"></i>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" class="prof-input" placeholder="Xác nhận mật khẩu mới" required>
                        </div>
                    </div>
                </div>
                <div class="prof-footer" style="margin-top: 25px;">
                    <div class="prof-notice">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Mật khẩu của bạn được mã hóa an toàn theo tiêu chuẩn bảo mật.</span>
                    </div>
                    <button type="submit" class="prof-btn-save"><i class="fa-solid fa-key"></i> ĐỔI MẬT KHẨU</button>
                </div>
            </form>
        </div>

        <!-- Khuôn mặt -->
        <div class="prof-card">
            <div class="prof-title" style="margin-bottom: 20px;">
                BẢO MẬT KHUÔN MẶT
                <?php if ($hasFace): ?>
                <span class="badge-face-yes"><i class="fa-solid fa-circle-check"></i> Đã đăng ký</span>
                <?php else: ?>
                <span class="badge-face-no"><i class="fa-solid fa-circle-xmark"></i> Chưa đăng ký</span>
                <?php endif; ?>
            </div>
            
            <p style="font-size:13px;color:#666;margin-bottom:20px; line-height: 1.5;">Đăng ký khuôn mặt để đăng nhập nhanh chóng, an toàn không cần mật khẩu.</p>
            <div class="video-box">
              <video id="regVideo" autoplay muted playsinline></video>
              <canvas id="regCanvas"></canvas>
              <div class="scan-anim" id="scanAnim"></div>
            </div>
            <div class="face-status" id="faceStatus">Nhấn "Bắt đầu" để mở camera</div>
            <div class="face-captured" id="faceCaptured"><i class="fa-solid fa-circle-check"></i> Đã chụp khuôn mặt! Nhấn "Lưu" để xác nhận.</div>
            <form method="POST" id="faceForm">
              <input type="hidden" name="action" value="save_face">
              <input type="hidden" name="face_descriptor" id="faceDescInput">
            </form>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap; margin-top: 20px;">
              <button class="prof-btn-save" style="background:#ef4444" id="btnStart" onclick="startCamera()"><i class="fa-solid fa-camera"></i> Bắt đầu quét</button>
              <button class="prof-btn-save" style="background:#8b5cf6; display:none;" id="btnCapture" onclick="captureface()"><i class="fa-solid fa-expand"></i> Chụp khuôn mặt</button>
              <button class="prof-btn-save" style="background:#16a34a; display:none;" id="btnSave" onclick="saveFace()"><i class="fa-solid fa-floppy-disk"></i> Lưu khuôn mặt</button>
              <button class="prof-btn-save" style="background:#ef4444; display:none;" onclick="stopCamera()" id="btnStop"><i class="fa-solid fa-stop"></i> Dừng</button>
            </div>
        </div>
    </div>
  </div>
</div> <!-- content-pad -->
</div> <!-- main-content -->

<!-- Form ẩn để gửi yêu cầu chọn preset avatar -->
<form id="presetAvatarForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="select_preset_avatar">
    <input type="hidden" name="avatar_url" id="presetAvatarUrlInput">
</form>

<!-- POPUP MODAL CHỌN ẢNH ĐẠI DIỆN ĐỘNG / VIDEO -->
<div class="av-modal-backdrop" id="avatarModal">
    <div class="av-modal-box">
        <div class="av-modal-header">
            <h2 class="av-modal-title">Chọn ảnh đại diện</h2>
            <button class="av-modal-close" onclick="closeAvatarModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="av-tab-row">
            <button type="button" class="av-tab-btn active" id="tabAnimBtn" onclick="switchAvTab('anim')">
                <i class="fa-solid fa-film"></i> Anh dong / Video
            </button>
            <button type="button" class="av-tab-btn" id="tabStaticBtn" onclick="switchAvTab('static')">
                <i class="fa-regular fa-image"></i> Ảnh tĩnh
            </button>
        </div>

        <!-- TAB 1: ẢNH ĐỘNG / VIDEO AVATARS -->
        <div id="avTabAnim">
            <div class="av-grid">
                <div class="av-item-card" onclick="selectPresetAvatar('https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExOHpucTNhdzFlNzE1Y2wxcW1ocnh0cHNzeWdzNmcyb2oxdWFtYWRpdSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/134BfF8C7pBqND/giphy.gif')">
                    <img src="https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExOHpucTNhdzFlNzE1Y2wxcW1ocnh0cHNzeWdzNmcyb2oxdWFtYWRpdSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/134BfF8C7pBqND/giphy.gif" alt="Ponyo Wave">
                </div>
                <div class="av-item-card" onclick="selectPresetAvatar('https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExcWVnd3JsaXU3MW5sNXlsbHFsaHp0OXBrd200ZXBhZnh1MWN6YTRndyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/xT8qB308vIYUP63876/giphy.gif')">
                    <img src="https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExcWVnd3JsaXU3MW5sNXlsbHFsaHp0OXBrd200ZXBhZnh1MWN6YTRndyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/xT8qB308vIYUP63876/giphy.gif" alt="Ponyo Bubble">
                </div>
                <div class="av-item-card" onclick="selectPresetAvatar('https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExeGJidXU3dmZwbnh2bTNhcWNmZ2JscXhheG1iaHlpcGFyOHZ2cmJ4eCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/N1cfVLn7355vy/giphy.gif')">
                    <img src="https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExeGJidXU3dmZwbnh2bTNhcWNmZ2JscXhheG1iaHlpcGFyOHZ2cmJ4eCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/N1cfVLn7355vy/giphy.gif" alt="Shin-chan Whistle">
                </div>
                <div class="av-item-card" onclick="selectPresetAvatar('https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExNDJyOG1pMmJ2eWZ3ZXlzNmk2amU2em5ndXZpM2dremU3czM0OGg2ZyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/aD1fI3UUWC4/giphy.gif')">
                    <img src="https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExNDJyOG1pMmJ2eWZ3ZXlzNmk2amU2em5ndXZpM2dremU3czM0OGg2ZyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/aD1fI3UUWC4/giphy.gif" alt="Anime Laugh">
                </div>
                <div class="av-item-card" onclick="selectPresetAvatar('https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExbnlyeGJraXBrd3ptNWRwcmptd2o4dDNkMnZlOHBqcnoxNDZpMHFvZyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/5t4gidk6Z7Rte/giphy.gif')">
                    <img src="https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExbnlyeGJraXBrd3ptNWRwcmptd2o4dDNkMnZlOHBqcnoxNDZpMHFvZyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/5t4gidk6Z7Rte/giphy.gif" alt="Shin-chan Blush">
                </div>
                <div class="av-item-card" onclick="selectPresetAvatar('https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExeWZsbHRiZ2hveDV3azl2ZnF6emNycDVzeHNmdm1lZzZzeGJvaXV3NSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/1dJWnks4OHYhu/giphy.gif')">
                    <img src="https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExeWZsbHRiZ2hveDV3azl2ZnF6emNycDVzeHNmdm1lZzZzeGJvaXV3NSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/1dJWnks4OHYhu/giphy.gif" alt="Shin-chan Hearts">
                </div>
            </div>
        </div>

        <!-- TAB 2: ẢNH TĨNH -->
        <div id="avTabStatic" style="display:none;">
            <div class="av-grid">
                <div class="av-item-card" onclick="selectPresetAvatar('/tkb/assets/img/logo_vkc.jpg')">
                    <img src="/tkb/assets/img/logo_vkc.jpg" alt="VKC Logo">
                </div>
                <div class="av-item-card" onclick="selectPresetAvatar('https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=300&auto=format&fit=crop')">
                    <img src="https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=300&auto=format&fit=crop" alt="Boy Avatar">
                </div>
                <div class="av-item-card" onclick="selectPresetAvatar('https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300&auto=format&fit=crop')">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300&auto=format&fit=crop" alt="Girl Avatar">
                </div>
            </div>
            
            <div style="text-align:center; margin-top:14px;">
                <button type="button" class="prof-btn-save" style="margin:0 auto; background:#0284c7;" onclick="document.getElementById('avatarInput').click()">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Tải ảnh/GIF từ máy tính của bạn
                </button>
            </div>
        </div>

        <!-- Footer note -->
        <div class="av-footer-note">
            💡 Bạn có thể chọn avatar yêu thích từ danh sách có sẵn hoặc tự tải ảnh/GIF từ máy tính lên!
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
function selectGender(val) {
    document.querySelectorAll('.gender-pill-option').forEach(el => {
        el.classList.remove('selected-nam', 'selected-nu', 'selected-khac');
    });

    const radio = document.querySelector('input[name="gioi_tinh"][value="' + val + '"]');
    if (radio) radio.checked = true;

    document.querySelectorAll('.gender-pill-option').forEach(lbl => {
        const inp = lbl.querySelector('input[type="radio"]');
        if (inp && inp.value === val) {
            if (val === 'Nam') lbl.classList.add('selected-nam');
            else if (val === 'Nữ') lbl.classList.add('selected-nu');
            else lbl.classList.add('selected-khac');
        }
    });

    // Instant Theme Preview & LocalStorage Update
    if (val === 'Nữ') {
        localStorage.setItem('student_mc_mode', 'female');
        localStorage.setItem('student_mc_theme', 'female');
        document.documentElement.setAttribute('data-mc-mode', 'female');
        document.documentElement.setAttribute('data-mc-theme', 'female');
        if (document.body) {
            document.body.setAttribute('data-mc-mode', 'female');
            document.body.setAttribute('data-mc-theme', 'female');
        }
    } else {
        localStorage.setItem('student_mc_mode', 'dark');
        localStorage.setItem('student_mc_theme', val === 'Nam' ? 'red' : 'purple');
        document.documentElement.setAttribute('data-mc-mode', 'dark');
        document.documentElement.setAttribute('data-mc-theme', val === 'Nam' ? 'red' : 'purple');
        if (document.body) {
            document.body.setAttribute('data-mc-mode', 'dark');
            document.body.setAttribute('data-mc-theme', val === 'Nam' ? 'red' : 'purple');
        }
    }
}

function openAvatarModal() {
    document.getElementById('avatarModal').classList.add('active');
}
function closeAvatarModal() {
    document.getElementById('avatarModal').classList.remove('active');
}

function switchAvTab(type) {
    const animTab = document.getElementById('avTabAnim');
    const staticTab = document.getElementById('avTabStatic');
    const animBtn = document.getElementById('tabAnimBtn');
    const staticBtn = document.getElementById('tabStaticBtn');

    if (type === 'anim') {
        animTab.style.display = 'block';
        staticTab.style.display = 'none';
        animBtn.classList.add('active');
        staticBtn.classList.remove('active');
    } else {
        animTab.style.display = 'none';
        staticTab.style.display = 'block';
        staticBtn.classList.add('active');
        animBtn.classList.remove('active');
    }
}

function selectPresetAvatar(url) {
    document.getElementById('presetAvatarUrlInput').value = url;
    document.getElementById('presetAvatarForm').submit();
}

let stream = null, modelsLoaded = false, capturedDesc = null;
const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';

function validateProfilePasswords() {
    var p1 = document.getElementById('new_password').value;
    var p2 = document.getElementById('confirm_new_password').value;
    if (p1.length < 6) {
        alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
        return false;
    }
    if (p1 !== p2) {
        alert('Mật khẩu nhập lại không khớp!');
        return false;
    }
    return true;
}

async function loadModels() {
    if (modelsLoaded) return true;
    document.getElementById('faceStatus').innerText = 'Đang tải mô hình nhận diện...';
    try {
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        modelsLoaded = true;
        return true;
    } catch(e) {
        document.getElementById('faceStatus').innerText = 'Lỗi tải mô hình!';
        return false;
    }
}

async function startCamera() {
    const ok = await loadModels();
    if (!ok) return;
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
        document.getElementById('regVideo').srcObject = stream;
        document.getElementById('scanAnim').classList.add('on');
        document.getElementById('faceStatus').innerText = 'Hãy nhìn thẳng vào camera...';
        document.getElementById('btnStart').style.display = 'none';
        document.getElementById('btnCapture').style.display = 'inline-flex';
        document.getElementById('btnStop').style.display = 'inline-flex';
    } catch(e) {
        document.getElementById('faceStatus').innerText = 'Không thể truy cập camera!';
    }
}

async function captureface() {
    document.getElementById('faceStatus').innerText = 'Đang phân tích khuôn mặt...';
    const video = document.getElementById('regVideo');
    const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
    if (detection) {
        capturedDesc = JSON.stringify(Array.from(detection.descriptor));
        document.getElementById('faceCaptured').style.display = 'block';
        document.getElementById('faceStatus').innerText = 'Đã quét thành công!';
        document.getElementById('btnCapture').style.display = 'none';
        document.getElementById('btnSave').style.display = 'inline-flex';
    } else {
        document.getElementById('faceStatus').innerText = 'Không phát hiện khuôn mặt! Vui lòng thử lại.';
    }
}

function saveFace() {
    if (!capturedDesc) return;
    document.getElementById('faceDescInput').value = capturedDesc;
    document.getElementById('faceForm').submit();
}

function stopCamera() {
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    document.getElementById('scanAnim').classList.remove('on');
    document.getElementById('faceStatus').innerText = 'Đã dừng camera';
    document.getElementById('btnStart').style.display = 'inline-flex';
    document.getElementById('btnCapture').style.display = 'none';
    document.getElementById('btnSave').style.display = 'none';
    document.getElementById('btnStop').style.display = 'none';
}
</script>
</body>
</html>
