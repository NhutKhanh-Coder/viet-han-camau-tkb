<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

$tab = $_POST['tab'] ?? $_GET['tab'] ?? 'manage'; // manage or grade
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle actions
if ($action === 'add') {
    $mid = (int)$_POST['mon_hoc_id'];
    $lop = trim($_POST['lop'] ?? '');
    $title = trim($_POST['tieu_de'] ?? '');
    $desc = trim($_POST['mo_ta'] ?? '');
    $gv_selected = (int)($_POST['giang_vien_id'] ?? 0);
    $deadline_date = trim($_POST['han_nop_date'] ?? '');
    $deadline_time = trim($_POST['han_nop_time'] ?? '23:59');
    $deadline = !empty($deadline_date) ? $deadline_date . ' ' . $deadline_time : '';
    
    if ($mid && $lop && $title) {
        $file_url = null;
        if (isset($_FILES['assign_file']) && $_FILES['assign_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['assign_file']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['assign_file']['tmp_name'], $target_file)) {
                $file_url = '/tkb/assets/uploads/assignments/' . $filename;
            } else {
                $msg = "error:Lỗi không thể tải lên tài liệu học liệu.";
            }
        }
        
        if (!$msg) {
            $deadline_val = !empty($deadline) ? date('Y-m-d H:i:s', strtotime($deadline)) : null;
            $stmt = $db->prepare("INSERT INTO assignments (giang_vien_id, mon_hoc_id, lop, tieu_de, mo_ta, han_nop, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssss", $gv_selected, $mid, $lop, $title, $desc, $deadline_val, $file_url);
            if ($stmt->execute()) {
                $msg = "success:Admin đã tạo và giao bài tập mới thành công cho lớp " . htmlspecialchars($lop) . "!";
                writeSystemLog("Admin tạo bài tập: $title cho lớp $lop");
            } else {
                $msg = "error:Lỗi giao bài tập: " . $db->error;
            }
        }
    } else {
        $msg = "error:Vui lòng điền đầy đủ tiêu đề, chọn lớp và chọn môn học.";
    }
} elseif ($action === 'delete') {
    $aid = (int)$_GET['assignment_id'];
    $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->bind_param("i", $aid);
    if ($stmt->execute()) {
        $msg = "success:Admin đã xóa bài tập thành công!";
        writeSystemLog("Admin xóa bài tập ID $aid");
    } else {
        $msg = "error:Lỗi xóa bài tập: " . $db->error;
    }
} elseif ($action === 'grade') {
    $sid = (int)$_POST['student_id'];
    $aid = (int)$_POST['assignment_id'];
    $grade = $_POST['grade'] !== '' ? (float)$_POST['grade'] : null;
    $feedback = trim($_POST['feedback'] ?? '');
    
    $chk_sub = $db->prepare("SELECT id FROM submissions WHERE student_id = ? AND assignment_id = ?");
    $chk_sub->bind_param("ii", $sid, $aid);
    $chk_sub->execute();
    $existing_sub = $chk_sub->get_result()->fetch_assoc();
    $chk_sub->close();

    if ($existing_sub) {
        $stmt = $db->prepare("UPDATE submissions SET grade = ?, feedback = ? WHERE student_id = ? AND assignment_id = ?");
        $stmt->bind_param("dsii", $grade, $feedback, $sid, $aid);
    } else {
        $stmt = $db->prepare("INSERT INTO submissions (student_id, assignment_id, grade, feedback, submitted_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iids", $sid, $aid, $grade, $feedback);
    }

    if ($stmt->execute()) {
        $msg = "success:Đã lưu điểm và nhận xét bài nộp thành công!";
        $tab = 'grade';
        writeSystemLog("Admin chấm bài tập ID $aid cho SV $sid: $grade đ");
    } else {
        $msg = "error:Lỗi chấm điểm: " . $db->error;
    }
}

// Filter parameters
$filter_gv = (int)($_GET['filter_gv'] ?? 0);
$filter_mon = (int)($_GET['filter_mon'] ?? 0);
$filter_lop = trim($_GET['filter_lop'] ?? '');
$search_q = trim($_GET['search_q'] ?? '');

