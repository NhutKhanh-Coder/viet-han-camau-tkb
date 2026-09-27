<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$cur_uid = (int)($_SESSION['user_id'] ?? 1);
$is_super = isSuperAdmin();
$msg = '';

// Xử lý đổi mật khẩu cho Sub-Admin (Chỉ Super Admin mới có quyền)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$is_super) {
        $msg = 'error:Chỉ Super Admin Lê Nhựt Khánh mới có quyền thay đổi thông tin quản trị viên!';
    } else {
        $act = $_POST['action'];
        if ($act === 'reset_subadmin_password') {
            $target_uid = (int)($_POST['target_uid'] ?? 0);
            $new_pass   = trim($_POST['new_password'] ?? '');

            // Không cho phép reset tài khoản Super Admin qua form này
            $chkTarget = $db->query("SELECT id, username, ho_ten FROM users WHERE id = $target_uid LIMIT 1");
            if (!$chkTarget || $chkTarget->num_rows === 0) {
                $msg = 'error:Không tìm thấy tài khoản quản trị viên yêu cầu!';
            } else {
                $tRow = $chkTarget->fetch_assoc();
                if ($tRow['username'] === 'admin' || strpos(mb_strtolower($tRow['ho_ten']), 'nhựt khánh') !== false) {
                    $msg = 'error:Tài khoản Super Admin Lê Nhựt Khánh không thể reset qua chức năng này!';
                } elseif (strlen($new_pass) < 6) {
                    $msg = 'error:Mật khẩu mới phải có ít nhất 6 ký tự!';
                } else {
                    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $up = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $up->bind_param("si", $hash, $target_uid);
                    if ($up->execute()) {
                        writeSystemLog("Đổi mật khẩu tài khoản Sub-Admin: " . $tRow['username']);
                        $msg = 'success:Đã đổi mật khẩu cho Quản trị viên ' . htmlspecialchars($tRow['ho_ten']) . ' thành công!';
                    } else {
                        $msg = 'error:Lỗi hệ thống khi cập nhật mật khẩu: ' . $db->error;
                    }
                }
            }
        }
    }
}

