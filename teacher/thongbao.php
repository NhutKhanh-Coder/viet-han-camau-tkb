<?php
require_once '../config.php';
requireTeacher();

$db = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
$gv_name = $_SESSION['ho_ten'] ?? 'Giảng viên';

$msg = '';
$msg_type = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_announcement') {
        $tieu_de  = trim($_POST['tieu_de'] ?? '');
        $noi_dung = trim($_POST['noi_dung'] ?? '');
        $loai     = trim($_POST['loai'] ?? 'Thông báo');
        $khoa     = trim($_POST['khoa'] ?? 'Công Nghệ Thông Tin');
        $switch   = isset($_POST['switch_schedule_mode']) ? 1 : 0;

        if (!empty($tieu_de) && !empty($noi_dung)) {
            $stmt_add = $db->prepare("INSERT INTO thong_bao (tieu_de, noi_dung, ngay_dang, trang_thai, khoa, loai, giang_vien_id, tac_gia) VALUES (?, ?, CURDATE(), 'Đã xuất bản', ?, ?, ?, ?)");
            $stmt_add->bind_param("ssssis", $tieu_de, $noi_dung, $khoa, $loai, $gv_id, $gv_name);
            if ($stmt_add->execute()) {
                if ($switch || $loai === 'Lịch thi') {
                    setSystemSetting('schedule_mode', 'lich_thi', $db);
                }
                header("Location: /tkb/teacher/thongbao.php?msg=ann_added");
                exit;
            } else {
                $msg = "Có lỗi xảy ra khi tạo thông báo.";
                $msg_type = 'danger';
            }
        } else {
            $msg = "Vui lòng điền đầy đủ tiêu đề và nội dung thông báo.";
            $msg_type = 'danger';
        }
    } elseif ($action === 'toggle_schedule_mode') {
        $new_mode = ($_POST['mode'] ?? '') === 'lich_thi' ? 'lich_thi' : 'lich_hoc';
        setSystemSetting('schedule_mode', $new_mode, $db);

        $mode_label = ($new_mode === 'lich_thi') ? 'LỊCH THI HÔM NAY' : 'LỊCH HỌC HÔM NAY';
        $tb_title = "Thông báo thay đổi thời khóa biểu: " . $mode_label;
        $tb_content = "Nhà trường và Giảng viên vừa cập nhật chế độ hiển thị thời khóa biểu thành: " . $mode_label . ". Vui lòng theo dõi chi tiết.";
        $tb_loai = ($new_mode === 'lich_thi') ? 'Lịch thi' : 'Thông báo';

        $stmt_auto = $db->prepare("INSERT INTO thong_bao (tieu_de, noi_dung, ngay_dang, trang_thai, khoa, loai, giang_vien_id, tac_gia) VALUES (?, ?, CURDATE(), 'Đã xuất bản', 'Công Nghệ Thông Tin', ?, ?, ?)");
        $stmt_auto->bind_param("sssis", $tb_title, $tb_content, $tb_loai, $gv_id, $gv_name);
        $stmt_auto->execute();

        header("Location: /tkb/teacher/thongbao.php?msg=mode_updated");
        exit;
    } elseif ($action === 'delete_announcement') {
        $ann_id = (int)($_POST['ann_id'] ?? 0);
        if ($ann_id > 0) {
            $stmt_del = $db->prepare("DELETE FROM thong_bao WHERE id = ?");
            $stmt_del->bind_param("i", $ann_id);
            $stmt_del->execute();
            header("Location: /tkb/teacher/thongbao.php?msg=ann_deleted");
            exit;
        }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'ann_added') {
        $msg = "Đã đăng thông báo thành công!";
    } elseif ($_GET['msg'] === 'mode_updated') {
        $msg = "Đã cập nhật chế độ lịch học / lịch thi!";
    } elseif ($_GET['msg'] === 'ann_deleted') {
        $msg = "Đã xóa thông báo!";
    }
}