// Fetch all classes
$res_c = $db->query("SELECT DISTINCT lop FROM students WHERE lop IS NOT NULL AND lop != '' ORDER BY lop");
$classes = $res_c ? $res_c->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all subjects
$res_m = $db->query("SELECT id, ten_mon, ma_mon FROM mon_hoc ORDER BY ten_mon");
$monList = $res_m ? $res_m->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all teachers
$res_gv = $db->query("SELECT id, ho_ten, ma_gv, khoa FROM giang_vien ORDER BY ho_ten");
$gvList = $res_gv ? $res_gv->fetch_all(MYSQLI_ASSOC) : [];

// Build SQL to fetch ALL assignments posted by ALL teachers with robust LEFT JOIN
$sql_assign = "
    SELECT a.*, 
           COALESCE(m.ten_mon, 'Môn học chung') as ten_mon, 
           COALESCE(g.ho_ten, u.ho_ten, 'Ban Quản Trị / Admin') as ten_giang_vien,
           g.ma_gv,
           (SELECT COUNT(id) FROM submissions WHERE assignment_id = a.id) as sub_count,
           (SELECT COUNT(id) FROM students WHERE lop = a.lop) as total_sv,
           (SELECT COUNT(id) FROM submissions WHERE assignment_id = a.id AND grade IS NOT NULL) as graded_count
    FROM assignments a
    LEFT JOIN mon_hoc m ON a.mon_hoc_id = m.id
    LEFT JOIN giang_vien g ON a.giang_vien_id = g.id
    LEFT JOIN users u ON a.giang_vien_id = u.id
    WHERE 1=1
";

if ($filter_gv > 0) {
    $sql_assign .= " AND a.giang_vien_id = " . (int)$filter_gv;
}
if ($filter_mon > 0) {
    $sql_assign .= " AND a.mon_hoc_id = " . (int)$filter_mon;
}
if (!empty($filter_lop)) {
    $lop_escaped = $db->real_escape_string($filter_lop);
    $sql_assign .= " AND a.lop = '$lop_escaped'";
}
if (!empty($search_q)) {
    $sq_escaped = $db->real_escape_string($search_q);
    $sql_assign .= " AND (a.tieu_de LIKE '%$sq_escaped%' OR a.mo_ta LIKE '%$sq_escaped%' OR g.ho_ten LIKE '%$sq_escaped%')";
}

$sql_assign .= " ORDER BY a.id DESC";
$res_assign = $db->query($sql_assign);
$assignments = $res_assign ? $res_assign->fetch_all(MYSQLI_ASSOC) : [];

// Overall stats for Admin KPI
$total_all_assignments = (int)($db->query("SELECT COUNT(*) as c FROM assignments")->fetch_assoc()['c'] ?? 0);
$total_all_submissions = (int)($db->query("SELECT COUNT(*) as c FROM submissions")->fetch_assoc()['c'] ?? 0);
$total_teachers_posted = (int)($db->query("SELECT COUNT(DISTINCT giang_vien_id) as c FROM assignments WHERE giang_vien_id > 0")->fetch_assoc()['c'] ?? 0);

// Fetch submissions for a specific assignment if selected
$selected_assignment_id = (int)($_GET['assignment_id'] ?? ($assignments[0]['id'] ?? 0));
$submissions = [];
$assignment_info = null;

