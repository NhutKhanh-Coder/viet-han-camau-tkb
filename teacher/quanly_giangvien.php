<?php
require_once '../config.php';
requireTeacher();
$db = getDB();
$msg = '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
global $NGANH_LIST;

if ($action === 'add') {
    $ma_gv = trim($_POST['ma_gv'] ?? '');
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $khoa = trim($_POST['khoa'] ?? '');
    if ($ma_gv && $ho_ten) {
        $pw_hash = password_hash('123456', PASSWORD_DEFAULT);
        $db->begin_transaction();
        try {
            $check = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $ma_gv);
            $check->execute();
            if ($check->get_result()->num_rows > 0) throw new Exception("Mã giảng viên đã tồn tại!");
            $check->close();
            $insUser = $db->prepare("INSERT INTO users (username,password,role,ho_ten) VALUES (?,?,'teacher',?)");
            $insUser->bind_param("sss", $ma_gv, $pw_hash, $ho_ten);
            $insUser->execute();
            $uid = $db->insert_id; $insUser->close();
            $insGv = $db->prepare("INSERT INTO giang_vien (ma_gv,ho_ten,khoa,user_id) VALUES (?,?,?,?)");
            $insGv->bind_param("sssi", $ma_gv, $ho_ten, $khoa, $uid);
            $insGv->execute(); $insGv->close();
            $db->commit();
            writeSystemLog("Thêm giảng viên mới: $ho_ten ($ma_gv)");
            $msg = "success:Thêm giảng viên thành công! Mật khẩu mặc định: 123456";
        } catch (Exception $e) { $db->rollback(); $msg = "error:Lỗi: " . $e->getMessage(); }
    }
} elseif ($action === 'edit') {
    $gv_id = (int)$_POST['id'];
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $khoa = trim($_POST['khoa'] ?? '');
    if ($gv_id && $ho_ten) {
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("UPDATE giang_vien SET ho_ten=?,khoa=? WHERE id=?");
            $stmt->bind_param("ssi", $ho_ten, $khoa, $gv_id);
            $stmt->execute();
            $stmt_u = $db->prepare("SELECT user_id FROM giang_vien WHERE id=?");
            $stmt_u->bind_param("i", $gv_id);
            $stmt_u->execute();
            $user_id = $stmt_u->get_result()->fetch_assoc()['user_id'] ?? 0;
            if ($user_id) {
                $stmt_up_u = $db->prepare("UPDATE users SET ho_ten=? WHERE id=?");
                $stmt_up_u->bind_param("si", $ho_ten, $user_id);
                $stmt_up_u->execute();
            }
            $db->commit();
            writeSystemLog("Cập nhật thông tin giảng viên ID: $gv_id");
            $msg = "success:Cập nhật thông tin giảng viên thành công!";
        } catch (Exception $e) { $db->rollback(); $msg = "error:Lỗi cập nhật: " . $e->getMessage(); }
    }
} elseif ($action === 'delete') {
    $gv_id = (int)$_GET['id'];
    $stmt_u = $db->prepare("SELECT user_id, ho_ten FROM giang_vien WHERE id=?");
    $stmt_u->bind_param("i", $gv_id);
    $stmt_u->execute();
    $gv = $stmt_u->get_result()->fetch_assoc();
    if ($gv) {
        $uid = $gv['user_id'];
        $name = $gv['ho_ten'];
        $db->begin_transaction();
        try {
            $db->query("DELETE FROM giang_vien WHERE id=$gv_id");
            if ($uid) $db->query("DELETE FROM users WHERE id=$uid");
            $db->commit();
            writeSystemLog("Xóa giảng viên: $name");
            $msg = "success:Đã xóa giảng viên thành công.";
        } catch (Exception $e) { $db->rollback(); $msg = "error:Lỗi xóa: " . $e->getMessage(); }
    }
} elseif ($action === 'assign') {
    $gv_id = (int)$_POST['id'];
    $subject_ids = $_POST['subjects'] ?? [];
    if ($gv_id) {
        $db->begin_transaction();
        try {
            $stmt_t = $db->prepare("SELECT khoa FROM giang_vien WHERE id=?");
            $stmt_t->bind_param("i", $gv_id);
            $stmt_t->execute();
            $teacher_khoa = $stmt_t->get_result()->fetch_assoc()['khoa'] ?? 'Công Nghệ Thông Tin';
            $db->query("DELETE FROM thoi_khoa_bieu WHERE giang_vien_id=$gv_id");
            if (!empty($subject_ids)) {
                $stmt_ins = $db->prepare("INSERT INTO thoi_khoa_bieu (mon_hoc_id,giang_vien_id,khoa,thu,tiet_bat_dau,tiet_ket_thuc,phong_hoc,hoc_ky,nam_hoc) VALUES (?,?,?,2,1,4,'302','HK1','2026-2027')");
                foreach ($subject_ids as $sub_id) {
                    $sub_id = (int)$sub_id;
                    $stmt_s = $db->prepare("SELECT khoa FROM mon_hoc WHERE id=?");
                    $stmt_s->bind_param("i", $sub_id);
                    $stmt_s->execute();
                    $sub_khoa = $stmt_s->get_result()->fetch_assoc()['khoa'] ?? $teacher_khoa;
                    if (empty($sub_khoa)) $sub_khoa = $teacher_khoa;
                    $stmt_ins->bind_param("iis", $sub_id, $gv_id, $sub_khoa);
                    $stmt_ins->execute();
                }
            }
            $db->commit();
            writeSystemLog("Phân công môn học cho giảng viên ID: $gv_id");
            $msg = "success:Phân công môn học thành công!";
        } catch (Exception $e) { $db->rollback(); $msg = "error:Lỗi phân công: " . $e->getMessage(); }
    }
}

