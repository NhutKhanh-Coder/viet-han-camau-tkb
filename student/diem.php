<?php
require_once '../config.php';
requireStudent();
$db   = getDB();
$sv_id = $_SESSION['student_id'];
$hk   = $_GET['hk']  ?? '';
$nh   = $_GET['nh']  ?? '';

$sql = "SELECT d.*, m.ten_mon, m.ma_mon, m.so_tin_chi 
        FROM diem d JOIN mon_hoc m ON d.mon_hoc_id=m.id 
        WHERE d.student_id=?";
$params = [$sv_id]; $types = "i";
if ($hk) { $sql .= " AND d.hoc_ky=?"; $params[] = $hk; $types .= "s"; }
if ($nh) { $sql .= " AND d.nam_hoc=?"; $params[] = $nh; $types .= "s"; }
$sql .= " ORDER BY m.ten_mon";

$st = $db->prepare($sql);
$st->bind_param($types, ...$params);
$st->execute();
$diems = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$totalTC = 0; $tongDiem = 0; $fail = 0;
foreach ($diems as $d) {
    if ($d['diem_tong_ket'] !== null) {
        $totalTC += $d['so_tin_chi'];
        $tongDiem += $d['diem_tong_ket'] * $d['so_tin_chi'];
        if ($d['diem_tong_ket'] < 5) $fail++;
    }
}
$gpa4 = $totalTC > 0 ? round($tongDiem / $totalTC, 2) : 0;

function diemBadge($d) {
    if ($d === null) return '<span style="color:var(--text2)">Chưa có</span>';
    if ($d >= 9.5) return "<span class='badge badge-excel'>$d (Xuất sắc)</span>";
    if ($d >= 8.0) return "<span class='badge badge-good'>$d (Giỏi)</span>";
    if ($d >= 6.5) return "<span class='badge' style='background:rgba(217,27,67,0.08);color:var(--accent)'>$d (Khá)</span>";
    if ($d >= 5.0) return "<span class='badge badge-pass'>$d (Trung bình)</span>";
    return "<span class='badge badge-fail'>$d (Yếu)</span>";
}
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Bảng Điểm</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/student_nav.php'; ?>
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="fa-solid fa-chart-bar" style="color:var(--accent)"></i> Bảng Điểm</h1>
      <p class="page-sub">Kết quả học tập của bạn</p>
    </div>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
      <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
      <div class="stat-info"><div class="stat-value"><?= $gpa4 ?></div><div class="stat-label">Điểm TB tích lũy</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="color:var(--accent)"><i class="fa-solid fa-book"></i></div>
      <div class="stat-info"><div class="stat-value"><?= $totalTC ?></div><div class="stat-label">Tín chỉ tích lũy</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="color:#f87171"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div class="stat-info"><div class="stat-value"><?= $fail ?></div><div class="stat-label">Môn không đạt</div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <span class="card-title">Chi tiết điểm</span>
      <form method="GET" style="display:flex;gap:10px">
        <select name="hk" class="form-select" style="width:auto" onchange="this.form.submit()">
          <option value="">-- Tất cả HK --</option>
          <option <?= $hk=='HK1'?'selected':'' ?>>HK1</option>
          <option <?= $hk=='HK2'?'selected':'' ?>>HK2</option>
        </select>
        <select name="nh" class="form-select" style="width:auto" onchange="this.form.submit()">
          <option value="">-- Tất cả NH --</option>
          <option <?= $nh=='2024-2025'?'selected':'' ?>>2024-2025</option>
          <option <?= $nh=='2023-2024'?'selected':'' ?>>2023-2024</option>
        </select>
      </form>
    </div>
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Mã môn</th><th>Tên môn</th><th>TC</th>
            <th>HK/NH</th><th>Giữa kỳ</th><th>Cuối kỳ</th><th>Tổng kết</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($diems)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text2);padding:30px">Chưa có dữ liệu điểm</td></tr>
          <?php else: foreach ($diems as $i => $d): ?>
          <tr>
            <td style="color:var(--text2)"><?= $i+1 ?></td>
             <td><code style="color:#f43f6d"><?= $d['ma_mon'] ?></code></td>
            <td style="font-weight:500"><?= htmlspecialchars($d['ten_mon']) ?></td>
            <td style="text-align:center"><?= $d['so_tin_chi'] ?></td>
            <td><span class="badge" style="background:rgba(255,255,255,0.08);color:var(--text2)"><?= $d['hoc_ky'] ?>/<?= $d['nam_hoc'] ?></span></td>
            <td><?= $d['diem_giua_ky'] ?? '<span style="color:var(--text2)">-</span>' ?></td>
            <td><?= $d['diem_cuoi_ky'] ?? '<span style="color:var(--text2)">-</span>' ?></td>
            <td><?= diemBadge($d['diem_tong_ket']) ?></td>
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
