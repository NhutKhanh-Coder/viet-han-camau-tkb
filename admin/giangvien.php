<?php
require_once '../config.php';
requireAdmin();
$db = getDB();
$msg = '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $ma_gv = trim($_POST['ma_gv'] ?? '');
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $khoa = trim($_POST['khoa'] ?? '');
    
    if ($ma_gv && $ho_ten) {
        $pw_hash = password_hash('123456', PASSWORD_DEFAULT);
        
        $db->begin_transaction();
        try {
            // Check username uniqueness
            $check = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $ma_gv);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Mã giảng viên (Tên đăng nhập) đã tồn tại!");
            }
            $check->close();
            
            // Create user
            $insUser = $db->prepare("INSERT INTO users (username, password, role, ho_ten) VALUES (?, ?, 'teacher', ?)");
            $insUser->bind_param("sss", $ma_gv, $pw_hash, $ho_ten);
            $insUser->execute();
            $uid = $db->insert_id;
            $insUser->close();
            
            // Create giang_vien
            $insGv = $db->prepare("INSERT INTO giang_vien (ma_gv, ho_ten, khoa, user_id) VALUES (?, ?, ?, ?)");
            $insGv->bind_param("sssi", $ma_gv, $ho_ten, $khoa, $uid);
            $insGv->execute();
            $insGv->close();
            
            $db->commit();
            writeSystemLog("Thêm giảng viên mới: $ho_ten ($ma_gv)");
            $msg = "success:Thêm giảng viên mới thành công! Mật khẩu đăng nhập mặc định: 123456";
        } catch (Exception $e) {
            $db->rollback();
            $msg = "error:Lỗi: " . $e->getMessage();
        }
    }
} elseif ($action === 'edit') {
    $gv_id = (int)$_POST['id'];
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $khoa = trim($_POST['khoa'] ?? '');
    
    if ($gv_id && $ho_ten) {
        $db->begin_transaction();
        try {
            // Update giang_vien
            $stmt = $db->prepare("UPDATE giang_vien SET ho_ten = ?, khoa = ? WHERE id = ?");
            $stmt->bind_param("ssi", $ho_ten, $khoa, $gv_id);
            $stmt->execute();
            
            // Fetch user_id
            $stmt_u = $db->prepare("SELECT user_id FROM giang_vien WHERE id = ?");
            $stmt_u->bind_param("i", $gv_id);
            $stmt_u->execute();
            $user_id = $stmt_u->get_result()->fetch_assoc()['user_id'] ?? 0;
            
            if ($user_id) {
                // Update users
                $stmt_up_u = $db->prepare("UPDATE users SET ho_ten = ? WHERE id = ?");
                $stmt_up_u->bind_param("si", $ho_ten, $user_id);
                $stmt_up_u->execute();
            }
            
            $db->commit();
            writeSystemLog("Cập nhật thông tin giảng viên ID: $gv_id");
            $msg = "success:Cập nhật thông tin giảng viên thành công!";
        } catch (Exception $e) {
            $db->rollback();
            $msg = "error:Lỗi cập nhật: " . $e->getMessage();
        }
    }
} elseif ($action === 'reset_password') {
    $gv_id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $new_pass = trim($_POST['new_password'] ?? '');
    if (empty($new_pass)) $new_pass = '123456';
    
    $stmt_u = $db->prepare("SELECT g.user_id, g.ho_ten, g.ma_gv, u.username FROM giang_vien g LEFT JOIN users u ON g.user_id = u.id WHERE g.id = ?");
    $stmt_u->bind_param("i", $gv_id);
    $stmt_u->execute();
    $gv = $stmt_u->get_result()->fetch_assoc();
    
    if ($gv) {
        if ($gv['username'] === 'admin' || $gv['username'] === 'phanngoctuyen') {
            $msg = "error:Không có quyền đổi mật khẩu của tài khoản Quản trị viên!";
        } else {
            $uid = (int)$gv['user_id'];
            $pw_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            if ($uid > 0) {
                $db->query("UPDATE users SET password='$pw_hash' WHERE id=$uid");
            }
            writeSystemLog("Admin reset mật khẩu cho giảng viên {$gv['ho_ten']} ({$gv['ma_gv']})");
            $msg = "success:Đã reset mật khẩu cho giảng viên {$gv['ho_ten']} ({$gv['ma_gv']}) thành công! Mật khẩu mới: $new_pass";
        }
    } else {
        $msg = "error:Không tìm thấy thông tin giảng viên!";
    }
} elseif ($action === 'update_role_status') {
    $gv_id = (int)$_POST['id'];
    $chuc_vu = trim($_POST['chuc_vu'] ?? 'Giảng viên');
    $status = trim($_POST['status'] ?? 'active');
    if (!in_array($status, ['active', 'locked'])) $status = 'active';
    
    $stmt_u = $db->prepare("SELECT g.user_id, g.ho_ten, g.ma_gv, u.username FROM giang_vien g LEFT JOIN users u ON g.user_id = u.id WHERE g.id = ?");
    $stmt_u->bind_param("i", $gv_id);
    $stmt_u->execute();
    $gv = $stmt_u->get_result()->fetch_assoc();
    
    if ($gv) {
        if ($gv['username'] === 'admin' || $gv['username'] === 'phanngoctuyen') {
            $msg = "error:Không thể sửa vai trò của tài khoản Quản trị viên!";
        } else {
            $uid = (int)$gv['user_id'];
            $stmt_up = $db->prepare("UPDATE giang_vien SET chuc_vu = ? WHERE id = ?");
            $stmt_up->bind_param("si", $chuc_vu, $gv_id);
            $stmt_up->execute();
            if ($uid > 0) {
                $stmt_us = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt_us->bind_param("si", $status, $uid);
                $stmt_us->execute();
            }
            writeSystemLog("Admin cập nhật chức vụ/trạng thái cho giảng viên {$gv['ho_ten']}: $chuc_vu, $status");
            $msg = "success:Đã cập nhật chức vụ ($chuc_vu) và trạng thái tài khoản cho giảng viên {$gv['ho_ten']} thành công!";
        }
    } else {
        $msg = "error:Không tìm thấy thông tin giảng viên!";
    }
} elseif ($action === 'delete') {
    $gv_id = (int)$_GET['id'];
    
    $stmt_u = $db->prepare("SELECT g.user_id, g.ho_ten, u.username FROM giang_vien g LEFT JOIN users u ON g.user_id = u.id WHERE g.id = ?");
    $stmt_u->bind_param("i", $gv_id);
    $stmt_u->execute();
    $gv = $stmt_u->get_result()->fetch_assoc();
    
    if ($gv) {
        if ($gv['username'] === 'admin' || $gv['username'] === 'phanngoctuyen') {
            $msg = "error:Không thể xóa tài khoản Quản trị viên!";
        } else {
            $uid = $gv['user_id'];
            $name = $gv['ho_ten'];
            
            $db->begin_transaction();
            try {
                // Delete giang_vien
                $db->query("DELETE FROM giang_vien WHERE id = $gv_id");
                if ($uid) {
                    // Delete user
                    $db->query("DELETE FROM users WHERE id = $uid");
                }
                $db->commit();
                writeSystemLog("Xóa giảng viên: $name");
                $msg = "success:Đã xóa giảng viên khỏi hệ thống thành công.";
            } catch (Exception $e) {
                $db->rollback();
                $msg = "error:Lỗi xóa giảng viên: " . $e->getMessage();
            }
        }
    }
} elseif ($action === 'assign') {
    $gv_id = (int)$_POST['id'];
    $subject_ids = $_POST['subjects'] ?? [];
    
    if ($gv_id) {
        $db->begin_transaction();
        try {
            $stmt_t = $db->prepare("SELECT khoa FROM giang_vien WHERE id = ?");
            $stmt_t->bind_param("i", $gv_id);
            $stmt_t->execute();
            $teacher_khoa = $stmt_t->get_result()->fetch_assoc()['khoa'] ?? 'Công Nghệ Thông Tin';
            
            $db->query("DELETE FROM thoi_khoa_bieu WHERE giang_vien_id = $gv_id");
            
            if (!empty($subject_ids)) {
                $stmt_ins = $db->prepare("INSERT INTO thoi_khoa_bieu (mon_hoc_id, giang_vien_id, khoa, thu, tiet_bat_dau, tiet_ket_thuc, phong_hoc, hoc_ky, nam_hoc) VALUES (?, ?, ?, 2, 1, 4, '302', 'HK1', '2026-2027')");
                foreach ($subject_ids as $sub_id) {
                    $sub_id = (int)$sub_id;
                    $stmt_s = $db->prepare("SELECT khoa FROM mon_hoc WHERE id = ?");
                    $stmt_s->bind_param("i", $sub_id);
                    $stmt_s->execute();
                    $sub_khoa = $stmt_s->get_result()->fetch_assoc()['khoa'] ?? $teacher_khoa;
                    if (empty($sub_khoa)) {
                        $sub_khoa = $teacher_khoa;
                    }
                    $stmt_ins->bind_param("iis", $sub_id, $gv_id, $sub_khoa);
                    $stmt_ins->execute();
                }
            }
            
            $db->commit();
            writeSystemLog("Phân công môn học cho giảng viên ID: $gv_id");
            $msg = "success:Phân công môn học thành công!";
        } catch (Exception $e) {
            $db->rollback();
            $msg = "error:Lỗi phân công: " . $e->getMessage();
        }
    }
}

