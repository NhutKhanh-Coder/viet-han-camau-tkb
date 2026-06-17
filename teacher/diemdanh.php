<?php
require_once '../config.php';
requireTeacher();
$db = getDB();
$msg = '';
$gv_id = $_SESSION['giang_vien_id'] ?? 0;
$action = $_POST['action'] ?? '';

// Fetch teacher department
$st_t = $db->prepare("SELECT khoa FROM giang_vien WHERE id = ?");
$st_t->bind_param("i", $gv_id);
$st_t->execute();
$t_row = $st_t->get_result()->fetch_assoc();
$teacher_khoa = $t_row['khoa'] ?? '';

if ($action === 'save_attendance') {
    $mon_hoc_id = (int)$_POST['mon_hoc_id'];
    $ngay_diem_danh = $_POST['ngay_diem_danh'];
    $statuses = $_POST['status'] ?? [];
    $notes = $_POST['ghi_chu'] ?? [];
    
    // Secure check: verify if students belong to teacher's department
    if ($teacher_khoa) {
        foreach ($statuses as $student_id => $status) {
            $student_id = (int)$student_id;
            $st_chk = $db->prepare("SELECT id FROM students WHERE id = ? AND LOWER(khoa) = LOWER(?)");
            $st_chk->bind_param("is", $student_id, $teacher_khoa);
            $st_chk->execute();
            if ($st_chk->get_result()->num_rows === 0) {
                $msg = 'error:Một hoặc nhiều sinh viên không thuộc khoa của bạn!';
                break;
            }
        }
    }
    
    if (!$msg) {
        // Secure check: verify if this teacher teaches this subject
        $checkTaught = $db->prepare("SELECT id FROM thoi_khoa_bieu WHERE giang_vien_id = ? AND mon_hoc_id = ?");
        $checkTaught->bind_param("ii", $gv_id, $mon_hoc_id);
        $checkTaught->execute();
        if ($checkTaught->get_result()->num_rows === 0) {
            $msg = 'error:Bạn không có quyền thực hiện điểm danh cho môn học này!';
        } else {
            $db->begin_transaction();
            try {
                $st_del = $db->prepare("DELETE FROM diem_danh WHERE mon_hoc_id=? AND ngay_diem_danh=? AND student_id=?");
                $st_ins = $db->prepare("INSERT INTO diem_danh (student_id, mon_hoc_id, ngay_diem_danh, trang_thai, ghi_chu) VALUES (?, ?, ?, ?, ?)");
                
                foreach ($statuses as $student_id => $status) {
                    $student_id = (int)$student_id;
                    $note = $notes[$student_id] ?? '';
                    
                    $st_del->bind_param("isi", $mon_hoc_id, $ngay_diem_danh, $student_id);
                    $st_del->execute();
                    
                    $st_ins->bind_param("iisss", $student_id, $mon_hoc_id, $ngay_diem_danh, $status, $note);
                    $st_ins->execute();
                }
                $db->commit();
                $msg = 'success:Đã lưu điểm danh thành công!';
            } catch(Exception $e) {
                $db->rollback();
                $msg = 'error:Lỗi lưu điểm danh: ' . $e->getMessage();
            }
        }
    }
}

$flop = $_GET['lop'] ?? '';
$fmon = $_GET['mon_hoc_id'] ?? '';
$fngay = $_GET['ngay_diem_danh'] ?? date('Y-m-d');

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
    SELECT DISTINCT m.id, m.ten_mon 
    FROM thoi_khoa_bieu tkb 
    JOIN mon_hoc m ON tkb.mon_hoc_id = m.id 
    WHERE tkb.giang_vien_id = ? 
    ORDER BY m.ten_mon
");
$st_mon->bind_param("i", $gv_id);
$st_mon->execute();
$monList = $st_mon->get_result()->fetch_all(MYSQLI_ASSOC);

