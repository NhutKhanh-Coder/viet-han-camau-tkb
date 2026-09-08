<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

// Helper: Extract YouTube ID
function getYoutubeIdAdmin($url) {
    if (empty($url)) return '';
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/=\s]{11})%i';
    if (preg_match($pattern, $url, $match)) {
        return $match[1];
    }
    return '';
}

$tab = $_GET['tab'] ?? 'docs'; // docs or lessons
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add_doc') {
    $mid = (int)$_POST['mon_hoc_id'];
    $title = trim($_POST['ten_tai_lieu'] ?? '');
    $link = trim($_POST['link_download'] ?? '');
    $gv_selected = (int)($_POST['giang_vien_id'] ?? 0);
    $file_url = '';
    
    // File upload
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/documents/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $original_filename = $_FILES['doc_file']['name'];
        if (empty($title)) {
            $title = pathinfo($original_filename, PATHINFO_FILENAME);
        }
        $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
        $clean_name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($original_filename, PATHINFO_FILENAME));
        $filename = time() . '_' . $clean_name . ($ext ? '.' . $ext : '');
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $target_file)) {
            $file_url = '/tkb/assets/uploads/documents/' . $filename;
        } else {
            $msg = "error:Lỗi không thể lưu tệp tải lên.";
        }
    }
    
    if (empty($file_url) && !empty($link)) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $link) && !str_starts_with($link, '/')) {
            $link = "https://" . $link;
        }
        $file_url = $link;
        if (empty($title)) {
            $title = getYoutubeIdAdmin($link) ? "Video bài giảng YouTube" : "Tài liệu học tập";
        }
    }
    
    if ($mid && !empty($title) && !empty($file_url) && !$msg) {
        $stmt = $db->prepare("INSERT INTO tai_lieu (giang_vien_id, mon_hoc_id, ten_tai_lieu, link_download) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $gv_selected, $mid, $title, $file_url);
        if ($stmt->execute()) {
            $msg = "success:Đã tải lên & lưu tài liệu học tập mới thành công!";
            writeSystemLog("Admin tải lên tài liệu: $title");
        } else {
            $msg = "error:Lỗi lưu tài liệu: " . $db->error;
        }
    } elseif (!$msg) {
        $msg = "error:Vui lòng chọn tệp tải lên hoặc dán link và chọn môn học.";
    }
} elseif ($action === 'delete_doc') {
    $tid = (int)$_GET['tailieu_id'];
    $stmt = $db->prepare("DELETE FROM tai_lieu WHERE id = ?");
    $stmt->bind_param("i", $tid);
    if ($stmt->execute()) {
        $msg = "success:Admin đã xóa tài liệu thành công!";
        writeSystemLog("Admin xóa tài liệu ID $tid");
    }
} elseif ($action === 'delete_lesson') {
    $lid = (int)$_GET['lesson_id'];
    $stmt = $db->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->bind_param("i", $lid);
    if ($stmt->execute()) {
        $msg = "success:Admin đã xóa bài học thành công!";
        writeSystemLog("Admin xóa bài học ID $lid");
    }
}

// Filter params
$filter_gv = (int)($_GET['filter_gv'] ?? 0);
$filter_mon = (int)($_GET['filter_mon'] ?? 0);
$search_q = trim($_GET['search_q'] ?? '');

// Fetch all subjects
$res_m = $db->query("SELECT id, ten_mon, ma_mon FROM mon_hoc ORDER BY ten_mon");
$monList = $res_m ? $res_m->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all teachers
$res_gv = $db->query("SELECT id, ho_ten, ma_gv, khoa FROM giang_vien ORDER BY ho_ten");
$gvList = $res_gv ? $res_gv->fetch_all(MYSQLI_ASSOC) : [];

// Query all documents posted by all teachers
$sql_docs = "
    SELECT t.*, 
           COALESCE(m.ten_mon, 'Tài liệu chung') as ten_mon, 
           COALESCE(g.ho_ten, u.ho_ten, 'Ban Quản Trị / Admin') as ten_giang_vien,
           g.ma_gv
    FROM tai_lieu t
    LEFT JOIN mon_hoc m ON t.mon_hoc_id = m.id
    LEFT JOIN giang_vien g ON t.giang_vien_id = g.id
    LEFT JOIN users u ON t.giang_vien_id = u.id
    WHERE 1=1
";
if ($filter_gv > 0) $sql_docs .= " AND t.giang_vien_id = " . (int)$filter_gv;
if ($filter_mon > 0) $sql_docs .= " AND t.mon_hoc_id = " . (int)$filter_mon;
if (!empty($search_q)) {
    $sq = $db->real_escape_string($search_q);
    $sql_docs .= " AND (t.ten_tai_lieu LIKE '%$sq%' OR g.ho_ten LIKE '%$sq%' OR m.ten_mon LIKE '%$sq%')";
}
$sql_docs .= " ORDER BY t.id DESC";
$res_docs = $db->query($sql_docs);
$documents = $res_docs ? $res_docs->fetch_all(MYSQLI_ASSOC) : [];