// Search & Filter
$search = trim($_GET['search'] ?? '');
$khoa_filter = trim($_GET['khoa'] ?? '');

$query = "SELECT g.*, COALESCE(u.status, 'active') as user_status, COALESCE(g.chuc_vu, 'Giảng viên') as chuc_vu, u.username 
          FROM giang_vien g 
          LEFT JOIN users u ON g.user_id = u.id 
          WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (g.ma_gv LIKE ? OR g.ho_ten LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if ($khoa_filter) {
    $query .= " AND g.khoa = ?";
    $params[] = $khoa_filter;
    $types .= "s";
}

$query .= " ORDER BY g.ho_ten ASC";
$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
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
        $res = $db->query("SELECT mon_hoc_id FROM thoi_khoa_bieu WHERE giang_vien_id = $aid");
        while ($row = $res->fetch_assoc()) {
            $assigned_subject_ids[] = (int)$row['mon_hoc_id'];
        }
        $all_subjects = $db->query("SELECT id, ma_mon, ten_mon, khoa FROM mon_hoc ORDER BY ten_mon")->fetch_all(MYSQLI_ASSOC);
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Giảng viên - Hệ Thống Quản Trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body class="admin-portal">
    <?php include '../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-chalkboard-user"></i> Quản lý Giảng Viên</h1>
                <p class="page-sub">Quản lý thông tin hồ sơ, phân công môn giảng dạy và tài khoản đăng nhập của Giảng viên</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('show')"><i class="fa-solid fa-user-plus"></i> Thêm giảng viên</button>
        </div>

        <!-- Feedback messages -->
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

        <!-- Search & Filter Form -->
        <div class="filter-bar">
            <form method="GET">
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Tìm theo tên hoặc mã GV..." class="search-input" value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="khoa" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả các khoa --</option>
                    <?php global $NGANH_LIST; foreach ($NGANH_LIST as $ng): ?>
                        <option value="<?= htmlspecialchars($ng) ?>" <?= $khoa_filter === $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-ghost"><i class="fa-solid fa-filter"></i> Lọc</button>
            </form>
            <span style="font-size:13px; font-weight:700; color:#475569; background:#f1f5f9; padding:6px 14px; border-radius:20px; border:1px solid #e2e8f0; white-space:nowrap;">
                Tổng: <b style="color:#e11d48;"><?= count($teachers) ?></b> giảng viên
            </span>
        </div>

        <div class="card">
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th style="width:140px;">Mã Giảng Viên</th>
                            <th>Họ tên giảng viên</th>
                            <th style="width:240px;">Khoa công tác</th>
                            <th style="width:190px;">Chức vụ &amp; Trạng thái</th>
                            <th style="width:290px; text-align:center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">Không tìm thấy giảng viên nào phù hợp.</td>
                            </tr>
                        <?php else: foreach ($teachers as $tc): 
                            $is_admin_acc = ($tc['username'] === 'admin' || $tc['username'] === 'phanngoctuyen');
                            $chuc_vu = $tc['chuc_vu'] ?? 'Giảng viên';
                            $status = $tc['user_status'] ?? 'active';
                        ?>
                            <tr>
                                <td>
                                    <span style="display:inline-block; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:12.5px; font-weight:700; background:#f8fafc; color:#0f172a; padding:4px 9px; border-radius:6px; border:1px solid #e2e8f0; white-space:nowrap;">
                                        <?= htmlspecialchars($tc['ma_gv']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color:#2563eb; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:12px; border:1px solid #bfdbfe; flex-shrink:0;">
                                            <i class="fa-solid fa-chalkboard-user"></i>
                                        </div>
                                        <div>
                                            <strong style="color: #0f172a; white-space:nowrap; display:block;"><?= htmlspecialchars($tc['ho_ten']) ?></strong>
                                            <?php if (!empty($tc['username'])): ?>
                                                <span style="font-size:11px; color:#64748b;">User: <?= htmlspecialchars($tc['username']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="display:inline-block; padding:4px 10px; border-radius:6px; background:#eff6ff; color:#2563eb; font-weight:700; font-size:12px; border:1px solid #dbeafe; white-space:nowrap;">
                                        <?= htmlspecialchars($tc['khoa'] ?: 'Chưa cập nhật') ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:4px;">
                                        <div>
                                            <?php if ($chuc_vu === 'Trưởng khoa'): ?>
                                                <span style="display:inline-block; font-size:11px; font-weight:800; background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:6px; border:1px solid #fde68a;">
                                                    <i class="fa-solid fa-crown"></i> Trưởng khoa
                                                </span>
                                            <?php elseif ($chuc_vu === 'Phó khoa'): ?>
                                                <span style="display:inline-block; font-size:11px; font-weight:800; background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:6px; border:1px solid #bae6fd;">
                                                    <i class="fa-solid fa-star"></i> Phó khoa
                                                </span>
                                            <?php elseif ($chuc_vu === 'Giảng viên chính'): ?>
                                                <span style="display:inline-block; font-size:11px; font-weight:800; background:#f3e8ff; color:#7e22ce; padding:2px 8px; border-radius:6px; border:1px solid #e9d5ff;">
                                                    <i class="fa-solid fa-award"></i> GV Chính
                                                </span>
                                            <?php else: ?>
                                                <span style="display:inline-block; font-size:11px; font-weight:700; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; border:1px solid #e2e8f0;">
                                                    <i class="fa-solid fa-chalkboard-user"></i> <?= htmlspecialchars($chuc_vu) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if ($status === 'locked'): ?>
                                                <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:800; color:#dc2626; background:#fef2f2; padding:2px 7px; border-radius:4px; border:1px solid #fecaca;">
                                                    <i class="fa-solid fa-lock"></i> Đã khóa
                                                </span>
                                            <?php else: ?>
                                                <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#16a34a; background:#f0fdf4; padding:2px 7px; border-radius:4px; border:1px solid #bbf7d0;">
                                                    <i class="fa-solid fa-circle-check"></i> Hoạt động
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display:inline-flex; align-items:center; gap:5px; justify-content:center; flex-wrap:wrap;">
                                        <?php if ($is_admin_acc): ?>
                                            <span style="font-size:11.5px; font-weight:700; color:#9333ea; background:#faf5ff; border:1px solid #f3e8ff; padding:4px 10px; border-radius:6px;">
                                                <i class="fa-solid fa-shield"></i> Quản trị viên
                                            </span>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-ghost btn-sm" style="color:#7c3aed; border-color:#ddd6fe; background:#f5f3ff !important;" onclick="openGvRoleModal(<?= $tc['id'] ?>, '<?= htmlspecialchars(addslashes($tc['ho_ten'])) ?>', '<?= htmlspecialchars(addslashes($tc['chuc_vu'])) ?>', '<?= htmlspecialchars(addslashes($tc['user_status'])) ?>')" title="Cấp quyền chức vụ & Trạng thái">
                                                <i class="fa-solid fa-user-shield"></i> Quyền
                                            </button>
                                            <button type="button" class="btn btn-ghost btn-sm" style="color:#d97706; border-color:#fde68a; background:#fffbeb !important;" onclick="openGvResetPassModal(<?= $tc['id'] ?>, '<?= htmlspecialchars(addslashes($tc['ho_ten'])) ?>', '<?= htmlspecialchars(addslashes($tc['ma_gv'])) ?>')" title="Reset mật khẩu mặc định">
                                                <i class="fa-solid fa-key"></i> Đổi MK
                                            </button>
                                            <a href="?assign_id=<?= $tc['id'] ?>&search=<?= urlencode($search) ?>&khoa=<?= urlencode($khoa_filter) ?>" class="btn btn-ghost btn-sm" style="color:#0284c7; border-color:#bae6fd; background:#f0f9ff !important;" title="Phân công môn"><i class="fa-solid fa-book-bookmark"></i> Phân công</a>
                                            <a href="?edit_id=<?= $tc['id'] ?>&search=<?= urlencode($search) ?>&khoa=<?= urlencode($khoa_filter) ?>" class="btn btn-edit btn-sm" title="Sửa"><i class="fa-solid fa-pen"></i></a>
                                            <a href="?action=delete&id=<?= $tc['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa giảng viên này? Mọi thông tin liên quan sẽ bị xóa!')" class="btn btn-danger btn-sm" title="Xóa"><i class="fa-solid fa-trash"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Thêm -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <div class="modal-title">Thêm giảng viên mới</div>
            <div class="modal-sub">Mật khẩu mặc định tự động khởi tạo là <strong>123456</strong></div>
            <form method="POST" action="giangvien.php">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label class="form-label">Mã Giảng Viên *</label>
                    <input type="text" name="ma_gv" class="form-control" placeholder="Ví dụ: gv005" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Họ và tên giảng viên *</label>
                    <input type="text" name="ho_ten" class="form-control" placeholder="Ví dụ: Trần Văn B" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Khoa phụ trách / Công tác</label>
                    <select name="khoa" class="form-control" required style="cursor:pointer;">
                        <?php foreach ($NGANH_LIST as $ng): ?>
                            <option value="<?= htmlspecialchars($ng) ?>"><?= htmlspecialchars($ng) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
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
            <div class="modal-title">Cập nhật thông tin giảng viên</div>
            <div class="modal-sub">Mã GV: <strong style="color:#0f172a;"><?= htmlspecialchars($editRow['ma_gv']) ?></strong></div>
            <form method="POST" action="giangvien.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
                
                <div class="form-group">
                    <label class="form-label">Họ và tên giảng viên *</label>
                    <input type="text" name="ho_ten" class="form-control" value="<?= htmlspecialchars($editRow['ho_ten']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Khoa phụ trách / Công tác</label>
                    <select name="khoa" class="form-control" required style="cursor:pointer;">
                        <?php foreach ($NGANH_LIST as $ng): ?>
                            <option value="<?= htmlspecialchars($ng) ?>" <?= $editRow['khoa'] === $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                    <a href="giangvien.php" class="btn btn-ghost">Hủy</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Phân Công Môn Học -->
    <?php if ($assignRow): ?>
    <div class="modal-overlay show" id="assignModal">
        <div class="modal-box" style="width: 540px; max-width: 95vw;">
            <div class="modal-title">Phân công môn học giảng dạy</div>
            <div class="modal-sub">Giảng viên: <strong style="color:#0f172a;"><?= htmlspecialchars($assignRow['ho_ten']) ?></strong> (<?= htmlspecialchars($assignRow['ma_gv']) ?>)</div>
            <form method="POST" action="giangvien.php">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="id" value="<?= $assignRow['id'] ?>">
                
                <div class="form-group" style="max-height: 320px; overflow-y: auto; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 12px; background: #f8fafc; text-align: left;">
                    <label class="form-label" style="margin-bottom: 10px; display: block; font-weight: 700;">Chọn các môn học phụ trách:</label>
                    <?php if (empty($all_subjects)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 20px 0;">Chưa có môn học nào trong hệ thống.</p>
                    <?php else: foreach ($all_subjects as $sub): 
                        $is_checked = in_array((int)$sub['id'], $assigned_subject_ids, true);
                    ?>
                        <label style="display: flex; align-items: flex-start; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; cursor: pointer; color: #0f172a; font-size: 13.5px; margin: 0;">
                            <input type="checkbox" name="subjects[]" value="<?= $sub['id'] ?>" <?= $is_checked ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #e11d48; margin-top: 3px; cursor: pointer;">
                            <div style="flex: 1;">
                                <strong style="color: #0f172a; font-size: 13.5px; font-weight: 700; display: block;"><?= htmlspecialchars($sub['ten_mon']) ?></strong> 
                                <span style="font-size: 12px; color: #64748b; display: block; margin-top: 2px;">Mã môn: <code style="color: #e11d48; background:#fff; padding:1px 6px; border-radius:4px; border:1px solid #e2e8f0;"><?= htmlspecialchars($sub['ma_mon']) ?></code> &bull; Khoa: <?= htmlspecialchars($sub['khoa'] ?: 'Dùng chung') ?></span>
                            </div>
                        </label>
                    <?php endforeach; endif; ?>
                </div>

                <div class="modal-footer" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 10px;">
                    <a href="giangvien.php" class="btn btn-ghost">Hủy</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu phân công</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Reset Mật Khẩu Giảng Viên -->
    <div class="modal-overlay" id="gvResetPassModal">
      <div class="modal-box" style="max-width:440px;">
        <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-key" style="color:#d97706;"></i> Reset Mật Khẩu Giảng Viên
        </div>
        <div class="modal-sub" id="gvResetPassSubTitle">Khôi phục mật khẩu đăng nhập cho Giảng viên</div>
        <form method="POST">
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="id" id="gvResetPassId" value="">
          
          <div class="form-group" style="margin-top:16px;">
            <label class="form-label">Mật khẩu mới</label>
            <div style="position:relative;">
              <input type="text" class="form-control" name="new_password" id="gvResetPassNewInput" value="123456" placeholder="Nhập mật khẩu mới..." required style="padding-right:75px; font-weight:700; font-family:monospace; font-size:14px; letter-spacing:1px;">
              <button type="button" onclick="document.getElementById('gvResetPassNewInput').value = '123456'" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; font-size:11px; padding:4px 8px; font-weight:700; cursor:pointer;">Mặc định</button>
            </div>
            <div style="font-size:11.5px; color:#64748b; margin-top:6px;">Mật khẩu mặc định khuyến nghị là <code>123456</code>.</div>
          </div>

          <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
            <button type="button" class="btn btn-ghost" onclick="toggleModal('gvResetPassModal')">Hủy</button>
            <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg, #d97706, #b45309); border:none;"><i class="fa-solid fa-check"></i> Xác nhận Reset</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Cấp Quyền & Trạng Thái Giảng Viên -->
    <div class="modal-overlay" id="gvRoleModal">
      <div class="modal-box" style="max-width:440px;">
        <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-user-shield" style="color:#7c3aed;"></i> Cấp Quyền &amp; Trạng Thái Giảng Viên
        </div>
        <div class="modal-sub" id="gvRoleSubTitle">Phân quyền chức vụ và trạng thái hoạt động tài khoản</div>
        <form method="POST">
          <input type="hidden" name="action" value="update_role_status">
          <input type="hidden" name="id" id="gvRoleId" value="">

          <div class="form-group" style="margin-top:16px;">
            <label class="form-label">Chức vụ / Học hàm phụ trách</label>
            <select class="form-control" name="chuc_vu" id="gvRoleSelect">
              <option value="Giảng viên">Giảng viên bộ môn</option>
              <option value="Giảng viên chính">Giảng viên chính</option>
              <option value="Phó khoa">Phó trưởng Khoa</option>
              <option value="Trưởng khoa">Trưởng Khoa</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Trạng thái tài khoản</label>
            <select class="form-control" name="status" id="gvStatusSelect">
              <option value="active">🟢 Đang hoạt động (Bình thường)</option>
              <option value="locked">🔴 Tạm khóa tài khoản (Không cho đăng nhập)</option>
            </select>
          </div>

          <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
            <button type="button" class="btn btn-ghost" onclick="toggleModal('gvRoleModal')">Hủy</button>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu Quyền Hạn</button>
          </div>
        </form>
      </div>
    </div>

    <script>
    function toggleModal(id) {
        var el = document.getElementById(id);
        if (el) {
            el.classList.toggle('show');
            el.classList.toggle('open');
            el.classList.toggle('active');
        }
    }

    function openGvResetPassModal(id, name, magv) {
        document.getElementById('gvResetPassId').value = id;
        document.getElementById('gvResetPassSubTitle').innerHTML = 'Reset mật khẩu cho: <strong style="color:#0f172a;">' + name + ' (' + magv + ')</strong>';
        document.getElementById('gvResetPassNewInput').value = '123456';
        toggleModal('gvResetPassModal');
    }

    function openGvRoleModal(id, name, currentRole, currentStatus) {
        document.getElementById('gvRoleId').value = id;
        document.getElementById('gvRoleSubTitle').innerHTML = 'Cấp quyền & trạng thái cho: <strong style="color:#0f172a;">' + name + '</strong>';
        if (document.getElementById('gvRoleSelect')) {
            document.getElementById('gvRoleSelect').value = currentRole || 'Giảng viên';
        }
        if (document.getElementById('gvStatusSelect')) {
            document.getElementById('gvStatusSelect').value = currentStatus || 'active';
        }
        toggleModal('gvRoleModal');
    }
    </script>
</body>
</html>