$search = trim($_GET['search'] ?? '');
$khoa_filter = trim($_GET['khoa'] ?? '');
$query = "SELECT * FROM giang_vien WHERE 1=1";
$params = []; $types = "";
if ($search) {
    $query .= " AND (ma_gv LIKE ? OR ho_ten LIKE ?)";
    $s = "%$search%"; $params[] = $s; $params[] = $s; $types .= "ss";
}
if ($khoa_filter) {
    $query .= " AND khoa = ?";
    $params[] = $khoa_filter; $types .= "s";
}
$query .= " ORDER BY ho_ten ASC";
$stmt = $db->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$teachers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$editRow = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $editRow = $db->query("SELECT * FROM giang_vien WHERE id=$eid")->fetch_assoc();
}
$assignRow = null;
$assigned_subject_ids = [];
$all_subjects = [];
if (isset($_GET['assign_id'])) {
    $aid = (int)$_GET['assign_id'];
    $assignRow = $db->query("SELECT * FROM giang_vien WHERE id=$aid")->fetch_assoc();
    if ($assignRow) {
        $res = $db->query("SELECT mon_hoc_id FROM thoi_khoa_bieu WHERE giang_vien_id=$aid");
        while ($row = $res->fetch_assoc()) $assigned_subject_ids[] = (int)$row['mon_hoc_id'];
        $all_subjects = $db->query("SELECT id,ma_mon,ten_mon,khoa FROM mon_hoc ORDER BY ten_mon")->fetch_all(MYSQLI_ASSOC);
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Giảng Viên - Giảng viên</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
.form-control { background:var(--bg3); border:1px solid var(--border); color:var(--text); padding:10px 15px; border-radius:8px; width:100%; font-size:14px; outline:none; }
</style>
</head>
<body>
<?php include '../includes/teacher_nav.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-chalkboard-user" style="color:var(--accent)"></i> Quản lý Giảng Viên</h1>
        <p style="color:var(--text2); margin-top:5px;">Quản lý thông tin, khoa ngành phụ trách và phân công môn học của Giảng viên</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('show')"><i class="fa-solid fa-user-plus"></i> Thêm giảng viên</button>
</div>

<?php if ($msg):
    $parts = explode(':', $msg);
    $type = $parts[0]; $text = $parts[1];
?>
<div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="display:block; margin-bottom:20px;"><?= htmlspecialchars($text) ?></div>
<?php endif; ?>

<form method="GET" style="display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap;">
    <input type="text" name="search" placeholder="Tìm theo tên hoặc mã GV..." class="form-control" style="flex:1; min-width:200px;" value="<?= htmlspecialchars($search) ?>">
    <select name="khoa" class="form-control" style="width:250px; cursor:pointer;" onchange="this.form.submit()">
        <option value="">-- Tất cả các khoa --</option>
        <?php foreach ($NGANH_LIST as $ng): ?>
            <option value="<?= htmlspecialchars($ng) ?>" <?= $khoa_filter === $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Lọc</button>
</form>

<div class="card">
    <div class="card-head">
        <span class="card-title"><i class="fa-solid fa-users"></i> Danh sách giảng viên (<?= count($teachers) ?>)</span>
    </div>
    <div class="card-body" style="padding:0;">
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr>
                <th style="padding:15px;">Mã Giảng Viên</th>
                <th style="padding:15px;">Họ tên giảng viên</th>
                <th style="padding:15px;">Khoa công tác</th>
                <th style="padding:15px; text-align:center;">Thao tác</th>
            </tr></thead>
            <tbody>
            <?php if (empty($teachers)): ?>
                <tr><td colspan="4" style="text-align:center; color:var(--text2); padding:30px;">Không tìm thấy giảng viên nào.</td></tr>
            <?php else: foreach ($teachers as $tc): ?>
                <tr>
                    <td style="padding:15px; font-weight:700; color:var(--accent);"><?= htmlspecialchars($tc['ma_gv']) ?></td>
                    <td style="padding:15px; font-weight:600; color:var(--text);"><?= htmlspecialchars($tc['ho_ten']) ?></td>
                    <td style="padding:15px; color:var(--text2);">
                        <span class="badge" style="background:rgba(56,189,248,0.1); color:#38bdf8; border:1px solid rgba(56,189,248,0.2); font-weight:700;">
                            <?= htmlspecialchars($tc['khoa'] ?: 'Chưa cập nhật') ?>
                        </span>
                    </td>
                    <td style="padding:15px; text-align:center; display:flex; gap:6px; justify-content:center;">
                        <a href="?assign_id=<?= $tc['id'] ?>&search=<?= urlencode($search) ?>&khoa=<?= urlencode($khoa_filter) ?>" class="btn-ghost" style="padding:5px 8px; border-radius:6px; font-size:11px; border-color:var(--accent); color:var(--accent);"><i class="fa-solid fa-book"></i> Phân công</a>
                        <a href="?edit_id=<?= $tc['id'] ?>&search=<?= urlencode($search) ?>&khoa=<?= urlencode($khoa_filter) ?>" class="btn-ghost" style="padding:5px 8px; border-radius:6px; font-size:11px;"><i class="fa-solid fa-pen"></i> Sửa</a>
                        <a href="?action=delete&id=<?= $tc['id'] ?>" onclick="return confirm('Xóa giảng viên này? Mọi thông tin liên quan sẽ bị xóa!')" class="btn-ghost" style="color:#ef4444; border-color:rgba(239,68,68,0.2); padding:5px 8px; border-radius:6px; font-size:11px;"><i class="fa-solid fa-trash"></i> Xóa</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm -->
<div class="modal-overlay" id="addModal" onclick="if(event.target==this) document.getElementById('addModal').classList.remove('show')">
    <div class="modal-box">
        <div class="modal-title"><i class="fa-solid fa-user-plus" style="color:var(--accent)"></i> Thêm giảng viên mới</div>
        <div class="modal-sub">Mật khẩu mặc định: <strong>123456</strong></div>
        <form method="POST" action="quanly_giangvien.php">
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label class="form-label">Mã Giảng Viên *</label><input type="text" name="ma_gv" class="form-control" placeholder="Ví dụ: gv005" required></div>
            <div class="form-group"><label class="form-label">Họ và tên giảng viên *</label><input type="text" name="ho_ten" class="form-control" placeholder="Ví dụ: Trần Văn B" required></div>
            <div class="form-group"><label class="form-label">Khoa phụ trách</label>
                <select name="khoa" class="form-control" required style="cursor:pointer;">
                    <?php foreach ($NGANH_LIST as $ng): ?>
                        <option value="<?= htmlspecialchars($ng) ?>"><?= htmlspecialchars($ng) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addModal').classList.remove('show')">Hủy</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Tạo tài khoản</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa -->
<?php if ($editRow): ?>
<div class="modal-overlay show" id="editModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fa-solid fa-pen-to-square" style="color:var(--accent)"></i> Cập nhật thông tin giảng viên</div>
        <form method="POST" action="quanly_giangvien.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
            <div class="form-group"><label class="form-label">Mã Giảng Viên</label><input type="text" class="form-control" value="<?= htmlspecialchars($editRow['ma_gv']) ?>" disabled style="opacity:0.6;"></div>
            <div class="form-group"><label class="form-label">Họ và tên giảng viên *</label><input type="text" name="ho_ten" class="form-control" value="<?= htmlspecialchars($editRow['ho_ten']) ?>" required></div>
            <div class="form-group"><label class="form-label">Khoa phụ trách</label>
                <select name="khoa" class="form-control" required style="cursor:pointer;">
                    <?php foreach ($NGANH_LIST as $ng): ?>
                        <option value="<?= htmlspecialchars($ng) ?>" <?= $editRow['khoa'] === $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <a href="quanly_giangvien.php" class="btn btn-ghost" style="text-decoration:none;">Hủy</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal Phân Công Môn Học -->
<?php if ($assignRow): ?>
<div class="modal-overlay show" id="assignModal">
    <div class="modal-box" style="width:500px; max-width:95vw;">
        <div class="modal-title"><i class="fa-solid fa-book" style="color:var(--accent)"></i> Phân công môn học giảng dạy</div>
        <div class="modal-sub">Giảng viên: <strong><?= htmlspecialchars($assignRow['ho_ten']) ?></strong> (<?= htmlspecialchars($assignRow['ma_gv']) ?>)</div>
        <form method="POST" action="quanly_giangvien.php">
            <input type="hidden" name="action" value="assign">
            <input type="hidden" name="id" value="<?= $assignRow['id'] ?>">
            <div class="form-group" style="max-height:300px; overflow-y:auto; border:1px solid var(--border); padding:15px; border-radius:8px; margin-top:15px; background:var(--bg3);">
                <label class="form-label" style="margin-bottom:10px; display:block; font-weight:700;">Chọn các môn học giảng dạy:</label>
                <?php if (empty($all_subjects)): ?>
                    <p style="color:var(--text2); text-align:center;">Chưa có môn học nào trong hệ thống.</p>
                <?php else: foreach ($all_subjects as $sub):
                    $is_checked = in_array((int)$sub['id'], $assigned_subject_ids, true); ?>
                    <label style="display:flex; align-items:flex-start; gap:10px; padding:10px 0; border-bottom:1px solid var(--border); cursor:pointer; font-size:14px; margin:0;">
                        <input type="checkbox" name="subjects[]" value="<?= $sub['id'] ?>" <?= $is_checked ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--accent); margin-top:3px; cursor:pointer;">
                        <div style="flex:1;">
                            <strong style="color:var(--text); font-size:14px;"><?= htmlspecialchars($sub['ten_mon']) ?></strong>
                            <span style="font-size:11.5px; color:var(--text2); display:block; margin-top:3px;">Mã: <code style="color:var(--accent);"><?= htmlspecialchars($sub['ma_mon']) ?></code> &bull; Khoa: <?= htmlspecialchars($sub['khoa'] ?: 'Dùng chung') ?></span>
                        </div>
                    </label>
                <?php endforeach; endif; ?>
            </div>
            <div class="modal-footer" style="margin-top:24px;">
                <a href="quanly_giangvien.php" class="btn btn-ghost" style="text-decoration:none;">Hủy</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu phân công</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</body>
</html>
