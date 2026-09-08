<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config.php';
requireTeacher();
$db  = getDB();
$msg = '';

// Khởi tạo cột gioi_tinh nếu chưa có
$db->query("SHOW COLUMNS FROM students LIKE 'gioi_tinh'");
if ($db->affected_rows == 0) {
    $db->query("ALTER TABLE students ADD COLUMN `gioi_tinh` VARCHAR(20) DEFAULT 'Nam'");
}

// XỬ LÝ ACTION
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'export_csv') {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="danh_sach_sinh_vien.csv"');
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Mã SV', 'Họ tên', 'Giới tính', 'Ngày sinh', 'Lớp', 'Khoa', 'Email']);
    $sql = "SELECT s.*, u.username FROM students s JOIN users u ON s.user_id=u.id ORDER BY s.id DESC";
    $res = $db->query($sql);
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [
            $row['username'], $row['ho_ten'], $row['gioi_tinh'] ?? 'Nam',
            $row['ngay_sinh'] ? date('d/m/Y', strtotime($row['ngay_sinh'])) : '',
            $row['lop'], $row['khoa'], $row['email']
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
            $successCount = 0; $updateCount = 0; $rowNum = 0;
            $db->begin_transaction();
            try {
                $delimiter = ',';
                $firstLine = fgets($handle);
                if ($firstLine !== false) {
                    if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) $delimiter = ';';
                    rewind($handle);
                }
                while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                    $rowNum++;
                    if ($rowNum === 1) {
                        $firstCol = preg_replace('/^\xEF\xBB\xBF/', '', trim($row[0]));
                        if (stripos($firstCol, 'ma') !== false || stripos($firstCol, 'mã') !== false || stripos($firstCol, 'username') !== false) continue;
                    }
                    if (count($row) < 2) continue;
                    $ma_sv = preg_replace('/^\xEF\xBB\xBF/', '', trim($row[0]));
                    $ho_ten = trim($row[1] ?? '');
                    if (empty($ma_sv) || empty($ho_ten)) continue;
                    $gioi_tinh = trim($row[2] ?? 'Nam');
                    $ngay_sinh_raw = trim($row[3] ?? '');
                    $lop = trim($row[4] ?? '');
                    $khoa = trim($row[5] ?? '');
                    $email = trim($row[6] ?? '');
                    $ngay_sinh = null;
                    if (!empty($ngay_sinh_raw)) {
                        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $ngay_sinh_raw)) {
                            $parts = explode('/', $ngay_sinh_raw);
                            $ngay_sinh = sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
                        } elseif (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $ngay_sinh_raw)) {
                            $ngay_sinh = date('Y-m-d', strtotime($ngay_sinh_raw));
                        } else {
                            $time = strtotime($ngay_sinh_raw);
                            if ($time) $ngay_sinh = date('Y-m-d', $time);
                        }
                    }
                    $pw_plain = $ngay_sinh ? date('dmY', strtotime($ngay_sinh)) : '123456';
                    $pw_hash = password_hash($pw_plain, PASSWORD_DEFAULT);
                    $chk_res = null;
                    $r_chk = @$db->query("SELECT id FROM users WHERE username = '$ma_sv' LIMIT 1");
                    if ($r_chk && method_exists($r_chk, 'fetch_assoc')) {
                        $chk_res = $r_chk->fetch_assoc();
                    }
                    if ($chk_res) {
                        $uid = $chk_res['id'];
                        @$db->query("UPDATE students SET ho_ten='$ho_ten', ngay_sinh='$ngay_sinh', lop='$lop', khoa='$khoa', email='$email', gioi_tinh='$gioi_tinh' WHERE user_id=$uid");
                        $updateCount++;
                    } else {
                        @$db->query("INSERT INTO users (username, password, role) VALUES ('$ma_sv', '$pw_hash', 'student')");
                        $uid = (int)$db->insert_id;
                        @$db->query("INSERT INTO students (user_id, ma_sv, ho_ten, ngay_sinh, lop, khoa, email, gioi_tinh) VALUES ($uid, '$ma_sv', '$ho_ten', '$ngay_sinh', '$lop', '$khoa', '$email', '$gioi_tinh')");
                        $successCount++;
                    }
                }
                fclose($handle);
                $db->commit();
                $msg = "success:Nhập dữ liệu thành công! Thêm mới: $successCount SV, cập nhật: $updateCount SV.";
            } catch (Exception $e) { $db->rollback(); $msg = "error:Lỗi nhập file: " . $e->getMessage(); }
        } else { $msg = "error:Không mở được file CSV."; }
    } else { $msg = "error:Vui lòng tải lên file CSV hợp lệ."; }
}

