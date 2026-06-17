<?php
require_once '../config.php';
requirePrincipal();
$db = getDB();
$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $username = trim($_POST['username'] ?? '');
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!$username || !$password) {
        $msg = 'error:Vui lòng điền tên đăng nhập và mật khẩu!';
    } else {
        // Check duplicate username
        $check = $db->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $msg = 'error:Tên đăng nhập đã tồn tại trên hệ thống!';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $db->prepare("INSERT INTO users (username, ho_ten, password, role) VALUES (?, ?, ?, 'admin')");
            $st->bind_param("sss", $username, $ho_ten, $hash);
            if ($st->execute()) {
                $msg = 'success:Thêm tài khoản quản trị thành công!';
            } else {
                $msg = 'error:Lỗi hệ thống khi thêm quản trị viên: ' . $db->error;
            }
        }
    }
}

if ($action === 'edit') {
    $id = (int)$_POST['id'];
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $db->prepare("UPDATE users SET ho_ten = ?, password = ? WHERE id = ? AND role = 'admin'");
        $st->bind_param("ssi", $ho_ten, $hash, $id);
    } else {
        $st = $db->prepare("UPDATE users SET ho_ten = ? WHERE id = ? AND role = 'admin'");
        $st->bind_param("si", $ho_ten, $id);
    }
    
    if ($st->execute()) {
        $msg = 'success:Cập nhật thông tin quản trị viên thành công!';
    } else {
        $msg = 'error:Lỗi hệ thống khi cập nhật: ' . $db->error;
    }
}

if ($action === 'delete') {
    $id = (int)$_GET['id'];
    // Prevent principal from deleting their own user or deleting everything without safety
    $st = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
    $st->bind_param("i", $id);
    if ($st->execute()) {
        $msg = 'success:Đã xóa tài khoản quản trị thành công!';
    } else {
        $msg = 'error:Lỗi hệ thống khi xóa tài khoản!';
    }
}

$admins = $db->query("SELECT * FROM users WHERE role = 'admin' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

$editRow = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $editRow = $db->query("SELECT * FROM users WHERE id=$eid AND role='admin'")->fetch_assoc();
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Cán bộ Quản trị - Ban Giám Hiệu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
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
.form-input {
    width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px;
    background: var(--bg) !important; color: var(--text) !important; outline: none; font-family: 'Outfit', sans-serif;
}
.form-input:focus { border-color: var(--accent); }
</style>
</head>
<body>
<?php include '../includes/principal_nav.php'; ?>
<div class="main-content">
  <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-user-shield" style="color:#fbbf24"></i> Quản lý Cán bộ Quản trị (Admin)</h1>
        <p class="page-sub" style="color:var(--text2); margin-top:5px;">Danh sách và phân quyền tài khoản quản trị viên hệ thống</p>
    </div>
    <button class="btn btn-primary" onclick="toggleModal('addModal')" style="background:#fbbf24; color:#000; font-weight:700; border:none;"><i class="fa-solid fa-plus"></i> Thêm Admin mới</button>
  </div>

  <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType ?>" style="background:<?= $msgType=='success'?'rgba(16, 185, 129, 0.1)':'rgba(239, 68, 68, 0.1)' ?>; border: 1px solid <?= $msgType=='success'?'#10b981':'#ef4444' ?>; color: <?= $msgType=='success'?'#059669':'#dc2626' ?>; padding: 12px; border-radius: 10px; margin-bottom: 20px;">
        <?= htmlspecialchars($msgText) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-head"><span class="card-title">Danh sách tài khoản Admin</span></div>
    <div class="card-body" style="padding:0; overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Tên đăng nhập</th>
            <th>Họ và tên</th>
            <th>Ngày tạo</th>
            <th style="text-align: center;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($admins)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text2);padding:30px">Chưa có tài khoản quản trị nào.</td></tr>
          <?php else: foreach ($admins as $i => $adm): ?>
            <tr>
              <td style="color:var(--text2)"><?= $i + 1 ?></td>
              <td><code><?= htmlspecialchars($adm['username']) ?></code></td>
              <td><strong><?= htmlspecialchars($adm['ho_ten'] ?: 'Chưa đặt tên') ?></strong></td>
              <td><?= date('d/m/Y H:i', strtotime($adm['created_at'])) ?></td>
              <td style="text-align: center;">
                <a href="?edit_id=<?= $adm['id'] ?>" class="btn btn-ghost btn-sm" style="padding: 6px 12px; font-size:12px; margin-right:5px;"><i class="fa-solid fa-pen-to-square"></i> Sửa</a>
                <a href="?action=delete&id=<?= $adm['id'] ?>" class="btn btn-danger btn-sm" style="padding: 6px 12px; font-size:12px;" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản Admin <?= htmlspecialchars($adm['username']) ?>?')"><i class="fa-solid fa-trash-can"></i> Xóa</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Add -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-close" onclick="toggleModal('addModal')">&times;</div>
    <h3 class="modal-title"><i class="fa-solid fa-user-shield"></i> Thêm Admin Mới</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label class="form-label">Tên đăng nhập (Username)</label>
        <input type="text" name="username" class="form-input" required placeholder="Ví dụ: admin3">
      </div>
      <div class="form-group">
        <label class="form-label">Họ và tên</label>
        <input type="text" name="ho_ten" class="form-input" placeholder="Ví dụ: Nguyễn Văn C">
      </div>
      <div class="form-group">
        <label class="form-label">Mật khẩu</label>
        <input type="password" name="password" class="form-input" required placeholder="Nhập mật khẩu tài khoản">
      </div>
      <div style="text-align:right;">
        <button type="submit" class="btn btn-primary" style="background:#fbbf24; color:#000; font-weight:700;"><i class="fa-solid fa-floppy-disk"></i> Lưu lại</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<?php if ($editRow): ?>
<div class="modal-overlay show" id="editModal">
  <div class="modal-box">
    <div class="modal-close" onclick="window.location.href='admins.php'">&times;</div>
    <h3 class="modal-title"><i class="fa-solid fa-pen-to-square"></i> Cập nhật thông tin Admin</h3>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
      <div class="form-group">
        <label class="form-label">Tên đăng nhập</label>
        <input type="text" class="form-input" value="<?= htmlspecialchars($editRow['username']) ?>" disabled>
      </div>
      <div class="form-group">
        <label class="form-label">Họ và tên</label>
        <input type="text" name="ho_ten" class="form-input" value="<?= htmlspecialchars($editRow['ho_ten']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Mật khẩu mới (Để trống nếu không muốn đổi)</label>
        <input type="password" name="password" class="form-input" placeholder="Nhập mật khẩu mới">
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
