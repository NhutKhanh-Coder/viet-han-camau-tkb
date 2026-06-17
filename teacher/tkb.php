<?php
require_once '../config.php';
requireTeacher();
$db  = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;

$hk  = $_GET['hk']  ?? 'HK1';
$nh  = $_GET['nh']  ?? '2026-2027';

$sql = "SELECT t.*, m.ten_mon, m.ma_mon 
        FROM thoi_khoa_bieu t 
        JOIN mon_hoc m ON t.mon_hoc_id=m.id 
        WHERE t.giang_vien_id=? AND t.hoc_ky=? AND t.nam_hoc=?
        ORDER BY t.thu, t.tiet_bat_dau";
$st2 = $db->prepare($sql);
$st2->bind_param("iss", $gv_id, $hk, $nh);
$st2->execute();
$tkbRaw = $st2->get_result()->fetch_all(MYSQLI_ASSOC);

// Nhóm theo thu -> tiet
$tkb = [];
foreach ($tkbRaw as $r) {
    for ($t = $r['tiet_bat_dau']; $t <= $r['tiet_ket_thuc']; $t++) {
        $tkb[$r['thu']][$t] = $r;
    }
}
$days = [2=>'Thứ Hai',3=>'Thứ Ba',4=>'Thứ Tư',5=>'Thứ Năm',6=>'Thứ Sáu',7=>'Thứ Bảy'];
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Lịch Giảng Dạy - Giảng Viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
    <?php include '../includes/teacher_nav.php'; ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-calendar-days" style="color:var(--accent)"></i> Lịch Giảng Dạy</h1>
            <p class="page-sub">Học kỳ: <b><?= htmlspecialchars($hk) ?></b> &bull; Năm học: <b><?= htmlspecialchars($nh) ?></b></p>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <span class="card-title">Thời khóa biểu giảng dạy trong tuần</span>
            <form method="GET" style="display:flex;gap:10px;align-items:center">
                <select name="hk" class="form-select" style="width:auto" onchange="this.form.submit()">
                    <option <?= $hk=='HK1'?'selected':'' ?>>HK1</option>
                    <option <?= $hk=='HK2'?'selected':'' ?>>HK2</option>
                </select>
                <select name="nh" class="form-select" style="width:auto" onchange="this.form.submit()">
                    <option <?= $nh=='2026-2027'?'selected':'' ?>>2026-2027</option>
                    <option <?= $nh=='2025-2026'?'selected':'' ?>>2025-2026</option>
                    <option <?= $nh=='2024-2025'?'selected':'' ?>>2024-2025</option>
                    <option <?= $nh=='2023-2024'?'selected':'' ?>>2023-2024</option>
                </select>
            </form>
        </div>
        <div class="card-body" style="padding:16px;overflow-x:auto">
            <table class="tkb-table">
                <thead>
                    <tr>
                        <th style="width:60px">Tiết</th>
                        <?php foreach ($days as $d): ?>
                            <th><?= $d ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($tiet=1; $tiet<=10; $tiet++): ?>
                        <tr>
                            <td class="tiet-col"><?= $tiet ?><br><small style="font-weight:400;font-size:10px"><?= ['','7:00','7:55','8:50','9:45','10:40','11:35','13:00','13:55','14:50','15:45'][$tiet] ?></small></td>
                            <?php foreach ($days as $thu => $ten): ?>
                                <td>
                                    <?php if (isset($tkb[$thu][$tiet])):
                                        $m = $tkb[$thu][$tiet];
                                        if ($tiet == $m['tiet_bat_dau']): ?>
                                            <div class="tkb-cell" style="--span:<?= $m['tiet_ket_thuc']-$m['tiet_bat_dau']+1 ?>; background: rgba(56, 189, 248, 0.08); border-left: 3px solid #38bdf8;">
                                                <div class="tkb-cell-mon" style="color:#38bdf8; font-weight:700;"><?= htmlspecialchars($m['ten_mon']) ?></div>
                                                <div class="tkb-cell-room" style="color:var(--text); font-weight:600;"><i class="fa-solid fa-location-dot" style="font-size:10px;"></i> Phòng: <?= htmlspecialchars($m['phong_hoc']) ?></div>
                                                <div style="font-size:10px;color:var(--text2);margin-top:3px; font-weight:600;">Lớp/Ngành: <?= htmlspecialchars($m['khoa']) ?></div>
                                                <div style="font-size:10px;color:var(--text2);margin-top:2px;">Tiết <?= $m['tiet_bat_dau'] ?>-<?= $m['tiet_ket_thuc'] ?></div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div> <!-- content-pad -->
    </div> <!-- main-content -->
</body>
</html>
