<?php
require_once '../config.php';
requireTeacher();
$db  = getDB();
$msg = '';
$gv_id = $_SESSION['giang_vien_id'] ?? 0;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Fetch teacher department
$st_t = $db->prepare("SELECT khoa FROM giang_vien WHERE id = ?");
$st_t->bind_param("i", $gv_id);
$st_t->execute();
$t_row = $st_t->get_result()->fetch_assoc();
$teacher_khoa = $t_row['khoa'] ?? '';

if ($action === 'save') {
    $sid = (int)$_POST['student_id'];
    $mid = (int)$_POST['mon_hoc_id'];
    $hk  = trim($_POST['hoc_ky'] ?? 'HK1');
    $nh  = trim($_POST['nam_hoc'] ?? '2026-2027');
    $gk  = $_POST['diem_giua_ky'] !== '' ? (float)$_POST['diem_giua_ky'] : null;
    $ck  = $_POST['diem_cuoi_ky'] !== '' ? (float)$_POST['diem_cuoi_ky'] : null;
    $tk  = ($gk !== null && $ck !== null) ? round($gk * 0.3 + $ck * 0.7, 2) : null;
    $gc  = trim($_POST['ghi_chu'] ?? '');

    // Secure check: verify if student belongs to teacher's department
    if ($teacher_khoa) {
        $st_chk = $db->prepare("SELECT id FROM students WHERE id = ? AND LOWER(khoa) = LOWER(?)");
        $st_chk->bind_param("is", $sid, $teacher_khoa);
        $st_chk->execute();
        if ($st_chk->get_result()->num_rows === 0) {
            $msg = 'error:Sinh viên này không thuộc khoa của bạn!';
        }
    }

    if (!$msg) {
        // Secure check: verify if the teacher is assigned this subject
        $checkTaught = $db->prepare("SELECT id FROM thoi_khoa_bieu WHERE giang_vien_id = ? AND mon_hoc_id = ?");
        $checkTaught->bind_param("ii", $gv_id, $mid);
        $checkTaught->execute();
        if ($checkTaught->get_result()->num_rows === 0) {
            $msg = 'error:Bạn không có quyền quản lý điểm cho môn học này!';
        } else {
            $st = $db->prepare("INSERT INTO diem (student_id,mon_hoc_id,hoc_ky,nam_hoc,diem_giua_ky,diem_cuoi_ky,diem_tong_ket,ghi_chu)
                                 VALUES(?,?,?,?,?,?,?,?)
                                 ON DUPLICATE KEY UPDATE diem_giua_ky=?,diem_cuoi_ky=?,diem_tong_ket=?,ghi_chu=?");
            $st->bind_param("iissdddsddds",$sid,$mid,$hk,$nh,$gk,$ck,$tk,$gc,$gk,$ck,$tk,$gc);
            if ($st->execute()) $msg='success:Đã lưu điểm thành công!';
            else $msg='error:Lỗi lưu điểm: '.$db->error;
        }
    }
}

$flop  = $_GET['lop']  ?? '';
$fmon  = $_GET['mon_hoc_id'] ?? '';
$fhk   = $_GET['hk']   ?? 'HK1'; 
$fnh   = $_GET['nh']   ?? '2026-2027';

// Get classes list (restricted to teacher's department)
$lopList = [];
if ($teacher_khoa) {
    $st_l = $db->prepare("SELECT DISTINCT lop FROM students WHERE LOWER(khoa) = LOWER(?) ORDER BY lop");
    $st_l->bind_param("s", $teacher_khoa);
    $st_l->execute();
    $lopList = $st_l->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $lopList = $db->query("SELECT DISTINCT lop FROM students ORDER BY lop")->fetch_all(MYSQLI_ASSOC);
}

// Get subjects list assigned to this teacher
$st_mon = $db->prepare("
    SELECT DISTINCT m.id, m.ten_mon, m.ma_mon 
    FROM thoi_khoa_bieu tkb 
    JOIN mon_hoc m ON tkb.mon_hoc_id = m.id 
    WHERE tkb.giang_vien_id = ? 
    ORDER BY m.ten_mon
");
$st_mon->bind_param("i", $gv_id);
$st_mon->execute();
$monList = $st_mon->get_result()->fetch_all(MYSQLI_ASSOC);

// Get students list in the selected class (restricted to teacher's department)
$students = [];
if ($flop) {
    if ($teacher_khoa) {
        $stSv = $db->prepare("SELECT id, ma_sv, ho_ten FROM students WHERE lop = ? AND LOWER(khoa) = LOWER(?) ORDER BY ho_ten");
        $stSv->bind_param("ss", $flop, $teacher_khoa);
    } else {
        $stSv = $db->prepare("SELECT id, ma_sv, ho_ten FROM students WHERE lop = ? ORDER BY ho_ten");
        $stSv->bind_param("s", $flop);
    }
    $stSv->execute();
    $students = $stSv->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Retrieve grades of class/subject/term
$gradesMap = [];
if ($flop && $fmon) {
    $stGrades = $db->prepare("
        SELECT d.*, s.ma_sv, s.ho_ten 
        FROM diem d 
        JOIN students s ON d.student_id = s.id 
        WHERE d.mon_hoc_id = ? AND s.lop = ? AND d.hoc_ky = ? AND d.nam_hoc = ?
    ");
    $stGrades->bind_param("isss", $fmon, $flop, $fhk, $fnh);
    $stGrades->execute();
    $gradesList = $stGrades->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($gradesList as $g) {
        $gradesMap[$g['student_id']] = $g;
    }
}

function dBg($d) {
    if($d===null) return 'color:var(--text2)';
    if($d>=8.5) return 'color:#c084fc; font-weight:700;';
    if($d>=7.0) return 'color:#f43f6d; font-weight:700;';
    if($d>=5.0) return 'color:#4ade80; font-weight:700;';
    return 'color:#f87171; font-weight:700;';
}

$msgType = $msgText = '';
if($msg) [$msgType,$msgText]=explode(':',$msg,2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Nhập Điểm Giảng Viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
    <?php include '../includes/teacher_nav.php'; ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-star-half-stroke" style="color:var(--accent)"></i> Nhập Điểm Học Phần</h1>
            <p class="page-sub">Quản lý điểm số các môn học được phân công giảng dạy</p>
        </div>
    </div>

    <?php if ($msgText): ?>
        <div class="alert alert-<?= $msgType ?>" style="background:<?= $msgType=='success'?'rgba(16, 185, 129, 0.1)':'rgba(239, 68, 68, 0.1)' ?>; border: 1px solid <?= $msgType=='success'?'#10b981':'#ef4444' ?>; color: <?= $msgType=='success'?'#059669':'#dc2626' ?>; padding: 12px; border-radius: 10px; margin-bottom: 20px;">
            <?= htmlspecialchars($msgText) ?>
        </div>
    <?php endif; ?>

    <div class="filter-bar">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <select class="form-select" name="lop" required onchange="this.form.submit()">
                <option value="">-- Chọn lớp --</option>
                <?php foreach ($lopList as $l): ?>
                    <option <?= $flop == $l['lop'] ? 'selected' : '' ?>><?= htmlspecialchars($l['lop']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="mon_hoc_id" required onchange="this.form.submit()">
                <option value="">-- Chọn môn học --</option>
                <?php foreach ($monList as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $fmon == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['ten_mon']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="hk" onchange="this.form.submit()">
                <option <?= $fhk == 'HK1' ? 'selected' : '' ?>>HK1</option>
                <option <?= $fhk == 'HK2' ? 'selected' : '' ?>>HK2</option>
            </select>
            <select class="form-select" name="nh" onchange="this.form.submit()">
                <option <?= $fnh == '2026-2027' ? 'selected' : '' ?>>2026-2027</option>
                <option <?= $fnh == '2025-2026' ? 'selected' : '' ?>>2025-2026</option>
                <option <?= $fnh == '2024-2025' ? 'selected' : '' ?>>2024-2025</option>
            </select>
            <button class="btn btn-primary" type="submit">Lọc dữ liệu</button>
        </form>
    </div>

    <?php if ($flop && $fmon): ?>
        <div class="card">
            <div class="card-head">
                <span class="card-title">Danh sách sinh viên lớp: <b style="color:var(--accent)"><?= htmlspecialchars($flop) ?></b></span>
            </div>
            <div class="card-body" style="padding:0; overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Mã SV</th>
                            <th>Họ tên</th>
                            <th style="text-align: center; width: 120px;">Điểm giữa kỳ (30%)</th>
                            <th style="text-align: center; width: 120px;">Điểm cuối kỳ (70%)</th>
                            <th style="text-align: center; width: 100px;">Tổng kết</th>
                            <th>Ghi chú</th>
                            <th style="text-align: center; width: 120px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="7" style="text-align:center;color:var(--text2);padding:30px">Không tìm thấy sinh viên nào trong lớp này.</td></tr>
                        <?php else: foreach ($students as $sv): 
                            $gr = $gradesMap[$sv['id']] ?? null;
                            $gk_val = $gr ? $gr['diem_giua_ky'] : '';
                            $ck_val = $gr ? $gr['diem_cuoi_ky'] : '';
                            $tk_val = $gr ? $gr['diem_tong_ket'] : null;
                            $note_val = $gr ? $gr['ghi_chu'] : '';
                        ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="student_id" value="<?= $sv['id'] ?>">
                            <input type="hidden" name="mon_hoc_id" value="<?= htmlspecialchars($fmon) ?>">
                            <input type="hidden" name="hoc_ky" value="<?= htmlspecialchars($fhk) ?>">
                            <input type="hidden" name="nam_hoc" value="<?= htmlspecialchars($fnh) ?>">
                            <tr>
                                <td><code style="color:#f43f6d; font-weight: 600;"><?= htmlspecialchars($sv['ma_sv']) ?></code></td>
                                <td><strong><?= htmlspecialchars($sv['ho_ten']) ?></strong></td>
                                <td style="text-align: center;">
                                    <input type="number" step="0.1" min="0" max="10" name="diem_giua_ky" class="form-input" style="width: 80px; text-align: center; padding: 6px;" value="<?= $gk_val ?>">
                                </td>
                                <td style="text-align: center;">
                                    <input type="number" step="0.1" min="0" max="10" name="diem_cuoi_ky" class="form-input" style="width: 80px; text-align: center; padding: 6px;" value="<?= $ck_val ?>">
                                </td>
                                <td style="text-align: center; font-weight: 700;">
                                    <span style="<?= dBg($tk_val) ?>"><?= $tk_val !== null ? number_format($tk_val, 2) : '-' ?></span>
                                </td>
                                <td>
                                    <input type="text" name="ghi_chu" class="form-input" style="padding: 6px;" placeholder="Ghi chú..." value="<?= htmlspecialchars($note_val) ?>">
                                </td>
                                <td style="text-align: center;">
                                    <button type="submit" class="btn btn-primary btn-sm" style="padding: 6px 12px; font-size:12px; background:#10b981;"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
                                </td>
                            </tr>
                        </form>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div style="padding:40px 20px; text-align:center; color:var(--text2);">
                <i class="fa-solid fa-star-half-stroke" style="font-size:48px; color:rgba(255,255,255,0.05); margin-bottom:15px; display:block;"></i>
                <p style="margin:0; font-size:14px">Vui lòng chọn lớp và môn học để tiến hành nhập điểm số.</p>
            </div>
        </div>
    <?php endif; ?>
    </div> <!-- content-pad -->
    </div> <!-- main-content -->
</body>
</html>
