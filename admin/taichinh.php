<?php
require_once '../config.php';
requireAdmin();
$db = getDB();
$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $student_id = (int)$_POST['student_id'];
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $so_tien = (float)str_replace(',', '', $_POST['so_tien']);
    $ngay_nop = trim($_POST['ngay_nop'] ?? '');
    $trang_thai = trim($_POST['trang_thai'] ?? 'Chưa nộp');
    
    $st = $db->prepare("INSERT INTO tai_chinh (student_id, noi_dung, so_tien, ngay_nop, trang_thai) VALUES (?, ?, ?, ?, ?)");
    $st->bind_param("isdss", $student_id, $noi_dung, $so_tien, $ngay_nop, $trang_thai);
    if ($st->execute()) $msg = 'success:Tạo khoản thu thành công!';
    else $msg = 'error:Lỗi: ' . $db->error;
}
if ($action === 'edit') {
    $id = (int)$_POST['id'];
    $student_id = (int)$_POST['student_id'];
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $so_tien = (float)str_replace(',', '', $_POST['so_tien']);
    $ngay_nop = trim($_POST['ngay_nop'] ?? '');
    $trang_thai = trim($_POST['trang_thai'] ?? 'Chưa nộp');
    
    $st = $db->prepare("UPDATE tai_chinh SET student_id=?, noi_dung=?, so_tien=?, ngay_nop=?, trang_thai=? WHERE id=?");
    $st->bind_param("isdssi", $student_id, $noi_dung, $so_tien, $ngay_nop, $trang_thai, $id);
    if ($st->execute()) $msg = 'success:Cập nhật khoản thu thành công!';
    else $msg = 'error:Lỗi: ' . $db->error;
}
if ($action === 'delete') {
    $id = (int)$_GET['id'];
    if ($db->query("DELETE FROM tai_chinh WHERE id=$id")) $msg = 'success:Đã xóa khoản thu!';
    else $msg = 'error:Không thể xóa!';
}

global $NGANH_LIST;
$fkhoa = $_GET['khoa'] ?? '';
$flop  = $_GET['lop']  ?? '';
$fsv   = $_GET['sv']   ?? '';

$lopList = [];
if ($fkhoa) {
    $stLop = $db->prepare("SELECT DISTINCT lop FROM students WHERE khoa = ? ORDER BY lop");
    $stLop->bind_param("s", $fkhoa);
    $stLop->execute();
    $lopList = $stLop->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $lopList = $db->query("SELECT DISTINCT lop FROM students ORDER BY lop")->fetch_all(MYSQLI_ASSOC);
}

$svSql = "SELECT id,ma_sv,ho_ten,lop FROM students WHERE 1=1";
$svParams = []; $svTypes = "";
if ($flop) {
    $svSql .= " AND lop=?"; $svParams[] = $flop; $svTypes .= "s";
} else if ($fkhoa) {
    $svSql .= " AND khoa=?"; $svParams[] = $fkhoa; $svTypes .= "s";
}
$svSql .= " ORDER BY lop, ho_ten";
if (!empty($svParams)) {
    $stSv = $db->prepare($svSql); $stSv->bind_param($svTypes, ...$svParams); $stSv->execute();
    $svList = $stSv->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $svList = $db->query($svSql)->fetch_all(MYSQLI_ASSOC);
}

$sql = "SELECT t.*, s.ma_sv, s.ho_ten, s.lop FROM tai_chinh t JOIN students s ON t.student_id = s.id WHERE 1=1";
if ($fsv) {
    $sql .= " AND t.student_id=" . (int)$fsv;
} else {
    if ($flop) {
        $sql .= " AND s.lop='" . $db->real_escape_string($flop) . "'";
    } else if ($fkhoa) {
        $sql .= " AND s.khoa='" . $db->real_escape_string($fkhoa) . "'";
    }
}
$sql .= " ORDER BY t.id DESC";
$list = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

