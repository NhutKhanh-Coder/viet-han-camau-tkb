<?php
require_once '../config.php';
requireAdmin();
$db = getDB();
$msg = '';

// Handle Actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'rename') {
    $old_lop = trim($_POST['old_lop'] ?? '');
    $new_lop = trim($_POST['new_lop'] ?? '');
    if ($old_lop !== '' && $new_lop !== '') {
        $stmt = $db->prepare("UPDATE students SET lop = ? WHERE lop = ?");
        $stmt->bind_param("ss", $new_lop, $old_lop);
        if ($stmt->execute()) {
            $msg = "success:Đã đổi tên lớp từ '" . htmlspecialchars($old_lop) . "' sang '" . htmlspecialchars($new_lop) . "' thành công!";
            writeSystemLog("Rename class from $old_lop to $new_lop");
        } else {
            $msg = "error:Lỗi đổi tên lớp: " . $db->error;
        }
    } else {
        $msg = "error:Tên lớp không được để trống.";
    }
} elseif ($action === 'delete') {
    $lop = trim($_GET['lop'] ?? '');
    if ($lop !== '') {
        // Just empty the 'lop' column for students in this class instead of deleting students, or ask to delete. Let's empty it to be safe.
        $stmt = $db->prepare("UPDATE students SET lop = '' WHERE lop = ?");
        $stmt->bind_param("s", $lop);
        if ($stmt->execute()) {
            $msg = "success:Đã giải tán lớp '" . htmlspecialchars($lop) . "' thành công (Sinh viên đã được đưa về trạng thái tự do).";
            writeSystemLog("Disband class $lop");
        } else {
            $msg = "error:Lỗi giải tán lớp: " . $db->error;
        }
    }
} elseif ($action === 'reassign') {
    $sid = (int)$_POST['student_id'];
    $new_lop = trim($_POST['new_lop'] ?? '');
    if ($sid) {
        $stmt = $db->prepare("UPDATE students SET lop = ? WHERE id = ?");
        $stmt->bind_param("si", $new_lop, $sid);
        if ($stmt->execute()) {
            $msg = "success:Đã chuyển lớp sinh viên thành công!";
        } else {
            $msg = "error:Lỗi chuyển lớp: " . $db->error;
        }
    }
}

// Fetch all unique classes and student counts
$res_classes = $db->query("SELECT lop, COUNT(id) as total_students, khoa FROM students WHERE lop IS NOT NULL AND lop != '' GROUP BY lop, khoa ORDER BY lop");
$classes = $res_classes->fetch_all(MYSQLI_ASSOC);

// Selected class
$selected_lop = $_GET['lop'] ?? '';
$students = [];
if ($selected_lop) {
    $stmt = $db->prepare("SELECT id, ma_sv, ho_ten, email, sdt, khoa FROM students WHERE lop = ? ORDER BY ho_ten");
    $stmt->bind_param("s", $selected_lop);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch all students without a class for reassignment dropdown
$res_free_sv = $db->query("SELECT id, ma_sv, ho_ten FROM students WHERE lop IS NULL OR lop = '' ORDER BY ho_ten");
$free_students = $res_free_sv->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Lớp học - Hệ Thống Quản Trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .class-sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .class-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: #0f172a;
            transition: all 0.2s ease;
        }
        .class-item:hover, .class-item.active {
            border-color: #e11d48;
            background: #fff1f2;
            transform: translateX(4px);
        }
        .class-item.active strong {
            color: #e11d48;
        }
    </style>
