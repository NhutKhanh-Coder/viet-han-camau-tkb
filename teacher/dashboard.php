<?php
require_once '../config.php';
requireTeacher();

$db = getDB();
$gv_id   = $_SESSION['giang_vien_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
$gv_name = $_SESSION['ho_ten'] ?? 'Giảng viên';

$msg = '';
$msg_type = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_announcement') {
        $tieu_de  = trim($_POST['tieu_de'] ?? '');
        $noi_dung = trim($_POST['noi_dung'] ?? '');
        $loai     = trim($_POST['loai'] ?? 'Thông báo');
        $khoa     = trim($_POST['khoa'] ?? 'Công Nghệ Thông Tin');
        $switch   = isset($_POST['switch_schedule_mode']) ? 1 : 0;

        if (!empty($tieu_de) && !empty($noi_dung)) {
            $stmt_add = $db->prepare("INSERT INTO thong_bao (tieu_de, noi_dung, ngay_dang, trang_thai, khoa, loai, giang_vien_id, tac_gia) VALUES (?, ?, CURDATE(), 'Đã xuất bản', ?, ?, ?, ?)");
            $stmt_add->bind_param("ssssis", $tieu_de, $noi_dung, $khoa, $loai, $gv_id, $gv_name);
            if ($stmt_add->execute()) {
                if ($switch || $loai === 'Lịch thi') {
                    setSystemSetting('schedule_mode', 'lich_thi', $db);
                }
                header("Location: /tkb/teacher/dashboard.php?msg=ann_added");
                exit;
            } else {
                $msg = "Có lỗi xảy ra khi tạo thông báo.";
                $msg_type = 'danger';
            }
        } else {
            $msg = "Vui lòng điền đầy đủ tiêu đề và nội dung thông báo.";
            $msg_type = 'danger';
        }
    } elseif ($action === 'toggle_schedule_mode') {
        $new_mode = ($_POST['mode'] ?? '') === 'lich_thi' ? 'lich_thi' : 'lich_hoc';
        setSystemSetting('schedule_mode', $new_mode, $db);

        $mode_label  = ($new_mode === 'lich_thi') ? 'LỊCH THI HÔM NAY' : 'LỊCH HỌC HÔM NAY';
        $tb_title    = "Thông báo thay đổi thời khóa biểu: " . $mode_label;
        $tb_content  = "Nhà trường và Giảng viên vừa cập nhật chế độ hiển thị thời khóa biểu thành: " . $mode_label . ". Vui lòng theo dõi chi tiết.";
        $tb_loai     = ($new_mode === 'lich_thi') ? 'Lịch thi' : 'Thông báo';

        $stmt_auto = $db->prepare("INSERT INTO thong_bao (tieu_de, noi_dung, ngay_dang, trang_thai, khoa, loai, giang_vien_id, tac_gia) VALUES (?, ?, CURDATE(), 'Đã xuất bản', 'Công Nghệ Thông Tin', ?, ?, ?)");
        $stmt_auto->bind_param("sssis", $tb_title, $tb_content, $tb_loai, $gv_id, $gv_name);
        $stmt_auto->execute();

        header("Location: /tkb/teacher/dashboard.php?msg=mode_updated");
        exit;
    } elseif ($action === 'delete_announcement') {
        $ann_id = (int)($_POST['ann_id'] ?? 0);
        if ($ann_id > 0) {
            $stmt_del = $db->prepare("DELETE FROM thong_bao WHERE id = ?");
            $stmt_del->bind_param("i", $ann_id);
            $stmt_del->execute();
            header("Location: /tkb/teacher/dashboard.php?msg=ann_deleted");
            exit;
        }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'ann_added')     $msg = "✅ Đã đăng thông báo thành công!";
    elseif ($_GET['msg'] === 'mode_updated') $msg = "🔄 Đã cập nhật chế độ lịch học / lịch thi!";
    elseif ($_GET['msg'] === 'ann_deleted')  $msg = "🗑️ Đã xóa thông báo!";
}

// Stats
$stmt = $db->prepare("SELECT COUNT(DISTINCT mon_hoc_id) as c FROM thoi_khoa_bieu WHERE giang_vien_id = ?");
$stmt->bind_param("i", $gv_id); $stmt->execute();
$subjects_count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(DISTINCT d.student_id) as c FROM thoi_khoa_bieu tkb JOIN diem d ON tkb.mon_hoc_id = d.mon_hoc_id WHERE tkb.giang_vien_id = ?");
$stmt->bind_param("i", $gv_id); $stmt->execute();
$students_count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(DISTINCT s.lop) as c FROM thoi_khoa_bieu tkb JOIN students s ON s.khoa = tkb.khoa WHERE tkb.giang_vien_id = ?");
$stmt->bind_param("i", $gv_id); $stmt->execute();
$classes_count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;