// Lấy danh sách tất cả quản trị viên trong hệ thống
$admins = [];
$resAdm = $db->query("SELECT id, username, ho_ten, email, sdt, avatar, created_at FROM users WHERE role = 'admin' ORDER BY id ASC");
if ($resAdm) {
    while ($r = $resAdm->fetch_assoc()) {
        $admins[] = $r;
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân Quyền & Quản Lý Quản Trị Viên - Hệ Thống Quản Trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        :root {
            /* Dark Mode Default */
            --adm-bg: #0c0717;
            --adm-card-bg: #140d27;
            --adm-border: rgba(168, 85, 247, 0.2);
            --adm-card-hover-border: rgba(192, 132, 252, 0.45);
            --adm-text-main: #ffffff;
            --adm-text-muted: #a79bb7;
            --adm-table-th: #0f0a1e;
            --adm-table-hover: rgba(168, 85, 247, 0.08);
            --adm-shadow: 0 8px 30px rgba(0,0,0,0.4);
            --adm-box-gold-bg: rgba(245, 158, 11, 0.1);
            --adm-box-gold-border: rgba(245, 158, 11, 0.25);
            --adm-box-gold-text: #fde68a;
            --adm-box-blue-bg: rgba(2, 132, 199, 0.1);
            --adm-box-blue-border: rgba(2, 132, 199, 0.25);
            --adm-box-blue-text: #bae6fd;
        }

        body.adm-light-mode {
            /* Light Mode */
            --adm-bg: #f8fafc;
            --adm-card-bg: #ffffff;
            --adm-border: #e2e8f0;
            --adm-card-hover-border: #cbd5e1;
            --adm-text-main: #0f172a;
            --adm-text-muted: #64748b;
            --adm-table-th: #f8fafc;
            --adm-table-hover: #f8fafc;
            --adm-shadow: 0 2px 12px rgba(0,0,0,0.03);
            --adm-box-gold-bg: #fefce8;
            --adm-box-gold-border: #fef08a;
            --adm-box-gold-text: #854d0e;
            --adm-box-blue-bg: #f0f9ff;
            --adm-box-blue-border: #bae6fd;
            --adm-box-blue-text: #0369a1;
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

        .adm-hierarchy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        .adm-card {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-border);
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--adm-shadow);
            transition: all 0.3s ease;
        }
        .adm-card:hover {
            border-color: var(--adm-card-hover-border);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        .adm-card.super-admin {
            border-color: rgba(245, 158, 11, 0.4);
            background: var(--adm-card-bg);
        }
        .adm-card.super-admin::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f59e0b, #ec4899, #7c3aed);
        }
        .adm-card.sub-admin {
            border-color: rgba(2, 132, 199, 0.4);
            background: var(--adm-card-bg);
        }
        .adm-card.sub-admin::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0284c7, #6366f1, #7c3aed);
        }
        .adm-card-top {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }
        .adm-card-av {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
            background: var(--adm-card-bg);
        }
        .adm-card.super-admin .adm-card-av {
            border: 2px solid #f59e0b;
            box-shadow: 0 0 16px rgba(245, 158, 11, 0.25);
        }
        .adm-card.sub-admin .adm-card-av {
            border: 2px solid #0284c7;
            box-shadow: 0 0 16px rgba(2, 132, 199, 0.25);
        }
        .adm-card-av img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .adm-badge-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .tag-super {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24;
        }
        body.adm-light-mode .tag-super {
            background: #fefce8;
            border: 1px solid #fef08a;
            color: #b45309;
        }
        .tag-sub {
            background: rgba(2, 132, 199, 0.15);
            border: 1px solid rgba(2, 132, 199, 0.35);
            color: #38bdf8;
        }
        body.adm-light-mode .tag-sub {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0284c7;
        }
        .adm-perm-list {
            list-style: none;
            padding: 0;
            margin: 14px 0 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 12.5px;
            border-top: 1px solid var(--adm-border);
            padding-top: 14px;
        }
        .adm-perm-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--adm-text-muted);
        }
        .adm-perm-list li i.check {
            color: #10b981;
            font-size: 13px;
        }
        .adm-perm-list li i.cross {
            color: #ef4444;
            font-size: 13px;
        }
        .perm-check {
            color: #10b981;
            font-size: 17px;
        }
        .perm-cross {
            color: var(--adm-text-muted);
            opacity: 0.5;
            font-size: 16px;
        }
        .matrix-table th {
            background: var(--adm-table-th) !important;
            color: var(--adm-text-main) !important;
            padding: 14px 18px !important;
            border-bottom: 2px solid var(--adm-border) !important;
            font-size: 12.5px !important;
            font-weight: 700 !important;
        }
        .matrix-table td {
            padding: 14px 18px !important;
            border-bottom: 1px solid var(--adm-border) !important;
            font-size: 13px !important;
            color: var(--adm-text-muted);
        }
        .matrix-table tr:hover td {
            background: var(--adm-table-hover) !important;
        }
    </style>
