<?php
require_once '../config.php';
requirePrincipal();
$db  = getDB();

global $NGANH_LIST;
$fkhoa = $_GET['khoa'] ?? '';
$flop  = $_GET['lop']  ?? '';
$fsv   = $_GET['sv']   ?? ''; 
$fhk   = $_GET['hk']   ?? ''; 
$fnh   = $_GET['nh']   ?? '';

$lopList = [];
if ($fkhoa) {
    $stLop = $db->prepare("SELECT DISTINCT lop FROM students WHERE khoa = ? ORDER BY lop");
    $stLop->bind_param("s", $fkhoa);
    $stLop->execute();
    $lopList = $stLop->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $lopList = $db->query("SELECT DISTINCT lop FROM students ORDER BY lop")->fetch_all(MYSQLI_ASSOC);
}

$svSql = "SELECT id,ma_sv,ho_ten,lop FROM students WHERE 1=1";
$svParams = []; $svTypes = "";
if ($flop) {
    $svSql .= " AND lop=?"; $svParams[] = $flop; $svTypes .= "s";
} else if ($fkhoa) {
    $svSql .= " AND khoa=?"; $svParams[] = $fkhoa; $svTypes .= "s";
}
$svSql .= " ORDER BY lop, ho_ten";
if (!empty($svParams)) {
    $stSv = $db->prepare($svSql); $stSv->bind_param($svTypes, ...$svParams); $stSv->execute();
    $svList = $stSv->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $svList = $db->query($svSql)->fetch_all(MYSQLI_ASSOC);
}

$sql  = "SELECT d.*,s.ma_sv,s.ho_ten,s.lop,s.khoa,m.ten_mon,m.ma_mon FROM diem d 
         JOIN students s ON d.student_id=s.id JOIN mon_hoc m ON d.mon_hoc_id=m.id WHERE 1=1";

if ($fsv) { 
    $sql.=" AND d.student_id=" . (int)$fsv; 
} else {
    if ($flop) {
        $sql .= " AND s.lop='" . $db->real_escape_string($flop) . "'";
    } else if ($fkhoa) {
        $sql .= " AND s.khoa='" . $db->real_escape_string($fkhoa) . "'";
    }
}
if ($fhk) { $sql.=" AND d.hoc_ky='".addslashes($fhk)."'"; }
if ($fnh) { $sql.=" AND d.nam_hoc='".addslashes($fnh)."'"; }
$sql .= " ORDER BY s.lop,s.ho_ten,m.ten_mon";
$list = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

function dBg($d) {
    if($d===null) return 'color:var(--text2)';
    if($d>=8.5) return 'color:#c084fc; font-weight:700;';
    if($d>=7.0) return 'color:#f43f6d; font-weight:700;';
    if($d>=5.0) return 'color:#4ade80; font-weight:700;';
    return 'color:#f87171; font-weight:700;';
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Kết Quả Học Tập - Hiệu Trưởng</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
    <?php include '../includes/principal_nav.php'; ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-chart-line" style="color:#fbbf24"></i> Báo Cáo Kết Quả Học Tập</h1>
            <p class="page-sub">Xem và theo dõi học lực của toàn bộ sinh viên trong trường</p>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <select class="form-select" name="khoa" onchange="this.form.lop.value=''; this.form.sv.value=''; this.form.submit()">
                <option value="">-- Tất cả ngành --</option>
                <?php foreach ($NGANH_LIST as $ng): ?>
                    <option <?= $fkhoa == $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="lop" onchange="this.form.sv.value=''; this.form.submit()">
                <option value="">-- Tất cả lớp --</option>
                <?php foreach ($lopList as $l): ?>
                    <option <?= $flop == $l['lop'] ? 'selected' : '' ?>><?= htmlspecialchars($l['lop']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="sv" onchange="this.form.submit()">
                <option value="">-- Tất cả sinh viên --</option>
                <?php foreach ($svList as $sv): ?>
                    <option value="<?= $sv['id'] ?>" <?= $fsv == $sv['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sv['ho_ten']) ?> (<?= htmlspecialchars($sv['lop']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="hk" onchange="this.form.submit()">
                <option value="">-- Tất cả học kỳ --</option>
                <option <?= $fhk == 'HK1' ? 'selected' : '' ?>>HK1</option>
                <option <?= $fhk == 'HK2' ? 'selected' : '' ?>>HK2</option>
            </select>
            <select class="form-select" name="nh" onchange="this.form.submit()">
                <option value="">-- Tất cả năm học --</option>
                <option <?= $fnh == '2026-2027' ? 'selected' : '' ?>>2026-2027</option>
                <option <?= $fnh == '2025-2026' ? 'selected' : '' ?>>2025-2026</option>
                <option <?= $fnh == '2024-2025' ? 'selected' : '' ?>>2024-2025</option>
            </select>
            <button class="btn btn-primary" type="submit">Lọc</button>
        </form>
        <span style="color:var(--text2);font-size:13px;align-self:center">Tìm thấy: <b style="color:var(--text)"><?= count($list) ?></b> kết quả</span>
    </div>

    <div class="card">
        <div class="card-head">
            <span class="card-title">Kết quả thống kê điểm số</span>
        </div>
        <div class="card-body" style="padding:0; overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Lớp</th>
                        <th>Mã SV</th>
                        <th>Họ tên</th>
                        <th>Tên môn học</th>
                        <th style="text-align: center;">Điểm GK</th>
                        <th style="text-align: center;">Điểm CK</th>
                        <th style="text-align: center;">Tổng kết</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="9" style="text-align:center;color:var(--text2);padding:30px">Chưa có dữ liệu học tập nào khớp với bộ lọc.</td></tr>
                    <?php else: foreach ($list as $i => $row): ?>
                        <tr>
                            <td style="color:var(--text2)"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['lop']) ?></td>
                            <td><code style="color:#f43f6d; font-weight:600;"><?= htmlspecialchars($row['ma_sv']) ?></code></td>
                            <td><strong><?= htmlspecialchars($row['ho_ten']) ?></strong></td>
                            <td><?= htmlspecialchars($row['ten_mon']) ?></td>
                            <td style="text-align: center; font-weight: 600;"><?= $row['diem_giua_ky'] !== null ? number_format($row['diem_giua_ky'], 1) : '-' ?></td>
                            <td style="text-align: center; font-weight: 600;"><?= $row['diem_cuoi_ky'] !== null ? number_format($row['diem_cuoi_ky'], 1) : '-' ?></td>
                            <td style="text-align: center; font-weight: 700;">
                                <span style="<?= dBg($row['diem_tong_ket']) ?>"><?= $row['diem_tong_ket'] !== null ? number_format($row['diem_tong_ket'], 2) : '-' ?></span>
                            </td>
                            <td><span style="font-size:12px; color:var(--text2);"><?= htmlspecialchars($row['ghi_chu'] ?? '') ?></span></td>
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