if ($action === 'add') {
    $un = $db->real_escape_string(trim($_POST['username'] ?? ''));
    $ht = $db->real_escape_string(trim($_POST['ho_ten'] ?? ''));
    $nd = trim($_POST['ngay_sinh'] ?? '');
    $lp = $db->real_escape_string(trim($_POST['lop'] ?? ''));
    $kh = $db->real_escape_string(trim($_POST['khoa'] ?? ''));
    $em = $db->real_escape_string(trim($_POST['email'] ?? ''));
    $gt = $db->real_escape_string(trim($_POST['gioi_tinh'] ?? 'Nam'));

    $nd_sql = (!empty($nd) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nd)) ? "'" . $db->real_escape_string($nd) . "'" : "NULL";

    if (!empty($un) && !empty($ht)) {
        $pw_plain = (!empty($nd) && $nd !== 'NULL') ? date('dmY', strtotime($nd)) : '123456';
        $pw_hash  = password_hash($pw_plain, PASSWORD_DEFAULT);
        $pw_esc   = $db->real_escape_string($pw_hash);

        @$db->query("INSERT INTO users (username, password, role) VALUES ('$un', '$pw_esc', 'student')");
        $uid = (int)$db->insert_id;
        if (!$uid) {
            $r_u = @$db->query("SELECT id FROM users WHERE username='$un' LIMIT 1");
            if ($r_u && method_exists($r_u, 'fetch_assoc') && ($row_u = $r_u->fetch_assoc())) {
                $uid = (int)$row_u['id'];
            }
        }

        if ($uid > 0) {
            $ins = @$db->query("INSERT INTO students (user_id, ma_sv, ho_ten, ngay_sinh, lop, khoa, email, gioi_tinh) VALUES ($uid, '$un', '$ht', $nd_sql, '$lp', '$kh', '$em', '$gt')");
            if (!$ins) {
                @$db->query("INSERT INTO students (user_id, ma_sv, ho_ten, ngay_sinh, lop, khoa, email) VALUES ($uid, '$un', '$ht', $nd_sql, '$lp', '$kh', '$em')");
            }
            if (function_exists('writeSystemLog')) { writeSystemLog("Thêm sinh viên mới: $ht ($un)"); }
            session_write_close();
            header("Location: /tkb/teacher/quanly_sinhvien.php?msg=" . urlencode("success:Thêm sinh viên thành công!"));
            exit();
        }
    }
}

if ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $ht = $db->real_escape_string(trim($_POST['ho_ten'] ?? ''));
    $nd = trim($_POST['ngay_sinh'] ?? '');
    $lp = $db->real_escape_string(trim($_POST['lop'] ?? ''));
    $kh = $db->real_escape_string(trim($_POST['khoa'] ?? ''));
    $em = $db->real_escape_string(trim($_POST['email'] ?? ''));
    $gt = $db->real_escape_string(trim($_POST['gioi_tinh'] ?? 'Nam'));

    $nd_sql = (!empty($nd) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nd)) ? "'" . $db->real_escape_string($nd) . "'" : "NULL";

    if ($id > 0) {
        $u_id_target = 0;
        $has_student_row = false;
        $row_st = @$db->query("SELECT id, user_id FROM students WHERE id=$id OR user_id=$id LIMIT 1");
        if ($row_st && method_exists($row_st, 'fetch_assoc') && ($r_st = $row_st->fetch_assoc())) {
            $u_id_target = (int)$r_st['user_id'];
            $has_student_row = true;
        }
        if (!$u_id_target) {
            $u_id_target = $id; // Fallback to id as users.id
        }

        if (!empty($ht)) {
            @$db->query("UPDATE users SET ho_ten='$ht' WHERE id=$u_id_target");
        }

        if ($has_student_row) {
            $up = @$db->query("UPDATE students SET ho_ten='$ht', ngay_sinh=$nd_sql, lop='$lp', khoa='$kh', email='$em', gioi_tinh='$gt' WHERE id=$id OR user_id=$u_id_target");
            if (!$up) {
                @$db->query("UPDATE students SET ho_ten='$ht', ngay_sinh=$nd_sql, lop='$lp', khoa='$kh', email='$em' WHERE id=$id OR user_id=$u_id_target");
            }
        } else {
            $ma_sv = '';
            $r_ma = @$db->query("SELECT username FROM users WHERE id=$u_id_target LIMIT 1");
            if ($r_ma && method_exists($r_ma, 'fetch_assoc') && ($row_ma = $r_ma->fetch_assoc())) {
                $ma_sv = $row_ma['username'];
            }
            @$db->query("INSERT INTO students (user_id, ma_sv, ho_ten, ngay_sinh, lop, khoa, email, gioi_tinh) VALUES ($u_id_target, '$ma_sv', '$ht', $nd_sql, '$lp', '$kh', '$em', '$gt')");
        }

        if (function_exists('writeSystemLog')) { writeSystemLog("Cập nhật sinh viên ID: $u_id_target - Giới tính: $gt"); }
        session_write_close();
        header("Location: /tkb/teacher/quanly_sinhvien.php?msg=" . urlencode("success:Cập nhật thông tin sinh viên thành công!"));
        exit();
    }
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $u_id_del = 0;
        $row_st = @$db->query("SELECT user_id FROM students WHERE id=$id OR user_id=$id LIMIT 1");
        if ($row_st && method_exists($row_st, 'fetch_assoc') && ($r_st = $row_st->fetch_assoc())) {
            $u_id_del = (int)$r_st['user_id'];
        }
        if (!$u_id_del) {
            $u_id_del = $id;
        }

        @$db->query("DELETE FROM students WHERE id=$id OR user_id=$u_id_del");
        @$db->query("DELETE FROM users WHERE id=$u_id_del OR id=$id");

        if (function_exists('writeSystemLog')) { writeSystemLog("Xóa sinh viên ID: $u_id_del"); }
        session_write_close();
        header("Location: /tkb/teacher/quanly_sinhvien.php?msg=" . urlencode("success:Đã xóa sinh viên thành công!"));
        exit();
    }
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT COALESCE(s.id, u.id) as id, u.username, COALESCE(s.ma_sv, u.username) as ma_sv, COALESCE(s.ho_ten, u.ho_ten, u.username) as ho_ten, COALESCE(s.gioi_tinh, 'Nam') as gioi_tinh, COALESCE(s.lop, 'K24CDCNTT1') as lop, COALESCE(s.khoa, 'Công nghệ thông tin') as khoa, COALESCE(s.email, '') as email, s.ngay_sinh, s.avatar FROM users u LEFT JOIN students s ON u.id = s.user_id WHERE u.role = 'student'";
if ($search) $sql .= " AND (u.username LIKE '%$search%' OR s.ho_ten LIKE '%$search%' OR u.ho_ten LIKE '%$search%' OR s.lop LIKE '%$search%')";
$sql .= " ORDER BY u.id DESC";
$list = [];
$res_list = $db->query($sql);
if ($res_list) {
    while ($r = $res_list->fetch_assoc()) {
        $list[] = $r;
    }
}

