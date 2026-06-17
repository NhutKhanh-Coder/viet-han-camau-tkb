<?php
require_once '../config.php';
requireStudent();
$db = getDB();
$sv_id = $_SESSION['student_id'];
$msg = '';

// Lấy thông tin sinh viên
$st_sv = $db->prepare("SELECT * FROM students WHERE id = ?");
$st_sv->bind_param("i", $sv_id);
$st_sv->execute();
$sv = $st_sv->get_result()->fetch_assoc();
$khoa = $sv['khoa'] ?? '';

// Xử lý đăng ký / hủy đăng ký (Sinh viên chỉ được phép xem)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = 'error:Hệ thống đăng ký đã khóa. Tài khoản sinh viên hiện chỉ được quyền xem thông tin!';
}

// Lấy danh sách các môn học trong khoa của sinh viên
$mon_hoc_list = [];
if (!empty($khoa)) {
    $st_mon = $db->prepare("SELECT * FROM mon_hoc WHERE LOWER(khoa) = LOWER(?) ORDER BY ten_mon");
    $st_mon->bind_param("s", $khoa);
    $st_mon->execute();
    $mon_hoc_list = $st_mon->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Nếu không có khoa thì hiển thị tất cả
    $mon_hoc_list = $db->query("SELECT * FROM mon_hoc ORDER BY ten_mon")->fetch_all(MYSQLI_ASSOC);
}

// Lấy danh sách các môn học đã đăng ký của sinh viên
$registered_list = [];
$st_reg = $db->prepare("SELECT d.id AS diem_id, d.diem_giua_ky, d.diem_cuoi_ky, d.diem_tong_ket, m.id AS mon_hoc_id, m.ten_mon, m.ma_mon, m.so_tin_chi 
                        FROM diem d JOIN mon_hoc m ON d.mon_hoc_id = m.id 
                        WHERE d.student_id = ?");
$st_reg->bind_param("i", $sv_id);
$st_reg->execute();
$regs = $st_reg->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($regs as $r) {
    $registered_list[$r['mon_hoc_id']] = $r;
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng Ký Học Phần - Sinh Viên</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/student_nav.php'; ?>
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-book-open" style="color:var(--accent)"></i> Đăng Ký Học Phần</h1></div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType ?>" style="background:<?= $msgType=='success'?'rgba(16, 185, 129, 0.1)':'rgba(239, 68, 68, 0.1)' ?>; border: 1px solid <?= $msgType=='success'?'#10b981':'#ef4444' ?>; color: <?= $msgType=='success'?'#059669':'#dc2626' ?>; padding: 12px; border-radius: 10px; margin-bottom: 20px;">
    <?= htmlspecialchars($msgText) ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-head"><span class="card-title">Chương trình học ngành: <?= htmlspecialchars($khoa ?: 'Chưa cập nhật') ?></span></div>
    <div class="card-body">
      <p style="color:var(--text2); font-size:14px; margin-bottom: 20px; font-weight: 500;">
        <i class="fa-solid fa-circle-info" style="color:var(--accent); margin-right:5px;"></i> Hệ thống đăng ký học phần hiện tại chỉ mở ở chế độ <b>Đọc (Xem thông tin)</b>. Vui lòng liên hệ phòng đào tạo để được hỗ trợ điều chỉnh.
      </p>
      <table>
        <thead>
          <tr>
            <th>Mã HP</th>
            <th>Tên học phần</th>
            <th style="text-align: center;">Số tín chỉ</th>
            <th style="text-align: center;">Trạng thái</th>
            <th style="text-align: center;">Đăng ký</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($mon_hoc_list)): ?>
          <tr><td colspan="5" style="text-align:center;color:var(--text2);padding:30px">Không tìm thấy môn học nào thuộc khoa của bạn.</td></tr>
          <?php else: foreach ($mon_hoc_list as $mon): 
              $is_registered = isset($registered_list[$mon['id']]);
              $reg_info = $is_registered ? $registered_list[$mon['id']] : null;
          ?>
          <tr>
             <td><code style="color:#f43f6d; font-weight: 600;"><?= htmlspecialchars($mon['ma_mon']) ?></code></td>
            <td><strong><?= htmlspecialchars($mon['ten_mon']) ?></strong></td>
            <td style="text-align: center; font-weight: 600;"><?= $mon['so_tin_chi'] ?></td>
            <td style="text-align: center;">
              <?php if ($is_registered): ?>
                <?php if ($reg_info['diem_tong_ket'] !== null): ?>
                  <span class="badge badge-pass" style="background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid #10b981;">Đã hoàn thành (DTK: <?= $reg_info['diem_tong_ket'] ?>)</span>
                <?php else: ?>
                  <span class="badge badge-avg" style="background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid #f59e0b;">Đang học / Chờ nhập điểm</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge" style="background: rgba(100, 116, 139, 0.08); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.15);">Chưa đăng ký</span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;">
              <?php if ($is_registered): ?>
                <span style="font-size:13px; color:#10b981; font-weight:600;"><i class="fa-solid fa-circle-check"></i> Đã đăng ký</span>
              <?php else: ?>
                <span style="font-size:13px; color:var(--text2); font-weight:500;"><i class="fa-solid fa-circle-minus"></i> Chưa học</span>
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