$editRow = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $editRow = $db->query("SELECT * FROM tai_chinh WHERE id=$eid")->fetch_assoc();
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Tài chính</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/admin_nav.php'; ?>
<div class="main-content">
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-money-bill" style="color:#ef4444"></i> Quản lý Tài chính Sinh viên</h1></div>
    <button class="btn btn-primary" onclick="toggleModal('addModal')"><i class="fa-solid fa-file-invoice-dollar"></i> Tạo khoản thu mới</button>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <select class="form-select" name="khoa" onchange="this.form.lop.value=''; this.form.sv.value=''; this.form.submit()">
        <option value="">-- Tất cả ngành --</option>
        <?php foreach ($NGANH_LIST as $ng): ?>
        <option <?= $fkhoa == $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select" name="lop" onchange="this.form.sv.value=''; this.form.submit()">
        <option value="">-- Tất cả lớp --</option>
        <?php foreach ($lopList as $l): ?>
        <option <?= $flop == $l['lop'] ? 'selected' : '' ?>><?= htmlspecialchars($l['lop']) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select" name="sv" onchange="this.form.submit()">
        <option value="">-- Tất cả sinh viên --</option>
        <?php foreach ($svList as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $fsv == $s['id'] ? 'selected' : '' ?>>[<?= htmlspecialchars($s['lop']) ?>] <?= htmlspecialchars($s['ho_ten']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <span style="color:var(--text2);font-size:13px;align-self:center">Tổng: <b style="color:var(--text)"><?= count($list) ?></b> khoản thu</span>
  </div>

  <div class="card">
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>#</th><th>Sinh viên</th><th>Nội dung thu</th><th>Số tiền (VNĐ)</th><th>Trạng thái</th><th>Ngày nộp</th><th>Thao tác</th></tr></thead>
        <tbody>
          <?php if (empty($list)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text2);padding:30px">Chưa có khoản thu nào</td></tr>
          <?php else: foreach ($list as $i => $tc): ?>
          <tr>
            <td style="color:var(--text2)"><?= $i + 1 ?></td>
            <td><span style="font-weight:600"><?= htmlspecialchars($tc['ho_ten']) ?></span><br><code style="font-size:11px;color:#f43f6d"><?= htmlspecialchars($tc['ma_sv']) ?> - <?= htmlspecialchars($tc['lop']) ?></code></td>
            <td><?= htmlspecialchars($tc['noi_dung']) ?></td>
            <td style="font-weight:700"><?= number_format($tc['so_tien'], 0, ',', '.') ?></td>
            <td>
              <?php if ($tc['trang_thai'] === 'Đã nộp'): ?>
                <span class="badge badge-pass">Đã nộp</span>
              <?php else: ?>
                <span class="badge" style="background:#fef2f2;color:#ef4444;border:1px solid #fecaca">Chưa nộp</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--text2);font-size:13px"><?= $tc['ngay_nop'] ? date('d/m/Y', strtotime($tc['ngay_nop'])) : '-' ?></td>
            <td>
              <a href="?edit_id=<?= $tc['id'] ?>" class="btn btn-edit btn-sm"><i class="fa-solid fa-pen"></i></a>
              <a href="?action=delete&id=<?= $tc['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa khoản thu này?')"><i class="fa-solid fa-trash"></i></a>
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
    <div class="modal-title">Tạo khoản thu mới</div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label class="form-label">Sinh viên *</label>
        <select class="form-select" name="student_id" required>
          <option value="">-- Chọn sinh viên --</option>
          <?php foreach ($svList as $s): ?>
          <option value="<?= $s['id'] ?>">[<?= htmlspecialchars($s['lop']) ?>] <?= htmlspecialchars($s['ho_ten']) ?> (<?= htmlspecialchars($s['ma_sv']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Nội dung thu *</label>
        <input class="form-input" name="noi_dung" required placeholder="Học phí Học kỳ 1...">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Số tiền (VNĐ) *</label>
          <input class="form-input" name="so_tien" type="number" required placeholder="4500000">
        </div>
        <div class="form-group">
          <label class="form-label">Trạng thái</label>
          <select class="form-select" name="trang_thai">
            <option>Chưa nộp</option>
            <option>Đã nộp</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Ngày nộp</label>
        <input class="form-input" name="ngay_nop" type="date">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tạo</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Sửa -->
<?php if ($editRow): ?>
<div class="modal-overlay show" id="editModal">
  <div class="modal-box">
    <div class="modal-title">Sửa khoản thu</div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
      <div class="form-group">
        <label class="form-label">Sinh viên *</label>
        <select class="form-select" name="student_id" required>
          <option value="">-- Chọn sinh viên --</option>
          <?php foreach ($svList as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $s['id'] == $editRow['student_id'] ? 'selected' : '' ?>>[<?= htmlspecialchars($s['lop']) ?>] <?= htmlspecialchars($s['ho_ten']) ?> (<?= htmlspecialchars($s['ma_sv']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Nội dung thu *</label>
        <input class="form-input" name="noi_dung" required value="<?= htmlspecialchars($editRow['noi_dung']) ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Số tiền (VNĐ) *</label>
          <input class="form-input" name="so_tien" type="number" required value="<?= round($editRow['so_tien']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Trạng thái</label>
          <select class="form-select" name="trang_thai">
            <option <?= $editRow['trang_thai'] == 'Chưa nộp' ? 'selected' : '' ?>>Chưa nộp</option>
            <option <?= $editRow['trang_thai'] == 'Đã nộp' ? 'selected' : '' ?>>Đã nộp</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Ngày nộp</label>
        <input class="form-input" name="ngay_nop" type="date" value="<?= $editRow['ngay_nop'] ?>">
      </div>
      <div class="modal-footer">
        <a href="/tkb/admin/taichinh.php" class="btn btn-ghost">Hủy</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function toggleModal(id) {
  document.getElementById(id).classList.toggle('show');
}
</script>
</body>
</html>
