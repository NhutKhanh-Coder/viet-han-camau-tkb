<?php
require_once '../config.php';
requireAdmin();
$db  = getDB();
$msg = '';

// XỬ LÝ ACTION
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'export_csv') {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="danh_sach_sinh_vien.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Mã SV', 'Họ tên', 'Ngày sinh', 'Lớp', 'Khoa', 'Email']);
    $sql = "SELECT s.*, u.username FROM students s JOIN users u ON s.user_id=u.id ORDER BY s.id DESC";
    $res = $db->query($sql);
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [
            $row['username'],
            $row['ho_ten'],
            $row['ngay_sinh'] ? date('d/m/Y', strtotime($row['ngay_sinh'])) : '',
            $row['lop'],
            $row['khoa'],
            $row['email']
        ]);
    }
    fclose($output);
    $db->close();
    exit();
}

if ($action === 'import_csv') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($fileTmpPath, 'r');
        if ($handle !== false) {
            $successCount = 0;
            $updateCount = 0;
            $rowNum = 0;
            $db->begin_transaction();
            try {
                // Tự động phát hiện dấu phân cách (dấu phẩy hoặc dấu chấm phẩy)
                $delimiter = ',';
                $firstLine = fgets($handle);
                if ($firstLine !== false) {
                    if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
                        $delimiter = ';';
                    }
                    rewind($handle);
                }

                while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                    $rowNum++;
                    
                    // Bỏ qua dòng tiêu đề nếu có
                    if ($rowNum === 1) {
                        $firstCol = trim($row[0]);
                        $firstCol = preg_replace('/^\xEF\xBB\xBF/', '', $firstCol);
                        if (stripos($firstCol, 'ma') !== false || stripos($firstCol, 'mã') !== false || stripos($firstCol, 'username') !== false) {
                            continue;
                        }
                    }
                    
                    if (count($row) < 2) continue;
                    
                    $ma_sv = trim($row[0]);
                    $ma_sv = preg_replace('/^\xEF\xBB\xBF/', '', $ma_sv);
                    $ho_ten = trim($row[1] ?? '');
                    
                    if (empty($ma_sv) || empty($ho_ten)) continue;
                    
                    $ngay_sinh_raw = trim($row[2] ?? '');
                    $lop = trim($row[3] ?? '');
                    $khoa = trim($row[4] ?? '');
                    $email = trim($row[5] ?? '');
                    
                    // Chuẩn hóa ngày sinh sang định dạng YYYY-MM-DD
                    $ngay_sinh = null;
                    if (!empty($ngay_sinh_raw)) {
                        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $ngay_sinh_raw)) {
                            $parts = explode('/', $ngay_sinh_raw);
                            $ngay_sinh = sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
                        } elseif (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $ngay_sinh_raw)) {
                            $ngay_sinh = date('Y-m-d', strtotime($ngay_sinh_raw));
                        } else {
                            $time = strtotime($ngay_sinh_raw);
                            if ($time) {
                                $ngay_sinh = date('Y-m-d', $time);
                            }
                        }
                    }
                    
                    // Mật khẩu mặc định là ngày sinh ddmmyyyy, hoặc 123456
                    $pw_plain = '123456';
                    if ($ngay_sinh) {
                        $pw_plain = date('dmY', strtotime($ngay_sinh));
                    }
                    $pw_hash = password_hash($pw_plain, PASSWORD_DEFAULT);
                    
                    // Kiểm tra sinh viên tồn tại
                    $check = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $check->bind_param("s", $ma_sv);
                    $check->execute();
                    $chk_res = $check->get_result()->fetch_assoc();
                    $check->close();
                    
                    if ($chk_res) {
                        $uid = $chk_res['id'];
                        // Cập nhật thông tin sinh viên
                        $upSt = $db->prepare("UPDATE students SET ho_ten = ?, ngay_sinh = ?, lop = ?, khoa = ?, email = ? WHERE user_id = ?");
                        $upSt->bind_param("sssssi", $ho_ten, $ngay_sinh, $lop, $khoa, $email, $uid);
                        $upSt->execute();
                        $upSt->close();
                        $updateCount++;
                    } else {
                        // Tạo tài khoản mới
                        $insUser = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                        $insUser->bind_param("ss", $ma_sv, $pw_hash);
                        $insUser->execute();
                        $uid = $db->insert_id;
                        $insUser->close();
                        
                        // Thêm chi tiết sinh viên
                        $insStudent = $db->prepare("INSERT INTO students (user_id, ma_sv, ho_ten, ngay_sinh, lop, khoa, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $insStudent->bind_param("issssss", $uid, $ma_sv, $ho_ten, $ngay_sinh, $lop, $khoa, $email);
                        $insStudent->execute();
                        $insStudent->close();
                        
                        $successCount++;
                    }
                }
                fclose($handle);
                $db->commit();
                $msg = "success:Nhập dữ liệu thành công! Đã thêm mới: $successCount SV, cập nhật: $updateCount SV.";
            } catch (Exception $e) {
                $db->rollback();
                $msg = "error:Lỗi nhập file: " . $e->getMessage();
            }
        } else {
            $msg = "error:Không mở được file CSV.";
        }
    } else {
        $msg = "error:Vui lòng tải lên file CSV hợp lệ.";
    }
}

