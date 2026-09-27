<?php
require_once '../config.php';
requireAdmin();
$db = getDB();

// Handle logs clearing (optional admin feature)
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'clear') {
    if ($db->query("TRUNCATE TABLE system_logs")) {
        writeSystemLog("Xóa sạch nhật ký hệ thống");
        header("Location: nhatky.php");
        exit();
    }
}

// Fetch logs
$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM system_logs";
$params = [];
$types = "";

if ($search) {
    $sql .= " WHERE username LIKE ? OR hanh_dong LIKE ?";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql .= " ORDER BY id DESC LIMIT 500";
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhật Ký Hệ Thống - Hệ Thống Quản Trị</title>
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
                <h1 class="page-title"><i class="fa-solid fa-clock-rotate-left"></i> Nhật Ký Hệ Thống</h1>
                <p class="page-sub">Kiểm toán an ninh: Theo dõi lịch sử đăng nhập, thêm, sửa, xóa dữ liệu thời gian thực</p>
            </div>
            <a href="?action=clear" onclick="return confirm('Bạn có chắc muốn xóa sạch toàn bộ log nhật ký?')" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i> Xóa nhật ký</a>
        </div>

        <!-- Search input -->
        <div class="filter-bar">
            <form method="GET" style="display:flex; align-items:center; gap:12px; flex:1;">
                <div class="search-wrap" style="flex:1; max-width:420px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Tìm theo tài khoản hoặc hành động..." class="search-input" value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn btn-ghost"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</button>
            </form>
            <span style="font-size:13px; font-weight:700; color:#475569; background:#f1f5f9; padding:6px 14px; border-radius:20px; border:1px solid #e2e8f0;">
                Tổng: <b style="color:#e11d48;"><?= count($logs) ?></b> bản ghi
            </span>
        </div>

        <div class="card">
            <div class="card-head">
                <span class="card-title"><i class="fa-solid fa-list-check"></i> Danh sách log ghi nhận mới nhất (Tối đa 500 bản ghi)</span>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:60px; text-align:center;">ID</th>
                            <th style="width:170px;">Thời gian</th>
                            <th style="width:200px;">Tài khoản tác động</th>
                            <th>Hành động ghi nhận</th>
                            <th style="width:140px; text-align:center;">Địa chỉ IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:40px;">Chưa có bản ghi nhật ký hệ thống nào.</td></tr>
                        <?php else: foreach ($logs as $lg): 
                            $act = $lg['hanh_dong'];
                            $badge_bg = '#f1f5f9';
                            $badge_color = '#334155';
                            $badge_border = '#e2e8f0';
                            if (stripos($act, 'Đăng nhập') !== false) {
                                $badge_bg = '#f0f9ff'; $badge_color = '#0284c7'; $badge_border = '#bae6fd';
                            } elseif (stripos($act, 'Thêm') !== false || stripos($act, 'Tạo') !== false) {
                                $badge_bg = '#f0fdf4'; $badge_color = '#16a34a'; $badge_border = '#bbf7d0';
                            } elseif (stripos($act, 'Sửa') !== false || stripos($act, 'Cập nhật') !== false) {
                                $badge_bg = '#fffbeb'; $badge_color = '#d97706'; $badge_border = '#fde68a';
                            } elseif (stripos($act, 'Xóa') !== false || stripos($act, 'Hủy') !== false) {
                                $badge_bg = '#fef2f2'; $badge_color = '#dc2626'; $badge_border = '#fecaca';
                            }
                        ?>
                            <tr>
                                <td style="text-align:center; color:#94a3b8; font-size:12.5px; font-weight:600;"><?= $lg['id'] ?></td>
                                <td style="color:#64748b; font-size:13px; white-space:nowrap;">
                                    <?= date('d/m/Y H:i:s', strtotime($lg['ngay_tao'])) ?>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="width:28px; height:28px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#0f172a; font-size:11px; font-weight:700;">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                        <strong style="color:#0f172a; font-size:13.5px;"><?= htmlspecialchars($lg['username']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span style="display:inline-block; padding:4px 10px; border-radius:6px; background:<?= $badge_bg ?>; color:<?= $badge_color ?>; border:1px solid <?= $badge_border ?>; font-weight:600; font-size:13px;">
                                        <?= htmlspecialchars($act) ?>
                                    </span>
                                </td>
                                <td style="text-align:center; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:12.5px; color:#64748b;">
                                    <?= htmlspecialchars($lg['ip_address']) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
