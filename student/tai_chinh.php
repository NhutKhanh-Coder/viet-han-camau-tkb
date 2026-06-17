<?php
require_once '../config.php';
requireStudent();

$db = getDB();
$sv_id = $_SESSION['student_id'];

$st = $db->prepare("SELECT * FROM tai_chinh WHERE student_id=? ORDER BY ngay_nop DESC, id DESC");
$st->bind_param("i", $sv_id);
$st->execute();
$tai_chinh_list = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$tong_no = 0;
foreach ($tai_chinh_list as $tc) {
    if ($tc['trang_thai'] !== 'Đã nộp') {
        $tong_no += $tc['so_tien'];
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><title>Tài Chính</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/student_nav.php'; ?>
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-money-bill" style="color:#ef4444"></i> Thông tin tài chính</h1></div>
  </div>
  <div class="card">
    <div class="card-head"><span class="card-title">Học phí & Lệ phí Học Kỳ 1</span></div>
    <div class="card-body">
      <div style="background:<?= $tong_no > 0 ? '#fef2f2' : '#f0fdf4' ?>; border:1px solid <?= $tong_no > 0 ? '#fecaca' : '#bbf7d0' ?>; color:<?= $tong_no > 0 ? '#b91c1c' : '#15803d' ?>; padding:20px; border-radius:8px; display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <div><div style="font-size:13px; font-weight:700; margin-bottom:5px;">TỔNG NỢ CÒN LẠI</div><div style="font-size:24px; font-weight:800;"><?= number_format($tong_no, 0, ',', '.') ?> VNĐ</div></div>
        <div style="font-size:32px;"><i class="fa-solid <?= $tong_no > 0 ? 'fa-circle-exclamation' : 'fa-circle-check' ?>" style="color:<?= $tong_no > 0 ? '#ef4444' : '#22c55e' ?>;"></i></div>
      </div>
      <table>
        <thead>
          <tr><th>Nội dung thu</th><th>Số tiền (VNĐ)</th><th>Ngày nộp</th><th>Trạng thái</th></tr>
        </thead>
        <tbody>
          <?php if (empty($tai_chinh_list)): ?>
          <tr><td colspan="4" style="text-align:center;color:#999;padding:20px;">Không có khoản thu nào.</td></tr>
          <?php else: foreach($tai_chinh_list as $tc): ?>
          <tr>
            <td><?= htmlspecialchars($tc['noi_dung']) ?></td>
            <td style="font-weight:700;"><?= number_format($tc['so_tien'], 0, ',', '.') ?></td>
            <td><?= $tc['ngay_nop'] ? date('d/m/Y', strtotime($tc['ngay_nop'])) : '-' ?></td>
            <td>
              <?php if ($tc['trang_thai'] === 'Đã nộp'): ?>
              <span class="badge badge-pass">Đã nộp</span>
              <?php else: ?>
              <span class="badge" style="background:#fef2f2;color:#ef4444;border:1px solid #fecaca">Chưa nộp</span>
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