if ($selected_assignment_id) {
    $stmt_chk = $db->prepare("
        SELECT a.*, 
               COALESCE(m.ten_mon, 'Môn học chung') as ten_mon, 
               COALESCE(g.ho_ten, u.ho_ten, 'Ban Quản Trị / Admin') as ten_giang_vien,
               g.ma_gv
        FROM assignments a 
        LEFT JOIN mon_hoc m ON a.mon_hoc_id = m.id 
        LEFT JOIN giang_vien g ON a.giang_vien_id = g.id 
        LEFT JOIN users u ON a.giang_vien_id = u.id 
        WHERE a.id = ?
    ");
    $stmt_chk->bind_param("i", $selected_assignment_id);
    $stmt_chk->execute();
    $assignment_info = $stmt_chk->get_result()->fetch_assoc();
    
    if ($assignment_info) {
        $stmt_sub = $db->prepare("
            SELECT s.id as student_id, s.ma_sv, s.ho_ten, s.avatar,
                   sub.id as submission_id, sub.submission_text, sub.file_path, sub.grade, sub.feedback, sub.submitted_at
            FROM students s
            LEFT JOIN submissions sub ON s.id = sub.student_id AND sub.assignment_id = ?
            WHERE s.lop = ?
            ORDER BY s.ho_ten
        ");
        $stmt_sub->bind_param("is", $selected_assignment_id, $assignment_info['lop']);
        $stmt_sub->execute();
        $submissions = $stmt_sub->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản Lý Bài Tập Giáo Viên Đăng - Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
.tab-btn-admin {
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 13.5px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
    color: #a79bb7;
}
.tab-btn-admin:hover {
    background: rgba(168,85,247,0.12);
    color: #f3e8ff;
    border-color: rgba(168,85,247,0.3);
}
.tab-btn-admin.active {
    background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
    color: #ffffff;
    border-color: #a855f7;
    box-shadow: 0 4px 15px rgba(147, 51, 234, 0.35);
}
.assignment-item-card {
    background: rgba(20, 13, 38, 0.7);
    border: 1px solid rgba(168, 85, 247, 0.25);
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
}
.assignment-item-card:hover {
    border-color: rgba(168, 85, 247, 0.5);
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
}
.score-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 800;
    font-size: 12px;
}
.score-high { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); }
.score-mid  { background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); }
.score-low  { background: rgba(244, 63, 94, 0.2); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.35); }

.kpi-row-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.kpi-box {
    background: rgba(26, 17, 48, 0.75);
    border: 1px solid rgba(168, 85, 247, 0.25);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.teacher-tag-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(244, 114, 182, 0.15);
    color: #f472b6;
    border: 1px solid rgba(244, 114, 182, 0.3);
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}
</style>
</head>
<body class="admin-portal">
<?php include '../includes/admin_nav.php'; ?>

