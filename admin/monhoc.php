<?php
require_once '../config.php';
requireAdmin();
$db  = getDB();
$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $ma  = trim($_POST['ma_mon']   ?? '');
    $ten = trim($_POST['ten_mon']  ?? '');
    $tc  = (int)($_POST['so_tin_chi'] ?? 2);
    $kh  = trim($_POST['khoa'] ?? '');
    $st  = $db->prepare("INSERT INTO mon_hoc (ma_mon,ten_mon,so_tin_chi,khoa) VALUES(?,?,?,?)");
    $st->bind_param("ssis",$ma,$ten,$tc,$kh);
    if ($st->execute()) $msg='success:Thêm môn học thành công!';
    else $msg='error:Mã môn đã tồn tại!';
}
if ($action === 'edit') {
    $id  = (int)$_POST['id'];
    $ten = trim($_POST['ten_mon']   ?? '');
    $tc  = (int)($_POST['so_tin_chi'] ?? 2);
    $kh  = trim($_POST['khoa'] ?? '');
    $st  = $db->prepare("UPDATE mon_hoc SET ten_mon=?,so_tin_chi=?,khoa=? WHERE id=?");
    $st->bind_param("sisi",$ten,$tc,$kh,$id);
    $st->execute(); $msg='success:Cập nhật thành công!';
}
if ($action === 'delete') {
    $id = (int)$_GET['id'];
    if ($db->query("DELETE FROM mon_hoc WHERE id=$id")) $msg='success:Đã xóa!';
    else $msg='error:Không thể xóa (đang được sử dụng)!';
}

$list   = $db->query("SELECT * FROM mon_hoc ORDER BY ma_mon")->fetch_all(MYSQLI_ASSOC);
$editRow= null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $editRow = $db->query("SELECT * FROM mon_hoc WHERE id=$eid")->fetch_assoc();
}
$msgType=$msgText=''; if($msg) [$msgType,$msgText]=explode(':',$msg,2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Môn Học - Hệ Thống Quản Trị</title>
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
      <h1 class="page-title"><i class="fa-solid fa-book"></i> Quản lý Môn Học</h1>
      <p class="page-sub">Quản lý danh mục học phần đào tạo, phân bổ số tín chỉ và ngành học áp dụng</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="toggleModal('addModal')"><i class="fa-solid fa-plus"></i> Thêm môn học</button>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>">
    <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= htmlspecialchars($msgText) ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th style="width:50px; text-align:center;">#</th>
            <th style="width:160px;">Mã môn</th>
            <th>Tên môn học</th>
            <th style="width:280px;">Ngành / Khoa</th>
            <th style="width:140px; text-align:center;">Số tín chỉ</th>
            <th style="width:110px; text-align:center;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($list)): ?>
            <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:40px;">Chưa có môn học nào trong hệ thống.</td></tr>
          <?php else: foreach($list as $i=>$m): ?>
          <tr>
            <td style="text-align:center; color:#94a3b8; font-weight:600;"><?= $i+1 ?></td>
            <td>
              <span style="display:inline-block; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:12.5px; font-weight:700; background:#f8fafc; color:#0f172a; padding:4px 9px; border-radius:6px; border:1px solid #e2e8f0; white-space:nowrap;">
                <?= htmlspecialchars($m['ma_mon']) ?>
              </span>
            </td>
            <td>
              <strong style="color:#0f172a; font-size:14px;"><?= htmlspecialchars($m['ten_mon']) ?></strong>
            </td>
            <td style="color:#475569; font-size:13px;"><?= htmlspecialchars($m['khoa'] ?: 'Dùng chung / Chưa phân ngành') ?></td>
            <td style="text-align:center;">
              <span style="display:inline-block; padding:4px 10px; border-radius:6px; background:#f0fdf4; color:#16a34a; font-weight:800; font-size:12px; border:1px solid #bbf7d0;">
                <?= $m['so_tin_chi'] ?> TC
              </span>
            </td>
            <td style="text-align:center;">
              <div style="display:inline-flex; align-items:center; gap:6px; justify-content:center;">
                <a href="?edit_id=<?= $m['id'] ?>" class="btn btn-edit btn-sm" title="Sửa"><i class="fa-solid fa-pen"></i></a>
                <a href="?action=delete&id=<?= $m['id'] ?>" class="btn btn-danger btn-sm" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa môn học này?')"><i class="fa-solid fa-trash"></i></a>
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
  <div class="modal-box" style="max-width:480px">
    <div class="modal-title">Thêm môn học mới</div>
    <div class="modal-sub">Nhập đầy đủ mã môn, tên môn học và số tín chỉ</div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label class="form-label">Mã môn *</label>
        <input class="form-input" name="ma_mon" required placeholder="Ví dụ: CNTT101">
      </div>
      <div class="form-group">
        <label class="form-label">Tên môn học *</label>
        <input class="form-input" name="ten_mon" required placeholder="Ví dụ: Lập trình Web">
      </div>
      <div class="form-group">
        <label class="form-label">Khoa / Ngành áp dụng</label>
        <select class="form-select" name="khoa">
          <option value="">-- Dùng chung / Không chọn --</option>
          <?php global $NGANH_LIST; foreach ($NGANH_LIST as $ng): ?>
            <option value="<?= htmlspecialchars($ng) ?>"><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Số tín chỉ</label>
        <input class="form-input" name="so_tin_chi" type="number" min="1" max="10" value="2">
      </div>
      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm môn</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editRow): ?>
<div class="modal-overlay show" id="editModal">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-title">Sửa môn học</div>
    <div class="modal-sub">Mã môn: <strong style="color:#0f172a;"><?= htmlspecialchars($editRow['ma_mon']) ?></strong></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
      <div class="form-group">
        <label class="form-label">Tên môn học *</label>
        <input class="form-input" name="ten_mon" required value="<?= htmlspecialchars($editRow['ten_mon']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Khoa / Ngành áp dụng</label>
        <select class="form-select" name="khoa">
          <option value="">-- Dùng chung / Không chọn --</option>
          <?php global $NGANH_LIST; foreach ($NGANH_LIST as $ng): ?>
            <option value="<?= htmlspecialchars($ng) ?>" <?= ($editRow['khoa'] == $ng) ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Số tín chỉ</label>
        <input class="form-input" name="so_tin_chi" type="number" min="1" max="10" value="<?= $editRow['so_tin_chi'] ?>">
      </div>
      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
        <a href="/tkb/admin/monhoc.php" class="btn btn-ghost">Hủy</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function toggleModal(id){
  const m = document.getElementById(id);
  if(m) m.classList.toggle('show');
}
</script>
</body>
</html>