</head>
<body class="admin-portal <?= (isset($_COOKIE['adm_theme']) && $_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : '' ?>">
    <?php include '../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-user-shield"></i> Phân Quyền & Quản Lý Quản Trị Viên</h1>
                <p class="page-sub">Hệ thống phân cấp Quản Trị Viên (Super Admin & Sub-Admin) và Bảng ma trận đặc quyền phân hệ (RBAC)</p>
            </div>
        </div>

        <?php if (!empty($msg)): 
            $parts = explode(':', $msg, 2);
            $type = $parts[0];
            $text = $parts[1] ?? $msg;
        ?>
            <div class="alert <?= $type === 'success' ? 'alert-success' : 'alert-error' ?>" style="margin-bottom:20px;">
                <i class="fa-solid <?= $type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <span><?= htmlspecialchars($text) ?></span>
            </div>
        <?php endif; ?>

        <!-- KHU VỰC 1: CƠ CẤU QUẢN TRỊ VIÊN PHÂN CẤP -->
        <div style="margin-bottom: 14px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <div style="font-weight:800; font-size:16px; color:var(--adm-text-main); display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-sitemap" style="color:#7c3aed;"></i> Cơ Cấu Nhân Sự Quản Trị Hệ Thống
            </div>
            <div style="font-size:12px; color:var(--adm-text-muted);">
                <span class="tag-super adm-badge-tag"><i class="fa-solid fa-crown"></i> 1 Super Admin</span>
                <span class="tag-sub adm-badge-tag" style="margin-left:6px;"><i class="fa-solid fa-shield-halved"></i> 1 Sub-Admin</span>
            </div>
        </div>

        <div class="adm-hierarchy-grid">
            <!-- 1. TÀI KHOẢN SUPER ADMIN: LÊ NHỰT KHÁNH -->
            <div class="adm-card super-admin">
                <div class="adm-card-top">
                    <div class="adm-card-av">
                        <img src="/tkb/assets/img/avatar_khanh.png" onerror="this.onerror=null; this.src='/tkb/assets/img/avatars/admin_1_1777525453.jpg';" alt="Lê Nhựt Khánh">
                    </div>
                    <div>
                        <span class="adm-badge-tag tag-super">
                            <i class="fa-solid fa-crown"></i> Super Admin • Cấp Tối Cao
                        </span>
                        <div style="font-size: 18px; font-weight: 800; color: var(--adm-text-main); margin-bottom: 2px;">
                            Lê Nhựt Khánh
                        </div>
                        <div style="font-size: 12px; color: #d97706; font-weight: 600;">
                            @admin &bull; <i class="fa-solid fa-shield-check"></i> Toàn quyền hệ thống
                        </div>
                    </div>
                </div>

                <div style="font-size: 12px; color: var(--adm-box-gold-text); line-height: 1.5; margin-bottom: 10px; background: var(--adm-box-gold-bg); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--adm-box-gold-border);">
                    <i class="fa-solid fa-lock" style="color:#d97706;"></i> <strong>Tài khoản Gốc (Protected Root):</strong> Được bảo vệ vĩnh viễn trong mã nguồn. Nắm giữ toàn bộ thẩm quyền kiểm soát, phê duyệt, sao lưu và quản lý tất cả admin cấp dưới.
                </div>

                <ul class="adm-perm-list">
                    <li><i class="fa-solid fa-circle-check check"></i> Toàn quyền kiểm soát và quản lý toàn bộ hệ thống</li>
                    <li><i class="fa-solid fa-circle-check check"></i> Tạo, sửa, cấp quyền và đổi mật khẩu admin phụ</li>
                    <li><i class="fa-solid fa-circle-check check"></i> Không bị giới hạn bởi bất kỳ chính sách nào</li>
                    <li><i class="fa-solid fa-shield-halved check" style="color:#d97706;"></i> Không tài khoản nào có thể xóa hoặc hạ quyền</li>
                </ul>
            </div>

            <!-- 2. TÀI KHOẢN SUB-ADMIN: PHAN NGỌC TUYỀN -->
            <div class="adm-card sub-admin">
                <div class="adm-card-top">
                    <div class="adm-card-av">
                        <img src="https://ui-avatars.com/api/?name=Phan+Ngoc+Tuyen&background=38bdf8&color=ffffff&bold=true&size=128" alt="Phan Ngọc Tuyền">
                    </div>
                    <div>
                        <span class="adm-badge-tag tag-sub">
                            <i class="fa-solid fa-shield-halved"></i> Sub-Admin • Quản Trị Viên Phụ
                        </span>
                        <div style="font-size: 18px; font-weight: 800; color: var(--adm-text-main); margin-bottom: 2px;">
                            Phan Ngọc Tuyền
                        </div>
                        <div style="font-size: 12px; color: #0284c7; font-weight: 600;">
                            @phanngoctuyen &bull; <i class="fa-solid fa-turn-up" style="transform:rotate(90deg);"></i> Dưới quyền: Lê Nhựt Khánh
                        </div>
                    </div>
                </div>

                <div style="font-size: 12px; color: var(--adm-box-blue-text); line-height: 1.5; margin-bottom: 10px; background: var(--adm-box-blue-bg); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--adm-box-blue-border);">
                    <i class="fa-solid fa-user-check" style="color:#0284c7;"></i> <strong>Cấp quyền vận hành:</strong> Được ủy quyền quản lý công tác đào tạo, sinh viên, giáo viên, điểm số và tài nguyên học liệu của nhà trường.
                </div>

                <ul class="adm-perm-list">
                    <li><i class="fa-solid fa-circle-check check"></i> Quản lý sinh viên, giảng viên, lớp học, môn học</li>
                    <li><i class="fa-solid fa-circle-check check"></i> Quản lý bài tập, kho tài liệu, video bài giảng</li>
                    <li><i class="fa-solid fa-circle-check check"></i> Nhập và theo dõi bảng điểm sinh viên toàn trường</li>
                    <li><i class="fa-solid fa-circle-xmark cross"></i> <strong>KHÔNG THỂ</strong> sửa/xóa/đổi mật khẩu của Super Admin Lê Nhựt Khánh</li>
                    <li><i class="fa-solid fa-circle-xmark cross"></i> <strong>KHÔNG THỂ</strong> can thiệp cài đặt an ninh gốc hệ thống</li>
                </ul>

                <?php if ($is_super): ?>
                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--adm-border); display:flex; justify-content:flex-end;">
                        <button type="button" onclick="openResetModal(2, 'Phan Ngọc Tuyền', 'phanngoctuyen')" class="btn btn-sm" style="background:linear-gradient(135deg, #0284c7, #0369a1); color:#fff; border:none; border-radius:8px; padding:6px 14px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-key"></i> Đổi Mật Khẩu Admin Phụ
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KHU VỰC 2: BẢNG MA TRẬN PHÂN QUYỀN RBAC CHI TIẾT -->
        <div class="card" style="background:var(--adm-card-bg); border:1px solid var(--adm-border); border-radius:20px; box-shadow:var(--adm-shadow);">
            <div class="card-head" style="padding: 18px 24px; border-bottom: 1px solid var(--adm-border); display:flex; align-items:center; justify-content:space-between;">
                <span class="card-title" style="font-size:15px; font-weight:800; color:var(--adm-text-main);">
                    <i class="fa-solid fa-table-cells" style="color:#7c3aed;"></i> Bảng Đối Chiếu Đặc Quyền Tính Năng Giữa Các Cấp Bậc (RBAC)
                </span>
            </div>
            <div style="overflow-x:auto;">
                <table class="matrix-table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="min-width:260px; text-align:left;">Chức năng phân hệ</th>
                            <th style="text-align:center; width: 170px; background:var(--adm-box-gold-bg) !important; color:#d97706 !important;">
                                <i class="fa-solid fa-crown"></i> Super Admin<br><span style="font-size:10.5px; font-weight:normal; opacity:0.85;">(Lê Nhựt Khánh)</span>
                            </th>
                            <th style="text-align:center; width: 170px; background:var(--adm-box-blue-bg) !important; color:#0284c7 !important;">
                                <i class="fa-solid fa-shield-halved"></i> Sub-Admin<br><span style="font-size:10.5px; font-weight:normal; opacity:0.85;">(Phan Ngọc Tuyền)</span>
                            </th>
                            <th style="text-align:center; width: 150px;">
                                <i class="fa-solid fa-chalkboard-user"></i> Giảng viên<br><span style="font-size:10.5px; font-weight:normal; opacity:0.7;">(Teacher)</span>
                            </th>
                            <th style="text-align:center; width: 150px;">
                                <i class="fa-solid fa-user-graduate"></i> Sinh viên<br><span style="font-size:10.5px; font-weight:normal; opacity:0.7;">(Student)</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $matrix = [
                            'Quản lý & thay đổi thông tin Super Admin'   => ['super' => true, 'sub' => false, 'teacher' => false, 'student' => false],
                            'Cấp quyền & quản trị tài khoản Admin phụ'   => ['super' => true, 'sub' => false, 'teacher' => false, 'student' => false],
                            'Sao lưu & Phục hồi hệ thống toàn trường'   => ['super' => true, 'sub' => false, 'teacher' => false, 'student' => false],
                            'Cấu hình cài đặt toàn hệ thống (Config)'   => ['super' => true, 'sub' => false, 'teacher' => false, 'student' => false],
                            'Nhật ký kiểm toán an ninh (System Logs)'   => ['super' => true, 'sub' => false, 'teacher' => false, 'student' => false],
                            'Quản lý người dùng & sinh viên toàn trường' => ['super' => true, 'sub' => true,  'teacher' => false, 'student' => false],
                            'Quản lý giảng viên & phân khoa'             => ['super' => true, 'sub' => true,  'teacher' => false, 'student' => false],
                            'Quản lý lớp học & danh mục môn học'        => ['super' => true, 'sub' => true,  'teacher' => true,  'student' => false],
                            'Quản lý bài học & giáo trình đào tạo'      => ['super' => true, 'sub' => true,  'teacher' => true,  'student' => false],
                            'Quản lý bài tập & chấm điểm bài tập'       => ['super' => true, 'sub' => true,  'teacher' => true,  'student' => false],
                            'Quản lý đề thi trắc nghiệm & Quiz'         => ['super' => true, 'sub' => true,  'teacher' => true,  'student' => false],
                            'Quản lý kho tài liệu số & video bài giảng' => ['super' => true, 'sub' => true,  'teacher' => true,  'student' => false],
                            'Quản lý Đồ án & đề tài môn học'            => ['super' => true, 'sub' => true,  'teacher' => true,  'student' => false],
                            'Bảng điểm toàn trường & xuất báo cáo CSV'  => ['super' => true, 'sub' => true,  'teacher' => true,  'student' => false],
                            'Quản lý cấu hình Models AI Botchat'        => ['super' => true, 'sub' => true,  'teacher' => false, 'student' => false],
                        ];
                        foreach ($matrix as $featureName => $roles):
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--adm-text-main); font-size:13px;"><?= htmlspecialchars($featureName) ?></strong>
                                </td>
                                <!-- Super Admin -->
                                <td style="text-align: center; background: rgba(245,158,11,0.06);">
                                    <i class="fa-solid fa-circle-check perm-check" style="color:#d97706;" title="Toàn quyền tối cao"></i>
                                </td>
                                <!-- Sub Admin -->
                                <td style="text-align: center; background: rgba(56,189,248,0.06);">
                                    <?php if ($roles['sub']): ?>
                                        <i class="fa-solid fa-circle-check perm-check" style="color:#0284c7;" title="Cho phép thực hiện"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-xmark perm-cross" title="Bị giới hạn (Dưới quyền Super Admin)"></i>
                                    <?php endif; ?>
                                </td>
                                <!-- Teacher -->
                                <td style="text-align: center;">
                                    <?php if ($roles['teacher']): ?>
                                        <i class="fa-solid fa-circle-check perm-check" style="color:#7c3aed;" title="Cho phép"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-xmark perm-cross" title="Không có quyền"></i>
                                    <?php endif; ?>
                                </td>
                                <!-- Student -->
                                <td style="text-align: center;">
                                    <?php if ($roles['student']): ?>
                                        <i class="fa-solid fa-circle-check perm-check" style="color:#10b981;" title="Cho phép"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-xmark perm-cross" title="Không có quyền"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL ĐỔI MẬT KHẨU SUB-ADMIN -->
    <?php if ($is_super): ?>
    <div id="resetModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.7); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center; padding:16px;">
        <div style="background:var(--adm-card-bg); border:1px solid var(--adm-border); border-radius:20px; width:100%; max-width:440px; padding:24px; box-shadow:var(--adm-shadow);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom:1px solid var(--adm-border); padding-bottom:12px;">
                <div style="font-weight:800; font-size:16px; color:var(--adm-text-main); display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-key" style="color:#0284c7;"></i> Đổi Mật Khẩu Admin Phụ
                </div>
                <button type="button" onclick="closeResetModal()" style="background:none; border:none; color:var(--adm-text-muted); font-size:16px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="reset_subadmin_password">
                <input type="hidden" name="target_uid" id="resetTargetUid" value="">

                <div style="margin-bottom:14px;">
                    <div style="font-size:12px; color:var(--adm-text-muted); margin-bottom:4px;">Tài khoản cập nhật:</div>
                    <div id="resetTargetName" style="font-weight:700; color:#0284c7; font-size:14px;"></div>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--adm-text-main); margin-bottom:6px;">Mật khẩu mới:</label>
                    <input type="text" name="new_password" required minlength="6" placeholder="Nhập mật khẩu mới (VD: 123456)" style="width:100%; box-sizing:border-box; background:var(--adm-bg); border:1px solid var(--adm-border); border-radius:10px; padding:10px 14px; color:var(--adm-text-main); font-size:13px; outline:none;">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeResetModal()" style="background:var(--adm-bg); border:1px solid var(--adm-border); color:var(--adm-text-muted); border-radius:10px; padding:8px 16px; font-size:12.5px; font-weight:700; cursor:pointer;">Hủy bỏ</button>
                    <button type="submit" style="background:linear-gradient(135deg, #0284c7, #0369a1); border:none; color:#fff; border-radius:10px; padding:8px 20px; font-size:12.5px; font-weight:800; cursor:pointer; box-shadow:0 4px 14px rgba(2, 132, 199, 0.3);">Xác nhận lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openResetModal(uid, name, username) {
        document.getElementById('resetTargetUid').value = uid;
        document.getElementById('resetTargetName').textContent = name + ' (@' + username + ')';
        const m = document.getElementById('resetModal');
        m.style.display = 'flex';
    }
    function closeResetModal() {
        document.getElementById('resetModal').style.display = 'none';
    }
    </script>
    <?php endif; ?>
</body>
</html>