// Query all online lessons posted by all teachers
$sql_less = "
    SELECT l.*, 
           COALESCE(m.ten_mon, 'Bài học chung') as ten_mon, 
           COALESCE(g.ho_ten, u.ho_ten, 'Ban Quản Trị / Admin') as ten_giang_vien,
           g.ma_gv
    FROM lessons l
    LEFT JOIN mon_hoc m ON l.mon_hoc_id = m.id
    LEFT JOIN giang_vien g ON l.giang_vien_id = g.id
    LEFT JOIN users u ON l.giang_vien_id = u.id
    WHERE 1=1
";
if ($filter_gv > 0) $sql_less .= " AND l.giang_vien_id = " . (int)$filter_gv;
if ($filter_mon > 0) $sql_less .= " AND l.mon_hoc_id = " . (int)$filter_mon;
if (!empty($search_q)) {
    $sq = $db->real_escape_string($search_q);
    $sql_less .= " AND (l.tieu_de LIKE '%$sq%' OR l.noi_dung LIKE '%$sq%' OR g.ho_ten LIKE '%$sq%')";
}
$sql_less .= " ORDER BY l.id DESC";
$res_less = $db->query($sql_less);
$lessons = $res_less ? $res_less->fetch_all(MYSQLI_ASSOC) : [];

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Kho Tài Liệu &amp; Video Bài Giảng Của Giáo Viên - Admin</title>
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
.doc-card {
    background: rgba(20, 13, 38, 0.7);
    border: 1px solid rgba(168, 85, 247, 0.25);
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
}
.doc-card:hover {
    border-color: rgba(168, 85, 247, 0.5);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}