$editSV = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $res_e = @$db->query("SELECT COALESCE(s.id, u.id) as id, u.username, COALESCE(s.ma_sv, u.username) as ma_sv, COALESCE(s.ho_ten, u.ho_ten, u.username) as ho_ten, COALESCE(s.gioi_tinh, 'Nam') as gioi_tinh, COALESCE(s.lop, 'K24CDCNTT1') as lop, COALESCE(s.khoa, 'Công nghệ thông tin') as khoa, COALESCE(s.email, '') as email, s.ngay_sinh FROM users u LEFT JOIN students s ON u.id = s.user_id WHERE u.id = $eid OR s.id = $eid LIMIT 1");
    if ($res_e && method_exists($res_e, 'fetch_assoc')) {
        $editSV = $res_e->fetch_assoc();
    }
}

$msgType = $msgText = '';
$msg_param = $_GET['msg'] ?? $msg;
if ($msg_param) [$msgType, $msgText] = explode(':', $msg_param, 2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Sinh Viên & Giới Tính - Giảng viên</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/teacher_nav.php'; ?>
<div class="page-header">
  <div><h1 class="page-title"><i class="fa-solid fa-users" style="color:var(--accent)"></i> Quản lý Sinh Viên & Khởi Tạo Tài Khoản</h1></div>
  <div style="display:flex; gap:10px;">
    <a href="?action=export_csv" class="btn btn-ghost" style="border-color:rgba(217,27,67,0.2); color:var(--accent); background:var(--bg2);"><i class="fa-solid fa-file-export"></i> Xuất CSV</a>
    <button class="btn btn-ghost" style="border-color:rgba(217,27,67,0.2); color:var(--accent); background:var(--bg2);" onclick="toggleModal('importModal')"><i class="fa-solid fa-file-import"></i> Nhập CSV</button>
    <button class="btn btn-primary" onclick="toggleModal('addModal')"><i class="fa-solid fa-user-plus"></i> Thêm SV mới</button>
  </div>
</div>

<?php if ($msgText): ?>
<div class="alert alert-<?= $msgType ?>" style="display:block; margin-bottom:20px;"><?= htmlspecialchars($msgText) ?></div>
<?php endif; ?>

<div style="display:flex; align-items:center; gap:20px; margin-bottom:20px;">
  <div style="flex:1; position:relative;">
    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text2);"></i>
    <form method="GET" style="margin:0;"><input style="width:100%; padding:10px 14px 10px 40px; border:1px solid var(--border); border-radius:8px; background:var(--bg2); color:var(--text); font-size:14px; outline:none;" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm theo tên, mã SV, lớp..." oninput="this.form.submit()"></form>
  </div>
  <span style="color:var(--text2);font-size:13px;">Tổng: <b style="color:var(--text)"><?= count($list) ?></b> sinh viên</span>
</div>