if ($action === 'add') {
    $un = trim($_POST['username'] ?? '');
    $ht = trim($_POST['ho_ten'] ?? '');
    $nd = trim($_POST['ngay_sinh'] ?? '');
    $lp = trim($_POST['lop'] ?? '');
    $kh = trim($_POST['khoa'] ?? '');
    $em = trim($_POST['email'] ?? '');
    $pw = password_hash($nd ? date('dmY', strtotime($nd)) : '123456', PASSWORD_DEFAULT);
    $db->begin_transaction();
    try {
        $st = $db->prepare("INSERT INTO users (username,password,role) VALUES(?,?,'student')");
        $st->bind_param("ss", $un, $pw);
        $st->execute();
        $uid = $db->insert_id;
        $st2 = $db->prepare("INSERT INTO students (user_id,ma_sv,ho_ten,ngay_sinh,lop,khoa,email) VALUES(?,?,?,?,?,?,?)");
        $st2->bind_param("issssss", $uid, $un, $ht, $nd, $lp, $kh, $em);
        $st2->execute();
        $db->commit();
        $msg = 'success:Thêm sinh viên thành công! MK mặc định: ngày sinh (ddmmyyyy)';
    } catch(Exception $e) { $db->rollback(); $msg = 'error:Lỗi: ' . $e->getMessage(); }
}

if ($action === 'edit') {
    $id = (int)$_POST['id'];
    $ht = trim($_POST['ho_ten'] ?? '');
    $nd = trim($_POST['ngay_sinh'] ?? '');
    $lp = trim($_POST['lop'] ?? '');
    $kh = trim($_POST['khoa'] ?? '');
    $em = trim($_POST['email'] ?? '');
    $st = $db->prepare("UPDATE students SET ho_ten=?,ngay_sinh=?,lop=?,khoa=?,email=? WHERE id=?");
    $st->bind_param("sssssi", $ht, $nd, $lp, $kh, $em, $id);
    $st->execute();
    $msg = 'success:Cập nhật thành công!';
}

if ($action === 'reset_password') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $new_pass = trim($_POST['new_password'] ?? '');
    if (empty($new_pass)) $new_pass = '123456';
    $row = $db->query("SELECT user_id, ho_ten, ma_sv FROM students WHERE id=$id")->fetch_assoc();
    if ($row) {
        $uid = (int)$row['user_id'];
        $chkSuper = $db->query("SELECT username FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
        if ($chkSuper && ($chkSuper['username'] === 'admin' || $chkSuper['username'] === 'phanngoctuyen')) {
            $msg = 'error:Không có quyền đổi mật khẩu của tài khoản Quản trị viên!';
        } else {
            $pw_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password='$pw_hash' WHERE id=$uid");
            writeSystemLog("Admin reset mật khẩu cho học viên {$row['ma_sv']}");
            $msg = "success:Đã reset mật khẩu cho học viên {$row['ho_ten']} ({$row['ma_sv']}) thành công! Mật khẩu mới: $new_pass";
        }
    } else {
        $msg = 'error:Không tìm thấy thông tin sinh viên!';
    }
}

if ($action === 'update_role_status') {
    $id = (int)$_POST['id'];
    $vai_tro = trim($_POST['vai_tro'] ?? 'Sinh viên');
    $status = trim($_POST['status'] ?? 'active');
    if (!in_array($status, ['active', 'locked'])) $status = 'active';
    
    $row = $db->query("SELECT user_id, ho_ten, ma_sv FROM students WHERE id=$id")->fetch_assoc();
    if ($row) {
        $uid = (int)$row['user_id'];
        $db->query("UPDATE students SET vai_tro='$vai_tro' WHERE id=$id");
        $db->query("UPDATE users SET status='$status' WHERE id=$uid");
        writeSystemLog("Admin cấp quyền/trạng thái cho học viên {$row['ma_sv']}: $vai_tro, $status");
        $msg = "success:Đã cập nhật vai trò ($vai_tro) và trạng thái tài khoản cho học viên {$row['ho_ten']} thành công!";
    } else {
        $msg = 'error:Không tìm thấy thông tin học viên!';
    }
}

if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $row = $db->query("SELECT user_id, ho_ten, ma_sv FROM students WHERE id=$id")->fetch_assoc();
    if ($row) {
        $uid = (int)$row['user_id'];
        $chkSuper = $db->query("SELECT username FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
        if ($chkSuper && ($chkSuper['username'] === 'admin' || $chkSuper['username'] === 'phanngoctuyen')) {
            $msg = 'error:Không thể xóa tài khoản Quản trị viên hệ thống!';
        } else {
            $db->query("DELETE FROM students WHERE id=$id");
            $db->query("DELETE FROM users WHERE id=$uid");
            @$db->query("DELETE FROM student_code_storage WHERE student_id=$id");
            writeSystemLog("Admin xóa tài khoản sinh viên: {$row['ho_ten']} ({$row['ma_sv']})");
            $msg = 'success:Đã xóa hoàn tất tài khoản sinh viên khỏi hệ thống!';
        }
    }
}

// LẤY DANH SÁCH
$search = trim($_GET['q'] ?? '');
$sql = "SELECT s.*, u.username, COALESCE(u.status, 'active') as user_status, COALESCE(s.vai_tro, 'Sinh viên') as vai_tro FROM students s JOIN users u ON s.user_id=u.id";
if ($search) $sql .= " WHERE s.ho_ten LIKE '%$search%' OR s.ma_sv LIKE '%$search%' OR s.lop LIKE '%$search%'";
$sql .= " ORDER BY s.id DESC";
$list = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

