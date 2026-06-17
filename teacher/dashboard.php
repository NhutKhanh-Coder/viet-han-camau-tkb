<?php
require_once '../config.php';
requireTeacher();

$db = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// 1. Stats queries
// Total subjects taught
$stmt = $db->prepare("SELECT COUNT(DISTINCT mon_hoc_id) as total_subjects FROM thoi_khoa_bieu WHERE giang_vien_id = ?");
$stmt->bind_param("i", $gv_id);
$stmt->execute();
$subjects_count = $stmt->get_result()->fetch_assoc()['total_subjects'] ?? 0;

// Total students under their subjects
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT d.student_id) as total_students 
    FROM thoi_khoa_bieu tkb
    JOIN diem d ON tkb.mon_hoc_id = d.mon_hoc_id
    WHERE tkb.giang_vien_id = ?
");
$stmt->bind_param("i", $gv_id);
$stmt->execute();
$students_count = $stmt->get_result()->fetch_assoc()['total_students'] ?? 0;

// Total classes taught (distinct lop from student lists)
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT s.lop) as total_classes 
    FROM thoi_khoa_bieu tkb
    JOIN students s ON s.khoa = tkb.khoa
    WHERE tkb.giang_vien_id = ?
");
$stmt->bind_param("i", $gv_id);
$stmt->execute();
$classes_count = $stmt->get_result()->fetch_assoc()['total_classes'] ?? 0;

// 2. Schedule list
$stmt = $db->prepare("
    SELECT tkb.*, m.ten_mon, m.ma_mon, m.so_tin_chi 
    FROM thoi_khoa_bieu tkb 
    JOIN mon_hoc m ON tkb.mon_hoc_id = m.id 
    WHERE tkb.giang_vien_id = ? 
    ORDER BY tkb.thu, tkb.tiet_bat_dau
");
$stmt->bind_param("i", $gv_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Admin Announcements
$announcements = $db->query("SELECT * FROM thong_bao WHERE trang_thai = 'Đã xuất bản' ORDER BY ngay_dang DESC, id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tổng quan Giảng viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
    <?php include '../includes/teacher_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-house" style="color:var(--accent)"></i> Bảng Điều Khiển Giảng Viên</h1>
            <p style="color: var(--text2); margin-top: 5px;">Xin chào, Thầy/Cô <strong><?= htmlspecialchars($_SESSION['ho_ten'] ?? 'Giảng viên') ?></strong>. Chúc một ngày tốt lành!</p>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(56, 189, 248, 0.1); color: #38bdf8; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fa-solid fa-book-bookmark"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text2); font-weight: 500;">MÔN GIẢNG DẠY</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--text);"><?= $subjects_count ?> Môn</div>
            </div>
        </div>

        <div class="stat-card" style="padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text2); font-weight: 500;">SINH VIÊN ĐANG DẠY</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--text);"><?= $students_count ?> SV</div>
            </div>
        </div>

        <div class="stat-card" style="padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text2); font-weight: 500;">LỚP QUẢN LÝ</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--text);"><?= $classes_count ?> Lớp</div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
        <!-- Schedule Column -->
        <div>
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-calendar-week" style="color:var(--accent); margin-right:8px;"></i> Lịch giảng dạy của tôi</span>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 15px;">Thứ</th>
                                <th style="padding: 15px;">Mã HP</th>
                                <th style="padding: 15px;">Tên Học Phần</th>
                                <th style="padding: 15px; text-align: center;">Tiết học</th>
                                <th style="padding: 15px; text-align: center;">Phòng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($schedule)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text2); padding: 30px;">
                                        Không tìm thấy lịch giảng dạy nào trong học kỳ này.
                                    </td>
                                </tr>
                            <?php else: foreach ($schedule as $sk): ?>
                                <tr>
                                    <td style="padding: 15px; font-weight: 700; color: var(--accent);">Thứ <?= $sk['thu'] == 8 ? 'Chủ Nhật' : $sk['thu'] ?></td>
                                    <td style="padding: 15px;"><code><?= htmlspecialchars($sk['ma_mon']) ?></code></td>
                                    <td style="padding: 15px; font-weight: 600; color: var(--text);"><?= htmlspecialchars($sk['ten_mon']) ?></td>
                                    <td style="padding: 15px; text-align: center; font-weight: 600; color: #10b981;"><?= $sk['tiet_bat_dau'] ?> - <?= $sk['tiet_ket_thuc'] ?></td>
                                    <td style="padding: 15px; text-align: center;">
                                        <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text); border: 1px solid var(--border); padding: 5px 10px; font-weight: 600;"><?= htmlspecialchars($sk['phong_hoc'] ?: 'Online') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Announcements Column -->
        <div>
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-bullhorn" style="color:#f59e0b; margin-right:8px;"></i> Thông báo chung</span>
                </div>
                <div class="card-body" style="padding: 15px;">
                    <?php if (empty($announcements)): ?>
                        <div style="text-align: center; padding: 20px; color: var(--text2);">Chưa có thông báo nào.</div>
                    <?php else: foreach ($announcements as $ann): ?>
                        <div style="padding: 12px; border-bottom: 1px solid var(--border); margin-bottom: 10px; background: rgba(255,255,255,0.01); border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span class="badge" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); font-size: 10px;"><?= htmlspecialchars($ann['khoa'] ?: 'Chung') ?></span>
                                <span style="font-size: 11px; color: var(--text2);"><i class="fa-regular fa-clock"></i> <?= date('d/m/Y', strtotime($ann['ngay_dang'])) ?></span>
                            </div>
                            <h4 style="font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 4px; line-height: 1.4;"><?= htmlspecialchars($ann['tieu_de']) ?></h4>
                            <p style="font-size: 12px; color: var(--text2); line-height: 1.5; margin: 0;"><?= htmlspecialchars(mb_strimwidth(strip_tags($ann['noi_dung']), 0, 100, "...")) ?></p>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    </div> <!-- content-pad -->
    </div> <!-- main-content -->
</body>
</html>
