<?php
require_once '../config.php';
requireTeacher();
$db  = getDB();
$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
global $NGANH_LIST;

if ($action === 'add') {
    $ma  = trim($_POST['ma_mon']   ?? '');
    $ten = trim($_POST['ten_mon']  ?? '');
    $tc  = (int)($_POST['so_tin_chi'] ?? 2);
    $kh  = trim($_POST['khoa'] ?? '');
    $st  = $db->prepare("INSERT INTO mon_hoc (ma_mon,ten_mon,so_tin_chi,khoa) VALUES(?,?,?,?)");
    $st->bind_param("ssis", $ma, $ten, $tc, $kh);
    if ($st->execute()) { writeSystemLog("Thêm môn học: $ten ($ma)"); $msg = 'success:Thêm môn học thành công!'; }
    else $msg = 'error:Mã môn đã tồn tại hoặc có lỗi!';
}
if ($action === 'edit') {
    $id  = (int)$_POST['id'];
    $ten = trim($_POST['ten_mon']   ?? '');
    $tc  = (int)($_POST['so_tin_chi'] ?? 2);
    $kh  = trim($_POST['khoa'] ?? '');
    $st  = $db->prepare("UPDATE mon_hoc SET ten_mon=?,so_tin_chi=?,khoa=? WHERE id=?");
    $st->bind_param("sisi", $ten, $tc, $kh, $id);
    $st->execute();
    writeSystemLog("Cập nhật môn học ID: $id");
    $msg = 'success:Cập nhật môn học thành công!';
}
if ($action === 'delete') {
    $id = (int)$_GET['id'];
    if ($db->query("DELETE FROM mon_hoc WHERE id=$id")) { writeSystemLog("Xóa môn học ID: $id"); $msg = 'success:Đã xóa môn học!'; }
    else $msg = 'error:Không thể xóa (đang được sử dụng)!';
}

$list    = $db->query("SELECT * FROM mon_hoc ORDER BY ma_mon")->fetch_all(MYSQLI_ASSOC);
$editRow = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $editRow = $db->query("SELECT * FROM mon_hoc WHERE id=$eid")->fetch_assoc();
}
$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Môn Học - Giảng viên</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/teacher_nav.php'; ?>

<div class="page-header">
  <div><h1 class="page-title"><i class="fa-solid fa-book" style="color:var(--accent)"></i> Quản lý Môn Học</h1>
  <p style="color:var(--text2); margin-top:5px;">Thêm, sửa môn học và phân công môn học cho giảng viên</p></div>
  <button class="btn btn-primary" onclick="toggleModal('addModal')"><i class="fa-solid fa-plus"></i> Thêm môn học</button>
</div>

<?php if ($msgText): ?>
<div class="alert alert-<?= $msgType ?>" style="display:block; margin-bottom:20px;"><?= htmlspecialchars($msgText) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    <span class="card-title"><i class="fa-solid fa-list"></i> Danh sách môn học (<?= count($list) ?>)</span>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead><tr><th>#</th><th>Mã môn</th><th>Tên môn học</th><th>Ngành/Khoa</th><th style="text-align:center">Số tín chỉ</th><th style="text-align:center">Thao tác</th></tr></thead>
      <tbody>
        <?php foreach ($list as $i => $m): ?>
        <tr>
          <td style="color:var(--text2)"><?= $i+1 ?></td>
          <td><code style="color:#f43f6d"><?= htmlspecialchars($m['ma_mon']) ?></code></td>
          <td style="font-weight:500"><?= htmlspecialchars($m['ten_mon']) ?></td>
          <td style="color:var(--text2); font-size:13px;"><?= htmlspecialchars($m['khoa'] ?: 'Dùng chung') ?></td>
          <td style="text-align:center"><span class="badge" style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.2);"><?= $m['so_tin_chi'] ?> TC</span></td>
          <td style="text-align:center">
            <a href="?edit_id=<?= $m['id'] ?>" class="btn btn-edit btn-sm"><i class="fa-solid fa-pen"></i></a>
            <a href="?action=delete&id=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa môn học này?')"><i class="fa-solid fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Thêm -->
<div class="modal-overlay" id="addModal" onclick="if(event.target==this) toggleModal('addModal')">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-title"><i class="fa-solid fa-plus" style="color:var(--accent)"></i> Thêm môn học</div>
    <div class="modal-sub">Điền thông tin môn học mới vào hệ thống</div>
    <form method="POST" action="quanly_monhoc.php"><input type="hidden" name="action" value="add">
      <div class="form-group"><label class="form-label">Mã môn *</label><input class="form-input" name="ma_mon" required placeholder="CNTT101"></div>
      <div class="form-group"><label class="form-label">Tên môn học *</label><input class="form-input" name="ten_mon" required placeholder="Nhập môn lập trình"></div>
      <div class="form-group"><label class="form-label">Khoa / Ngành</label>
        <select class="form-select" name="khoa">
          <option value="">-- Dùng chung --</option>
          <?php foreach ($NGANH_LIST as $ng): ?>
            <option value="<?= htmlspecialchars($ng) ?>"><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Số tín chỉ</label><input class="form-input" name="so_tin_chi" type="number" min="1" max="5" value="2"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editRow): ?>
<div class="modal-overlay show">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-title"><i class="fa-solid fa-pen-to-square" style="color:var(--accent)"></i> Sửa môn học</div>
    <div class="modal-sub">Mã môn: <code style="color:#f43f6d"><?= htmlspecialchars($editRow['ma_mon']) ?></code></div>
    <form method="POST" action="quanly_monhoc.php"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= $editRow['id'] ?>">
      <div class="form-group"><label class="form-label">Tên môn học</label><input class="form-input" name="ten_mon" required value="<?= htmlspecialchars($editRow['ten_mon']) ?>"></div>
      <div class="form-group"><label class="form-label">Khoa / Ngành</label>
        <select class="form-select" name="khoa">
          <option value="">-- Dùng chung --</option>
          <?php foreach ($NGANH_LIST as $ng): ?>
            <option value="<?= htmlspecialchars($ng) ?>" <?= ($editRow['khoa'] == $ng) ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Số tín chỉ</label><input class="form-input" name="so_tin_chi" type="number" min="1" max="5" value="<?= $editRow['so_tin_chi'] ?>"></div>
      <div class="modal-footer">
        <a href="/tkb/teacher/quanly_monhoc.php" class="btn btn-ghost">Hủy</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>function toggleModal(id) { document.getElementById(id).classList.toggle('show'); }</script>
</body>
</html>