// Current Schedule Mode & All Announcements
$current_schedule_mode = getSystemSetting('schedule_mode', 'lich_hoc', $db);
$announcements = $db->query("SELECT * FROM thong_bao ORDER BY ngay_dang DESC, id DESC")->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Thông Báo & Lịch Thi - Giảng Viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .thongbao-hero-card {
            background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 24px;
            color: #fff;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
        }
        .mode-toggle-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.06);
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            margin-top: 15px;
        }
        .mode-status-pill {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .pill-hoc { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); }
        .pill-thi { background: rgba(239, 68, 68, 0.25); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.5); }
        
        .btn-switch-mode {
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 13.5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-switch-mode:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4); }

        .form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .form-input {
            width: 100%;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            color: #0f172a;
            padding: 10px 14px;
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 13.5px;
            margin-top: 5px;
            box-sizing: border-box;
        }
        .form-input:focus { outline: none; border-color: #8b5cf6; background: #fff; }
    </style>
</head>
<body class="admin-portal">
    <?php include '../includes/teacher_nav.php'; ?>

    <div class="main-content">
    <div class="content-pad">

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-bullhorn" style="color: #ec4899;"></i> Quản Lý Thông Báo & Lịch Thi</h1>
            <p style="color: var(--text2); margin-top: 5px;">Đăng thông báo học tập, thông báo lịch thi và điều khiển chế độ thời khóa biểu sinh viên.</p>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: <?= $msg_type === 'danger' ? '#fef2f2' : '#f0fdf4' ?>; border: 1px solid <?= $msg_type === 'danger' ? '#fecaca' : '#bbf7d0' ?>; color: <?= $msg_type === 'danger' ? '#991b1b' : '#166534' ?>; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="fa-solid <?= $msg_type === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i> <?= htmlspecialchars($msg) ?></span>
            <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; color:inherit; cursor:pointer; font-size:16px;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Mode Control Banner -->
    <div class="thongbao-hero-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-arrows-rotate" style="color: #a855f7;"></i> Điều Khiển Chế Độ Thời Khóa Biểu Toàn Trường
                </h3>
                <p style="font-size: 13px; color: #94a3b8; margin: 4px 0 0 0;">Khi chuyển sang chế độ <strong>LỊCH THI HÔM NAY</strong>, toàn bộ giao diện thời khóa biểu của sinh viên sẽ tự động làm nổi bật lịch thi.</p>
            </div>
            <button onclick="toggleAnnForm()" style="background: linear-gradient(90deg, #ec4899, #8b5cf6); color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer;">
                <i class="fa-solid fa-plus"></i> Đăng Thông Báo Mới
            </button>
        </div>

        <div class="mode-toggle-box">
            <div>
                <span style="font-size: 12px; color: #cbd5e1; font-weight: 600; display: block; margin-bottom: 4px;">CHẾ ĐỘ THỜI KHÓA BIỂU ĐANG BẬT:</span>
                <?php if ($current_schedule_mode === 'lich_thi'): ?>
                    <span class="mode-status-pill pill-thi"><i class="fa-solid fa-file-pen"></i> 📝 LỊCH THI HÔM NAY (Đang hiển thị cho SV)</span>
                <?php else: ?>
                    <span class="mode-status-pill pill-hoc"><i class="fa-solid fa-calendar-day"></i> 📅 LỊCH HỌC HÔM NAY (Đang hiển thị cho SV)</span>
                <?php endif; ?>
            </div>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="toggle_schedule_mode">
                <input type="hidden" name="mode" value="<?= $current_schedule_mode === 'lich_thi' ? 'lich_hoc' : 'lich_thi' ?>">
                <button type="submit" class="btn-switch-mode">
                    <?php if ($current_schedule_mode === 'lich_thi'): ?>
                        <i class="fa-solid fa-arrow-rotate-left"></i> Đổi Lại Thành Lịch Học
                    <?php else: ?>
                        <i class="fa-solid fa-arrow-right-arrow-left"></i> Đổi Lịch Học Thành Lịch Thi
                    <?php endif; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Form Tạo thông báo mới -->
    <div id="annFormBox" class="form-card" style="display: none;">
        <h3 style="font-size: 16px; font-weight: 800; color: #ec4899; margin: 0 0 15px 0; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-pen-to-square"></i> Tạo Thông Báo Mới / Cập Nhật Lịch Thi
        </h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_announcement">
            
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="font-size: 12px; color: #475569; font-weight: 700;">Tiêu đề thông báo *</label>
                    <input type="text" name="tieu_de" class="form-input" placeholder="Ví dụ: Thông báo Lịch thi Học kỳ 2" required>
                </div>
                <div>
                    <label style="font-size: 12px; color: #475569; font-weight: 700;">Loại thông báo</label>
                    <select name="loai" class="form-input">
                        <option value="Lịch thi">📝 Lịch thi học kỳ</option>
                        <option value="Thông báo">📢 Thông báo học tập</option>
                        <option value="Nghỉ học">🏖️ Thông báo nghỉ học</option>
                        <option value="Sự kiện">🏆 Sự kiện / Khác</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; color: #475569; font-weight: 700;">Khoa / Ngành áp dụng</label>
                    <select name="khoa" class="form-input">
                        <option value="Công Nghệ Thông Tin">Công Nghệ Thông Tin</option>
                        <option value="Cơ Khí Ô Tô">Cơ Khí Ô Tô</option>
                        <option value="Điện - Điện Tử">Điện - Điện Tử</option>
                        <option value="Quản Trị Doanh Nghiệp">Quản Trị Doanh Nghiệp</option>
                        <option value="Tất cả">Tất cả các Khoa</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-size: 12px; color: #475569; font-weight: 700;">Nội dung chi tiết *</label>
                <textarea name="noi_dung" rows="4" class="form-input" placeholder="Nhập chi tiết nội dung thông báo hoặc thông tin lịch thi (thời gian, phòng thi, hình thức thi)..." required></textarea>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 18px; border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                <label style="font-size: 13px; color: #e11d48; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="switch_schedule_mode" value="1" checked style="width: 18px; height: 18px; accent-color: #e11d48;">
                    Chuyển ngay chế độ thời khóa biểu sinh viên sang <strong>LỊCH THI HÔM NAY</strong>
                </label>

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="toggleAnnForm()" style="background: #f1f5f9; color: #64748b; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer;">Hủy</button>
                    <button type="submit" style="background: linear-gradient(90deg, #ec4899, #8b5cf6); color: #fff; border: none; padding: 10px 25px; border-radius: 8px; font-weight: 800; cursor: pointer;">
                        <i class="fa-solid fa-paper-plane"></i> Đăng Thông Báo
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Danh sách Thông báo đã đăng -->
    <div class="card">
        <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
            <span class="card-title"><i class="fa-solid fa-list-check" style="color:var(--accent); margin-right:8px;"></i> Danh sách thông báo đã phát hành</span>
            <span style="font-size: 12px; color: var(--text2); font-weight: 600;">Tổng số: <?= count($announcements) ?> thông báo</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 14px;">#</th>
                        <th style="padding: 14px;">Loại</th>
                        <th style="padding: 14px;">Tiêu đề thông báo</th>
                        <th style="padding: 14px;">Khoa áp dụng</th>
                        <th style="padding: 14px;">Tác giả</th>
                        <th style="padding: 14px; text-align: center;">Ngày đăng</th>
                        <th style="padding: 14px; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text2); padding: 30px;">
                                Chưa có thông báo nào trong hệ thống.
                            </td>
                        </tr>
                    <?php else: foreach ($announcements as $idx => $ann): ?>
                        <tr>
                            <td style="padding: 14px; font-weight: 700; color: var(--text2);"><?= $idx + 1 ?></td>
                            <td style="padding: 14px;">
                                <span class="badge" style="background: <?= ($ann['loai'] ?? '') === 'Lịch thi' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(245, 158, 11, 0.15)' ?>; color: <?= ($ann['loai'] ?? '') === 'Lịch thi' ? '#ef4444' : '#d97706' ?>; border: 1px solid <?= ($ann['loai'] ?? '') === 'Lịch thi' ? 'rgba(239, 68, 68, 0.3)' : 'rgba(245, 158, 11, 0.3)' ?>; font-weight: 700;">
                                    <?= htmlspecialchars($ann['loai'] ?? 'Thông báo') ?>
                                </span>
                            </td>
                            <td style="padding: 14px;">
                                <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($ann['tieu_de']) ?></div>
                                <div style="font-size: 12px; color: var(--text2); margin-top: 2px;"><?= htmlspecialchars(mb_strimwidth(strip_tags($ann['noi_dung']), 0, 100, "...")) ?></div>
                            </td>
                            <td style="padding: 14px; font-weight: 600; color: var(--text2);"><?= htmlspecialchars($ann['khoa'] ?: 'Chung') ?></td>
                            <td style="padding: 14px; font-style: italic; color: var(--text2);"><?= htmlspecialchars($ann['tac_gia'] ?: 'Giảng viên') ?></td>
                            <td style="padding: 14px; text-align: center; font-weight: 600; color: var(--accent);"><?= date('d/m/Y', strtotime($ann['ngay_dang'])) ?></td>
                            <td style="padding: 14px; text-align: center;">
                                <form method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thông báo này?');">
                                    <input type="hidden" name="action" value="delete_announcement">
                                    <input type="hidden" name="ann_id" value="<?= $ann['id'] ?>">
                                    <button type="submit" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 6px 12px; border-radius: 6px; font-weight: 700; cursor: pointer;">
                                        <i class="fa-solid fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div> <!-- content-pad -->
    </div> <!-- main-content -->

    <script>
    function toggleAnnForm() {
        const box = document.getElementById('annFormBox');
        if (box.style.display === 'none' || !box.style.display) {
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    }
    </script>
</body>
</html>
