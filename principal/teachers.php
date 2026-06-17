<?php
require_once '../config.php';
requirePrincipal();
$db = getDB();
$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $ma_gv = trim($_POST['ma_gv'] ?? '');
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $khoa = trim($_POST['khoa'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!$ma_gv || !$ho_ten || !$password) {
        $msg = 'error:Vui lòng nhập đầy đủ thông tin (Mã GV, họ tên, mật khẩu)!';
    } else {
        // Check duplicate ma_gv / username
        $check = $db->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $ma_gv);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $msg = 'error:Mã giáo viên đã tồn tại làm tài khoản đăng nhập!';
        } else {
            $db->begin_transaction();
            try {
                // Create user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $st_user = $db->prepare("INSERT INTO users (username, ho_ten, password, role) VALUES (?, ?, ?, 'teacher')");
                $st_user->bind_param("sss", $ma_gv, $ho_ten, $hash);
                $st_user->execute();
                $user_id = $db->insert_id;
                
                // Create giang_vien profile
                $st_gv = $db->prepare("INSERT INTO giang_vien (ma_gv, ho_ten, khoa, user_id) VALUES (?, ?, ?, ?)");
                $st_gv->bind_param("sssi", $ma_gv, $ho_ten, $khoa, $user_id);
                $st_gv->execute();
                
                $db->commit();
                $msg = 'success:Thêm giảng viên thành công!';
            } catch(Exception $e) {
                $db->rollback();
                $msg = 'error:Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}

if ($action === 'edit') {
    $id = (int)$_POST['id']; // users.id
    $ma_gv = trim($_POST['ma_gv'] ?? '');
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $khoa = trim($_POST['khoa'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $db->begin_transaction();
    try {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st_user = $db->prepare("UPDATE users SET ho_ten = ?, password = ? WHERE id = ?");
            $st_user->bind_param("ssi", $ho_ten, $hash, $id);
        } else {
            $st_user = $db->prepare("UPDATE users SET ho_ten = ? WHERE id = ?");
            $st_user->bind_param("si", $ho_ten, $id);
        }
        $st_user->execute();
        
        $st_gv = $db->prepare("UPDATE giang_vien SET ho_ten = ?, khoa = ? WHERE user_id = ?");
        $st_gv->bind_param("ssi", $ho_ten, $khoa, $id);
        $st_gv->execute();
        
        $db->commit();
        $msg = 'success:Cập nhật thông tin giảng viên thành công!';
    } catch(Exception $e) {
        $db->rollback();
        $msg = 'error:Lỗi hệ thống khi cập nhật: ' . $e->getMessage();
    }
}

if ($action === 'delete') {
    $id = (int)$_GET['id']; // users.id
    $db->begin_transaction();
    try {
        // Profile deletes first
        $st_gv = $db->prepare("DELETE FROM giang_vien WHERE user_id = ?");
        $st_gv->bind_param("i", $id);
        $st_gv->execute();
        
        // User deletes next
        $st_user = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
        $st_user->bind_param("i", $id);
        $st_user->execute();
        
        $db->commit();
        $msg = 'success:Đã xóa tài khoản giảng viên thành công!';
    } catch(Exception $e) {
        $db->rollback();
        $msg = 'error:Lỗi hệ thống khi xóa giảng viên: ' . $e->getMessage();
    }
}

// Fetch teachers list joining users table
$teachers = $db->query("
    SELECT g.*, u.id as user_id, u.created_at 
    FROM giang_vien g 
    JOIN users u ON g.user_id = u.id 
    WHERE u.role = 'teacher' 
    ORDER BY g.id DESC
")->fetch_all(MYSQLI_ASSOC);

$editRow = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id']; // users.id
    $editRow = $db->query("
        SELECT g.*, u.id as user_id 
        FROM giang_vien g 
        JOIN users u ON g.user_id = u.id 
        WHERE u.id = $eid AND u.role = 'teacher'
    ")->fetch_assoc();
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Giảng Viên - Ban Giám Hiệu</title>
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
        <h1 class="page-title"><i class="fa-solid fa-chalkboard-user" style="color:#fbbf24"></i> Quản lý Cán bộ Giảng viên</h1>
        <p class="page-sub" style="color:var(--text2); margin-top:5px;">Danh sách, khoa chuyên môn và tài khoản giảng dạy của giảng viên</p>
    </div>
    <button class="btn btn-primary" onclick="toggleModal('addModal')" style="background:#fbbf24; color:#000; font-weight:700; border:none;"><i class="fa-solid fa-plus"></i> Thêm Giảng viên</button>
  </div>

  <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType ?>" style="background:<?= $msgType=='success'?'rgba(16, 185, 129, 0.1)':'rgba(239, 68, 68, 0.1)' ?>; border: 1px solid <?= $msgType=='success'?'#10b981':'#ef4444' ?>; color: <?= $msgType=='success'?'#059669':'#dc2626' ?>; padding: 12px; border-radius: 10px; margin-bottom: 20px;">
        <?= htmlspecialchars($msgText) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-head"><span class="card-title">Danh sách Giảng viên</span></div>
    <div class="card-body" style="padding:0; overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Mã giảng viên</th>
            <th>Họ và tên</th>
            <th>Khoa chuyên môn</th>
            <th>Ngày tham gia</th>
            <th style="text-align: center;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($teachers)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--text2);padding:30px">Chưa có giảng viên nào trên hệ thống.</td></tr>
          <?php else: foreach ($teachers as $i => $gv): ?>
            <tr>
              <td style="color:var(--text2)"><?= $i + 1 ?></td>
              <td><code><?= htmlspecialchars($gv['ma_gv']) ?></code></td>
              <td><strong><?= htmlspecialchars($gv['ho_ten']) ?></strong></td>
              <td><span class="badge" style="background:rgba(56,189,248,0.12); color:#38bdf8; border:1px solid rgba(56,189,248,0.2);"><?= htmlspecialchars($gv['khoa'] ?: 'Tự do') ?></span></td>
              <td><?= date('d/m/Y', strtotime($gv['created_at'])) ?></td>
              <td style="text-align: center;">
                <a href="?edit_id=<?= $gv['user_id'] ?>" class="btn btn-ghost btn-sm" style="padding: 6px 12px; font-size:12px; margin-right:5px;"><i class="fa-solid fa-pen-to-square"></i> Sửa</a>
                <a href="?action=delete&id=<?= $gv['user_id'] ?>" class="btn btn-danger btn-sm" style="padding: 6px 12px; font-size:12px;" onclick="return confirm('Bạn có chắc chắn muốn xóa Giảng viên <?= htmlspecialchars($gv['ho_ten']) ?>?')"><i class="fa-solid fa-trash-can"></i> Xóa</a>
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
    <h3 class="modal-title"><i class="fa-solid fa-chalkboard-user"></i> Thêm Giảng Viên Mới</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label class="form-label">Mã giảng viên (Tài khoản)</label>
        <input type="text" name="ma_gv" class="form-input" required placeholder="Ví dụ: gv005">
      </div>
      <div class="form-group">
        <label class="form-label">Họ và tên giảng viên</label>
        <input type="text" name="ho_ten" class="form-input" required placeholder="Ví dụ: Trần Văn D">
      </div>
      <div class="form-group">
        <label class="form-label">Khoa chuyên môn</label>
        <input type="text" name="khoa" class="form-input" placeholder="Ví dụ: Công nghệ thông tin">
      </div>
      <div class="form-group">
        <label class="form-label">Mật khẩu tài khoản</label>
        <input type="password" name="password" class="form-input" required placeholder="Nhập mật khẩu cho tài khoản">
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
    <div class="modal-close" onclick="window.location.href='teachers.php'">&times;</div>
    <h3 class="modal-title"><i class="fa-solid fa-pen-to-square"></i> Cập nhật thông tin Giảng viên</h3>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editRow['user_id'] ?>">
      <div class="form-group">
        <label class="form-label">Mã giảng viên (Tài khoản)</label>
        <input type="text" class="form-input" value="<?= htmlspecialchars($editRow['ma_gv']) ?>" disabled>
      </div>
      <div class="form-group">
        <label class="form-label">Họ và tên giảng viên</label>
        <input type="text" name="ho_ten" class="form-input" value="<?= htmlspecialchars($editRow['ho_ten']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Khoa chuyên môn</label>
        <input type="text" name="khoa" class="form-input" value="<?= htmlspecialchars($editRow['khoa']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Mật khẩu mới (Để trống nếu không đổi)</label>
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
