<?php
require_once '../config.php';
requirePrincipal();
$db = getDB();

$fstatus = $_GET['trang_thai'] ?? '';
$flop    = $_GET['lop'] ?? '';

// Get classes list
$lopList = $db->query("SELECT DISTINCT lop FROM students ORDER BY lop")->fetch_all(MYSQLI_ASSOC);

// Base SQL
$sql = "SELECT tc.*, s.ma_sv, s.ho_ten, s.lop, s.khoa 
        FROM tai_chinh tc 
        JOIN students s ON tc.student_id = s.id 
        WHERE 1=1";

if ($fstatus) {
    $sql .= " AND tc.trang_thai = '" . $db->real_escape_string($fstatus) . "'";
}
if ($flop) {
    $sql .= " AND s.lop = '" . $db->real_escape_string($flop) . "'";
}
$sql .= " ORDER BY tc.id DESC";
$list = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

// Summaries
$total_paid = (float)($db->query("SELECT SUM(so_tien) as total FROM tai_chinh WHERE trang_thai = 'Đã nộp'")->fetch_assoc()['total'] ?? 0);
$total_unpaid = (float)($db->query("SELECT SUM(so_tien) as total FROM tai_chinh WHERE trang_thai != 'Đã nộp'")->fetch_assoc()['total'] ?? 0);

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Thống Kê Tài Chính - Hiệu Trưởng</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
    <?php include '../includes/principal_nav.php'; ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-coins" style="color:#fbbf24"></i> Thống Kê Học Phí & Tài Chính</h1>
            <p class="page-sub">Giám sát doanh thu và tình trạng học phí của sinh viên toàn trường</p>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="stats-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; border-left: 4px solid #10b981;">
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text2); font-weight: 500;">TỔNG HỌC PHÍ ĐÃ THU</div>
                <div style="font-size: 22px; font-weight: 800; color: #10b981;"><?= number_format($total_paid, 0, ',', '.') ?> VNĐ</div>
            </div>
        </div>

        <div class="stat-card" style="padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; border-left: 4px solid #ef4444;">
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text2); font-weight: 500;">TỔNG HỌC PHÍ CHƯA THU</div>
                <div style="font-size: 22px; font-weight: 800; color: #ef4444;"><?= number_format($total_unpaid, 0, ',', '.') ?> VNĐ</div>
            </div>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <select class="form-select" name="lop" onchange="this.form.submit()">
                <option value="">-- Tất cả lớp --</option>
                <?php foreach ($lopList as $l): ?>
                    <option <?= $flop == $l['lop'] ? 'selected' : '' ?>><?= htmlspecialchars($l['lop']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="trang_thai" onchange="this.form.submit()">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="Đã nộp" <?= $fstatus == 'Đã nộp' ? 'selected' : '' ?>>Đã nộp</option>
                <option value="Chưa nộp" <?= $fstatus == 'Chưa nộp' ? 'selected' : '' ?>>Chưa nộp</option>
            </select>
            <button class="btn btn-primary" type="submit">Lọc</button>
        </form>
        <span style="color:var(--text2);font-size:13px;align-self:center">Tìm thấy: <b style="color:var(--text)"><?= count($list) ?></b> khoản thu</span>
    </div>

    <div class="card">
        <div class="card-head"><span class="card-title">Chi tiết các khoản học phí</span></div>
        <div class="card-body" style="padding:0; overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Mã SV</th>
                        <th>Họ tên</th>
                        <th>Lớp</th>
                        <th>Nội dung thu</th>
                        <th>Số tiền (VNĐ)</th>
                        <th>Ngày nộp</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="7" style="text-align:center;color:var(--text2);padding:30px">Chưa có thông tin tài chính nào khớp bộ lọc.</td></tr>
                    <?php else: foreach ($list as $tc): ?>
                        <tr>
                            <td><code style="color:#f43f6d; font-weight:600;"><?= htmlspecialchars($tc['ma_sv']) ?></code></td>
                            <td><strong><?= htmlspecialchars($tc['ho_ten']) ?></strong></td>
                            <td><?= htmlspecialchars($tc['lop']) ?></td>
                            <td><?= htmlspecialchars($tc['noi_dung']) ?></td>
                            <td style="font-weight:700;"><?= number_format($tc['so_tien'], 0, ',', '.') ?></td>
                            <td><?= $tc['ngay_nop'] ? date('d/m/Y', strtotime($tc['ngay_nop'])) : '-' ?></td>
                            <td>
                                <?php if ($tc['trang_thai'] === 'Đã nộp'): ?>
                                    <span class="badge badge-pass" style="background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid #10b981;">Đã nộp</span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.12); color: #dc2626; border: 1px solid #ef4444;">Chưa nộp</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div> <!-- content-pad -->
    </div> <!-- main-content -->
</body>
</html>
