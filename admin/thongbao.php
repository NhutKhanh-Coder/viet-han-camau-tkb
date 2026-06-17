<?php
require_once '../config.php';
requireAdmin();
$db = getDB();
$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $tieu_de = trim($_POST['tieu_de'] ?? '');
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $ngay_dang = trim($_POST['ngay_dang'] ?? date('Y-m-d'));
    $trang_thai = trim($_POST['trang_thai'] ?? 'Đã xuất bản');
    $khoa = trim($_POST['khoa'] ?? '');
    $st = $db->prepare("INSERT INTO thong_bao (tieu_de, noi_dung, ngay_dang, trang_thai, khoa) VALUES (?, ?, ?, ?, ?)");
    $st->bind_param("sssss", $tieu_de, $noi_dung, $ngay_dang, $trang_thai, $khoa);
    if ($st->execute()) $msg = 'success:Đăng thông báo thành công!';
    else $msg = 'error:Lỗi: ' . $db->error;
}
if ($action === 'edit') {
    $id = (int)$_POST['id'];
    $tieu_de = trim($_POST['tieu_de'] ?? '');
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $ngay_dang = trim($_POST['ngay_dang'] ?? '');
    $trang_thai = trim($_POST['trang_thai'] ?? 'Đã xuất bản');
    $khoa = trim($_POST['khoa'] ?? '');
    $st = $db->prepare("UPDATE thong_bao SET tieu_de=?, noi_dung=?, ngay_dang=?, trang_thai=?, khoa=? WHERE id=?");
    $st->bind_param("sssssi", $tieu_de, $noi_dung, $ngay_dang, $trang_thai, $khoa, $id);
    if ($st->execute()) $msg = 'success:Cập nhật thông báo thành công!';
    else $msg = 'error:Lỗi: ' . $db->error;
}
if ($action === 'delete') {
    $id = (int)$_GET['id'];
    if ($db->query("DELETE FROM thong_bao WHERE id=$id")) $msg = 'success:Đã xóa thông báo!';
    else $msg = 'error:Không thể xóa!';
}

global $NGANH_LIST;
$fkhoa = $_GET['khoa'] ?? '';

$sql = "SELECT * FROM thong_bao WHERE 1=1";
if ($fkhoa) {
    $sql .= " AND khoa='" . $db->real_escape_string($fkhoa) . "'";
}
$sql .= " ORDER BY ngay_dang DESC, id DESC";
$list = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

$editRow = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $editRow = $db->query("SELECT * FROM thong_bao WHERE id=$eid")->fetch_assoc();
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Thông báo</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/admin_nav.php'; ?>
<div class="main-content">
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-bullhorn" style="color:#eab308"></i> Quản lý Thông báo</h1></div>
    <button class="btn btn-primary" onclick="toggleModal('addModal')"><i class="fa-solid fa-plus"></i> Đăng thông báo mới</button>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <select class="form-select" name="khoa" onchange="this.form.submit()">
        <option value="">-- Tất cả (Thông báo chung) --</option>
        <?php foreach ($NGANH_LIST as $ng): ?>
        <option <?= $fkhoa == $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <span style="color:var(--text2);font-size:13px;align-self:center">Tổng: <b style="color:var(--text)"><?= count($list) ?></b> thông báo</span>
  </div>

  <div class="card">
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>#</th><th>Ngày đăng</th><th>Ngành (Phạm vi)</th><th>Tiêu đề</th><th>Nội dung tóm tắt</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
          <?php if (empty($list)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text2);padding:30px">Chưa có thông báo nào</td></tr>
          <?php else: foreach ($list as $i => $tb): ?>
          <tr>
            <td style="color:var(--text2)"><?= $i + 1 ?></td>
            <td><?= date('d/m/Y', strtotime($tb['ngay_dang'])) ?></td>
            <td style="font-size:13px;color:var(--text2)"><?= $tb['khoa'] ? htmlspecialchars($tb['khoa']) : '<span style="color:#34d399">Tất cả ngành</span>' ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($tb['tieu_de']) ?></td>
            <td style="color:var(--text2);font-size:13px"><?= htmlspecialchars(mb_substr($tb['noi_dung'], 0, 50, 'UTF-8')) ?>...</td>
            <td><span class="badge badge-pass"><?= htmlspecialchars($tb['trang_thai']) ?></span></td>
            <td>
              <a href="?edit_id=<?= $tb['id'] ?>" class="btn btn-edit btn-sm"><i class="fa-solid fa-pen"></i></a>
              <a href="?action=delete&id=<?= $tb['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa thông báo này?')"><i class="fa-solid fa-trash"></i></a>
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
    <div class="modal-title">Đăng thông báo mới</div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label class="form-label">Phạm vi (Ngành) *</label>
        <select class="form-select" name="khoa">
          <option value="">-- Tất cả ngành (Thông báo chung) --</option>
          <?php foreach ($NGANH_LIST as $ng): ?>
          <option value="<?= htmlspecialchars($ng) ?>"><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Tiêu đề *</label>
        <input class="form-input" name="tieu_de" required placeholder="Nhập tiêu đề...">
      </div>
      <div class="form-group">
        <label class="form-label">Nội dung</label>
        <textarea class="form-input" name="noi_dung" rows="4" placeholder="Nhập nội dung thông báo..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Ngày đăng</label>
          <input class="form-input" name="ngay_dang" type="date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Trạng thái</label>
          <select class="form-select" name="trang_thai">
            <option>Đã xuất bản</option>
            <option>Bản nháp</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Đăng</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Sửa -->
<?php if ($editRow): ?>
<div class="modal-overlay show" id="editModal">
  <div class="modal-box">
    <div class="modal-title">Sửa thông báo</div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
      <div class="form-group">
        <label class="form-label">Phạm vi (Ngành) *</label>
        <select class="form-select" name="khoa">
          <option value="">-- Tất cả ngành (Thông báo chung) --</option>
          <?php foreach ($NGANH_LIST as $ng): ?>
          <option value="<?= htmlspecialchars($ng) ?>" <?= $editRow['khoa'] == $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Tiêu đề *</label>
        <input class="form-input" name="tieu_de" required value="<?= htmlspecialchars($editRow['tieu_de']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Nội dung</label>
        <textarea class="form-input" name="noi_dung" rows="4"><?= htmlspecialchars($editRow['noi_dung']) ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Ngày đăng</label>
          <input class="form-input" name="ngay_dang" type="date" value="<?= $editRow['ngay_dang'] ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Trạng thái</label>
          <select class="form-select" name="trang_thai">
            <option <?= $editRow['trang_thai'] == 'Đã xuất bản' ? 'selected' : '' ?>>Đã xuất bản</option>
            <option <?= $editRow['trang_thai'] == 'Bản nháp' ? 'selected' : '' ?>>Bản nháp</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <a href="/tkb/admin/thongbao.php" class="btn btn-ghost">Hủy</a>
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