// Lấy 1 SV để edit
$editSV = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $editSV = $db->query("SELECT * FROM students WHERE id=$eid")->fetch_assoc();
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Sinh Viên - Hệ Thống Quản Trị</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body class="admin-portal <?= (isset($_COOKIE['adm_theme']) && $_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : '' ?>">
<?php include '../includes/admin_nav.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="fa-solid fa-user-graduate"></i> Quản lý Sinh Viên</h1>
      <p class="page-sub">Quản lý danh sách sinh viên, thông tin lớp học, khoa ngành và tài khoản đăng nhập</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a href="?action=export_csv" class="btn btn-ghost"><i class="fa-solid fa-file-export" style="color:#0284c7;"></i> Xuất CSV</a>
      <button type="button" class="btn btn-ghost" onclick="toggleModal('importModal')"><i class="fa-solid fa-file-import" style="color:#10b981;"></i> Nhập CSV</button>
      <button type="button" class="btn btn-primary" onclick="toggleModal('addModal')"><i class="fa-solid fa-user-plus"></i> Thêm SV mới</button>
    </div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>">
    <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= htmlspecialchars($msgText) ?>
  </div>
  <?php endif; ?>

  <div class="filter-bar">
    <form method="GET" style="display:flex; align-items:center; gap:12px; flex:1;">
      <div class="search-wrap" style="flex:1; max-width:420px;">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input class="search-input" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm kiếm theo họ tên, mã SV, lớp học...">
      </div>
      <button type="submit" class="btn btn-ghost"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</button>
    </form>
    <span style="font-size:13px; font-weight:700; color:#475569; background:#f1f5f9; padding:6px 14px; border-radius:20px; border:1px solid #e2e8f0; white-space:nowrap;">
      Tổng: <b style="color:#e11d48;"><?= count($list) ?></b> sinh viên
    </span>
  </div>

  <div class="card">
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th style="width:50px; text-align:center;">#</th>
            <th style="width:130px;">Mã SV</th>
            <th>Họ tên Sinh viên</th>
            <th style="width:120px;">Lớp</th>
            <th style="width:200px;">Khoa / Ngành</th>
            <th style="width:170px; text-align:center;">Quyền &amp; Trạng thái</th>
            <th style="width:110px; text-align:center;">Ngày sinh</th>
            <th style="width:200px; text-align:center;">Thao tác quản trị</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($list)): ?>
          <tr><td colspan="8" style="text-align:center; color:#94a3b8; padding:40px;">Không tìm thấy dữ liệu sinh viên phù hợp.</td></tr>
          <?php else: foreach ($list as $i => $sv): ?>
          <tr>
            <td style="text-align:center; color:#94a3b8; font-weight:600;"><?= $i+1 ?></td>
            <td>
              <span style="display:inline-block; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:12.5px; font-weight:700; background:#f8fafc; color:#0f172a; padding:4px 9px; border-radius:6px; border:1px solid #e2e8f0; white-space:nowrap;">
                <?= htmlspecialchars($sv['ma_sv']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex; align-items:center; gap:12px;">
                <?php if(!empty($sv['avatar'])): ?>
                  <img src="/tkb/assets/img/avatars/<?= htmlspecialchars($sv['avatar']) ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1.5px solid #e2e8f0; flex-shrink:0;" alt="Avatar">
                <?php else: ?>
                  <?php 
                    $parts = explode(' ', trim($sv['ho_ten']));
                    $initial = mb_substr(array_pop($parts), 0, 1, 'UTF-8');
                  ?>
                  <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color:#2563eb; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; border:1px solid #bfdbfe; flex-shrink:0;">
                    <?= mb_strtoupper($initial, 'UTF-8') ?>
                  </div>
                <?php endif; ?>
                <div>
                  <div style="font-weight:700; color:#0f172a; white-space:nowrap;"><?= htmlspecialchars($sv['ho_ten']) ?></div>
                  <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($sv['email'] ?: '-') ?></div>
                </div>
              </div>
            </td>
            <td>
              <span style="display:inline-block; padding:4px 10px; border-radius:6px; background:#eff6ff; color:#2563eb; font-weight:700; font-size:12px; border:1px solid #dbeafe; white-space:nowrap;">
                <?= htmlspecialchars($sv['lop']) ?>
              </span>
            </td>
            <td style="color:#475569; font-size:13px; white-space:nowrap;"><?= htmlspecialchars($sv['khoa'] ?: 'Chưa phân ngành') ?></td>
            <td style="text-align:center; white-space:nowrap;">
              <div style="display:flex; flex-direction:column; align-items:center; gap:4px;">
                <span style="display:inline-block; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; background:<?= ($sv['vai_tro'] === 'Lớp trưởng' || $sv['vai_tro'] === 'Bí thư chi đoàn') ? '#faf5ff; color:#7c3aed; border:1px solid #ddd6fe;' : '#f1f5f9; color:#475569;' ?>">
                  <i class="fa-solid <?= ($sv['vai_tro'] === 'Lớp trưởng' || $sv['vai_tro'] === 'Bí thư chi đoàn') ? 'fa-crown' : 'fa-user-graduate' ?>"></i> <?= htmlspecialchars($sv['vai_tro']) ?>
                </span>
                <?php if ($sv['user_status'] === 'locked'): ?>
                  <span style="display:inline-block; font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px; background:#fef2f2; color:#ef4444; border:1px solid #fecaca;"><i class="fa-solid fa-lock"></i> Đã khóa</span>
                <?php else: ?>
                  <span style="display:inline-block; font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;"><i class="fa-solid fa-circle-check"></i> Hoạt động</span>
                <?php endif; ?>
              </div>
            </td>
            <td style="text-align:center; color:#64748b; font-size:13px; white-space:nowrap;"><?= $sv['ngay_sinh'] ? date('d/m/Y', strtotime($sv['ngay_sinh'])) : '-' ?></td>
            <td style="text-align:center;">
              <div style="display:inline-flex; align-items:center; gap:5px; justify-content:center; flex-wrap:wrap;">
                <button type="button" onclick="openStudentGradesModal(<?= $sv['id'] ?>, '<?= htmlspecialchars(addslashes($sv['ho_ten'])) ?>', '<?= htmlspecialchars(addslashes($sv['ma_sv'])) ?>', '<?= htmlspecialchars(addslashes($sv['lop'])) ?>')" class="btn btn-sm" style="background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; border-radius:7px; height:28px; padding:0 7px; font-weight:700; font-size:11px;" title="Xem điểm số & kết quả"><i class="fa-solid fa-graduation-cap"></i> Điểm</button>
                <button type="button" onclick="openStudentRoleModal(<?= $sv['id'] ?>, '<?= htmlspecialchars(addslashes($sv['ho_ten'])) ?>', '<?= htmlspecialchars(addslashes($sv['vai_tro'])) ?>', '<?= htmlspecialchars($sv['user_status']) ?>')" class="btn btn-sm" style="background:#faf5ff; color:#7c3aed; border:1px solid #ddd6fe; border-radius:7px; height:28px; padding:0 7px; font-weight:700; font-size:11px;" title="Cấp quyền & Khóa/Mở tài khoản"><i class="fa-solid fa-user-shield"></i> Quyền</button>
                <button type="button" onclick="openStudentResetPassModal(<?= $sv['id'] ?>, '<?= htmlspecialchars(addslashes($sv['ho_ten'])) ?>', '<?= htmlspecialchars(addslashes($sv['ma_sv'])) ?>')" class="btn btn-sm" style="background:#fffbeb; color:#d97706; border:1px solid #fde68a; border-radius:7px; height:28px; padding:0 7px; font-weight:700; font-size:11px;" title="Reset Mật Khẩu"><i class="fa-solid fa-key"></i> Đổi MK</button>
                <a href="?edit_id=<?= $sv['id'] ?>" class="btn btn-edit btn-sm" style="height:28px; padding:0 7px; border-radius:7px;" title="Chỉnh sửa"><i class="fa-solid fa-pen"></i></a>
                <a href="?action=delete&id=<?= $sv['id'] ?>" class="btn btn-danger btn-sm" style="height:28px; padding:0 7px; border-radius:7px;" title="Xóa tài khoản" onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn tài khoản sinh viên này?');"><i class="fa-solid fa-trash"></i></a>
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
    <div class="modal-title">Thêm sinh viên mới</div>
    <div class="modal-sub">Mật khẩu mặc định tự động = ngày sinh (ddmmyyyy) hoặc 123456</div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <div class="form-group"><label class="form-label">Mã SV *</label><input class="form-input" name="username" required placeholder="SV2024xxx"></div>
        <div class="form-group"><label class="form-label">Họ tên *</label><input class="form-input" name="ho_ten" required placeholder="Nguyễn Văn A"></div>
      </div>
      <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <div class="form-group"><label class="form-label">Ngày sinh</label><input class="form-input" name="ngay_sinh" type="date"></div>
        <div class="form-group"><label class="form-label">Lớp *</label><input class="form-input" name="lop" required placeholder="CNTT24A"></div>
      </div>
      <div class="form-group"><label class="form-label">Khoa / Ngành</label>
        <select class="form-select" name="khoa">
          <option value="">-- Chọn ngành --</option>
          <?php global $NGANH_LIST; foreach ($NGANH_LIST as $ng): ?>
            <option value="<?= htmlspecialchars($ng) ?>"><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input class="form-input" name="email" type="email" placeholder="sv@email.com"></div>
      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm sinh viên</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Nhập CSV -->
<div class="modal-overlay" id="importModal">
  <div class="modal-box">
    <div class="modal-title">Nhập sinh viên từ file CSV</div>
    <div class="modal-sub">
      Chọn file CSV chứa danh sách sinh viên. Định dạng các cột chuẩn:<br>
      <code style="background:#f1f5f9; padding:4px 8px; border-radius:6px; display:inline-block; margin-top:6px; font-size:12px; color:#0f172a;">Mã SV, Họ tên, Ngày sinh (dd/mm/yyyy), Lớp, Khoa, Email</code>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="import_csv">
      <div class="form-group" style="margin-top: 20px;">
        <label class="form-label">Chọn file CSV *</label>
        <input class="form-input" name="csv_file" type="file" accept=".csv" required style="padding: 8px 12px; background:#fff;">
      </div>
      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('importModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Bắt đầu nhập</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Sửa -->
<?php if ($editSV): ?>
<div class="modal-overlay show" id="editModal">
  <div class="modal-box">
    <div class="modal-title">Sửa thông tin sinh viên</div>
    <div class="modal-sub">Mã SV: <strong style="color:#0f172a;"><?= htmlspecialchars($editSV['ma_sv']) ?></strong></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editSV['id'] ?>">
      <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <div class="form-group"><label class="form-label">Họ tên</label><input class="form-input" name="ho_ten" value="<?= htmlspecialchars($editSV['ho_ten']) ?>" required></div>
        <div class="form-group"><label class="form-label">Ngày sinh</label><input class="form-input" name="ngay_sinh" type="date" value="<?= $editSV['ngay_sinh'] ?>"></div>
      </div>
      <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <div class="form-group"><label class="form-label">Lớp</label><input class="form-input" name="lop" value="<?= htmlspecialchars($editSV['lop']) ?>"></div>
        <div class="form-group"><label class="form-label">Khoa / Ngành</label>
          <select class="form-select" name="khoa">
            <option value="">-- Chọn ngành --</option>
            <?php global $NGANH_LIST; foreach ($NGANH_LIST as $ng): ?>
              <option value="<?= htmlspecialchars($ng) ?>" <?= ($editSV['khoa'] === $ng) ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input class="form-input" name="email" type="email" value="<?= htmlspecialchars($editSV['email']) ?>"></div>
      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
        <a href="/tkb/admin/students.php" class="btn btn-ghost">Hủy</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- MODAL XEM TOÀN BỘ BẢNG ĐIỂM CỦA SINH VIÊN (ADMIN VIEW) -->
<div class="modal-overlay" id="gradesModal" style="z-index: 99999;">
  <div class="modal-box" style="max-width: 900px; width: 95%; max-height: 85vh; display: flex; flex-direction: column; padding: 0; overflow: hidden; background: #140d27; border: 1px solid rgba(168,85,247,0.3); box-shadow: 0 20px 60px rgba(0,0,0,0.7); border-radius: 20px;">
    
    <!-- Header -->
    <div style="padding: 20px 24px; border-bottom: 1px solid rgba(168,85,247,0.18); display: flex; align-items: center; justify-content: space-between; background: rgba(20,13,38,0.95);">
      <div>
        <div style="font-family:'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #f3e8ff; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-graduation-cap" style="color: #10b981;"></i> Bảng Điểm &amp; Kết Quả Học Tập
        </div>
        <div id="gradesStudentMeta" style="font-size: 12.5px; color: #c4b5fd; margin-top: 4px; font-weight: 600;">
          Đang tải dữ liệu...
        </div>
      </div>
      <button type="button" onclick="toggleModal('gradesModal')" style="background: rgba(255,255,255,0.08); border: none; width: 32px; height: 32px; border-radius: 8px; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <!-- Body / Content -->
    <div id="gradesModalContent" style="padding: 24px; overflow-y: auto; flex: 1; color: #e9d5ff;">
      <div style="text-align: center; padding: 40px 20px; color: #a79bb7;">
        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 28px; color: #a855f7; margin-bottom: 12px;"></i>
        <div>Đang truy vấn bảng điểm toàn diện của sinh viên...</div>
      </div>
    </div>

    <!-- Footer -->
    <div style="padding: 14px 24px; border-top: 1px solid rgba(168,85,247,0.18); background: rgba(20,13,38,0.95); display: flex; justify-content: flex-end; gap: 10px;">
      <button type="button" class="btn btn-ghost" onclick="toggleModal('gradesModal')" style="background: rgba(168,85,247,0.15); color: #e9d5ff; border: 1px solid rgba(168,85,247,0.25);">Đóng</button>
    </div>

  </div>
</div>

<!-- Modal Reset Mật Khẩu Sinh Viên -->
<div class="modal-overlay" id="resetPassModal">
  <div class="modal-box" style="max-width:440px;">
    <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
      <i class="fa-solid fa-key" style="color:#d97706;"></i> Reset Mật Khẩu Học Viên
    </div>
    <div class="modal-sub" id="resetPassSubTitle">Đặt lại mật khẩu truy cập cho sinh viên</div>
    <form method="POST">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="id" id="resetPassStudentId" value="">
      
      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Mật khẩu mới</label>
        <div style="position:relative;">
          <input type="text" class="form-input" name="new_password" id="resetPassNewInput" value="123456" placeholder="Nhập mật khẩu mới..." required style="padding-right:70px; font-weight:700; font-family:monospace; font-size:14px; letter-spacing:1px;">
          <button type="button" onclick="document.getElementById('resetPassNewInput').value = '123456'" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; font-size:11px; padding:3px 7px; font-weight:700; cursor:pointer;">Mặc định</button>
        </div>
        <div style="font-size:11.5px; color:#64748b; margin-top:6px;">Gợi ý: Mặc định là <code>123456</code>. Học viên có thể tự đổi mật khẩu sau khi đăng nhập.</div>
      </div>

      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('resetPassModal')">Hủy</button>
        <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg, #d97706, #b45309); border:none;"><i class="fa-solid fa-check"></i> Xác nhận Reset</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Cấp Quyền & Trạng Thái Sinh Viên -->
<div class="modal-overlay" id="roleModal">
  <div class="modal-box" style="max-width:440px;">
    <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
      <i class="fa-solid fa-user-shield" style="color:#7c3aed;"></i> Cấp Quyền &amp; Trạng Thái Học Viên
    </div>
    <div class="modal-sub" id="roleSubTitle">Phân quyền chức vụ và trạng thái kích hoạt tài khoản</div>
    <form method="POST">
      <input type="hidden" name="action" value="update_role_status">
      <input type="hidden" name="id" id="roleStudentId" value="">

      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Vai trò / Chức vụ cán sự</label>
        <select class="form-select" name="vai_tro" id="roleSelect">
          <option value="Sinh viên">Sinh viên chính quy</option>
          <option value="Lớp trưởng">Lớp trưởng / Ban cán sự</option>
          <option value="Bí thư chi đoàn">Bí thư chi đoàn lớp</option>
          <option value="Trợ giảng sinh viên">Trợ giảng sinh viên (TA)</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Trạng thái tài khoản</label>
        <select class="form-select" name="status" id="statusSelect">
          <option value="active">🟢 Đang hoạt động (Bình thường)</option>
          <option value="locked">🔴 Tạm khóa tài khoản (Không cho đăng nhập)</option>
        </select>
      </div>

      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('roleModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu Quyền Hạn</button>
      </div>
    </form>
  </div>
</div>

<script>
function openStudentResetPassModal(id, name, masv) {
  document.getElementById('resetPassStudentId').value = id;
  document.getElementById('resetPassSubTitle').innerHTML = 'Reset mật khẩu cho: <strong style="color:#0f172a;">' + name + ' (' + masv + ')</strong>';
  document.getElementById('resetPassNewInput').value = '123456';
  toggleModal('resetPassModal');
}

function openStudentRoleModal(id, name, currentRole, currentStatus) {
  document.getElementById('roleStudentId').value = id;
  document.getElementById('roleSubTitle').innerHTML = 'Cấp quyền cho: <strong style="color:#0f172a;">' + name + '</strong>';
  if (document.getElementById('roleSelect')) {
    document.getElementById('roleSelect').value = currentRole || 'Sinh viên';
  }
  if (document.getElementById('statusSelect')) {
    document.getElementById('statusSelect').value = currentStatus || 'active';
  }
  toggleModal('roleModal');
}
function toggleModal(id){
  const m=document.getElementById(id);
  if(m) {
    m.classList.toggle('show');
    m.classList.toggle('open');
    m.classList.toggle('active');
  }
}

function openStudentGradesModal(studentId, name, msv, lop) {
  var metaEl = document.getElementById('gradesStudentMeta');
  var contentEl = document.getElementById('gradesModalContent');
  if (metaEl) {
    metaEl.innerHTML = '<span style="color:#f3e8ff; font-weight:700;">' + name + '</span> • Mã SV: <span style="font-family:monospace; background:rgba(168,85,247,0.2); padding:2px 6px; border-radius:4px; color:#c084fc;">' + msv + '</span> • Lớp: <span style="color:#38bdf8;">' + (lop || 'Chưa phân lớp') + '</span>';
  }
  if (contentEl) {
    contentEl.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: #a79bb7;">' +
      '<i class="fa-solid fa-circle-notch fa-spin" style="font-size: 28px; color: #a855f7; margin-bottom: 12px;"></i>' +
      '<div>Đang truy vấn bảng điểm toàn diện của sinh viên...</div>' +
    '</div>';
  }
  
  toggleModal('gradesModal');

  fetch('/tkb/api/get_student_grades.php?student_id=' + studentId)
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (!data || !data.success) {
        contentEl.innerHTML = '<div style="text-align:center; padding:30px; color:#f87171;"><i class="fa-solid fa-triangle-exclamation" style="font-size:24px; margin-bottom:8px;"></i><div>' + (data && data.error ? data.error : 'Không thể lấy dữ liệu điểm của sinh viên!') + '</div></div>';
        return;
      }

      var quizzes = data.quiz_attempts || [];
      var practice = data.practice_submissions || [];
      var assignments = data.assignment_submissions || [];
      var projects = data.projects || [];
      var subjectGrades = data.subject_grades || [];

      // Calculate Averages
      var quizAvg = '-';
      if (quizzes.length > 0) {
        var totalQ = 0;
        quizzes.forEach(function(q) { totalQ += parseFloat(q.score) || 0; });
        quizAvg = (totalQ / quizzes.length).toFixed(1) + ' đ';
      }

      var pracAvg = '-';
      var pracGradedCount = 0;
      var pracTotal = 0;
      practice.forEach(function(p) {
        if (p.score !== null && p.score !== undefined && p.score !== '') {
          pracTotal += parseFloat(p.score) || 0;
          pracGradedCount++;
        }
      });
      if (pracGradedCount > 0) {
        pracAvg = (pracTotal / pracGradedCount).toFixed(1) + ' đ';
      }

      var html = '';

      // 1. KPI Summary Cards
      html += '<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:24px;">' +
        '<div style="background: rgba(168,85,247,0.12); border: 1px solid rgba(168,85,247,0.25); border-radius:14px; padding:14px 16px;">' +
          '<div style="font-size:11.5px; color:#c4b5fd; font-weight:700;"><i class="fa-solid fa-brain"></i> Trắc Nghiệm Quiz</div>' +
          '<div style="font-size:22px; font-weight:800; color:#f3e8ff; margin:6px 0 2px;">' + quizzes.length + ' <span style="font-size:12px; color:#a79bb7; font-weight:600;">lượt làm</span></div>' +
          '<div style="font-size:11px; color:#34d399; font-weight:700;">Điểm TB: ' + quizAvg + '</div>' +
        '</div>' +
        '<div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); border-radius:14px; padding:14px 16px;">' +
          '<div style="font-size:11.5px; color:#6ee7b7; font-weight:700;"><i class="fa-solid fa-code"></i> Thực Hành Lập Trình</div>' +
          '<div style="font-size:22px; font-weight:800; color:#f3e8ff; margin:6px 0 2px;">' + practice.length + ' <span style="font-size:12px; color:#a79bb7; font-weight:600;">bài nộp</span></div>' +
          '<div style="font-size:11px; color:#34d399; font-weight:700;">Điểm TB: ' + pracAvg + '</div>' +
        '</div>' +
        '<div style="background: rgba(56,189,248,0.12); border: 1px solid rgba(56,189,248,0.25); border-radius:14px; padding:14px 16px;">' +
          '<div style="font-size:11.5px; color:#7dd3fc; font-weight:700;"><i class="fa-solid fa-pen-to-square"></i> Bài Tập Về Nhà</div>' +
          '<div style="font-size:22px; font-weight:800; color:#f3e8ff; margin:6px 0 2px;">' + assignments.length + ' <span style="font-size:12px; color:#a79bb7; font-weight:600;">bài nộp</span></div>' +
          '<div style="font-size:11px; color:#38bdf8; font-weight:700;">Đã chấm: ' + assignments.filter(function(a){ return a.grade !== null; }).length + ' bài</div>' +
        '</div>' +
        '<div style="background: rgba(236,72,153,0.12); border: 1px solid rgba(236,72,153,0.25); border-radius:14px; padding:14px 16px;">' +
          '<div style="font-size:11.5px; color:#f472b6; font-weight:700;"><i class="fa-solid fa-file-code"></i> Đồ Án / Khóa Luận</div>' +
          '<div style="font-size:22px; font-weight:800; color:#f3e8ff; margin:6px 0 2px;">' + projects.length + ' <span style="font-size:12px; color:#a79bb7; font-weight:600;">đề tài</span></div>' +
          '<div style="font-size:11px; color:#ec4899; font-weight:700;">' + (projects.length > 0 ? (projects[0].trang_thai || 'Đang thực hiện') : 'Chưa phân công') + '</div>' +
        '</div>' +
      '</div>';

      // 2. Sections

      // SECTION: THỰC HÀNH LẬP TRÌNH
      html += '<div style="margin-bottom:24px;">' +
        '<div style="font-size:14px; font-weight:800; color:#34d399; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between;">' +
          '<span><i class="fa-solid fa-code"></i> 1. Kết Quả Thực Hành / Thi Code (' + practice.length + ')</span>' +
          '<a href="/tkb/teacher/quanly_thuchanh.php" target="_blank" style="font-size:11px; color:#a79bb7; text-decoration:none;">Quản lý thực hành <i class="fa-solid fa-arrow-up-right-from-square"></i></a>' +
        '</div>';
      if (practice.length === 0) {
        html += '<div style="background:rgba(255,255,255,0.03); padding:14px; border-radius:10px; font-size:12px; color:#94a3b8; text-align:center;">Sinh viên chưa nộp bài thực hành nào.</div>';
      } else {
        html += '<div style="overflow-x:auto;"><table style="width:100%; border-collapse:collapse; font-size:12.5px;">' +
          '<thead><tr style="background:rgba(16,185,129,0.15); color:#6ee7b7; text-align:left;">' +
            '<th style="padding:8px 12px; border-radius:8px 0 0 8px;">Phiên Thực Hành</th>' +
            '<th style="padding:8px 12px; width:120px;">Thời Gian Nộp</th>' +
            '<th style="padding:8px 12px; width:80px; text-align:center;">Điểm</th>' +
            '<th style="padding:8px 12px;">Nhận Xét</th>' +
            '<th style="padding:8px 12px; width:90px; text-align:center; border-radius:0 8px 8px 0;">Xem Code</th>' +
          '</tr></thead><tbody>';
        practice.forEach(function(p) {
          var scoreBadge = (p.score !== null && p.score !== undefined && p.score !== '') ? ('<span style="font-weight:800; color:#34d399; background:rgba(16,185,129,0.2); padding:3px 8px; border-radius:6px;">' + p.score + ' đ</span>') : '<span style="color:#94a3b8; font-size:11px;">Chưa chấm</span>';
          html += '<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">' +
            '<td style="padding:10px 12px; font-weight:700; color:#f3e8ff;">' + (p.session_title || ('Phiên #' + p.session_id)) + '</td>' +
            '<td style="padding:10px 12px; color:#a79bb7; font-size:11.5px;">' + (p.submitted_at ? p.submitted_at.substring(0, 16) : '-') + '</td>' +
            '<td style="padding:10px 12px; text-align:center;">' + scoreBadge + '</td>' +
            '<td style="padding:10px 12px; color:#c4b5fd; font-size:12px;">' + (p.teacher_feedback || '<i style="color:#64748b;">Chưa có nhận xét</i>') + '</td>' +
            '<td style="padding:10px 12px; text-align:center;"><a href="/tkb/teacher/cham_code.php?submission_id=' + p.id + '" target="_blank" style="background:#7c3aed; color:#fff; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; text-decoration:none; display:inline-block;"><i class="fa-solid fa-code"></i> Chấm</a></td>' +
          '</tr>';
        });
        html += '</tbody></table></div>';
      }
      html += '</div>';

      // SECTION: TRẮC NGHIỆM QUIZ
      html += '<div style="margin-bottom:24px;">' +
        '<div style="font-size:14px; font-weight:800; color:#c084fc; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between;">' +
          '<span><i class="fa-solid fa-brain"></i> 2. Kết Quả Thi Trắc Nghiệm Quiz (' + quizzes.length + ')</span>' +
          '<a href="/tkb/teacher/quiz.php" target="_blank" style="font-size:11px; color:#a79bb7; text-decoration:none;">Quản lý Quiz <i class="fa-solid fa-arrow-up-right-from-square"></i></a>' +
        '</div>';
      if (quizzes.length === 0) {
        html += '<div style="background:rgba(255,255,255,0.03); padding:14px; border-radius:10px; font-size:12px; color:#94a3b8; text-align:center;">Sinh viên chưa làm bài Quiz nào.</div>';
      } else {
        html += '<div style="overflow-x:auto;"><table style="width:100%; border-collapse:collapse; font-size:12.5px;">' +
          '<thead><tr style="background:rgba(168,85,247,0.15); color:#d8b4fe; text-align:left;">' +
            '<th style="padding:8px 12px; border-radius:8px 0 0 8px;">Bài Quiz &amp; Môn Học</th>' +
            '<th style="padding:8px 12px; width:80px; text-align:center;">Mã Đề</th>' +
            '<th style="padding:8px 12px; width:90px; text-align:center;">Đúng/Tổng</th>' +
            '<th style="padding:8px 12px; width:80px; text-align:center;">Điểm</th>' +
            '<th style="padding:8px 12px; width:120px;">Ngày Làm</th>' +
            '<th style="padding:8px 12px; border-radius:0 8px 8px 0;">Đánh Giá / Nhận Xét</th>' +
          '</tr></thead><tbody>';
        quizzes.forEach(function(q) {
          html += '<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">' +
            '<td style="padding:10px 12px; font-weight:700; color:#f3e8ff;">' + (q.quiz_title || 'Bài thi trắc nghiệm') + '<div style="font-size:11px; color:#a79bb7; font-weight:500;">' + (q.subject_name || '') + '</div></td>' +
            '<td style="padding:10px 12px; text-align:center;"><span style="font-family:monospace; background:rgba(255,255,255,0.08); padding:2px 6px; border-radius:4px; font-size:11px;">' + (q.ma_de || 'Chuẩn') + '</span></td>' +
            '<td style="padding:10px 12px; text-align:center; font-weight:700; color:#38bdf8;">' + (q.score || 0) + '/' + (q.total_questions || 0) + '</td>' +
            '<td style="padding:10px 12px; text-align:center;"><span style="font-weight:800; color:#34d399; background:rgba(16,185,129,0.2); padding:3px 8px; border-radius:6px;">' + (q.score || 0) + ' đ</span></td>' +
            '<td style="padding:10px 12px; color:#a79bb7; font-size:11.5px;">' + (q.attempted_at ? q.attempted_at.substring(0, 16) : '-') + '</td>' +
            '<td style="padding:10px 12px; color:#c4b5fd; font-size:11.5px; line-height:1.4;">' + (q.nhan_xet || '<i style="color:#64748b;">Đã nộp bài thành công</i>') + '</td>' +
          '</tr>';
        });
        html += '</tbody></table></div>';
      }
      html += '</div>';

      // SECTION: BÀI TẬP VỀ NHÀ & ĐỒ ÁN
      html += '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">' +
        '<div>' +
          '<div style="font-size:14px; font-weight:800; color:#38bdf8; margin-bottom:10px;"><i class="fa-solid fa-pen-to-square"></i> 3. Bài Tập Về Nhà (' + assignments.length + ')</div>';
      if (assignments.length === 0) {
        html += '<div style="background:rgba(255,255,255,0.03); padding:14px; border-radius:10px; font-size:12px; color:#94a3b8; text-align:center;">Chưa nộp bài tập nào.</div>';
      } else {
        html += '<div style="display:flex; flex-direction:column; gap:8px;">';
        assignments.forEach(function(a) {
          var gradeDisplay = (a.grade !== null && a.grade !== undefined) ? ('<span style="color:#34d399; font-weight:800; background:rgba(16,185,129,0.2); padding:2px 6px; border-radius:4px;">' + a.grade + ' đ</span>') : '<span style="color:#94a3b8; font-size:11px;">Chờ chấm</span>';
          html += '<div style="background:rgba(56,189,248,0.06); border:1px solid rgba(56,189,248,0.15); border-radius:10px; padding:10px 12px;">' +
            '<div style="display:flex; justify-content:space-between; align-items:center;">' +
              '<strong style="font-size:12.5px; color:#f3e8ff;">' + (a.assignment_title || 'Bài tập') + '</strong>' +
              gradeDisplay +
            '</div>' +
            '<div style="font-size:11px; color:#94a3b8; margin-top:2px;">Môn: ' + (a.subject_name || '-') + ' • GV: ' + (a.teacher_name || '-') + '</div>' +
            (a.feedback ? ('<div style="font-size:11.5px; color:#c4b5fd; margin-top:4px;">' + a.feedback + '</div>') : '') +
          '</div>';
        });
        html += '</div>';
      }
      html += '</div>';

      // SECTION: ĐỒ ÁN
      html += '<div>' +
        '<div style="font-size:14px; font-weight:800; color:#f472b6; margin-bottom:10px;"><i class="fa-solid fa-file-code"></i> 4. Đồ Án / Khóa Luận (' + projects.length + ')</div>';
      if (projects.length === 0) {
        html += '<div style="background:rgba(255,255,255,0.03); padding:14px; border-radius:10px; font-size:12px; color:#94a3b8; text-align:center;">Chưa được phân công đề tài.</div>';
      } else {
        html += '<div style="display:flex; flex-direction:column; gap:8px;">';
        projects.forEach(function(pr) {
          var pGrade = (pr.diem !== null && pr.diem !== undefined) ? ('<span style="color:#34d399; font-weight:800; background:rgba(16,185,129,0.2); padding:2px 6px; border-radius:4px;">' + pr.diem + ' đ</span>') : '<span style="color:#94a3b8; font-size:11px;">Chờ chấm</span>';
          html += '<div style="background:rgba(236,72,153,0.06); border:1px solid rgba(236,72,153,0.15); border-radius:10px; padding:10px 12px;">' +
            '<div style="display:flex; justify-content:space-between; align-items:center;">' +
              '<strong style="font-size:12.5px; color:#f3e8ff;">' + (pr.ten_do_an || 'Đề tài') + '</strong>' +
              pGrade +
            '</div>' +
            '<div style="font-size:11px; color:#94a3b8; margin-top:2px;">Trạng thái: <span style="color:#f472b6; font-weight:700;">' + (pr.trang_thai || 'Đang làm') + '</span> • GVHD: ' + (pr.teacher_name || '-') + '</div>' +
            (pr.nhan_xet ? ('<div style="font-size:11.5px; color:#c4b5fd; margin-top:4px;">' + pr.nhan_xet + '</div>') : '') +
          '</div>';
        });
        html += '</div>';
      }
      html += '</div></div>';

      contentEl.innerHTML = html;
    })
    .catch(function(err) {
      contentEl.innerHTML = '<div style="text-align:center; padding:30px; color:#f87171;"><i class="fa-solid fa-circle-exclamation" style="font-size:24px; margin-bottom:8px;"></i><div>Lỗi kết nối máy chủ khi tải điểm!</div></div>';
    });
}
</script>
</body>
</html>
