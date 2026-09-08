<?php
require_once '../config.php';
requireTeacher();
$db = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;

// Fetch teacher department
$st_t = $db->prepare("SELECT khoa FROM giang_vien WHERE id = ?");
$st_t->bind_param("i", $gv_id);
$st_t->execute();
$t_row = $st_t->get_result()->fetch_assoc();
$teacher_khoa = $t_row['khoa'] ?? '';

// Fetch all classes of this department
$classes = [];
if ($teacher_khoa) {
    $stmt = $db->prepare("SELECT lop, COUNT(id) as total_students FROM students WHERE LOWER(khoa) = LOWER(?) GROUP BY lop ORDER BY lop");
    $stmt->bind_param("s", $teacher_khoa);
    $stmt->execute();
    $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $res = $db->query("SELECT lop, COUNT(id) as total_students FROM students GROUP BY lop ORDER BY lop");
    $classes = $res->fetch_all(MYSQLI_ASSOC);
}

$selected_lop = $_GET['lop'] ?? '';
$students = [];
if ($selected_lop) {
    if ($teacher_khoa) {
        $stmt = $db->prepare("SELECT id, ma_sv, ho_ten, email, sdt, avatar FROM students WHERE lop = ? AND LOWER(khoa) = LOWER(?) ORDER BY ho_ten");
        $stmt->bind_param("ss", $selected_lop, $teacher_khoa);
    } else {
        $stmt = $db->prepare("SELECT id, ma_sv, ho_ten, email, sdt, avatar FROM students WHERE lop = ? ORDER BY ho_ten");
        $stmt->bind_param("s", $selected_lop);
    }
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Lớp học - Giảng viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
    <?php include '../includes/teacher_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-graduation-cap" style="color:var(--accent)"></i> Quản Lý Lớp Học</h1>
            <p style="color: var(--text2); margin-top: 5px;">Khoa phụ trách: <span class="badge" style="background:rgba(56,189,248,0.12); color:#38bdf8; border:1px solid rgba(56,189,248,0.2)"><?= htmlspecialchars($teacher_khoa ?: 'Tất cả khoa') ?></span></p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
        <!-- Classes list -->
        <div class="card">
            <div class="card-head">
                <span class="card-title"><i class="fa-solid fa-list-ul"></i> Danh sách lớp học</span>
            </div>
            <div class="card-body" style="padding: 10px 0;">
                <?php if (empty($classes)): ?>
                    <p style="text-align: center; color: var(--text2); padding: 20px;">Không tìm thấy lớp học nào.</p>
                <?php else: foreach ($classes as $cl): ?>
                    <a href="?lop=<?= urlencode($cl['lop']) ?>" class="list-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid var(--border); text-decoration: none; color: var(--text); background: <?= $selected_lop === $cl['lop'] ? 'rgba(255,255,255,0.04)' : 'transparent' ?>; border-left: 3px solid <?= $selected_lop === $cl['lop'] ? 'var(--accent)' : 'transparent' ?>; transition: 0.15s;">
                        <span style="font-weight: 700; font-size: 14px;"><?= htmlspecialchars($cl['lop']) ?></span>
                        <span class="badge" style="background: rgba(16,185,129,0.1); color:#10b981; font-weight:600; border: 1px solid rgba(16,185,129,0.2)"><?= $cl['total_students'] ?> SV</span>
                    </a>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Class Details -->
        <div class="card">
            <div class="card-head">
                <span class="card-title"><i class="fa-solid fa-circle-info"></i> <?= $selected_lop ? "Chi tiết lớp: " . htmlspecialchars($selected_lop) : "Chọn một lớp để xem chi tiết" ?></span>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (!$selected_lop): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text2);">
                        <i class="fa-solid fa-arrow-left" style="font-size: 32px; margin-bottom: 15px; display: block; color: var(--accent);"></i>
                        Vui lòng chọn một lớp bên trái để xem danh sách sinh viên.
                    </div>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 15px;">Mã SV</th>
                                <th style="padding: 15px;">Họ tên</th>
                                <th style="padding: 15px;">Email</th>
                                <th style="padding: 15px;">Số điện thoại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text2); padding: 30px;">
                                        Không tìm thấy sinh viên nào trong lớp này.
                                    </td>
                                </tr>
                            <?php else: foreach ($students as $st): ?>
                                <tr>
                                    <td style="padding: 15px; font-weight: 700; color: var(--accent);"><?= htmlspecialchars($st['ma_sv']) ?></td>
                                    <td style="padding: 15px; font-weight: 600; color: var(--text);">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php 
                                                $st_av = !empty($st['avatar']) ? '/tkb/assets/img/avatars/' . htmlspecialchars($st['avatar']) : '/tkb/assets/img/logo_vkc.jpg';
                                            ?>
                                            <img src="<?= $st_av ?>" alt="avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--accent); flex-shrink: 0;">
                                            <?= htmlspecialchars($st['ho_ten']) ?>
                                        </div>
                                    </td>
                                    <td style="padding: 15px; color: var(--text2); font-size: 13px;"><?= htmlspecialchars($st['email'] ?: 'Chưa cập nhật') ?></td>
                                    <td style="padding: 15px; color: var(--text2); font-size: 13px;"><?= htmlspecialchars($st['sdt'] ?: 'Chưa cập nhật') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    </div> <!-- content-pad -->
    </div> <!-- main-content -->
</body>
</html>
