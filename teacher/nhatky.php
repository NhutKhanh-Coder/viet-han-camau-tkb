<?php
require_once '../config.php';
requireTeacher();
$db = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'clear') {
    if ($db->query("TRUNCATE TABLE system_logs")) {
        writeSystemLog("Xóa sạch nhật ký hệ thống");
        header("Location: nhatky.php"); exit();
    }
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM system_logs";
$params = []; $types = "";
if ($search) {
    $sql .= " WHERE username LIKE ? OR hanh_dong LIKE ?";
    $s = "%$search%"; $params[] = $s; $params[] = $s; $types .= "ss";
}
$sql .= " ORDER BY id DESC LIMIT 500";
$stmt = $db->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Nhật ký hệ thống - Giảng viên</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/teacher_nav.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--accent)"></i> Nhật Ký Hệ Thống</h1>
        <p style="color:var(--text2); margin-top:5px;">Theo dõi lịch sử đăng nhập, thêm, sửa, xóa dữ liệu thời gian thực</p>
    </div>
    <a href="?action=clear" onclick="return confirm('Bạn có chắc muốn xóa sạch toàn bộ log nhật ký?')" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i> Xóa nhật ký</a>
</div>

<form method="GET" style="display:flex; gap:10px; margin-bottom:20px;">
    <input type="text" name="search" placeholder="Tìm theo tài khoản hoặc hành động..." style="flex:1; padding:10px 15px; border:1px solid var(--border); border-radius:8px; background:var(--bg2); color:var(--text); font-size:14px; outline:none;" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Tìm</button>
</form>

<div class="card">
    <div class="card-head">
        <span class="card-title"><i class="fa-solid fa-list"></i> Danh sách log mới nhất (tối đa 500 bản ghi)</span>
    </div>
    <div class="card-body" style="padding:0;">
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr>
                <th style="padding:12px 15px; width:60px;">ID</th>
                <th style="padding:12px 15px; width:150px;">Thời gian</th>
                <th style="padding:12px 15px; width:180px;">Tài khoản</th>
                <th style="padding:12px 15px;">Hành động ghi nhận</th>
                <th style="padding:12px 15px; width:160px; text-align:center;">Địa chỉ IP</th>
            </tr></thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text2);padding:30px;">Chưa có bản ghi nhật ký nào.</td></tr>
            <?php else: foreach ($logs as $lg):
                $act = $lg['hanh_dong'];
                $color = 'var(--text)';
                if (strpos($act, 'Đăng nhập') !== false) $color = '#38bdf8';
                elseif (strpos($act, 'Thêm') !== false) $color = '#10b981';
                elseif (strpos($act, 'Cập nhật') !== false) $color = '#f59e0b';
                elseif (strpos($act, 'Xóa') !== false) $color = '#ef4444';
            ?>
                <tr>
                    <td style="padding:12px 15px; color:var(--text2); font-size:13px;"><?= $lg['id'] ?></td>
                    <td style="padding:12px 15px; color:var(--text2); font-size:13px;"><?= date('d/m/Y H:i:s', strtotime($lg['ngay_tao'])) ?></td>
                    <td style="padding:12px 15px; font-weight:700; color:var(--text);"><i class="fa-solid fa-circle-user" style="color:var(--accent); margin-right:6px;"></i><?= htmlspecialchars($lg['username']) ?></td>
                    <td style="padding:12px 15px; font-weight:600; color:<?= $color ?>;"><?= htmlspecialchars($act) ?></td>
                    <td style="padding:12px 15px; text-align:center; font-family:monospace; font-size:12px; color:var(--text2);"><?= htmlspecialchars($lg['ip_address']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