.badge-youtube {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    color: #fb7185;
    background: rgba(244, 63, 94, 0.15);
    border: 1px solid rgba(244, 63, 94, 0.3);
    padding: 3px 8px;
    border-radius: 6px;
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
        <i class="fa-solid fa-folder-open" style="color: #a855f7;"></i> Quản Lý Tài Liệu &amp; Video Học Liệu Toàn Trường
      </h1>
      <p class="page-sub">Xem toàn bộ sách, bài giảng, slide PDF và video YouTube do tất cả Giáo Viên đăng lên</p>
    </div>
    <div style="display:flex; gap:10px;">
      <a href="/tkb/admin/baitap.php" class="btn btn-ghost" style="background:rgba(56,189,248,0.15); color:#38bdf8; border:1px solid rgba(56,189,248,0.3);">
        <i class="fa-solid fa-pen-to-square"></i> Quản Lý Bài Tập GV Đăng
      </a>
    </div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px;">
    <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= htmlspecialchars($msgText) ?>
  </div>
  <?php endif; ?>

  <!-- Tabs Header -->
  <div style="display:flex; gap:10px; margin-bottom:20px;">
    <a href="?tab=docs&filter_gv=<?= $filter_gv ?>&filter_mon=<?= $filter_mon ?>" class="tab-btn-admin <?= $tab === 'docs' ? 'active' : '' ?>">
      <i class="fa-solid fa-file-lines"></i> Tài Liệu &amp; Video Đã Tải Lên (<?= count($documents) ?>)
    </a>
    <a href="?tab=lessons&filter_gv=<?= $filter_gv ?>&filter_mon=<?= $filter_mon ?>" class="tab-btn-admin <?= $tab === 'lessons' ? 'active' : '' ?>">
      <i class="fa-solid fa-book-open-reader"></i> Bài Giảng Lý Thuyết Của GV (<?= count($lessons) ?>)
    </a>
  </div>

  <!-- Filter Toolbar -->
  <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 16px; padding: 18px 20px; margin-bottom: 24px;">
    <form method="GET" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) 100px; gap: 12px; align-items: end;">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

      <div>
        <label class="form-label" style="font-size:12px; font-weight:700; color:#c4b5fd;"><i class="fa-solid fa-chalkboard-user"></i> Lọc Theo Giáo Viên:</label>
        <select name="filter_gv" onchange="this.form.submit()" class="form-select" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:9px 12px; font-size:13px;">
          <option value="0">-- Tất cả giáo viên toàn trường --</option>
          <?php foreach ($gvList as $gv): ?>
            <option value="<?= $gv['id'] ?>" <?= ($filter_gv == $gv['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($gv['ho_ten']) ?> (<?= htmlspecialchars($gv['ma_gv'] ?: 'GV') ?>)
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
        <label class="form-label" style="font-size:12px; font-weight:700; color:#c4b5fd;"><i class="fa-solid fa-magnifying-glass"></i> Tìm Kiếm:</label>
        <input type="text" name="search_q" value="<?= htmlspecialchars($search_q) ?>" placeholder="Tên tài liệu, bài giảng..." class="form-input" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:9px 12px; font-size:13px;">
      </div>

      <div>
        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:10px; border-radius:10px; font-weight:700; background:#7c3aed; border:none;">
          Lọc
        </button>
      </div>
    </form>
  </div>

  <?php if ($tab === 'docs'): ?>
    <div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 24px; align-items: start;">
      
      <!-- Left: Upload New Document as Admin -->
      <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 16px; padding: 22px;">
        <div style="font-size: 15px; font-weight: 800; color: #f3e8ff; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-cloud-arrow-up" style="color: #a855f7;"></i> Tải Lên / Chia Sẻ Tài Liệu (Admin)
        </div>

        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add_doc">
          <input type="hidden" name="tab" value="docs">

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Chọn Môn Học *</label>
            <select name="mon_hoc_id" class="form-select" required style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
              <option value="">-- Chọn môn học --</option>
              <?php foreach ($monList as $mon): ?>
                <option value="<?= $mon['id'] ?>"><?= htmlspecialchars($mon['ten_mon']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Giáo Viên Sở Hữu / Người Đăng</label>
            <select name="giang_vien_id" class="form-select" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
              <option value="0">Ban Quản Trị / Admin</option>
              <?php foreach ($gvList as $gv): ?>
                <option value="<?= $gv['id'] ?>"><?= htmlspecialchars($gv['ho_ten']) ?> (<?= htmlspecialchars($gv['ma_gv']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Tên Tài Liệu / Video</label>
            <input type="text" name="ten_tai_lieu" class="form-input" placeholder="Ví dụ: Slide bài giảng Chương 1 (PDF / YouTube)" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Chọn Tệp Từ Máy Tính (PDF, DOCX, PPTX, ZIP...)</label>
            <input type="file" name="doc_file" class="form-input" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:8px 12px;">
          </div>

          <div class="form-group" style="margin-bottom:20px;">
            <label class="form-label">Hoặc Dán Liên Kết Download / Link Video YouTube</label>
            <input type="text" name="link_download" class="form-input" placeholder="https://youtube.com/watch?v=... hoặc https://drive.google.com/..." style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:10px; padding:10px 14px;">
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-weight:800; font-size:14px; background:linear-gradient(135deg, #9333ea, #7c3aed); border:none; border-radius:10px;">
            <i class="fa-solid fa-upload"></i> Lưu &amp; Đăng Tài Liệu
          </button>
        </form>
      </div>

      <!-- Right: Documents Posted by All Teachers -->
      <div>
        <div style="font-size: 16px; font-weight: 800; color: #f3e8ff; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
          <span><i class="fa-solid fa-list-check" style="color: #38bdf8;"></i> Danh Sách Tài Liệu Của Giáo Viên (<?= count($documents) ?>)</span>
          <?php if ($filter_gv || $filter_mon || $search_q): ?>
            <a href="?tab=docs" style="font-size:12px; color:#fb7185; text-decoration:none; font-weight:700;"><i class="fa-solid fa-xmark"></i> Xóa lọc</a>
          <?php endif; ?>
        </div>

        <?php if (empty($documents)): ?>
          <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 16px; padding: 50px 30px; text-align: center; color: #94a3b8;">
            <i class="fa-solid fa-folder-open" style="font-size: 42px; color: #a855f7; margin-bottom: 14px;"></i>
            <div style="font-size: 15px; font-weight: 700; color: #f3e8ff; margin-bottom: 6px;">Chưa có tài liệu nào</div>
            <div style="font-size: 13px; color: #a79bb7;">Hiện chưa có giáo viên nào đăng tài liệu cho bộ lọc này.</div>
          </div>
        <?php else: foreach ($documents as $d): 
          $yt_id = getYoutubeIdAdmin($d['link_download']);
        ?>
          <div class="doc-card">
            <div>
              <div style="font-size: 15.5px; font-weight: 800; color: #f3e8ff; display: flex; align-items: center; gap: 8px;">
                <?php if ($yt_id): ?>
                  <i class="fa-brands fa-youtube" style="color: #f43f5e; font-size: 18px;"></i>
                <?php else: ?>
                  <i class="fa-solid fa-file-lines" style="color: #38bdf8; font-size: 16px;"></i>
                <?php endif; ?>
                <?= htmlspecialchars($d['ten_tai_lieu']) ?>
                <?php if ($yt_id): ?>
                  <span class="badge-youtube"><i class="fa-brands fa-youtube"></i> Video</span>
                <?php endif; ?>
              </div>

              <div style="font-size: 12px; color: #c4b5fd; margin-top: 6px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <span class="teacher-tag-badge">
                  <i class="fa-solid fa-chalkboard-user"></i> GV: <?= htmlspecialchars($d['ten_giang_vien']) ?>
                </span>
                <span style="color:#38bdf8; font-weight:700; background:rgba(56,189,248,0.12); padding:3px 8px; border-radius:6px;">
                  <i class="fa-solid fa-book"></i> <?= htmlspecialchars($d['ten_mon']) ?>
                </span>
                <span style="color:#94a3b8;"><i class="fa-solid fa-calendar-day"></i> <?= date('d/m/Y', strtotime($d['created_at'])) ?></span>
              </div>
            </div>

            <div style="display: flex; gap: 8px; align-items: center;">
              <a href="<?= htmlspecialchars($d['link_download']) ?>" target="_blank" class="btn btn-sm" style="background:#7c3aed; color:#fff; border-radius:8px; font-weight:700; font-size:12px; padding:7px 14px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <i class="fa-solid <?= $yt_id ? 'fa-play' : 'fa-download' ?>"></i> <?= $yt_id ? 'Xem Video' : 'Tải Về' ?>
              </a>
              <a href="?action=delete_doc&tailieu_id=<?= $d['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa tài liệu này?')" class="btn btn-sm btn-danger" style="border-radius:8px; padding:7px 10px;" title="Xóa tài liệu">
                <i class="fa-solid fa-trash"></i>
              </a>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

    </div>
  <?php endif; ?>

  <?php if ($tab === 'lessons'): ?>
    <div>
      <div style="font-size: 16px; font-weight: 800; color: #f3e8ff; margin-bottom: 16px;">
        <i class="fa-solid fa-book-open-reader" style="color: #38bdf8;"></i> Danh Sách Bài Giảng Lý Thuyết Do Giáo Viên Đăng (<?= count($lessons) ?>)
      </div>

      <?php if (empty($lessons)): ?>
        <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 16px; padding: 50px 30px; text-align: center; color: #94a3b8;">
          <i class="fa-solid fa-book-open" style="font-size: 42px; color: #a855f7; margin-bottom: 14px;"></i>
          <div style="font-size: 15px; font-weight: 700; color: #f3e8ff; margin-bottom: 6px;">Chưa có bài giảng lý thuyết nào</div>
        </div>
      <?php else: foreach ($lessons as $ls): 
        $yt_id = getYoutubeIdAdmin($ls['video_url'] ?? '');
      ?>
        <div class="card" style="background: rgba(20, 13, 38, 0.7); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 14px; padding: 20px; margin-bottom: 16px;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
            <div>
              <div style="font-size: 16px; font-weight: 800; color: #f3e8ff;"><?= htmlspecialchars($ls['tieu_de']) ?></div>
              <div style="font-size: 12.5px; color: #c4b5fd; margin-top: 6px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <span class="teacher-tag-badge">
                  <i class="fa-solid fa-chalkboard-user"></i> GV: <?= htmlspecialchars($ls['ten_giang_vien']) ?>
                </span>
                <span style="color:#38bdf8; font-weight:700; background:rgba(56,189,248,0.12); padding:3px 8px; border-radius:6px;">
                  <i class="fa-solid fa-book"></i> <?= htmlspecialchars($ls['ten_mon']) ?>
                </span>
              </div>
            </div>
            <a href="?action=delete_lesson&lesson_id=<?= $ls['id'] ?>&tab=lessons" onclick="return confirm('Bạn có chắc chắn muốn xóa bài giảng này?')" class="btn btn-sm btn-danger" style="border-radius:8px; padding:7px 10px;">
              <i class="fa-solid fa-trash"></i>
            </a>
          </div>

          <?php if (!empty($ls['noi_dung'])): ?>
            <div style="font-size: 13px; color: #d8b4fe; margin-top: 12px; line-height: 1.6; background: rgba(0,0,0,0.3); padding: 12px 16px; border-radius: 8px;">
              <?= nl2br(htmlspecialchars($ls['noi_dung'])) ?>
            </div>
          <?php endif; ?>

          <?php if ($yt_id): ?>
            <div style="margin-top: 14px; max-width: 640px;">
              <iframe width="100%" height="340" src="https://www.youtube.com/embed/<?= $yt_id ?>" frameborder="0" allowfullscreen style="border-radius: 10px;"></iframe>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  <?php endif; ?>

</div>

</body>
</html>
<?php
$db->close();
?>
