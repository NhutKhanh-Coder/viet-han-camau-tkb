<?php
require_once '../config.php';
requireStudent();

$db = getDB();
$sv_id = $_SESSION['student_id'];

$st = $db->prepare("SELECT trang_thai, COUNT(*) as sl FROM diem_danh WHERE student_id=? GROUP BY trang_thai");
$st->bind_param("i", $sv_id);
$st->execute();
$res = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$tong = 0;
$co_mat = 0;
$vang_mat = 0;
$co_phep = 0;

foreach ($res as $r) {
    $tong += $r['sl'];
    if ($r['trang_thai'] === 'Có mặt') $co_mat += $r['sl'];
    elseif ($r['trang_thai'] === 'Vắng mặt') $vang_mat += $r['sl'];
    elseif ($r['trang_thai'] === 'Có phép') $co_phep += $r['sl'];
}

$st2 = $db->prepare("SELECT d.*, m.ten_mon FROM diem_danh d JOIN mon_hoc m ON d.mon_hoc_id = m.id WHERE d.student_id=? ORDER BY d.ngay_diem_danh DESC LIMIT 10");
$st2->bind_param("i", $sv_id);
$st2->execute();
$history = $st2->get_result()->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><title>Điểm Danh</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/student_nav.php'; ?>
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-user-check" style="color:var(--accent)"></i> Chuyên cần & Điểm danh</h1></div>
  </div>
  <div class="card">
    <div class="card-head"><span class="card-title">Tình trạng chuyên cần HK1</span></div>
    <div class="card-body">
      <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:20px; margin-bottom: 20px;">
        <div style="background:rgba(217, 27, 67, 0.04); border:1px solid rgba(217, 27, 67, 0.15); padding:20px; border-radius:8px; text-align:center;"><div style="font-size:24px; font-weight:800; color:var(--accent);"><?= $tong ?></div><div style="font-size:12px; font-weight:700; color:#f43f6d; text-transform:uppercase;">Tổng số buổi</div></div>
        <div style="background:#dcfce7; border:1px solid #bbf7d0; padding:20px; border-radius:8px; text-align:center;"><div style="font-size:24px; font-weight:800; color:#15803d;"><?= $co_mat ?></div><div style="font-size:12px; font-weight:700; color:#4ade80; text-transform:uppercase;">Có mặt</div></div>
        <div style="background:#fef08a; border:1px solid #fde047; padding:20px; border-radius:8px; text-align:center;"><div style="font-size:24px; font-weight:800; color:#854d0e;"><?= $co_phep ?></div><div style="font-size:12px; font-weight:700; color:#ca8a04; text-transform:uppercase;">Có phép</div></div>
        <div style="background:#fef2f2; border:1px solid #fecaca; padding:20px; border-radius:8px; text-align:center;"><div style="font-size:24px; font-weight:800; color:#b91c1c;"><?= $vang_mat ?></div><div style="font-size:12px; font-weight:700; color:#f87171; text-transform:uppercase;">Vắng mặt</div></div>
      </div>
      
      <?php if ($tong == 0): ?>
      <p style="text-align:center; color:#666; font-size:14px; margin-top:30px;">Chưa có dữ liệu điểm danh nào.</p>
      <?php else: ?>
      <p style="text-align:center; color:#666; font-size:14px; margin-top:30px;"><i class="fa-regular fa-face-smile" style="font-size:24px; color:#f59e0b; display:block; margin-bottom:10px;"></i> <?= $vang_mat == 0 ? 'Tình trạng điểm danh của bạn rất tốt. Hãy tiếp tục phát huy nhé!' : 'Hãy chú ý đi học đầy đủ để đạt kết quả tốt nhất!' ?></p>
      
      <h3 style="margin-top:30px; font-size:16px;">Lịch sử điểm danh gần đây</h3>
      <table>
        <thead><tr><th>Ngày</th><th>Môn học</th><th>Trạng thái</th><th>Ghi chú</th></tr></thead>
        <tbody>
            <?php foreach($history as $h): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($h['ngay_diem_danh'])) ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($h['ten_mon']) ?></td>
                <td>
                    <?php if ($h['trang_thai'] == 'Có mặt') echo '<span class="badge badge-pass">Có mặt</span>';
                    elseif ($h['trang_thai'] == 'Vắng mặt') echo '<span class="badge" style="background:#fef2f2;color:#ef4444;border:1px solid #fecaca">Vắng mặt</span>';
                    else echo '<span class="badge" style="background:#fef08a;color:#854d0e;border:1px solid #fde047">Có phép</span>'; ?>
                </td>
                <td style="font-size:13px;color:#666;"><?= htmlspecialchars($h['ghi_chu']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div> <!-- content-pad -->
</div> <!-- main-content -->
</body>
</html>
