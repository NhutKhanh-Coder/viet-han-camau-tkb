<?php
require_once '../config.php';
requirePrincipal();
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
<title>Quản lý Thông báo - Hiệu Trưởng</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
/* CSS MODAL */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    z-index: 1000; display: none; align-items: center; justify-content: center;
}
.modal-overlay.show { display: flex; }
.modal-box {
    background: var(--bg2); border: 1px solid var(--border);
    padding: 30px; border-radius: 14px; width: 100%; max-width: 500px;
    box-shadow: var(--shadow-lg); position: relative;
}
.modal-title { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 800; color: var(--text); margin-bottom: 20px; }
.modal-close { position: absolute; top: 15px; right: 15px; cursor: pointer; color: var(--text2); font-size: 20px; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: 12px; font-weight: 700; color: var(--text2); margin-bottom: 8px; text-transform: uppercase; }
.form-input, .form-textarea, .form-select {
    width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px;
    background: var(--bg) !important; color: var(--text) !important; outline: none; font-family: 'Outfit', sans-serif;
}
.form-textarea { resize: vertical; min-height: 100px; }
.form-input:focus, .form-textarea:focus, .form-select:focus { border-color: var(--accent); }
</style>
</head>
<body>
<?php include '../includes/principal_nav.php'; ?>
<div class="main-content" style="padding:40px 30px;">
  <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
    <div>
        <h1 class="page-title" style="margin:0;"><i class="fa-solid fa-bullhorn" style="color:#fbbf24"></i> Đăng Phát Thông Báo</h1>
        <p class="page-sub" style="margin-top:5px; color:var(--text2);">Đăng tải và cập nhật tin tức, quy định cho cán bộ và sinh viên</p>
    </div>
    <button class="btn btn-primary" onclick="toggleModal('addModal')" style="background:#fbbf24; color:#000; border:none; font-weight:700;"><i class="fa-solid fa-plus"></i> Đăng thông báo mới</button>
  </div>

  <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType ?>" style="background:<?= $msgType=='success'?'rgba(16, 185, 129, 0.1)':'rgba(239, 68, 68, 0.1)' ?>; border: 1px solid <?= $msgType=='success'?'#10b981':'#ef4444' ?>; color: <?= $msgType=='success'?'#059669':'#dc2626' ?>; padding: 12px; border-radius: 10px; margin-bottom: 20px;">
        <?= htmlspecialchars($msgText) ?>
    </div>
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
        <thead><tr><th>#</th><th>Ngày đăng</th><th>Phạm vi</th><th>Tiêu đề</th><th>Nội dung</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
          <?php if (empty($list)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text2);padding:30px">Chưa có thông báo nào</td></tr>
          <?php else: foreach ($list as $i => $tb): ?>
          <tr>
            <td style="color:var(--text2)"><?= $i + 1 ?></td>
            <td style="white-space: nowrap;"><?= date('d/m/Y', strtotime($tb['ngay_dang'])) ?></td>
            <td><span class="badge" style="background:rgba(251,191,36,0.12); color:#fbbf24; border:1px solid rgba(251,191,36,0.2);"><?= htmlspecialchars($tb['khoa'] ?: 'Chung') ?></span></td>
            <td><strong><?= htmlspecialchars($tb['tieu_de']) ?></strong></td>
            <td><div style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text2);"><?= htmlspecialchars(strip_tags($tb['noi_dung'])) ?></div></td>
            <td>
              <?php if ($tb['trang_thai'] === 'Đã xuất bản'): ?>
                <span class="badge badge-pass" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid #10b981;">Đã xuất bản</span>
              <?php else: ?>
                <span class="badge" style="background:var(--bg3); color:var(--text2); border:1px solid var(--border);">Bản nháp</span>
              <?php endif; ?>
            </td>
            <td style="white-space: nowrap;">
              <a href="?edit_id=<?= $tb['id'] ?>" class="btn btn-ghost btn-sm" style="padding: 6px 12px; font-size:12px; margin-right:5px;"><i class="fa-solid fa-pen-to-square"></i> Sửa</a>
              <a href="?action=delete&id=<?= $tb['id'] ?>" class="btn btn-danger btn-sm" style="padding: 6px 12px; font-size:12px;" onclick="return confirm('Xóa thông báo này?')"><i class="fa-solid fa-trash-can"></i> Xóa</a>
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
    <div class="modal-close" onclick="toggleModal('addModal')">&times;</div>
    <h3 class="modal-title"><i class="fa-solid fa-bullhorn"></i> Đăng thông báo mới</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label class="form-label">Tiêu đề</label>
        <input type="text" name="tieu_de" class="form-input" required placeholder="Nhập tiêu đề thông báo">
      </div>
      <div class="form-group">
        <label class="form-label">Phạm vi ngành học</label>
        <select name="khoa" class="form-select">
          <option value="">-- Thông báo chung --</option>
          <?php foreach ($NGANH_LIST as $ng): ?>
          <option><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Nội dung</label>
        <textarea name="noi_dung" class="form-textarea" required placeholder="Nhập nội dung thông báo..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Trạng thái</label>
        <select name="trang_thai" class="form-select">
          <option>Đã xuất bản</option>
          <option>Bản nháp</option>
        </select>
      </div>
      <div style="text-align:right;">
        <button type="submit" class="btn btn-primary" style="background:#fbbf24; color:#000; font-weight:700;"><i class="fa-solid fa-paper-plane"></i> Đăng phát</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Sửa -->
<?php if ($editRow): ?>
<div class="modal-overlay show" id="editModal">
  <div class="modal-box">
    <div class="modal-close" onclick="window.location.href='thongbao.php'">&times;</div>
    <h3 class="modal-title"><i class="fa-solid fa-pen-to-square"></i> Cập nhật thông báo</h3>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
      <div class="form-group">
        <label class="form-label">Tiêu đề</label>
        <input type="text" name="tieu_de" class="form-input" value="<?= htmlspecialchars($editRow['tieu_de']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phạm vi ngành học</label>
        <select name="khoa" class="form-select">
          <option value="">-- Thông báo chung --</option>
          <?php foreach ($NGANH_LIST as $ng): ?>
          <option <?= $editRow['khoa'] == $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Nội dung</label>
        <textarea name="noi_dung" class="form-textarea" required><?= htmlspecialchars($editRow['noi_dung']) ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Ngày đăng</label>
        <input type="date" name="ngay_dang" class="form-input" value="<?= htmlspecialchars($editRow['ngay_dang']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Trạng thái</label>
        <select name="trang_thai" class="form-select">
          <option <?= $editRow['trang_thai'] == 'Đã xuất bản' ? 'selected' : '' ?>>Đã xuất bản</option>
          <option <?= $editRow['trang_thai'] == 'Bản nháp' ? 'selected' : '' ?>>Bản nháp</option>
        </select>
      </div>
      <div style="text-align:right;">
        <button type="submit" class="btn btn-primary" style="background:#fbbf24; color:#000; font-weight:700;"><i class="fa-solid fa-save"></i> Cập nhật</button>
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