// Schedule
$stmt = $db->prepare("SELECT tkb.*, m.ten_mon, m.ma_mon, m.so_tin_chi FROM thoi_khoa_bieu tkb JOIN mon_hoc m ON tkb.mon_hoc_id = m.id WHERE tkb.giang_vien_id = ? ORDER BY tkb.thu, tkb.tiet_bat_dau");
$stmt->bind_param("i", $gv_id); $stmt->execute();
$schedule = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Settings
$current_schedule_mode = getSystemSetting('schedule_mode', 'lich_hoc', $db);
$announcements = $db->query("SELECT * FROM thong_bao WHERE trang_thai = 'Đã xuất bản' ORDER BY ngay_dang DESC, id DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

$db->close();

$is_exam_mode = $current_schedule_mode === 'lich_thi';
$thu_labels   = ['','','2','3','4','5','6','7','CN'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Giảng Viên – <?= htmlspecialchars($gv_name) ?></title>
  <meta name="description" content="Bảng điều khiển giảng viên – Quản lý lịch giảng dạy, thông báo và lịch thi.">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/tkb/assets/teacher_portal.css">
</head>
<body class="teacher-portal">

<?php include '../includes/teacher_nav.php'; ?>

  <!-- Topbar -->
  <div class="tp-topbar">
    <div class="tp-topbar-left">
      <div>
        <div class="tp-breadcrumb">
          <span>Hệ thống</span>
          <span class="sep">/</span>
          <span class="current">Dashboard</span>
        </div>
        <div class="tp-page-title">Bảng Điều Khiển</div>
      </div>
    </div>
    <div class="tp-topbar-right">
      <a href="/tkb/teacher/thongbao.php" class="tp-topbar-btn" title="Thông báo">
        <i class="fa-solid fa-bullhorn"></i>
      </a>
      <a href="/tkb/teacher/profile.php" class="tp-topbar-btn" title="Hồ sơ">
        <i class="fa-solid fa-user"></i>
      </a>
    </div>
  </div>

  <!-- Content -->
  <div class="tp-content">

    <!-- Alert -->
    <?php if (!empty($msg)): ?>
    <div class="tp-alert tp-alert-<?= $msg_type === 'danger' ? 'danger' : 'success' ?>">
      <span><i class="fa-solid fa-<?= $msg_type === 'danger' ? 'triangle-exclamation' : 'circle-check' ?>"></i> <?= htmlspecialchars($msg) ?></span>
      <button class="tp-alert-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="tp-page-header tp-mb-3">
      <div class="tp-page-header-left">
        <h1>
          <span class="page-icon"><i class="fa-solid fa-gauge-high"></i></span>
          Bảng Điều Khiển
        </h1>
        <p class="tp-page-subtitle">Xin chào, Thầy/Cô <strong><?= htmlspecialchars($gv_name) ?></strong> — <?= date('l, d/m/Y') ?></p>
      </div>
      <div class="tp-flex">
        <button onclick="document.getElementById('tp-ann-form').classList.toggle('tp-hidden')" class="tp-btn tp-btn-pink">
          <i class="fa-solid fa-plus"></i> Đăng Thông Báo
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="tp-stats-grid tp-mb-3">
      <div class="tp-stat-card accent">
        <div class="tp-stat-icon accent"><i class="fa-solid fa-book-bookmark"></i></div>
        <div>
          <div class="tp-stat-label">Môn giảng dạy</div>
          <div class="tp-stat-value"><?= $subjects_count ?><span class="tp-stat-unit">Môn</span></div>
        </div>
      </div>
      <div class="tp-stat-card green">
        <div class="tp-stat-icon green"><i class="fa-solid fa-users"></i></div>
        <div>
          <div class="tp-stat-label">Sinh viên đang dạy</div>
          <div class="tp-stat-value"><?= $students_count ?><span class="tp-stat-unit">SV</span></div>
        </div>
      </div>
      <div class="tp-stat-card orange">
        <div class="tp-stat-icon orange"><i class="fa-solid fa-graduation-cap"></i></div>
        <div>
          <div class="tp-stat-label">Lớp quản lý</div>
          <div class="tp-stat-value"><?= $classes_count ?><span class="tp-stat-unit">Lớp</span></div>
        </div>
      </div>
    </div>

    <!-- Announcement & Schedule Mode Control -->
    <div class="tp-action-card tp-mb-3">
      <div class="tp-action-card-header">
        <div style="flex:1;">
          <h3>
            <span class="tp-action-card-icon"><i class="fa-solid fa-bullhorn"></i></span>
            Quản Lý Thông Báo &amp; Chế Độ Thời Khóa Biểu
          </h3>
          <p class="tp-action-card-desc">Đăng thông báo và chuyển đổi chế độ xem lịch cho sinh viên ngay lập tức.</p>
        </div>
      </div>
      <div class="tp-action-card-body">
        <!-- Mode box -->
        <div class="tp-mode-box">
          <div>
            <div class="tp-mode-label">Chế độ hiện tại hiển thị với sinh viên:</div>
            <?php if ($is_exam_mode): ?>
              <span class="tp-mode-pill thi">
                <i class="fa-solid fa-file-pen"></i> 📝 Lịch Thi Học Kỳ <span style="opacity:.6;font-weight:500;font-size:11px;">(Đang bật)</span>
              </span>
            <?php else: ?>
              <span class="tp-mode-pill hoc">
                <i class="fa-solid fa-calendar-day"></i> 📅 Lịch Học Hôm Nay <span style="opacity:.6;font-weight:500;font-size:11px;">(Đang bật)</span>
              </span>
            <?php endif; ?>
          </div>
          <form method="POST" style="margin:0;">
            <input type="hidden" name="action" value="toggle_schedule_mode">
            <input type="hidden" name="mode" value="<?= $is_exam_mode ? 'lich_hoc' : 'lich_thi' ?>">
            <button type="submit" class="tp-btn tp-btn-purple">
              <?php if ($is_exam_mode): ?>
                <i class="fa-solid fa-arrow-rotate-left"></i> Đổi Thành Lịch Học
              <?php else: ?>
                <i class="fa-solid fa-arrow-right-arrow-left"></i> Đổi Thành Lịch Thi
              <?php endif; ?>
            </button>
          </form>
        </div>

        <!-- Add Announcement Form (hidden by default) -->
        <div id="tp-ann-form" class="tp-hidden" style="border:1.5px solid var(--tp-border);border-radius:var(--tp-r-md);padding:22px;background:var(--tp-bg);margin-top:4px;">
          <div class="tp-flex-between tp-mb-2">
            <h4 style="font-size:14.5px;font-weight:700;color:var(--tp-text);display:flex;align-items:center;gap:9px;">
              <i class="fa-solid fa-pen-to-square" style="color:var(--tp-accent);"></i>
              Tạo Thông Báo Mới
            </h4>
            <button type="button" onclick="document.getElementById('tp-ann-form').classList.add('tp-hidden')" class="tp-btn tp-btn-ghost tp-btn-sm">
              <i class="fa-solid fa-times"></i> Đóng
            </button>
          </div>
          <form method="POST">
            <input type="hidden" name="action" value="add_announcement">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:14px;margin-bottom:14px;">
              <div class="tp-form-group" style="margin:0;">
                <label class="tp-form-label">Tiêu đề thông báo *</label>
                <input type="text" name="tieu_de" class="tp-form-control" placeholder="Vd: Lịch thi cuối kỳ HK2..." required>
              </div>
              <div class="tp-form-group" style="margin:0;">
                <label class="tp-form-label">Loại thông báo</label>
                <select name="loai" class="tp-form-control">
                  <option value="Lịch thi">📝 Lịch thi học kỳ</option>
                  <option value="Thông báo">📢 Thông báo học tập</option>
                  <option value="Nghỉ học">🏖️ Nghỉ học</option>
                  <option value="Sự kiện">🏆 Sự kiện</option>
                </select>
              </div>
              <div class="tp-form-group" style="margin:0;">
                <label class="tp-form-label">Khoa / Ngành</label>
                <select name="khoa" class="tp-form-control">
                  <option value="Công Nghệ Thông Tin">CNTT</option>
                  <option value="Cơ Khí Ô Tô">Cơ Khí Ô Tô</option>
                  <option value="Điện - Điện Tử">Điện - Điện Tử</option>
                  <option value="Quản Trị Doanh Nghiệp">QTDN</option>
                  <option value="Tất cả">Tất cả các Khoa</option>
                </select>
              </div>
            </div>
            <div class="tp-form-group">
              <label class="tp-form-label">Nội dung chi tiết *</label>
              <textarea name="noi_dung" rows="3" class="tp-form-control" placeholder="Nhập chi tiết nội dung thông báo hoặc lịch thi..." required></textarea>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px dashed var(--tp-border);">
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--tp-text2);cursor:pointer;">
                <input type="checkbox" name="switch_schedule_mode" value="1" checked style="width:16px;height:16px;accent-color:var(--tp-accent);">
                Chuyển sinh viên sang xem <strong style="color:var(--tp-accent);">Lịch Thi</strong>
              </label>
              <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Đăng Thông Báo
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Schedule + Announcements -->
    <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:20px;align-items:start;">
      <!-- Schedule Card -->
      <div class="tp-card">
        <div class="tp-card-head">
          <div class="tp-card-title">
            <span class="tp-card-title-icon" style="background:rgba(79,70,229,.1);color:var(--tp-accent);">
              <i class="fa-solid fa-calendar-week"></i>
            </span>
            Lịch giảng dạy của tôi
          </div>
          <span class="tp-badge <?= $is_exam_mode ? 'tp-badge-red' : 'tp-badge-blue' ?>">
            <?= $is_exam_mode ? '📝 Lịch Thi' : '📅 Lịch Học' ?>
          </span>
        </div>
        <div class="tp-table-wrap">
          <table class="tp-table">
            <thead>
              <tr>
                <th>Thứ</th>
                <th>Mã HP</th>
                <th>Tên Học Phần</th>
                <th style="text-align:center;">Tiết / Giờ thi</th>
                <th style="text-align:center;">Phòng</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($schedule)): ?>
              <tr>
                <td colspan="5">
                  <div class="tp-empty">
                    <div class="tp-empty-icon"><i class="fa-regular fa-calendar-xmark"></i></div>
                    <div class="tp-empty-text">Không có lịch giảng dạy trong học kỳ này.</div>
                  </div>
                </td>
              </tr>
              <?php else: foreach ($schedule as $sk): ?>
              <tr>
                <td><span style="font-weight:700;color:var(--tp-accent);">Thứ <?= $sk['thu'] == 8 ? 'CN' : $sk['thu'] ?></span></td>
                <td><code><?= htmlspecialchars($sk['ma_mon']) ?></code></td>
                <td>
                  <span style="font-weight:600;"><?= htmlspecialchars($sk['ten_mon']) ?></span>
                  <?php if ($is_exam_mode): ?>
                    <span class="tp-badge tp-badge-red" style="margin-left:6px;font-size:10px;">Thi CK</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;font-weight:600;color:var(--tp-success);">
                  <?= $sk['tiet_bat_dau'] ?> – <?= $sk['tiet_ket_thuc'] ?>
                </td>
                <td style="text-align:center;">
                  <span class="tp-badge tp-badge-gray"><?= htmlspecialchars($sk['phong_hoc'] ?: 'Online') ?></span>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Announcements Card -->
      <div class="tp-card">
        <div class="tp-card-head">
          <div class="tp-card-title">
            <span class="tp-card-title-icon" style="background:rgba(245,158,11,.1);color:#d97706;">
              <i class="fa-solid fa-bullhorn"></i>
            </span>
            Thông báo
          </div>
          <a href="/tkb/teacher/thongbao.php" class="tp-badge tp-badge-accent" style="cursor:pointer;text-decoration:none;">
            Xem tất cả <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i>
          </a>
        </div>
        <div style="padding:0;">
          <?php if (empty($announcements)): ?>
          <div class="tp-empty" style="padding:30px 20px;">
            <div class="tp-empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
            <div class="tp-empty-text">Chưa có thông báo nào.</div>
          </div>
          <?php else: foreach ($announcements as $ann): ?>
          <div class="tp-ann-item">
            <div class="tp-ann-header">
              <div class="tp-ann-badges">
                <span class="tp-badge <?= ($ann['loai'] ?? '') === 'Lịch thi' ? 'tp-badge-red' : 'tp-badge-orange' ?>" style="font-size:10px;">
                  <?= htmlspecialchars($ann['loai'] ?? 'Thông báo') ?>
                </span>
                <span class="tp-badge tp-badge-gray" style="font-size:10px;">
                  <?= htmlspecialchars($ann['khoa'] ?: 'Chung') ?>
                </span>
              </div>
              <div class="tp-ann-meta">
                <span class="tp-ann-date"><i class="fa-regular fa-clock"></i> <?= date('d/m', strtotime($ann['ngay_dang'])) ?></span>
                <form method="POST" style="margin:0;display:inline;" onsubmit="return confirm('Xóa thông báo này?')">
                  <input type="hidden" name="action" value="delete_announcement">
                  <input type="hidden" name="ann_id" value="<?= $ann['id'] ?>">
                  <button type="submit" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:13px;padding:2px 4px;line-height:1;" title="Xóa">&times;</button>
                </form>
              </div>
            </div>
            <div class="tp-ann-title"><?= htmlspecialchars($ann['tieu_de']) ?></div>
            <div class="tp-ann-body"><?= htmlspecialchars(mb_strimwidth(strip_tags($ann['noi_dung']), 0, 100, '...')) ?></div>
            <?php if (!empty($ann['tac_gia'])): ?>
            <div class="tp-ann-author">Đăng bởi: <?= htmlspecialchars($ann['tac_gia']) ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /tp-content -->
</div><!-- /tp-main -->

<style>
.tp-hidden { display: none !important; }
@media (max-width: 900px) {
  [style*="grid-template-columns:minmax(0,2fr)"] {
    grid-template-columns: 1fr !important;
  }
}
</style>

</body>
</html>
