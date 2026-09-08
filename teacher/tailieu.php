<?php
require_once '../config.php';
requireTeacher();

if (isAdmin()) {
    $qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: /tkb/admin/tailieu.php' . $qs);
    exit;
}

$db = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;
$msg = '';

// Auto-migrate: Add video_url to lessons table if not exists
@$db->query("ALTER TABLE `lessons` ADD COLUMN `video_url` VARCHAR(500) NULL DEFAULT NULL AFTER `tieu_de`");

// Helper: Extract YouTube ID
function getYoutubeId($url) {
    if (empty($url)) return '';
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/=\s]{11})%i';
    if (preg_match($pattern, $url, $match)) {
        return $match[1];
    }
    return '';
}

// Helper: Extract first YouTube link from plain text
function extractFirstYoutubeUrl($text) {
    if (empty($text)) return '';
    if (preg_match('~(https?://(?:www\.)?(?:youtube\.com/(?:watch\?v=|embed/|v/|shorts/)|youtu\.be/)[\w\-]{11}[^\s<"]*)~i', $text, $match)) {
        return $match[1];
    }
    return '';
}

$tab = $_GET['tab'] ?? 'docs'; // docs or lessons

// Handle Actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'add_doc') {
    $mid = (int)$_POST['mon_hoc_id'];
    $title = trim($_POST['ten_tai_lieu'] ?? '');
    $link = trim($_POST['link_download'] ?? '');
    $file_url = '';
    
    // File upload support (Primary option)
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
    
    // Check pasted link URL if no file uploaded
    if (empty($file_url) && !empty($link)) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $link) && !str_starts_with($link, '/')) {
            $link = "https://" . $link;
        }
        $file_url = $link;
        if (empty($title)) {
            if (getYoutubeId($link)) {
                $title = "Video bài giảng YouTube";
            } else {
                $title = "Tài liệu học tập";
            }
        }
    }
    
    if ($mid && !empty($title) && !empty($file_url) && !$msg) {
        $stmt = $db->prepare("INSERT INTO tai_lieu (giang_vien_id, mon_hoc_id, ten_tai_lieu, link_download) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $gv_id, $mid, $title, $file_url);
        if ($stmt->execute()) {
            $msg = "success:Đã tải lên & chia sẻ tài liệu/video học tập mới thành công!";
        } else {
            $msg = "error:Lỗi lưu tài liệu: " . $db->error;
        }
    } elseif (!$msg) {
        if (empty($file_url)) {
            $msg = "error:Vui lòng chọn tệp từ máy tính để tải lên hoặc nhập liên kết tài liệu/YouTube.";
        } else {
            $msg = "error:Vui lòng nhập tên tài liệu và chọn môn học.";
        }
    }
} elseif ($action === 'delete_doc') {
    $tid = (int)$_GET['tailieu_id'];
    $stmt = $db->prepare("DELETE FROM tai_lieu WHERE id = ? AND giang_vien_id = ?");
    $stmt->bind_param("ii", $tid, $gv_id);
    if ($stmt->execute()) {
        $msg = "success:Đã xóa tài liệu thành công!";
    }
} elseif ($action === 'add_lesson') {
    $mid = (int)$_POST['mon_hoc_id'];
    $title = trim($_POST['tieu_de'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $content = trim($_POST['noi_dung'] ?? '');
    
    if (!empty($video_url) && !preg_match("~^(?:f|ht)tps?://~i", $video_url)) {
        $video_url = "https://" . $video_url;
    }
    
    if ($mid && $title) {
        $saved = false;
        // Try insert with video_url first
        $stmt = $db->prepare("INSERT INTO lessons (giang_vien_id, mon_hoc_id, tieu_de, video_url, noi_dung) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iisss", $gv_id, $mid, $title, $video_url, $content);
            if ($stmt->execute()) {
                $saved = true;
            }
        }
        
        // Fallback without video_url column if DB structure doesn't support it yet
        if (!$saved) {
            $stmt = $db->prepare("INSERT INTO lessons (giang_vien_id, mon_hoc_id, tieu_de, noi_dung) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iiss", $gv_id, $mid, $title, $content);
                if ($stmt->execute()) {
                    $saved = true;
                }
            }
        }
        
        if ($saved) {
            $msg = "success:Thêm bài học mới thành công!";
        } else {
            $msg = "error:Lỗi thêm bài học: " . $db->error;
        }
    } else {
        $msg = "error:Vui lòng nhập đầy đủ tiêu đề và chọn môn học.";
    }
} elseif ($action === 'delete_lesson') {
    $lid = (int)$_GET['lesson_id'];
    $stmt = $db->prepare("DELETE FROM lessons WHERE id = ? AND giang_vien_id = ?");
    $stmt->bind_param("ii", $lid, $gv_id);
    if ($stmt->execute()) {
        $msg = "success:Đã xóa bài học thành công!";
    } else {
        $msg = "error:Lỗi xóa bài học: " . $db->error;
    }
}

