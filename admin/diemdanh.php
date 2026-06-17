<?php
require_once '../config.php';
requireAdmin();
$db = getDB();
$msg = '';
$action = $_POST['action'] ?? '';

if ($action === 'save_attendance') {
    $mon_hoc_id = (int)$_POST['mon_hoc_id'];
    $ngay_diem_danh = $_POST['ngay_diem_danh'];
    $statuses = $_POST['status'] ?? [];
    $notes = $_POST['ghi_chu'] ?? [];
    
    $db->begin_transaction();
    try {
        $st = $db->prepare("INSERT INTO diem_danh (student_id, mon_hoc_id, ngay_diem_danh, trang_thai, ghi_chu) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE trang_thai=VALUES(trang_thai), ghi_chu=VALUES(ghi_chu)");
        // Wait, no unique key for diem_danh yet, let me just delete existing for this day and insert.
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

global $NGANH_LIST;
$fkhoa = $_GET['khoa'] ?? '';
$flop = $_GET['lop'] ?? '';
$fmon = $_GET['mon_hoc_id'] ?? '';

$lopList = [];
if ($fkhoa) {
    $stLop = $db->prepare("SELECT DISTINCT lop FROM students WHERE khoa = ? ORDER BY lop");
    $stLop->bind_param("s", $fkhoa);
    $stLop->execute();
    $lopList = $stLop->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $lopList = $db->query("SELECT DISTINCT lop FROM students ORDER BY lop")->fetch_all(MYSQLI_ASSOC);
}

$monList = $db->query("SELECT id, ten_mon FROM mon_hoc ORDER BY ten_mon")->fetch_all(MYSQLI_ASSOC);
$fngay = $_GET['ngay_diem_danh'] ?? date('Y-m-d');

$students = [];
$attendanceMap = [];
if ($flop && $fmon && $fngay) {
    $st = $db->prepare("SELECT id, ma_sv, ho_ten FROM students WHERE lop=? ORDER BY ho_ten");
    $st->bind_param("s", $flop);
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
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Điểm danh</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
  .radio-group { display: flex; gap: 10px; }
  .radio-group label { cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 14px; }
  .att-co-mat { color: #15803d; font-weight: 600; }
  .att-vang-mat { color: #b91c1c; font-weight: 600; }
  .att-phep { color: #eab308; font-weight: 600; }
</style>
</head>
<body>
<?php include '../includes/admin_nav.php'; ?>
<div class="main-content">
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-user-check" style="color:var(--accent)"></i> Quản lý Điểm danh</h1></div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <select class="form-select" name="khoa" onchange="this.form.lop.value=''; this.form.submit()">
        <option value="">-- Tất cả ngành --</option>
        <?php foreach ($NGANH_LIST as $ng): ?>
        <option <?= $fkhoa == $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
        <?php endforeach; ?>
      </select>
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
    <div style="padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
      <span style="font-weight: 700; font-size: 16px;">Danh sách lớp: <span style="color:var(--accent)"><?= htmlspecialchars($flop) ?></span></span>
      <span style="color: #666; font-size: 14px;">Ngày: <?= date('d/m/Y', strtotime($fngay)) ?></span>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_attendance">
      <input type="hidden" name="mon_hoc_id" value="<?= htmlspecialchars($fmon) ?>">
      <input type="hidden" name="ngay_diem_danh" value="<?= htmlspecialchars($fngay) ?>">
      
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr><th>#</th><th>Mã SV</th><th>Họ tên</th><th style="min-width:250px">Trạng thái</th><th>Ghi chú</th></tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text2);padding:30px">Không tìm thấy sinh viên nào trong lớp này</td></tr>
            <?php else: foreach ($students as $i => $sv): 
                $att = $attendanceMap[$sv['id']] ?? null;
                $status = $att['trang_thai'] ?? 'Có mặt';
                $note = $att['ghi_chu'] ?? '';
            ?>
            <tr>
              <td style="color:var(--text2)"><?= $i + 1 ?></td>
              <td><code style="color:#f43f6d"><?= htmlspecialchars($sv['ma_sv']) ?></code></td>
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
      <div style="padding: 20px; text-align: right; border-top: 1px solid #eee; background: #fafafa; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 15px;"><i class="fa-solid fa-floppy-disk"></i> Lưu kết quả điểm danh</button>
      </div>
      <?php endif; ?>
    </form>
  </div>
  <?php else: ?>
  <div class="card">
    <div style="padding:40px 20px; text-align:center; color:#666;">
      <i class="fa-regular fa-calendar-check" style="font-size:48px; color:#cbd5e1; margin-bottom:15px; display:block;"></i>
      <p style="margin:0; font-size:14px">Vui lòng chọn lớp, môn học và ngày để xem hoặc nhập điểm danh</p>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