</head>
<body class="admin-portal">
    <?php include '../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-graduation-cap"></i> Quản Lý Lớp Học</h1>
                <p class="page-sub">Xem danh sách lớp, sửa đổi thông tin lớp học và chuyển lớp sinh viên</p>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($msg): 
            $parts = explode(':', $msg);
            $type = $parts[0];
            $text = $parts[1];
        ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>">
                <i class="fa-solid <?= $type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <?= htmlspecialchars($text) ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px; align-items: start;">
            <!-- Left: Class List -->
            <div class="class-sidebar">
                <div class="card">
                    <div class="card-head">
                        <span class="card-title"><i class="fa-solid fa-list-ul"></i> Danh sách Lớp (<?= count($classes) ?>)</span>
                    </div>
                    <div class="card-body" style="padding: 16px; display:flex; flex-direction:column; gap:8px;">
                        <?php if (empty($classes)): ?>
                            <p style="text-align: center; color: #94a3b8; padding: 20px 0;">Chưa có lớp học nào được tạo.</p>
                        <?php else: foreach ($classes as $cl): ?>
                            <div>
                                <a href="?lop=<?= urlencode($cl['lop']) ?>" class="class-item <?= $selected_lop === $cl['lop'] ? 'active' : '' ?>">
                                    <div>
                                        <strong style="font-size: 14px;"><?= htmlspecialchars($cl['lop']) ?></strong>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($cl['khoa'] ?: 'Chưa phân khoa') ?></div>
                                    </div>
                                    <span style="background:#eff6ff; color:#2563eb; font-weight:700; font-size:11.5px; padding:3px 8px; border-radius:6px; border:1px solid #dbeafe;"><?= $cl['total_students'] ?> SV</span>
                                </a>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-head">
                        <span class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> Thao tác nhanh</span>
                    </div>
                    <div class="card-body" style="padding: 16px;">
                        <button type="button" class="btn btn-primary" style="width: 100%; justify-content:center;" onclick="showAddStudentModal()"><i class="fa-solid fa-plus"></i> Thêm SV vào lớp học</button>
                    </div>
                </div>
            </div>

            <!-- Right: Class Details -->
            <div>
                <?php if (!$selected_lop): ?>
                    <div class="card" style="padding: 60px 20px; text-align: center; color: #64748b;">
                        <i class="fa-solid fa-arrow-left" style="font-size: 36px; color: #e11d48; margin-bottom: 16px; display: block;"></i>
                        <p style="font-size:15px; font-weight:600; color:#0f172a; margin-bottom:6px;">Chưa chọn lớp học</p>
                        <p style="font-size:13px; color:#64748b;">Vui lòng chọn một lớp học ở danh sách bên trái để xem danh sách sinh viên và quản trị lớp học.</p>
                    </div>
                <?php else: ?>
                    <!-- Rename & Disband Card -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-head" style="display:flex; justify-content:space-between; align-items:center;">
                            <span class="card-title"><i class="fa-solid fa-gear"></i> Thiết lập Lớp: <?= htmlspecialchars($selected_lop) ?></span>
                            <a href="?action=delete&lop=<?= urlencode($selected_lop) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn giải tán lớp này? Tất cả sinh viên trong lớp sẽ được chuyển về lớp trống.')">
                                <i class="fa-solid fa-trash-can"></i> Giải tán lớp
                            </a>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="?action=rename" style="display: flex; gap: 14px; align-items: flex-end;">
                                <input type="hidden" name="old_lop" value="<?= htmlspecialchars($selected_lop) ?>">
                                <div style="flex: 1;">
                                    <label class="form-label">Đổi tên lớp học</label>
                                    <input type="text" name="new_lop" class="form-control" placeholder="Nhập tên lớp mới..." value="<?= htmlspecialchars($selected_lop) ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu tên mới</button>
                            </form>
                        </div>
                    </div>

                    <!-- Student List Card -->
                    <div class="card">
                        <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="card-title"><i class="fa-solid fa-users"></i> Danh sách Sinh viên (<?= count($students) ?>)</span>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($students)): ?>
                                <p style="text-align: center; color: #94a3b8; padding: 40px;">Chưa có sinh viên nào trong lớp này.</p>
                            <?php else: ?>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <th style="width: 140px;">Mã SV</th>
                                            <th style="min-width: 200px;">Họ tên</th>
                                            <th style="width: 220px;">Email</th>
                                            <th style="width: 140px;">Số điện thoại</th>
                                            <th style="width: 120px; text-align: center;">Chuyển lớp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $s): ?>
                                            <tr>
                                                <td>
                                                    <span style="display:inline-block; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:12.5px; font-weight:700; background:#f8fafc; color:#0f172a; padding:4px 9px; border-radius:6px; border:1px solid #e2e8f0; white-space:nowrap;">
                                                        <?= htmlspecialchars($s['ma_sv']) ?>
                                                    </span>
                                                </td>
                                                <td style="font-weight: 700; color: #0f172a; white-space:nowrap;"><?= htmlspecialchars($s['ho_ten']) ?></td>
                                                <td style="color: #64748b; font-size:13px;"><?= htmlspecialchars($s['email'] ?: '-') ?></td>
                                                <td style="color: #64748b; font-size:13px;"><?= htmlspecialchars($s['sdt'] ?: '-') ?></td>
                                                <td style="text-align: center;">
                                                    <button type="button" class="btn btn-ghost btn-sm" onclick="openReassignModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['ho_ten']) ?>', '<?= htmlspecialchars($selected_lop) ?>')">
                                                        <i class="fa-solid fa-arrow-right-arrow-left" style="color:#2563eb;"></i> Chuyển
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- 1. Add Student to Class Modal -->
    <div class="modal-overlay" id="addStudentModal">
        <div class="modal-box" style="width: 500px; max-width: 95vw;">
            <div class="modal-title"><i class="fa-solid fa-user-plus" style="color:#e11d48;"></i> Thêm SV vào Lớp học</div>
            <div class="modal-sub">Gán sinh viên tự do chưa có lớp vào lớp <strong><?= htmlspecialchars($selected_lop ?: 'được chọn') ?></strong></div>
            <form method="POST" action="?action=reassign">
                <div class="form-group">
                    <label class="form-label">Chọn sinh viên tự do *</label>
                    <select name="student_id" class="form-select" required style="cursor: pointer;">
                        <option value="">-- Chọn sinh viên --</option>
                        <?php foreach ($free_students as $fs): ?>
                            <option value="<?= $fs['id'] ?>"><?= htmlspecialchars($fs['ho_ten']) ?> (<?= htmlspecialchars($fs['ma_sv']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Chỉ định Lớp học *</label>
                    <input type="text" name="new_lop" class="form-control" placeholder="Ví dụ: CNTT24A..." value="<?= htmlspecialchars($selected_lop) ?>" required>
                </div>
                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                    <button type="button" class="btn btn-ghost" onclick="toggleModal('addStudentModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-circle-check"></i> Xác nhận thêm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Reassign Student Modal -->
    <div class="modal-overlay" id="reassignModal">
        <div class="modal-box" style="width: 500px; max-width: 95vw;">
            <div class="modal-title"><i class="fa-solid fa-arrow-right-arrow-left" style="color:#e11d48;"></i> Chuyển lớp sinh viên</div>
            <div class="modal-sub">Thay đổi lớp học cho sinh viên được chọn</div>
            <form method="POST" action="?action=reassign">
                <input type="hidden" name="student_id" id="reassign_student_id">
                <div class="form-group">
                    <label class="form-label">Sinh viên</label>
                    <input type="text" id="reassign_student_name" class="form-control" readonly style="background: #f1f5f9; color:#475569;">
                </div>
                <div class="form-group">
                    <label class="form-label">Lớp hiện tại</label>
                    <input type="text" id="reassign_student_old_lop" class="form-control" readonly style="background: #f1f5f9; color:#475569;">
                </div>
                <div class="form-group">
                    <label class="form-label">Lớp đích cần chuyển đến *</label>
                    <input type="text" name="new_lop" class="form-control" placeholder="Nhập tên lớp đích (Ví dụ: CNTT24A)" required>
                </div>
                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                    <button type="button" class="btn btn-ghost" onclick="toggleModal('reassignModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-right-left"></i> Chuyển lớp ngay</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.toggle('show');
        }
        function showAddStudentModal() {
            document.getElementById('addStudentModal').classList.add('show');
        }
        function openReassignModal(sid, name, oldLop) {
            document.getElementById('reassign_student_id').value = sid;
            document.getElementById('reassign_student_name').value = name;
            document.getElementById('reassign_student_old_lop').value = oldLop;
            document.getElementById('reassignModal').classList.add('show');
        }
    </script>
</body>
</html>