// Fetch teacher's department
$st_t = $db->prepare("SELECT khoa FROM giang_vien WHERE id = ?");
$st_t->bind_param("i", $gv_id);
$st_t->execute();
$teacher_khoa = $st_t->get_result()->fetch_assoc()['khoa'] ?? '';

// Fetch subjects taught by this teacher
$stmt_mon = $db->prepare("
    SELECT DISTINCT m.id, m.ten_mon 
    FROM thoi_khoa_bieu tkb 
    JOIN mon_hoc m ON tkb.mon_hoc_id = m.id 
    WHERE tkb.giang_vien_id = ? 
    ORDER BY m.ten_mon
");
$stmt_mon->bind_param("i", $gv_id);
$stmt_mon->execute();
$monList = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($monList)) {
    if (!empty($teacher_khoa)) {
        $stmt_mon = $db->prepare("SELECT id, ten_mon FROM mon_hoc WHERE LOWER(khoa) = LOWER(?) ORDER BY ten_mon");
        $stmt_mon->bind_param("s", $teacher_khoa);
        $stmt_mon->execute();
        $monList = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    if (empty($monList)) {
        $res = $db->query("SELECT id, ten_mon FROM mon_hoc ORDER BY ten_mon");
        $monList = $res->fetch_all(MYSQLI_ASSOC);
    }
}

$selected_mon_id = (int)($_GET['mon_hoc_id'] ?? ($monList[0]['id'] ?? 0));

// Fetch documents list
$documents = [];
if ($selected_mon_id) {
    $stmt_doc = $db->prepare("SELECT * FROM tai_lieu WHERE giang_vien_id = ? AND mon_hoc_id = ? ORDER BY id DESC");
    $stmt_doc->bind_param("ii", $gv_id, $selected_mon_id);
    $stmt_doc->execute();
    $documents = $stmt_doc->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch lessons list
$lessons = [];
if ($selected_mon_id) {
    $stmt_less = $db->prepare("SELECT * FROM lessons WHERE giang_vien_id = ? AND mon_hoc_id = ? ORDER BY id DESC");
    $stmt_less->bind_param("ii", $gv_id, $selected_mon_id);
    $stmt_less->execute();
    $lessons = $stmt_less->get_result()->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài liệu & Bài học - Giảng viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .form-control {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 15px;
            border-radius: 8px;
            width: 100%;
            font-size: 14px;
            outline: none;
            margin-bottom: 15px;
        }
        .btn-submit {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.15s;
        }
        .btn-submit:hover {
            opacity: 0.9;
        }
        .doc-item {
            background: rgba(255,255,255,0.01);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tab-btn {
            padding: 10px 20px;
            border-bottom: 3px solid transparent;
            font-weight: 700;
            color: var(--text2);
            text-decoration: none;
            font-size: 14.5px;
            transition: 0.15s;
        }
        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }
        .lesson-card {
            background: rgba(255,255,255,0.01);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .video-responsive-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            overflow: hidden;
            border-radius: 10px;
            background: #000;
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        .video-responsive-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .badge-youtube {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            padding: 3px 8px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <?php include '../includes/teacher_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-folder" style="color:var(--accent)"></i> Quản Lý Tài Liệu & Bài Học</h1>
            <p style="color: var(--text2); margin-top: 5px;">Chia sẻ học liệu học tập (Slides, PDF, Video YouTube) và biên soạn bài giảng lý thuyết trực tuyến</p>
        </div>
    </div>

    <!-- Alert message -->
    <?php if ($msg): 
        $parts = explode(':', $msg);
        $type = $parts[0];
        $text = $parts[1];
    ?>
        <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="display: block; margin-bottom: 20px;">
            <?= htmlspecialchars($text) ?>
        </div>
    <?php endif; ?>

    <!-- Filter Header -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; gap: 5px;">
                <a href="?tab=docs&mon_hoc_id=<?= $selected_mon_id ?>" class="tab-btn <?= $tab === 'docs' ? 'active' : '' ?>"><i class="fa-solid fa-file-pdf"></i> Tài liệu học tập &amp; Video</a>
                <a href="?tab=lessons&mon_hoc_id=<?= $selected_mon_id ?>" class="tab-btn <?= $tab === 'lessons' ? 'active' : '' ?>"><i class="fa-solid fa-file-lines"></i> Bài giảng lý thuyết</a>
            </div>
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-weight: 700; font-size: 13.5px;"><i class="fa-solid fa-filter"></i> Học phần:</span>
                <select onchange="window.location.href='?tab=<?= $tab ?>&mon_hoc_id='+this.value" class="search-select" style="margin:0; min-width: 200px;">
                    <?php foreach ($monList as $mon): ?>
                        <option value="<?= $mon['id'] ?>" <?= $selected_mon_id === $mon['id'] ? 'selected' : '' ?>><?= htmlspecialchars($mon['ten_mon']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 30px; align-items: start;">
        <!-- Left: Upload/Write Form -->
        <div class="card">
            <?php if ($tab === 'docs'): ?>
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-cloud-arrow-up"></i> Chia sẻ tài liệu &amp; Video</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="?action=add_doc&tab=docs&mon_hoc_id=<?= $selected_mon_id ?>" enctype="multipart/form-data" id="docUploadForm">
                        <div class="form-group">
                            <label class="form-label">Học phần nhận tài liệu</label>
                            <select name="mon_hoc_id" class="form-control" required>
                                <?php foreach ($monList as $mon): ?>
                                    <option value="<?= $mon['id'] ?>" <?= $selected_mon_id === $mon['id'] ? 'selected' : '' ?>><?= htmlspecialchars($mon['ten_mon']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Dropzone File Upload Area -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700;"><i class="fa-solid fa-cloud-arrow-up" style="color:var(--accent);"></i> Tải tệp lên trực tiếp</label>
                            <div class="dropzone-box" onclick="document.getElementById('docFileInput').click()" id="dropzoneBox" style="border: 2px dashed var(--accent); border-radius: 12px; padding: 22px 15px; text-align: center; background: rgba(217, 27, 67, 0.03); cursor: pointer; transition: all 0.2s ease;">
                                <input type="file" name="doc_file" id="docFileInput" style="display:none;" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.txt,.png,.jpg,.jpeg,.mp4" onchange="handleFileSelected(this)">
                                <div id="dropzoneContent">
                                    <i class="fa-solid fa-folder-open" style="font-size: 36px; color: var(--accent); margin-bottom: 8px; display: block;"></i>
                                    <div style="font-weight: 800; font-size: 14.5px; color: var(--text);">Bấm để chọn tệp tài liệu từ máy tính</div>
                                    <div style="font-size: 11.5px; color: var(--text2); margin-top: 4px;">Hỗ trợ PDF, Word, PowerPoint, Excel, ZIP, RAR, Ảnh...</div>
                                </div>
                                <div id="fileSelectedInfo" style="display:none; text-align:center;">
                                    <i class="fa-solid fa-file-circle-check" style="font-size: 36px; color: #22c55e; margin-bottom: 6px; display: block;"></i>
                                    <div id="selectedFileName" style="font-weight: 800; font-size: 14px; color: var(--text); word-break: break-all;"></div>
                                    <div id="selectedFileSize" style="font-size: 11.5px; color: var(--text2); margin-top: 2px;"></div>
                                    <span style="font-size: 11.5px; color: var(--accent); font-weight: 700; text-decoration: underline; margin-top: 6px; display: inline-block;">Bấm để đổi tệp khác</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tên hiển thị tài liệu / Video</label>
                            <input type="text" name="ten_tai_lieu" id="tenTaiLieuInput" class="form-control" placeholder="Tự động lấy tên file nếu bạn bỏ trống">
                        </div>

                        <!-- Optional Drive or YouTube Link Accordion -->
                        <details style="margin-bottom: 18px; border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; background: rgba(255,255,255,0.01);" open>
                            <summary style="cursor: pointer; font-weight: 700; font-size: 12.5px; color: var(--text2);">
                                <i class="fa-brands fa-youtube" style="color:#ef4444;"></i> Hoặc dán liên kết Video YouTube / Google Drive / URL
                            </summary>
                            <div style="margin-top: 10px;">
                                <input type="text" name="link_download" id="linkDownloadInput" class="form-control" style="margin-bottom: 4px;" placeholder="https://www.youtube.com/watch?v=... hoặc Google Drive..." oninput="handleLinkInput(this.value)">
                                <small style="color: var(--text2); font-size: 11px;">Hỗ trợ dán link YouTube (video, shorts, youtu.be), Google Drive, Web link...</small>
                                <div id="docYtPreview" style="display:none; margin-top: 10px;">
                                    <div class="video-responsive-wrapper" id="docYtFrame"></div>
                                </div>
                            </div>
                        </details>

                        <button type="submit" class="btn-submit" style="width:100%; padding:12px; font-size:14.5px; display:flex; align-items:center; justify-content:center; gap:8px;"><i class="fa-solid fa-cloud-arrow-up"></i> Lưu &amp; Chia Sẻ Học Liệu</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-file-signature"></i> Biên soạn bài giảng mới</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="?action=add_lesson&tab=lessons&mon_hoc_id=<?= $selected_mon_id ?>">
                        <div class="form-group">
                            <label class="form-label">Học phần giảng dạy</label>
                            <select name="mon_hoc_id" class="form-control" required>
                                <?php foreach ($monList as $mon): ?>
                                    <option value="<?= $mon['id'] ?>" <?= $selected_mon_id === $mon['id'] ? 'selected' : '' ?>><?= htmlspecialchars($mon['ten_mon']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tiêu đề bài học</label>
                            <input type="text" name="tieu_de" class="form-control" placeholder="Ví dụ: Bài 1: Tổng quan về ngôn ngữ HTML &amp; CSS" required>
                        </div>

                        <!-- YouTube Video URL Field -->
                        <div class="form-group">
                            <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
                                <span><i class="fa-brands fa-youtube" style="color:#ef4444; font-size:16px;"></i> Link Video YouTube bài giảng (Tùy chọn)</span>
                                <span style="font-size:11px; color:var(--text2); font-weight:normal;">YouTube Watch, Shorts, youtu.be</span>
                            </label>
                            <input type="text" name="video_url" id="lessonVideoUrl" class="form-control" placeholder="https://www.youtube.com/watch?v=... hoặc https://youtu.be/..." oninput="previewLessonYoutube(this.value)">
                            <div id="lessonYtPreview" style="display:none; margin-top: 10px; margin-bottom: 15px;">
                                <div class="video-responsive-wrapper" id="lessonYtFrame"></div>
                                <div style="font-size: 12px; color: #22c55e; font-weight: 700; display: flex; align-items: center; gap: 6px; margin-top: 6px;">
                                    <i class="fa-solid fa-circle-check"></i> Đã nhận diện Video YouTube bài giảng hợp lệ
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nội dung bài giảng lý thuyết</label>
                            <textarea name="noi_dung" class="form-control" rows="8" placeholder="Nhập nội dung bài giảng chi tiết (hướng dẫn, ghi chú, mã nguồn, nội dung lý thuyết)..." required style="resize: vertical;"></textarea>
                        </div>
                        <button type="submit" class="btn-submit" style="width:100%; padding:12px; font-size:14.5px; display:flex; align-items:center; justify-content:center; gap:8px;"><i class="fa-solid fa-plus"></i> Tạo bài giảng</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Items list -->
        <div>
            <?php if ($tab === 'docs'): ?>
                <!-- Tài liệu list -->
                <?php if (empty($documents)): ?>
                    <div class="card" style="padding: 40px; text-align: center; color: var(--text2);">
                        <i class="fa-solid fa-box-open" style="font-size: 32px; color: var(--accent); margin-bottom: 15px; display: block;"></i>
                        Chưa có tài liệu hoặc video nào được chia sẻ cho học phần này.
                    </div>
                <?php else: foreach ($documents as $doc): 
                    $doc_link = $doc['link_download'] ?? '#';
                    if (!empty($doc_link) && $doc_link !== '#' && !preg_match("~^(?:f|ht)tps?://~i", $doc_link) && !str_starts_with($doc_link, '/')) {
                        $doc_link = "https://" . $doc_link;
                    }
                    $yt_id = getYoutubeId($doc_link);
                ?>
                    <div class="doc-item">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <?php if ($yt_id): ?>
                                <div style="background: rgba(239, 68, 68, 0.12); width: 45px; height: 45px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 22px;">
                                    <i class="fa-brands fa-youtube"></i>
                                </div>
                            <?php else: ?>
                                <div style="background: rgba(225, 29, 72, 0.1); width: 45px; height: 45px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 18px;">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <h4 style="font-size: 14.5px; font-weight: 700; color: var(--text); margin:0;"><?= htmlspecialchars($doc['ten_tai_lieu']) ?></h4>
                                    <?php if ($yt_id): ?>
                                        <span class="badge-youtube"><i class="fa-brands fa-youtube"></i> YouTube</span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size: 11.5px; color: var(--text2); display:block; margin-top:2px;">Chia sẻ ngày: <?= date('d/m/Y', strtotime($doc['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <?php if ($yt_id): ?>
                                <button type="button" onclick="openYoutubeVideoModal('<?= $yt_id ?>', '<?= htmlspecialchars($doc['ten_tai_lieu'], ENT_QUOTES, 'UTF-8') ?>')" class="btn-ghost" style="color:#ef4444; border-color:rgba(239,68,68,0.3); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor:pointer;">
                                    <i class="fa-solid fa-play"></i> Xem Video
                                </button>
                                <a href="<?= htmlspecialchars($doc_link) ?>" target="_blank" class="btn-ghost" style="padding: 8px 10px; border-radius: 8px; font-size: 12px; text-decoration: none;" title="Mở trên YouTube">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($doc_link) ?>" target="_blank" class="btn-ghost" style="padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-circle-down"></i> Tải về / Xem
                                </a>
                            <?php endif; ?>
                            <a href="?action=delete_doc&tailieu_id=<?= $doc['id'] ?>&tab=docs&mon_hoc_id=<?= $selected_mon_id ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa mục này?')" class="btn-ghost" style="color:#ef4444; border-color:rgba(239,68,68,0.15); padding: 8px 12px; border-radius: 8px; font-size: 12px; text-decoration: none;">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; endif; ?>

            <?php else: ?>
                <!-- Bài học list -->
                <?php if (empty($lessons)): ?>
                    <div class="card" style="padding: 40px; text-align: center; color: var(--text2);">
                        <i class="fa-solid fa-box-open" style="font-size: 32px; color: var(--accent); margin-bottom: 15px; display: block;"></i>
                        Chưa soạn bài giảng lý thuyết nào cho học phần này.
                    </div>
                <?php else: foreach ($lessons as $less): 
                    $v_url = $less['video_url'] ?? '';
                    $yt_id = getYoutubeId($v_url);
                    if (!$yt_id) {
                        $extracted_url = extractFirstYoutubeUrl($less['noi_dung'] ?? '');
                        $yt_id = getYoutubeId($extracted_url);
                        if ($yt_id && empty($v_url)) {
                            $v_url = $extracted_url;
                        }
                    }
                ?>
                    <div class="lesson-card">
                        <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                            <div>
                                <h3 style="font-size:16px; font-weight:800; color:var(--text); margin-bottom:4px;"><?= htmlspecialchars($less['tieu_de']) ?></h3>
                                <?php if ($yt_id): ?>
                                    <span class="badge-youtube"><i class="fa-brands fa-youtube"></i> Video bài giảng</span>
                                <?php endif; ?>
                            </div>
                            <a href="?action=delete_lesson&lesson_id=<?= $less['id'] ?>&tab=lessons&mon_hoc_id=<?= $selected_mon_id ?>" class="btn-ghost" style="color:#ef4444; border-color:rgba(239,68,68,0.15); padding: 5px 8px; border-radius: 6px; font-size: 11px; text-decoration:none;" onclick="return confirm('Bạn có chắc chắn muốn xóa bài học này?')">
                                <i class="fa-solid fa-trash-can"></i> Xóa
                            </a>
                        </div>
                        <p style="font-size: 13.5px; color: var(--text2); line-height: 1.6; white-space: pre-wrap; margin-bottom:12px; max-height:120px; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical;">
                            <?= htmlspecialchars($less['noi_dung']) ?>
                        </p>
                        <div style="font-size: 11.5px; color: var(--text2); border-top: 1px solid var(--border); padding-top: 10px; display:flex; justify-content:space-between; align-items:center;">
                            <span>Đăng ngày: <?= date('d/m/Y H:i', strtotime($less['created_at'])) ?></span>
                            <button class="btn-ghost" style="padding:5px 12px; border-radius:6px; font-size:12px; font-weight:700;" 
                                data-title="<?= htmlspecialchars($less['tieu_de'], ENT_QUOTES, 'UTF-8') ?>" 
                                data-content="<?= htmlspecialchars($less['noi_dung'], ENT_QUOTES, 'UTF-8') ?>" 
                                data-video="<?= htmlspecialchars($yt_id, ENT_QUOTES, 'UTF-8') ?>" 
                                onclick="showFullLessonModal(this.getAttribute('data-title'), this.getAttribute('data-content'), this.getAttribute('data-video'))">
                                <i class="fa-solid fa-eye"></i> Xem chi tiết <?= $yt_id ? '& Video' : '' ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lesson Viewer Modal -->
    <div class="modal-overlay" id="lessonModal" onclick="if(event.target==this) toggleModal('lessonModal')">
        <div class="modal-box" style="width: 760px; max-width: 95vw;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px; border-bottom: 1px solid rgba(217, 27, 67, 0.2); padding-bottom: 12px;">
                <h3 class="modal-title" id="m_lesson_title" style="margin:0; font-size:17px;">Chi tiết bài học</h3>
                <button onclick="toggleModal('lessonModal')" style="background:none; border:none; color:var(--text2); font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <!-- YouTube Video player container inside Lesson Modal -->
            <div id="m_lesson_video_box" style="display:none; margin-bottom: 16px;">
                <div class="video-responsive-wrapper" id="m_lesson_video_iframe"></div>
            </div>

            <div id="m_lesson_content" style="font-size:14.5px; color:var(--text); line-height:1.75; white-space:pre-wrap; max-height:400px; overflow-y:auto; padding-right:10px;">
            </div>
        </div>
    </div>

    <!-- Standalone Video Player Modal -->
    <div class="modal-overlay" id="videoModal" onclick="if(event.target==this) toggleModal('videoModal')">
        <div class="modal-box" style="width: 760px; max-width: 95vw;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px; border-bottom: 1px solid rgba(239, 68, 68, 0.3); padding-bottom: 12px;">
                <h3 class="modal-title" id="v_modal_title" style="margin:0; font-size:16px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-brands fa-youtube" style="color:#ef4444;"></i> <span>Xem Video Học Liệu</span>
                </h3>
                <button onclick="toggleModal('videoModal')" style="background:none; border:none; color:var(--text2); font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="video-responsive-wrapper" id="v_modal_iframe_box"></div>
        </div>
    </div>

    <script>
        function parseYoutubeId(url) {
            if (!url) return null;
            const regExp = /(?:youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/i;
            const match = url.match(regExp);
            return match ? match[1] : null;
        }

        function handleFileSelected(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                document.getElementById('dropzoneContent').style.display = 'none';
                document.getElementById('fileSelectedInfo').style.display = 'block';
                document.getElementById('selectedFileName').innerText = file.name;
                
                let sizeStr = (file.size / 1024).toFixed(1) + ' KB';
                if (file.size > 1024 * 1024) {
                    sizeStr = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
                }
                document.getElementById('selectedFileSize').innerText = sizeStr;

                const titleInput = document.getElementById('tenTaiLieuInput');
                if (!titleInput.value.trim()) {
                    const lastDot = file.name.lastIndexOf('.');
                    const fileNameWithoutExt = lastDot > 0 ? file.name.substring(0, lastDot) : file.name;
                    titleInput.value = fileNameWithoutExt.replace(/_/g, ' ');
                }
            }
        }

        function handleLinkInput(val) {
            const ytId = parseYoutubeId(val.trim());
            const previewBox = document.getElementById('docYtPreview');
            const frameBox = document.getElementById('docYtFrame');
            const titleInput = document.getElementById('tenTaiLieuInput');

            if (ytId) {
                previewBox.style.display = 'block';
                frameBox.innerHTML = `<iframe src="https://www.youtube.com/embed/${ytId}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
                if (!titleInput.value.trim()) {
                    titleInput.value = 'Video bài giảng YouTube';
                }
            } else {
                previewBox.style.display = 'none';
                frameBox.innerHTML = '';
            }
        }

        function previewLessonYoutube(val) {
            const ytId = parseYoutubeId(val.trim());
            const previewBox = document.getElementById('lessonYtPreview');
            const frameBox = document.getElementById('lessonYtFrame');

            if (ytId) {
                previewBox.style.display = 'block';
                frameBox.innerHTML = `<iframe src="https://www.youtube.com/embed/${ytId}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
            } else {
                previewBox.style.display = 'none';
                frameBox.innerHTML = '';
            }
        }

        function toggleModal(id) {
            const m = document.getElementById(id);
            if (m.style.display === 'flex') {
                m.style.display = 'none';
                // Stop any playing iframes
                if (id === 'lessonModal') {
                    document.getElementById('m_lesson_video_iframe').innerHTML = '';
                }
                if (id === 'videoModal') {
                    document.getElementById('v_modal_iframe_box').innerHTML = '';
                }
            } else {
                m.style.display = 'flex';
            }
        }

        function showFullLessonModal(title, jsonContent, ytId) {
            document.getElementById('m_lesson_title').innerText = title;
            try {
                const content = JSON.parse(jsonContent);
                document.getElementById('m_lesson_content').innerText = content;
            } catch(e) {
                document.getElementById('m_lesson_content').innerText = jsonContent;
            }

            const videoBox = document.getElementById('m_lesson_video_box');
            const videoFrame = document.getElementById('m_lesson_video_iframe');
            
            if (ytId && ytId.trim()) {
                videoBox.style.display = 'block';
                videoFrame.innerHTML = `<iframe src="https://www.youtube.com/embed/${ytId}?rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
            } else {
                videoBox.style.display = 'none';
                videoFrame.innerHTML = '';
            }

            document.getElementById('lessonModal').style.display = 'flex';
        }

        function openYoutubeVideoModal(ytId, title) {
            document.getElementById('v_modal_title').querySelector('span').innerText = title || 'Xem Video Học Liệu';
            document.getElementById('v_modal_iframe_box').innerHTML = `<iframe src="https://www.youtube.com/embed/${ytId}?autoplay=1&rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
            document.getElementById('videoModal').style.display = 'flex';
        }
    </script>
</body>
</html>
