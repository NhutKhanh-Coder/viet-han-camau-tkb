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

if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $row = $db->query("SELECT user_id FROM students WHERE id=$id")->fetch_assoc();
    if ($row) {
        $db->query("DELETE FROM users WHERE id={$row['user_id']}");
        $msg = 'success:Đã xóa sinh viên!';
    }
}

// LẤY DANH SÁCH
$search = trim($_GET['q'] ?? '');
$sql = "SELECT s.*, u.username FROM students s JOIN users u ON s.user_id=u.id";
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
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Sinh Viên</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/admin_nav.php'; ?>
<div class="main-content">
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-users" style="color:var(--accent)"></i> Quản lý Sinh Viên</h1></div>
    <div style="display:flex; gap:10px;">
      <a href="?action=export_csv" class="btn btn-ghost" style="border-color:rgba(217, 27, 67, 0.2); color:var(--accent); background:#fff;"><i class="fa-solid fa-file-export"></i> Xuất CSV</a>
      <button class="btn btn-ghost" style="border-color:rgba(217, 27, 67, 0.2); color:var(--accent); background:#fff;" onclick="toggleModal('importModal')"><i class="fa-solid fa-file-import"></i> Nhập CSV</button>
      <button class="btn btn-primary" onclick="toggleModal('addModal')"><i class="fa-solid fa-user-plus"></i> Thêm SV mới</button>
    </div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <div class="search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <form method="GET"><input class="search-input" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm theo tên, mã SV, lớp..." oninput="this.form.submit()"></form>
    </div>
    <span style="color:var(--text2);font-size:13px;align-self:center">Tổng: <b style="color:var(--text)"><?= count($list) ?></b> sinh viên</span>
  </div>

  <div class="card">
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>#</th><th>Mã SV</th><th>Họ tên</th><th>Lớp</th><th>Khoa</th><th>Email</th><th>Ngày sinh</th><th style="text-align:center">Thao tác</th></tr></thead>
        <tbody>
          <?php if (empty($list)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text2);padding:30px">Không có dữ liệu</td></tr>
          <?php else: foreach ($list as $i => $sv): ?>
          <tr>
            <td style="color:var(--text2)"><?= $i+1 ?></td>
            <td><code style="color:#f43f6d"><?= $sv['ma_sv'] ?></code></td>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <?php if(!empty($sv['avatar'])): ?>
                  <img src="/tkb/assets/img/avatars/<?= htmlspecialchars($sv['avatar']) ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover; border:1px solid var(--accent);" alt="Avatar">
                <?php else: ?>
                  <?php 
                    $parts = explode(' ', trim($sv['ho_ten']));
                    $initial = mb_substr(array_pop($parts), 0, 1, 'UTF-8');
                  ?>
                  <div style="width:32px; height:32px; border-radius:50%; background:rgba(255,0,0,0.1); color:var(--accent); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; border:1px solid rgba(255,0,0,0.2);"><?= mb_strtoupper($initial, 'UTF-8') ?></div>
                <?php endif; ?>
                <span style="font-weight:500"><?= htmlspecialchars($sv['ho_ten']) ?></span>
              </div>
            </td>
            <td><?= $sv['lop'] ?></td>
            <td><?= htmlspecialchars($sv['khoa']) ?></td>
            <td style="color:var(--text2);font-size:12px"><?= $sv['email'] ?></td>
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
</div>

<!-- Modal Thêm -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-title">Thêm sinh viên mới</div>
    <div class="modal-sub">Mật khẩu mặc định = ngày sinh (ddmmyyyy)</div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Mã SV *</label><input class="form-input" name="username" required placeholder="SV2024xxx"></div>
        <div class="form-group"><label class="form-label">Họ tên *</label><input class="form-input" name="ho_ten" required placeholder="Nguyễn Văn A"></div>
      </div>
      <div class="form-row">
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
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Nhập CSV -->
<div class="modal-overlay" id="importModal">
  <div class="modal-box">
    <div class="modal-title">Nhập sinh viên từ file CSV</div>
    <div class="modal-sub">
      Chọn file CSV chứa danh sách sinh viên. Định dạng cột gợi ý:<br>
      <code>Mã SV, Họ tên, Ngày sinh (dd/mm/yyyy), Lớp, Khoa, Email</code>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="import_csv">
      <div class="form-group" style="margin-top: 20px;">
        <label class="form-label">Chọn file CSV *</label>
        <input class="form-input" name="csv_file" type="file" accept=".csv" required style="padding: 8px 12px;">
      </div>
      <div class="modal-footer" style="margin-top: 30px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('importModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Nhập dữ liệu</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Sửa -->
<?php if ($editSV): ?>
<div class="modal-overlay show" id="editModal">
  <div class="modal-box">
    <div class="modal-title">Sửa thông tin sinh viên</div>
    <div class="modal-sub">Mã SV: <?= $editSV['ma_sv'] ?></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editSV['id'] ?>">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Họ tên</label><input class="form-input" name="ho_ten" value="<?= htmlspecialchars($editSV['ho_ten']) ?>" required></div>
        <div class="form-group"><label class="form-label">Ngày sinh</label><input class="form-input" name="ngay_sinh" type="date" value="<?= $editSV['ngay_sinh'] ?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Lớp</label><input class="form-input" name="lop" value="<?= $editSV['lop'] ?>"></div>
        <div class="form-group"><label class="form-label">Khoa / Ngành</label>
          <select class="form-select" name="khoa">
            <option value="">-- Chọn ngành --</option>
            <?php global $NGANH_LIST; foreach ($NGANH_LIST as $ng): ?>
              <option value="<?= htmlspecialchars($ng) ?>" <?= ($editSV['khoa'] === $ng) ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input class="form-input" name="email" type="email" value="<?= $editSV['email'] ?>"></div>
      <div class="modal-footer">
        <a href="/tkb/admin/students.php" class="btn btn-ghost">Hủy</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function toggleModal(id){
  const m=document.getElementById(id);
  m.classList.toggle('show');
}
</script>
</body>
</html>