<div class="card">
  <div style="overflow-x:auto">
    <table>
      <thead><tr><th>#</th><th>Mã SV</th><th>Họ tên</th><th>Giới tính</th><th>Lớp</th><th>Khoa</th><th>Email</th><th>Ngày sinh</th><th style="text-align:center">Thao tác</th></tr></thead>
      <tbody>
        <?php if (empty($list)): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--text2);padding:30px">Không có dữ liệu</td></tr>
        <?php else: foreach ($list as $i => $sv): 
            $gt = $sv['gioi_tinh'] ?? 'Nam';
        ?>
        <tr>
          <td style="color:var(--text2)"><?= $i+1 ?></td>
          <td><code style="color:#f43f6d"><?= htmlspecialchars($sv['ma_sv']) ?></code></td>
          <td>
            <div style="display:flex; align-items:center; gap:10px;">
              <?php if (!empty($sv['avatar'])): 
                  $av_src = (strpos($sv['avatar'], 'http') === 0 || strpos($sv['avatar'], '/') === 0) ? $sv['avatar'] : '/tkb/assets/img/avatars/' . $sv['avatar'];
              ?>
                <img src="<?= htmlspecialchars($av_src) ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover; border:1px solid var(--accent); flex-shrink:0;" alt="Avatar">
              <?php else: ?>
                <?php
                  $parts = explode(' ', trim($sv['ho_ten']));
                  $initial = mb_substr(array_pop($parts), 0, 1, 'UTF-8');
                ?>
                <div style="width:32px; height:32px; border-radius:50%; background:rgba(217,27,67,0.08); color:var(--accent); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; border:1px solid rgba(217,27,67,0.15); flex-shrink:0;"><?= mb_strtoupper($initial, 'UTF-8') ?></div>
              <?php endif; ?>
              <span style="font-weight:500"><?= htmlspecialchars($sv['ho_ten']) ?></span>
            </div>
          </td>
          <td>
            <?php if ($gt === 'Nữ'): ?>
              <span style="background:#fce7f3; color:#db2777; padding:4px 10px; border-radius:50px; font-weight:800; font-size:12px;"><i class="fa-solid fa-venus"></i> Nữ (Pastel)</span>
            <?php elseif ($gt === 'Khác'): ?>
              <span style="background:#f3e8ff; color:#9333ea; padding:4px 10px; border-radius:50px; font-weight:800; font-size:12px;"><i class="fa-solid fa-genderless"></i> Khác</span>
            <?php else: ?>
              <span style="background:#dbeafe; color:#2563eb; padding:4px 10px; border-radius:50px; font-weight:800; font-size:12px;"><i class="fa-solid fa-mars"></i> Nam</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($sv['lop']) ?></td>
          <td><?= htmlspecialchars($sv['khoa']) ?></td>
          <td style="color:var(--text2);font-size:12px"><?= htmlspecialchars($sv['email']) ?></td>
          <td style="color:var(--text2);font-size:12px"><?= $sv['ngay_sinh'] ? date('d/m/Y', strtotime($sv['ngay_sinh'])) : '-' ?></td>
          <td style="text-align:center">
            <a href="?edit_id=<?= $sv['id'] ?>" class="btn btn-edit btn-sm"><i class="fa-solid fa-pen"></i></a>
            <a href="?action=delete&id=<?= $sv['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa sinh viên này?')"><i class="fa-solid fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Thêm SV Mới (CÓ CHỌN GIỚI TÍNH) -->
<div class="modal-overlay" id="addModal" onclick="if(event.target==this) toggleModal('addModal')">
  <div class="modal-box">
    <div class="modal-title"><i class="fa-solid fa-user-plus" style="color:var(--accent)"></i> Thêm sinh viên mới</div>
    <div class="modal-sub">Mật khẩu mặc định = ngày sinh (ddmmyyyy), hoặc 123456 nếu không có</div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Mã SV *</label><input class="form-input" name="username" required placeholder="SV2024xxx"></div>
        <div class="form-group"><label class="form-label">Họ tên *</label><input class="form-input" name="ho_ten" required placeholder="Nguyễn Văn A"></div>
      </div>

      <div class="form-row">
        <div class="form-group">
            <label class="form-label">Giới tính sinh viên *</label>
            <select class="form-select" name="gioi_tinh" required>
                <option value="Nam">👦 Sinh viên Nam (Giao diện chuẩn)</option>
                <option value="Nữ">👧 Sinh viên Nữ (Tự động chuyển Giao diện Pastel Luyentu)</option>
                <option value="Khác">✨ Khác</option>
            </select>
        </div>
        <div class="form-group"><label class="form-label">Ngày sinh</label><input class="form-input" name="ngay_sinh" type="date"></div>
      </div>

      <div class="form-row">
        <div class="form-group"><label class="form-label">Lớp *</label><input class="form-input" name="lop" required placeholder="CNTT24A"></div>
        <div class="form-group"><label class="form-label">Khoa / Ngành</label>
            <select class="form-select" name="khoa">
            <option value="">-- Chọn ngành --</option>
            <?php 
              $nganh_safe = (isset($NGANH_LIST) && is_array($NGANH_LIST)) ? $NGANH_LIST : ['Công nghệ thông tin', 'Cơ khí ô tô', 'Điện - Điện tử', 'Quản trị doanh nghiệp'];
              foreach ($nganh_safe as $ng): 
            ?>
                <option value="<?= htmlspecialchars($ng) ?>"><?= htmlspecialchars($ng) ?></option>
            <?php endforeach; ?>
            </select>
        </div>
      </div>

      <div class="form-group"><label class="form-label">Email</label><input class="form-input" name="email" type="email" placeholder="sv@email.com"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm Sinh Viên</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Nhập CSV -->