$students = [];
$attendanceMap = [];
if ($flop && $fmon && $fngay) {
    if ($teacher_khoa) {
        $st = $db->prepare("SELECT id, ma_sv, ho_ten FROM students WHERE lop=? AND LOWER(khoa) = LOWER(?) ORDER BY ho_ten");
        $st->bind_param("ss", $flop, $teacher_khoa);
    } else {
        $st = $db->prepare("SELECT id, ma_sv, ho_ten FROM students WHERE lop=? ORDER BY ho_ten");
        $st->bind_param("s", $flop);
    }
    $st->execute();
    $students = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $st2 = $db->prepare("SELECT student_id, trang_thai, ghi_chu FROM diem_danh WHERE mon_hoc_id=? AND ngay_diem_danh=?");
    $st2->bind_param("is", $fmon, $fngay);
    $st2->execute();
    $attRecords = $st2->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($attRecords as $r) {
        $attendanceMap[$r['student_id']] = $r;
    }
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Điểm Danh Giảng Viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
      .radio-group { display: flex; gap: 15px; }
      .radio-group label { cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 14px; }
      .att-co-mat { color: #4ade80; font-weight: 600; }
      .att-vang-mat { color: #f87171; font-weight: 600; }
      .att-phep { color: #fbbf24; font-weight: 600; }
    </style>
</head>
<body>
    <?php include '../includes/teacher_nav.php'; ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-user-check" style="color:var(--accent)"></i> Điểm Danh Lớp Học</h1>
            <p class="page-sub">Ghi nhận thông tin đi học, nghỉ học của sinh viên mỗi buổi dạy</p>
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
            <select class="form-select" name="mon_hoc_id" required>
                <option value="">-- Chọn môn học --</option>
                <?php foreach ($monList as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $fmon == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['ten_mon']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" class="form-input" name="ngay_diem_danh" value="<?= htmlspecialchars($fngay) ?>" required>
            <button class="btn btn-primary" type="submit">Xem / Nhập</button>
        </form>
    </div>

    <?php if ($flop && $fmon && $fngay): ?>
        <div class="card">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; font-size: 16px;">Danh sách lớp: <span style="color:var(--accent)"><?= htmlspecialchars($flop) ?></span></span>
                <span style="color: var(--text2); font-size: 14px;">Ngày: <?= date('d/m/Y', strtotime($fngay)) ?></span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_attendance">
                <input type="hidden" name="mon_hoc_id" value="<?= htmlspecialchars($fmon) ?>">
                <input type="hidden" name="ngay_diem_danh" value="<?= htmlspecialchars($fngay) ?>">
                
                <div style="overflow-x:auto">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Mã SV</th>
                                <th>Họ tên</th>
                                <th style="min-width:250px">Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr><td colspan="5" style="text-align:center;color:var(--text2);padding:30px">Không tìm thấy sinh viên nào trong lớp này.</td></tr>
                            <?php else: foreach ($students as $i => $sv): 
                                $att = $attendanceMap[$sv['id']] ?? null;
                                $status = $att['trang_thai'] ?? 'Có mặt';
                                $note = $att['ghi_chu'] ?? '';
                            ?>
                            <tr>
                                <td style="color:var(--text2)"><?= $i + 1 ?></td>
                                <td><code style="color:#f43f6d; font-weight:600;"><?= htmlspecialchars($sv['ma_sv']) ?></code></td>
                                <td style="font-weight:600"><?= htmlspecialchars($sv['ho_ten']) ?></td>
                                <td>
                                    <div class="radio-group">
                                        <label class="att-co-mat"><input type="radio" name="status[<?= $sv['id'] ?>]" value="Có mặt" <?= $status == 'Có mặt' ? 'checked' : '' ?>> Có mặt</label>
                                        <label class="att-vang-mat"><input type="radio" name="status[<?= $sv['id'] ?>]" value="Vắng mặt" <?= $status == 'Vắng mặt' ? 'checked' : '' ?>> Vắng</label>
                                        <label class="att-phep"><input type="radio" name="status[<?= $sv['id'] ?>]" value="Có phép" <?= $status == 'Có phép' ? 'checked' : '' ?>> Phép</label>
                                    </div>
                                </td>
                                <td>
                                    <input class="form-input" style="padding:5px 10px; font-size:13px;" name="ghi_chu[<?= $sv['id'] ?>]" value="<?= htmlspecialchars($note) ?>" placeholder="Ghi chú...">
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($students)): ?>
                    <div style="padding: 20px; text-align: right; border-top: 1px solid var(--border); background: var(--bg2); border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 15px; background:#10b981;"><i class="fa-solid fa-floppy-disk"></i> Lưu kết quả điểm danh</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    <?php else: ?>
        <div class="card">
            <div style="padding:40px 20px; text-align:center; color:var(--text2);">
                <i class="fa-regular fa-calendar-check" style="font-size:48px; color:rgba(255,255,255,0.05); margin-bottom:15px; display:block;"></i>
                <p style="margin:0; font-size:14px">Vui lòng chọn lớp, môn học và ngày học để tiến hành điểm danh.</p>
            </div>
        </div>
    <?php endif; ?>
    </div> <!-- content-pad -->
    </div> <!-- main-content -->
</body>
</html>
