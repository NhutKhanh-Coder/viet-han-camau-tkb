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
        .page-header {
            margin-bottom: 24px;
        }
        .adm-hierarchy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        .adm-card {
            background: linear-gradient(135deg, rgba(20, 13, 39, 0.95), rgba(11, 7, 24, 0.95));
            border: 1px solid rgba(168, 85, 247, 0.22);
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 35px rgba(0,0,0,0.45);
            transition: all 0.3s ease;
        }
        .adm-card:hover {
            border-color: rgba(192, 132, 252, 0.45);
            transform: translateY(-2px);
            box-shadow: 0 15px 45px rgba(168, 85, 247, 0.18);
        }
        .adm-card.super-admin {
            border-color: rgba(245, 158, 11, 0.4);
            background: linear-gradient(135deg, rgba(30, 20, 10, 0.7) 0%, rgba(20, 13, 39, 0.95) 100%);
        }
        .adm-card.super-admin::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #ec4899, #a855f7);
        }
        .adm-card.sub-admin {
            border-color: rgba(56, 189, 248, 0.35);
        }
        .adm-card.sub-admin::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #38bdf8, #818cf8, #a855f7);
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
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        }
        .adm-card.super-admin .adm-card-av {
            border: 2px solid #f59e0b;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.35);
        }
        .adm-card.sub-admin .adm-card-av {
            border: 2px solid #38bdf8;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
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
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fbbf24;
        }
        .tag-sub {
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.35);
            color: #38bdf8;
        }
        .adm-perm-list {
            list-style: none;
            padding: 0;
            margin: 14px 0 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 12.5px;
            border-top: 1px solid rgba(255,255,255,0.06);
            padding-top: 14px;
        }
        .adm-perm-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #c4b5fd;
        }
        .adm-perm-list li i.check {
            color: #10b981;
            font-size: 13px;
        }
        .adm-perm-list li i.cross {
            color: #f43f5e;
            font-size: 13px;
        }
        .perm-check {
            color: #10b981;
            font-size: 17px;
        }
        .perm-cross {
            color: #f43f5e;
            font-size: 16px;
            opacity: 0.6;
        }
        .matrix-table th {
            background: #140d27 !important;
            color: #f3e8ff !important;
            padding: 14px 18px !important;
            border-bottom: 2px solid rgba(168,85,247,0.25) !important;
            font-size: 12.5px !important;
            font-weight: 700 !important;
        }
        .matrix-table td {
            padding: 14px 18px !important;
            border-bottom: 1px solid rgba(168,85,247,0.1) !important;
            font-size: 13px !important;
            color: #c4b5fd;
        }
        .matrix-table tr:hover td {
            background: rgba(168,85,247,0.06) !important;
        }
    </style>