<div class="main-content">
  
  <div class="page-header">
    <div>
      <h1 class="page-title" style="display:flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-pen-to-square" style="color: #a855f7;"></i> Quản Lý Bài Tập Giáo Viên Đăng Lên
      </h1>
      <p class="page-sub">Theo dõi toàn bộ bài tập, đề kiểm tra do tất cả Giáo Viên đăng lên, kiểm tra tiến độ nộp bài và chấm điểm toàn trường</p>
    </div>
    <div style="display:flex; gap:10px;">
      <a href="/tkb/admin/tailieu.php" class="btn btn-ghost" style="background:rgba(168,85,247,0.15); color:#c084fc; border:1px solid rgba(168,85,247,0.3);">
        <i class="fa-solid fa-folder-open"></i> Xem Kho Tài Liệu Giáo Viên
      </a>
      <a href="/tkb/admin/diem.php?tab=baitap" class="btn btn-ghost" style="background:rgba(56,189,248,0.15); color:#38bdf8; border:1px solid rgba(56,189,248,0.3);">
        <i class="fa-solid fa-graduation-cap"></i> Bảng Điểm Tổng Hợp
      </a>
    </div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px;">
    <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= htmlspecialchars($msgText) ?>
  </div>
  <?php endif; ?>

  <!-- 4 KPI Summary Cards -->
  <div class="kpi-row-grid">
    <div class="kpi-box">
      <div class="kpi-icon" style="background:rgba(168,85,247,0.15); color:#c084fc;">
        <i class="fa-solid fa-file-signature"></i>
      </div>
      <div>
        <div style="font-size:22px; font-weight:800; color:#fff;"><?= $total_all_assignments ?></div>
        <div style="font-size:12px; color:#a79bb7; font-weight:600;">Tổng Bài Tập GV Đăng</div>
      </div>
    </div>

    <div class="kpi-box">
      <div class="kpi-icon" style="background:rgba(244,114,182,0.15); color:#f472b6;">
        <i class="fa-solid fa-chalkboard-user"></i>
      </div>
      <div>
        <div style="font-size:22px; font-weight:800; color:#fff;"><?= $total_teachers_posted ?></div>
        <div style="font-size:12px; color:#a79bb7; font-weight:600;">Giáo Viên Đã Giao Bài</div>
      </div>
    </div>

    <div class="kpi-box">
      <div class="kpi-icon" style="background:rgba(56,189,248,0.15); color:#38bdf8;">
        <i class="fa-solid fa-cloud-arrow-up"></i>
      </div>
      <div>
        <div style="font-size:22px; font-weight:800; color:#fff;"><?= $total_all_submissions ?></div>
        <div style="font-size:12px; color:#a79bb7; font-weight:600;">Lượt Nộp Bài Của Sinh Viên</div>
      </div>
    </div>

    <div class="kpi-box">
      <div class="kpi-icon" style="background:rgba(16,185,129,0.15); color:#34d399;">
        <i class="fa-solid fa-graduation-cap"></i>
      </div>
      <div>
        <div style="font-size:22px; font-weight:800; color:#fff;"><?= count($assignments) ?></div>
        <div style="font-size:12px; color:#a79bb7; font-weight:600;">Bài Tập Đang Hiển Thị</div>
      </div>
    </div>
  </div>

  <!-- Tabs Header -->
  <div style="display:flex; gap:10px; margin-bottom:20px;">
    <a href="?tab=manage&filter_gv=<?= $filter_gv ?>&filter_mon=<?= $filter_mon ?>&filter_lop=<?= urlencode($filter_lop) ?>" class="tab-btn-admin <?= $tab === 'manage' ? 'active' : '' ?>">
      <i class="fa-solid fa-list-check"></i> Toàn Bộ Bài Tập Giáo Viên Đăng (<?= count($assignments) ?>)
    </a>
    <a href="?tab=grade&assignment_id=<?= $selected_assignment_id ?>" class="tab-btn-admin <?= $tab === 'grade' ? 'active' : '' ?>">
      <i class="fa-solid fa-graduation-cap"></i> Chấm Điểm &amp; Xem Bài Nộp Của Sinh Viên
    </a>
  </div>

  <?php if ($tab === 'manage'): ?>
    
    <!-- Filter Toolbar for Admin to inspect teacher posts -->
    <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 16px; padding: 18px 20px; margin-bottom: 24px;">
      <form method="GET" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 100px; gap: 12px; align-items: end;">
        <input type="hidden" name="tab" value="manage">

        <div>
          <label class="form-label" style="font-size:12px; font-weight:700; color:#c4b5fd;"><i class="fa-solid fa-chalkboard-user"></i> Lọc Theo Giáo Viên:</label>
          <select name="filter_gv" onchange="this.form.submit()" class="form-select" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:9px 12px; font-size:13px;">
            <option value="0">-- Tất cả giáo viên toàn trường --</option>
            <?php foreach ($gvList as $gv): ?>
              <option value="<?= $gv['id'] ?>" <?= ($filter_gv == $gv['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($gv['ho_ten']) ?> (<?= htmlspecialchars($gv['ma_gv'] ?: 'GV') ?> - <?= htmlspecialchars($gv['khoa']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label" style="font-size:12px; font-weight:700; color:#c4b5fd;"><i class="fa-solid fa-book"></i> Lọc Theo Môn Học:</label>
          <select name="filter_mon" onchange="this.form.submit()" class="form-select" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:9px 12px; font-size:13px;">
            <option value="0">-- Tất cả môn học --</option>
            <?php foreach ($monList as $mon): ?>
              <option value="<?= $mon['id'] ?>" <?= ($filter_mon == $mon['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($mon['ten_mon']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label" style="font-size:12px; font-weight:700; color:#c4b5fd;"><i class="fa-solid fa-users"></i> Lọc Theo Lớp:</label>
          <select name="filter_lop" onchange="this.form.submit()" class="form-select" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:9px 12px; font-size:13px;">
            <option value="">-- Tất cả lớp học --</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= htmlspecialchars($c['lop']) ?>" <?= ($filter_lop === $c['lop']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['lop']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label" style="font-size:12px; font-weight:700; color:#c4b5fd;"><i class="fa-solid fa-magnifying-glass"></i> Tìm Kiếm:</label>
          <input type="text" name="search_q" value="<?= htmlspecialchars($search_q) ?>" placeholder="Tên bài tập, đề bài..." class="form-input" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:9px 12px; font-size:13px;">
        </div>

        <div>
          <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:10px; border-radius:10px; font-weight:700; background:#7c3aed; border:none;">
            Lọc
          </button>
        </div>
      </form>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 24px; align-items: start;">
      
      <!-- Left: Create Form (Admin can assign on behalf of any teacher) -->
      <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 16px; padding: 22px;">
        <div style="font-size: 15px; font-weight: 800; color: #f3e8ff; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-folder-plus" style="color: #a855f7;"></i> Tạo &amp; Giao Bài Tập Mới (Quyền Admin)
        </div>

        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="tab" value="manage">

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Chọn Môn Học *</label>
            <select name="mon_hoc_id" class="form-select" required style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
              <option value="">-- Chọn môn học --</option>
              <?php foreach ($monList as $mon): ?>
                <option value="<?= $mon['id'] ?>"><?= htmlspecialchars($mon['ten_mon']) ?> (<?= htmlspecialchars($mon['ma_mon']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Chọn Lớp Nhận Bài *</label>
            <select name="lop" class="form-select" required style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
              <option value="">-- Chọn lớp học --</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= htmlspecialchars($c['lop']) ?>"><?= htmlspecialchars($c['lop']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Giảng Viên Đăng Bài / Người Phụ Trách</label>
            <select name="giang_vien_id" class="form-select" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
              <option value="0">Ban Quản Trị / Admin</option>
              <?php foreach ($gvList as $gv): ?>
                <option value="<?= $gv['id'] ?>"><?= htmlspecialchars($gv['ho_ten']) ?> (<?= htmlspecialchars($gv['ma_gv']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Tiêu Đề Bài Tập *</label>
            <input type="text" name="tieu_de" class="form-input" placeholder="Ví dụ: Bài tập thực hành 1: Thiết kế giao diện" required style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Mô Tả / Đề Bài Chi Tiết</label>
            <textarea name="mo_ta" class="form-input" rows="4" placeholder="Nhập yêu cầu đề bài, quy chế nộp file..." style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;"></textarea>
          </div>

          <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
            <div class="form-group">
              <label class="form-label">Hạn Nộp (Ngày)</label>
              <input type="date" name="han_nop_date" class="form-input" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
            </div>
            <div class="form-group">
              <label class="form-label">Giờ Hết Hạn</label>
              <input type="time" name="han_nop_time" class="form-input" value="23:59" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
            </div>
          </div>

          <div class="form-group" style="margin-bottom:20px;">
            <label class="form-label">Tài Liệu Đính Kèm (PDF, DOCX, ZIP...)</label>
            <input type="file" name="assign_file" class="form-input" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:8px 12px;">
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-weight:800; font-size:14px; background:linear-gradient(135deg, #9333ea, #7c3aed); border:none; border-radius:10px;">
            <i class="fa-solid fa-paper-plane"></i> Giao Bài Tập Mới
          </button>
        </form>
      </div>

      <!-- Right: List of Assignments Posted by Teachers (Admin view) -->
      <div>
        <div style="font-size: 16px; font-weight: 800; color: #f3e8ff; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
          <span><i class="fa-solid fa-list-check" style="color: #38bdf8;"></i> Danh Sách Bài Tập Của Giáo Viên (<?= count($assignments) ?>)</span>
          <?php if ($filter_gv || $filter_mon || $filter_lop || $search_q): ?>
            <a href="?tab=manage" style="font-size:12px; color:#fb7185; text-decoration:none; font-weight:700;"><i class="fa-solid fa-xmark"></i> Xóa bộ lọc</a>
          <?php endif; ?>
        </div>

        <?php if (empty($assignments)): ?>
          <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 16px; padding: 50px 30px; text-align: center; color: #94a3b8;">
            <i class="fa-solid fa-folder-open" style="font-size: 42px; color: #a855f7; margin-bottom: 14px;"></i>
            <div style="font-size: 15px; font-weight: 700; color: #f3e8ff; margin-bottom: 6px;">Không tìm thấy bài tập nào</div>
            <div style="font-size: 13px; color: #a79bb7;">Hiện tại chưa có bài tập nào phù hợp với bộ lọc đã chọn. Bạn có thể chọn giao bài tập mới bên trái!</div>
          </div>
        <?php else: foreach ($assignments as $as): ?>
          <div class="assignment-item-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
              <div>
                <div style="font-size: 16px; font-weight: 800; color: #f3e8ff;"><?= htmlspecialchars($as['tieu_de']) ?></div>
                <div style="font-size: 12.5px; color: #c4b5fd; margin-top: 6px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                  <span class="teacher-tag-badge">
                    <i class="fa-solid fa-chalkboard-user"></i> GV: <?= htmlspecialchars($as['ten_giang_vien']) ?>
                  </span>
                  <span style="color:#38bdf8; font-weight:700; background:rgba(56,189,248,0.12); padding:3px 8px; border-radius:6px; border:1px solid rgba(56,189,248,0.25);">
                    <i class="fa-solid fa-book"></i> <?= htmlspecialchars($as['ten_mon']) ?>
                  </span>
                  <span style="color:#34d399; font-weight:700; background:rgba(16,185,129,0.12); padding:3px 8px; border-radius:6px; border:1px solid rgba(16,185,129,0.25);">
                    <i class="fa-solid fa-users"></i> Lớp <?= htmlspecialchars($as['lop']) ?>
                  </span>
                </div>
              </div>
              <div style="display:flex; gap:6px; align-items:center;">
                <a href="?tab=grade&assignment_id=<?= $as['id'] ?>" class="btn btn-sm" style="background:#7c3aed; color:#fff; border-radius:8px; font-weight:700; font-size:12px; padding:7px 14px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                  <i class="fa-solid fa-graduation-cap"></i> Chấm Điểm (<?= $as['sub_count'] ?>/<?= $as['total_sv'] ?>)
                </a>
                <a href="?action=delete&assignment_id=<?= $as['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa bài tập này của giáo viên? Tất cả bài nộp sẽ bị xóa.')" class="btn btn-sm btn-danger" style="border-radius:8px; padding:7px 10px;" title="Xóa bài tập">
                  <i class="fa-solid fa-trash"></i>
                </a>
              </div>
            </div>

            <?php if (!empty($as['mo_ta'])): ?>
              <div style="font-size: 13px; color: #d8b4fe; margin-top: 12px; line-height: 1.6; background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.04);">
                <?= nl2br(htmlspecialchars($as['mo_ta'])) ?>
              </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; font-size: 12px; color: #a79bb7; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 10px; flex-wrap: wrap; gap: 8px;">
              <div>
                <i class="fa-solid fa-clock"></i> Hạn nộp: <strong style="color:#fb7185;"><?= $as['han_nop'] ? date('d/m/Y H:i', strtotime($as['han_nop'])) : 'Không giới hạn' ?></strong>
                <span style="margin-left: 12px; color:#34d399;"><i class="fa-solid fa-circle-check"></i> Đã chấm: <?= $as['graded_count'] ?>/<?= $as['sub_count'] ?> bài</span>
              </div>
              <?php if (!empty($as['file_path'])): ?>
                <a href="<?= htmlspecialchars($as['file_path']) ?>" target="_blank" style="color: #38bdf8; text-decoration: none; font-weight: 700; display:inline-flex; align-items:center; gap:5px;">
                  <i class="fa-solid fa-paperclip"></i> Tải đề bài đính kèm
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

    </div>
  <?php endif; ?>

  <!-- TAB: CHẤM ĐIỂM BÀI NỘP CỦA SINH VIÊN -->
  <?php if ($tab === 'grade'): ?>
    
    <!-- Select Assignment Dropdown -->
    <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 16px; padding: 18px 22px; margin-bottom: 24px;">
      <form method="GET" style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
        <input type="hidden" name="tab" value="grade">
        <label style="font-weight:700; color:#f3e8ff; font-size:13.5px;"><i class="fa-solid fa-filter"></i> Chọn Bài Tập Của Giáo Viên Cần Xem / Chấm:</label>
        <select name="assignment_id" onchange="this.form.submit()" class="form-select" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:9px 14px; min-width:360px; font-size:13px;">
          <?php foreach ($assignments as $as): ?>
            <option value="<?= $as['id'] ?>" <?= ($selected_assignment_id == $as['id']) ? 'selected' : '' ?>>
              [Lớp <?= htmlspecialchars($as['lop']) ?>] <?= htmlspecialchars($as['tieu_de']) ?> (GV: <?= htmlspecialchars($as['ten_giang_vien']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <?php if ($assignment_info): ?>
      <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 16px; overflow: hidden;">
        <div style="padding: 18px 22px; border-bottom: 1px solid rgba(168,85,247,0.2); display: flex; justify-content: space-between; align-items: center; flex-wrap:wrap; gap:10px;">
          <div>
            <div style="font-size: 17px; font-weight: 800; color: #f3e8ff;"><?= htmlspecialchars($assignment_info['tieu_de']) ?></div>
            <div style="font-size: 12.5px; color: #a79bb7; margin-top: 4px; display:flex; gap:10px; flex-wrap:wrap;">
              <span>Giáo viên đăng: <strong style="color:#f472b6;"><?= htmlspecialchars($assignment_info['ten_giang_vien']) ?></strong></span>
              <span>Lớp: <strong style="color:#38bdf8;"><?= htmlspecialchars($assignment_info['lop']) ?></strong></span>
              <span>Môn: <strong style="color:#c084fc;"><?= htmlspecialchars($assignment_info['ten_mon']) ?></strong></span>
              <span>Hạn nộp: <strong style="color:#fb7185;"><?= $assignment_info['han_nop'] ? date('d/m/Y H:i', strtotime($assignment_info['han_nop'])) : 'Không giới hạn' ?></strong></span>
            </div>
          </div>
          <span style="font-size: 13px; font-weight: 700; color: #34d399; background: rgba(16,185,129,0.15); padding: 7px 16px; border-radius: 20px; border: 1px solid rgba(16,185,129,0.3);">
            Đã nộp: <?= count(array_filter($submissions, function($s){ return !empty($s['submission_id']); })) ?> / <?= count($submissions) ?> sinh viên
          </span>
        </div>

        <div style="overflow-x:auto;">
          <table>
            <thead>
              <tr>
                <th style="width:45px; text-align:center;">#</th>
                <th style="width:130px;">Mã SV</th>
                <th>Sinh viên</th>
                <th style="width:140px; text-align:center;">Trạng Thái Nộp</th>
                <th>Bài Làm / File Nộp</th>
                <th style="width:100px; text-align:center;">Điểm</th>
                <th style="width:120px; text-align:center;">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($submissions)): ?>
                <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:30px;">Không có sinh viên nào trong lớp <?= htmlspecialchars($assignment_info['lop']) ?>.</td></tr>
              <?php else: foreach ($submissions as $i => $sub): 
                $is_submitted = !empty($sub['submission_id']);
                $gr = ($sub['grade'] !== null) ? (float)$sub['grade'] : null;
              ?>
                <tr>
                  <td style="text-align:center; color:#94a3b8; font-weight:600;"><?= $i+1 ?></td>
                  <td><span style="font-family:monospace; font-weight:700; color:#f3e8ff;"><?= htmlspecialchars($sub['ma_sv']) ?></span></td>
                  <td><strong style="color:#f3e8ff;"><?= htmlspecialchars($sub['ho_ten']) ?></strong></td>
                  <td style="text-align:center;">
                    <?php if ($is_submitted): ?>
                      <span style="padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:rgba(16,185,129,0.2); color:#34d399; border:1px solid rgba(16,185,129,0.35);">
                        <i class="fa-solid fa-circle-check"></i> Đã nộp
                      </span>
                      <div style="font-size:10.5px; color:#a79bb7; margin-top:2px;"><?= date('d/m H:i', strtotime($sub['submitted_at'])) ?></div>
                    <?php else: ?>
                      <span style="padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:rgba(244,63,94,0.15); color:#fb7185; border:1px solid rgba(244,63,94,0.3);">
                        <i class="fa-solid fa-circle-xmark"></i> Chưa nộp
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($is_submitted): ?>
                      <?php if (!empty($sub['file_path'])): ?>
                        <div style="margin-bottom:4px;">
                          <a href="<?= htmlspecialchars($sub['file_path']) ?>" target="_blank" style="color:#38bdf8; font-weight:700; text-decoration:none; font-size:12px;">
                            <i class="fa-solid fa-download"></i> Tải file bài nộp
                          </a>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($sub['submission_text'])): ?>
                        <div style="font-size:12px; color:#e9d5ff; background:rgba(0,0,0,0.25); padding:6px 10px; border-radius:6px; max-height:60px; overflow-y:auto;">
                          <?= nl2br(htmlspecialchars($sub['submission_text'])) ?>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($sub['feedback'])): ?>
                        <div style="font-size:11px; color:#c4b5fd; margin-top:3px;"><i class="fa-solid fa-comment-dots"></i> Nhận xét: <?= htmlspecialchars($sub['feedback']) ?></div>
                      <?php endif; ?>
                    <?php else: ?>
                      <span style="color:#64748b; font-size:12px;">-</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center;">
                    <?php if ($gr !== null): ?>
                      <span class="score-badge <?= $gr >= 8 ? 'score-high' : ($gr >= 5 ? 'score-mid' : 'score-low') ?>"><?= $gr ?> đ</span>
                    <?php else: ?>
                      <span class="score-badge" style="background:rgba(255,255,255,0.06); color:#94a3b8;">-</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center;">
                    <button type="button" onclick="openGradeFormModal(<?= $sub['student_id'] ?>, <?= $assignment_info['id'] ?>, '<?= htmlspecialchars(addslashes($sub['ho_ten'])) ?>', <?= $gr !== null ? $gr : "''" ?>, '<?= htmlspecialchars(addslashes($sub['feedback'] ?? '')) ?>')" class="btn btn-sm" style="background:#7c3aed; color:#fff; border-radius:8px; padding:5px 12px; font-weight:700; font-size:11.5px;">
                      <i class="fa-solid fa-pen"></i> Chấm Điểm
                    </button>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</div>

<!-- MODAL CHẤM ĐIỂM BÀI NỘP ADMIN -->
<div class="modal-overlay" id="gradeModal">
  <div class="modal-box" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); border-radius:18px; color:#f3e8ff;">
    <div class="modal-title" style="color:#38bdf8;"><i class="fa-solid fa-graduation-cap"></i> Chấm Điểm Bài Tập (Admin)</div>
    <div class="modal-sub" id="gradeModalMeta">Sinh viên: ...</div>
    <form method="POST">
      <input type="hidden" name="action" value="grade">
      <input type="hidden" name="tab" value="grade">
      <input type="hidden" name="assignment_id" id="gradeAssignId">
      <input type="hidden" name="student_id" id="gradeStudentId">

      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Điểm Số (Thang 10) *</label>
        <input class="form-input" name="grade" id="gradeScoreInput" type="number" step="0.1" min="0" max="10" required placeholder="8.5" style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;">
      </div>

      <div class="form-group">
        <label class="form-label">Lời Nhận Xét / Đánh Giá</label>
        <textarea class="form-input" name="feedback" id="gradeFeedbackInput" rows="3" placeholder="Nhập lời nhận xét cho sinh viên..." style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;"></textarea>
      </div>

      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('gradeModal')" style="color:#a79bb7;">Hủy</button>
        <button type="submit" class="btn btn-primary" style="background:#0284c7; border:none;"><i class="fa-solid fa-floppy-disk"></i> Lưu Điểm Số</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id){
  const m=document.getElementById(id);
  if(m) m.classList.toggle('show');
}

function openGradeFormModal(studentId, assignmentId, studentName, currentGrade, feedback) {
  document.getElementById('gradeStudentId').value = studentId;
  document.getElementById('gradeAssignId').value = assignmentId;
  document.getElementById('gradeModalMeta').innerText = 'Sinh viên: ' + studentName;
  document.getElementById('gradeScoreInput').value = currentGrade;
  document.getElementById('gradeFeedbackInput').value = feedback;
  toggleModal('gradeModal');
}
</script>
</body>
</html>
<?php
$db->close();
?>