<div class="modal-overlay" id="importModal" onclick="if(event.target==this) toggleModal('importModal')">
  <div class="modal-box">
    <div class="modal-title"><i class="fa-solid fa-file-import" style="color:var(--accent)"></i> Nhập sinh viên từ file CSV</div>
    <div class="modal-sub">Định dạng cột: <code>Mã SV, Họ tên, Giới tính (Nam/Nữ), Ngày sinh (dd/mm/yyyy), Lớp, Khoa, Email</code></div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="import_csv">
      <div class="form-group" style="margin-top:20px;">
        <label class="form-label">Chọn file CSV *</label>
        <input class="form-input" name="csv_file" type="file" accept=".csv" required style="padding:8px 12px;">
      </div>
      <div class="modal-footer" style="margin-top:30px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('importModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Nhập dữ liệu</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Sửa SV (CÓ SỬA GIỚI TÍNH) -->
<?php if (!empty($editSV) && is_array($editSV)): ?>
<div class="modal-overlay show" id="editModal">
  <div class="modal-box">
    <div class="modal-title"><i class="fa-solid fa-pen-to-square" style="color:var(--accent)"></i> Sửa thông tin sinh viên</div>
    <div class="modal-sub">Mã SV: <?= htmlspecialchars($editSV['ma_sv'] ?? '') ?></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= htmlspecialchars($editSV['id'] ?? 0) ?>">
      
      <div class="form-row">
        <div class="form-group"><label class="form-label">Họ tên</label><input class="form-input" name="ho_ten" value="<?= htmlspecialchars($editSV['ho_ten'] ?? '') ?>" required></div>
        <div class="form-group">
            <label class="form-label">Giới tính sinh viên</label>
            <select class="form-select" name="gioi_tinh">
                <option value="Nam" <?= (($editSV['gioi_tinh'] ?? 'Nam') === 'Nam') ? 'selected' : '' ?>>👦 Sinh viên Nam</option>
                <option value="Nữ" <?= (($editSV['gioi_tinh'] ?? 'Nam') === 'Nữ') ? 'selected' : '' ?>>👧 Sinh viên Nữ (Giao diện Pastel Luyentu)</option>
                <option value="Khác" <?= (($editSV['gioi_tinh'] ?? 'Nam') === 'Khác') ? 'selected' : '' ?>>✨ Khác</option>
            </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group"><label class="form-label">Ngày sinh</label><input class="form-input" name="ngay_sinh" type="date" value="<?= htmlspecialchars($editSV['ngay_sinh'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">Lớp</label><input class="form-input" name="lop" value="<?= htmlspecialchars($editSV['lop'] ?? '') ?>"></div>
      </div>

      <div class="form-group"><label class="form-label">Khoa / Ngành</label>
        <select class="form-select" name="khoa">
          <option value="">-- Chọn ngành --</option>
          <?php 
            $nganh_safe = (isset($NGANH_LIST) && is_array($NGANH_LIST)) ? $NGANH_LIST : ['Công nghệ thông tin', 'Cơ khí ô tô', 'Điện - Điện tử', 'Quản trị doanh nghiệp'];
            foreach ($nganh_safe as $ng): 
          ?>
            <option value="<?= htmlspecialchars($ng) ?>" <?= (($editSV['khoa'] ?? '') === $ng) ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group"><label class="form-label">Email</label><input class="form-input" name="email" type="email" value="<?= htmlspecialchars($editSV['email'] ?? '') ?>"></div>
      
      <div class="modal-footer">
        <a href="/tkb/teacher/quanly_sinhvien.php" class="btn btn-ghost">Hủy</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu Thay Đổi</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function toggleModal(id) { document.getElementById(id).classList.toggle('show'); }
</script>
</body>
</html>