</head>
<body class="admin-portal">
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
            <div style="font-weight:800; font-size:16px; color:#ffffff; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-sitemap" style="color:#a855f7;"></i> Cơ Cấu Nhân Sự Quản Trị Hệ Thống
            </div>
            <div style="font-size:12px; color:#a79bb7;">
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
                        <div style="font-size: 18px; font-weight: 800; color: #ffffff; margin-bottom: 2px;">
                            Lê Nhựt Khánh
                        </div>
                        <div style="font-size: 12px; color: #fbbf24; font-weight: 600;">
                            @admin &bull; <i class="fa-solid fa-shield-check"></i> Toàn quyền hệ thống
                        </div>
                    </div>
                </div>

                <div style="font-size: 12px; color: #cbd5e1; line-height: 1.5; margin-bottom: 10px; background: rgba(0,0,0,0.25); padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(245,158,11,0.2);">
                    <i class="fa-solid fa-lock" style="color:#fbbf24;"></i> <strong>Tài khoản Gốc (Protected Root):</strong> Được bảo vệ vĩnh viễn trong mã nguồn. Nắm giữ toàn bộ thẩm quyền kiểm soát, phê duyệt, sao lưu và quản lý tất cả admin cấp dưới.
                </div>

                <ul class="adm-perm-list">
                    <li><i class="fa-solid fa-circle-check check"></i> Toàn quyền kiểm soát và quản lý toàn bộ hệ thống</li>
                    <li><i class="fa-solid fa-circle-check check"></i> Tạo, sửa, cấp quyền và đổi mật khẩu admin phụ</li>
                    <li><i class="fa-solid fa-circle-check check"></i> Không bị giới hạn bởi bất kỳ chính sách nào</li>
                    <li><i class="fa-solid fa-shield-halved check" style="color:#fbbf24;"></i> Không tài khoản nào có thể xóa hoặc hạ quyền</li>
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
                        <div style="font-size: 18px; font-weight: 800; color: #ffffff; margin-bottom: 2px;">
                            Phan Ngọc Tuyền
                        </div>
                        <div style="font-size: 12px; color: #38bdf8; font-weight: 600;">
                            @phanngoctuyen &bull; <i class="fa-solid fa-turn-up" style="transform:rotate(90deg);"></i> Dưới quyền: Lê Nhựt Khánh
                        </div>
                    </div>
                </div>

                <div style="font-size: 12px; color: #cbd5e1; line-height: 1.5; margin-bottom: 10px; background: rgba(0,0,0,0.25); padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(56,189,248,0.2);">
                    <i class="fa-solid fa-user-check" style="color:#38bdf8;"></i> <strong>Cấp quyền vận hành:</strong> Được ủy quyền quản lý công tác đào tạo, sinh viên, giáo viên, điểm số và tài nguyên học liệu của nhà trường.
                </div>

                <ul class="adm-perm-list">
                    <li><i class="fa-solid fa-circle-check check"></i> Quản lý sinh viên, giảng viên, lớp học, môn học</li>
                    <li><i class="fa-solid fa-circle-check check"></i> Quản lý bài tập, kho tài liệu, video bài giảng</li>
                    <li><i class="fa-solid fa-circle-check check"></i> Nhập và theo dõi bảng điểm sinh viên toàn trường</li>
                    <li><i class="fa-solid fa-circle-xmark cross"></i> <strong>KHÔNG THỂ</strong> sửa/xóa/đổi mật khẩu của Super Admin Lê Nhựt Khánh</li>
                    <li><i class="fa-solid fa-circle-xmark cross"></i> <strong>KHÔNG THỂ</strong> can thiệp cài đặt an ninh gốc hệ thống</li>
                </ul>

                <?php if ($is_super): ?>
                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.08); display:flex; justify-content:flex-end;">
                        <button type="button" onclick="openResetModal(2, 'Phan Ngọc Tuyền', 'phanngoctuyen')" class="btn btn-sm" style="background:linear-gradient(135deg, #0284c7, #0369a1); color:#fff; border:none; border-radius:8px; padding:6px 14px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-key"></i> Đổi Mật Khẩu Admin Phụ
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KHU VỰC 2: BẢNG MA TRẬN PHÂN QUYỀN RBAC CHI TIẾT -->
        <div class="card" style="background:rgba(20,13,39,0.95); border:1px solid rgba(168,85,247,0.22); border-radius:20px;">
            <div class="card-head" style="padding: 18px 24px; border-bottom: 1px solid rgba(168,85,247,0.18); display:flex; align-items:center; justify-content:space-between;">
                <span class="card-title" style="font-size:15px; font-weight:800; color:#f3e8ff;">
                    <i class="fa-solid fa-table-cells" style="color:#a855f7;"></i> Bảng Đối Chiếu Đặc Quyền Tính Năng Giữa Các Cấp Bậc (RBAC)
                </span>
            </div>
            <div style="overflow-x:auto;">
                <table class="matrix-table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="min-width:260px; text-align:left;">Chức năng phân hệ</th>
                            <th style="text-align:center; width: 170px; background:rgba(245,158,11,0.12) !important; color:#fbbf24 !important;">
                                <i class="fa-solid fa-crown"></i> Super Admin<br><span style="font-size:10.5px; font-weight:normal; opacity:0.85;">(Lê Nhựt Khánh)</span>
                            </th>
                            <th style="text-align:center; width: 170px; background:rgba(56,189,248,0.12) !important; color:#38bdf8 !important;">
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
                                    <strong style="color: #f3e8ff; font-size:13px;"><?= htmlspecialchars($featureName) ?></strong>
                                </td>
                                <!-- Super Admin -->
                                <td style="text-align: center; background: rgba(245,158,11,0.03);">
                                    <i class="fa-solid fa-circle-check perm-check" style="color:#fbbf24;" title="Toàn quyền tối cao"></i>
                                </td>
                                <!-- Sub Admin -->
                                <td style="text-align: center; background: rgba(56,189,248,0.03);">
                                    <?php if ($roles['sub']): ?>
                                        <i class="fa-solid fa-circle-check perm-check" style="color:#38bdf8;" title="Cho phép thực hiện"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-xmark perm-cross" title="Bị giới hạn (Dưới quyền Super Admin)"></i>
                                    <?php endif; ?>
                                </td>
                                <!-- Teacher -->
                                <td style="text-align: center;">
                                    <?php if ($roles['teacher']): ?>
                                        <i class="fa-solid fa-circle-check perm-check" style="color:#a855f7;" title="Cho phép"></i>
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
    <div id="resetModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center; padding:16px;">
        <div style="background:#140d27; border:1px solid rgba(168,85,247,0.35); border-radius:20px; width:100%; max-width:440px; padding:24px; box-shadow:0 20px 50px rgba(0,0,0,0.8);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom:1px solid rgba(168,85,247,0.18); padding-bottom:12px;">
                <div style="font-weight:800; font-size:16px; color:#f3e8ff; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-key" style="color:#38bdf8;"></i> Đổi Mật Khẩu Admin Phụ
                </div>
                <button type="button" onclick="closeResetModal()" style="background:none; border:none; color:#a79bb7; font-size:16px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="reset_subadmin_password">
                <input type="hidden" name="target_uid" id="resetTargetUid" value="">

                <div style="margin-bottom:14px;">
                    <div style="font-size:12px; color:#a79bb7; margin-bottom:4px;">Tài khoản cập nhật:</div>
                    <div id="resetTargetName" style="font-weight:700; color:#38bdf8; font-size:14px;"></div>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:#c4b5fd; margin-bottom:6px;">Mật khẩu mới:</label>
                    <input type="text" name="new_password" required minlength="6" placeholder="Nhập mật khẩu mới (VD: 123456)" style="width:100%; box-sizing:border-box; background:#090514; border:1px solid rgba(168,85,247,0.3); border-radius:10px; padding:10px 14px; color:#fff; font-size:13px;">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeResetModal()" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#a79bb7; border-radius:10px; padding:8px 16px; font-size:12.5px; font-weight:700; cursor:pointer;">Hủy bỏ</button>
                    <button type="submit" style="background:linear-gradient(135deg, #0284c7, #0369a1); border:none; color:#fff; border-radius:10px; padding:8px 20px; font-size:12.5px; font-weight:800; cursor:pointer;">Xác nhận lưu</button>
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
