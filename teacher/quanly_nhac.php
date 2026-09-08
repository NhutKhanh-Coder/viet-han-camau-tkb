<?php
require_once '../config.php';
requireTeacher();

$db = getDB();

// Tự động tạo hoặc nâng cấp bảng music_library nếu chưa có cột media_type / cover_image
$db->query("
CREATE TABLE IF NOT EXISTS `music_library` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `artist_or_description` VARCHAR(255) DEFAULT NULL,
  `type` ENUM('file', 'youtube') NOT NULL DEFAULT 'youtube',
  `file_path` VARCHAR(255) DEFAULT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `youtube_url` VARCHAR(255) DEFAULT NULL,
  `youtube_id` VARCHAR(50) DEFAULT NULL,
  `genre` VARCHAR(50) DEFAULT 'lofi',
  `media_type` VARCHAR(20) DEFAULT 'audio',
  `teacher_id` INT DEFAULT 0,
  `teacher_name` VARCHAR(100) DEFAULT 'Giảng viên',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Kiểm tra xem các cột đã có chưa
$checkCol = $db->query("SHOW COLUMNS FROM music_library LIKE 'media_type'");
if ($checkCol->num_rows == 0) {
    $db->query("ALTER TABLE music_library ADD COLUMN `media_type` VARCHAR(20) DEFAULT 'audio' AFTER `genre`");
}
$checkCover = $db->query("SHOW COLUMNS FROM music_library LIKE 'cover_image'");
if ($checkCover->num_rows == 0) {
    $db->query("ALTER TABLE music_library ADD COLUMN `cover_image` VARCHAR(255) DEFAULT NULL AFTER `file_path`");
}

$teacher_uid = $_SESSION['user_id'] ?? 0;
$teacher_gv_id = $_SESSION['giang_vien_id'] ?? 0;
$teacher_name = $_SESSION['ho_ten'] ?? 'Giảng viên';

$msg = '';
$error = '';

// Hàm tách lấy YouTube Video ID từ link
function extractYouTubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return '';
}

// Xử lý XÓA
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM music_library WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    
    if ($item) {
        if ($item['type'] === 'file' && !empty($item['file_path'])) {
            $filepath = __DIR__ . '/../' . ltrim($item['file_path'], '/');
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }
        if (!empty($item['cover_image']) && strpos($item['cover_image'], 'http') !== 0) {
            $coverpath = __DIR__ . '/../' . ltrim($item['cover_image'], '/');
            if (file_exists($coverpath)) {
                @unlink($coverpath);
            }
        }
        $delStmt = $db->prepare("DELETE FROM music_library WHERE id = ?");
        $delStmt->bind_param("i", $del_id);
        $delStmt->execute();
        $msg = "Đã xóa bài hát/phim thành công!";
    }
}

// Xử lý THÊM MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_music') {
    $title = trim($_POST['title'] ?? '');
    $artist = trim($_POST['artist'] ?? '');
    $genre = trim($_POST['genre'] ?? 'lofi');
    $media_type = trim($_POST['media_type'] ?? 'audio'); // 'audio' hoặc 'video'
    $type = trim($_POST['type'] ?? 'youtube');
    $cover_image = trim($_POST['cover_image_url'] ?? '');
    
    // Tải ảnh bìa lên nếu có file đính kèm
    if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
        $cTmp = $_FILES['cover_file']['tmp_name'];
        $cName = $_FILES['cover_file']['name'];
        $cExt = strtolower(pathinfo($cName, PATHINFO_EXTENSION));
        if (in_array($cExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $coverDir = __DIR__ . '/../assets/uploads/covers/';
            if (!is_dir($coverDir)) @mkdir($coverDir, 0777, true);
            $newCoverName = 'cover_' . time() . '_' . rand(100, 999) . '.' . $cExt;
            if (move_uploaded_file($cTmp, $coverDir . $newCoverName)) {
                $cover_image = 'assets/uploads/covers/' . $newCoverName;
            }
        }
    }

    if (empty($title)) {
        $error = "Vui lòng nhập tên bài hát / phim!";
    } else {
        if ($type === 'youtube') {
            $yt_url = trim($_POST['youtube_url'] ?? '');
            $yt_id = extractYouTubeId($yt_url);
            
            if (empty($yt_id)) {
                $error = "Đường dẫn YouTube không hợp lệ! Vui lòng dán đúng đường dẫn dạng https://www.youtube.com/watch?v=... hoặc https://youtu.be/...";
            } else {
                $stmt = $db->prepare("INSERT INTO music_library (title, artist_or_description, type, youtube_url, youtube_id, genre, media_type, cover_image, teacher_id, teacher_name) VALUES (?, ?, 'youtube', ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssis", $title, $artist, $yt_url, $yt_id, $genre, $media_type, $cover_image, $teacher_uid, $teacher_name);
                if ($stmt->execute()) {
                    $msg = "Đã thêm đường dẫn YouTube vào thư viện thành công!";
                } else {
                    $error = "Lỗi lưu cơ sở dữ liệu: " . $db->error;
                }
            }
        } else if ($type === 'file') {
            if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['media_file']['tmp_name'];
                $fileName = $_FILES['media_file']['name'];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                $audioAllowed = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
                $videoAllowed = ['mp4', 'webm', 'ogg', 'mkv', 'mov', 'avi'];
                $allowed = array_merge($audioAllowed, $videoAllowed);
                
                if (!in_array($ext, $allowed)) {
                    $error = "Định dạng file không hỗ trợ! Chấp nhận audio (mp3, wav, m4a...) hoặc video (mp4, webm...).";
                } else {
                    if (in_array($ext, $videoAllowed)) {
                        $media_type = 'video';
                    } else {
                        $media_type = 'audio';
                    }

                    $uploadDir = __DIR__ . '/../assets/uploads/media/';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0777, true);
                    }
                    
                    $newFileName = 'media_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $destPath = $uploadDir . $newFileName;
                    $dbRelPath = 'assets/uploads/media/' . $newFileName;
                    
                    if (move_uploaded_file($fileTmp, $destPath)) {
                        $stmt = $db->prepare("INSERT INTO music_library (title, artist_or_description, type, file_path, genre, media_type, cover_image, teacher_id, teacher_name) VALUES (?, ?, 'file', ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssis", $title, $artist, $dbRelPath, $genre, $media_type, $cover_image, $teacher_uid, $teacher_name);
                        if ($stmt->execute()) {
                            $msg = "Đã tải lên file " . ($media_type === 'video' ? 'phim/video' : 'nhạc') . " thành công!";
                        } else {
                            $error = "Lỗi lưu DB: " . $db->error;
                        }
                    } else {
                        $error = "Không thể lưu file lên server!";
                    }
                }
            } else {
                $error = "Vui lòng chọn file audio hoặc video từ máy tính!";
            }
        }
    }
}

// Lấy danh sách nhạc & phim
$musicList = [];
$res_mus = $db->query("SELECT * FROM music_library ORDER BY id DESC");
if ($res_mus) {
    if (method_exists($res_mus, 'fetch_all')) {
        $musicList = $res_mus->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($r = $res_mus->fetch_assoc()) {
            $musicList[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Nhạc & Phim — Giảng Viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .qn-container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .qn-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .qn-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        .qn-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .qn-title i {
            color: #e11d48;
        }
        .qn-form-group {
            margin-bottom: 16px;
        }
        .qn-label {
            display: block;
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }
        .qn-input, .qn-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        .qn-input:focus, .qn-select:focus {
            border-color: #e11d48;
            box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.1);
        }
        .qn-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .qn-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .qn-btn-primary {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #fff;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
        }
        .qn-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(225, 29, 72, 0.35);
        }
        .qn-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        .qn-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-weight: 700;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        .qn-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }
        .qn-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-yt { background: #fee2e2; color: #dc2626; }
        .badge-file { background: #dbeafe; color: #1d4ed8; }
        .badge-genre { background: #f1f5f9; color: #475569; }
        .badge-video { background: #fef3c7; color: #b45309; }
        .badge-audio { background: #e0e7ff; color: #4338ca; }
        .qn-alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .qn-alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .qn-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .tab-type-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-type-btn.active {
            background: #e11d48;
            color: #fff;
            border-color: #e11d48;
        }
    </style>
</head>
<body>
    <?php include '../includes/teacher_nav.php'; ?>

<div class="qn-container">
    
    <div class="qn-card">
        <div class="qn-header">
            <div class="qn-title">
                <i class="fa-solid fa-film"></i>
                <span>Quản Lý Nhạc & Phim Đăng Cho Sinh Viên</span>
            </div>
            <span style="font-size: 13px; color: #64748b;">
                <i class="fa-solid fa-graduation-cap"></i> Tự động hiển thị hình ảnh bìa mượt mà cho sinh viên
            </span>
        </div>

        <?php if ($msg): ?>
            <div class="qn-alert qn-alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="qn-alert qn-alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_music">
            <input type="hidden" name="type" id="typeInput" value="youtube">

            <div style="display:flex; gap:10px; margin-bottom: 18px;">
                <button type="button" class="tab-type-btn active" id="tabYtBtn" onclick="switchType('youtube')">
                    <i class="fa-brands fa-youtube" style="color:#ef4444;"></i> Nhập Link YouTube (Phim / Nhạc)
                </button>
                <button type="button" class="tab-type-btn" id="tabFileBtn" onclick="switchType('file')">
                    <i class="fa-solid fa-upload" style="color:#3b82f6;"></i> Upload File (MP3, MP4, WEBM)
                </button>
            </div>

            <div class="qn-grid-2">
                <div class="qn-form-group">
                    <label class="qn-label">Tên bài hát / Tiêu đề Phim <span style="color:red;">*</span></label>
                    <input type="text" name="title" class="qn-input" placeholder="Ví dụ: Phim Tài Liệu Lịch Sử, Nhạc Lofi Học Bài..." required>
                </div>

                <div class="qn-form-group">
                    <label class="qn-label">Tác giả / Mô tả thêm</label>
                    <input type="text" name="artist" class="qn-input" placeholder="Ví dụ: Thầy An chia sẻ, Phim truyền cảm hứng...">
                </div>
            </div>

            <div class="qn-grid-2">
                <div class="qn-form-group">
                    <label class="qn-label">Phân loại Media</label>
                    <select name="media_type" class="qn-select">
                        <option value="audio">🎵 Âm nhạc (Audio)</option>
                        <option value="video">🎬 Phim / Video</option>
                    </select>
                </div>

                <div class="qn-form-group">
                    <label class="qn-label">Thể loại</label>
                    <select name="genre" class="qn-select">
                        <option value="lofi">🎧 Lofi / Thư giãn</option>
                        <option value="film">🎬 Phim Giải Trí / Tài Liệu</option>
                        <option value="vpop">🇻🇳 V-Pop / Nhạc Việt</option>
                        <option value="classical">🎻 Cổ điển (Classical)</option>
                        <option value="acoustic">🎸 Acoustic</option>
                        <option value="lecture">📚 Video Bài Giảng</option>
                    </select>
                </div>
            </div>

            <div class="qn-grid-2">
                <!-- Input tùy thuộc vào Type -->
                <div class="qn-form-group" id="groupYoutube">
                    <label class="qn-label">Đường dẫn YouTube URL <span style="color:red;">*</span></label>
                    <input type="url" name="youtube_url" id="ytUrlInput" class="qn-input" placeholder="https://www.youtube.com/watch?v=... hoặc https://youtu.be/...">
                </div>

                <div class="qn-form-group" id="groupFile" style="display:none;">
                    <label class="qn-label">Chọn File (Audio MP3/WAV hoặc Video MP4/WEBM) <span style="color:red;">*</span></label>
                    <input type="file" name="media_file" id="mediaFileInput" class="qn-input" accept="audio/*,video/*">
                </div>

                <div class="qn-form-group">
                    <label class="qn-label">Ảnh bìa / Thumbnail (Tùy chọn)</label>
                    <input type="file" name="cover_file" class="qn-input" accept="image/*">
                </div>
            </div>

            <button type="submit" class="qn-btn qn-btn-primary">
                <i class="fa-solid fa-plus"></i> Thêm Nhạc / Phim Cho Sinh Viên
            </button>
        </form>
    </div>

    <!-- Danh sách nhạc & phim đã thêm -->
    <div class="qn-card">
        <div class="qn-header">
            <div class="qn-title">
                <i class="fa-solid fa-list-check"></i>
                <span>Danh Sách Nhạc & Phim Đã Đăng</span>
            </div>
            <span class="qn-badge badge-genre">Tổng số: <?= count($musicList) ?> mục</span>
        </div>

        <?php if (empty($musicList)): ?>
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-film" style="font-size: 40px; margin-bottom: 12px; opacity: 0.5;"></i>
                <p style="margin: 0; font-size: 14px;">Chưa có bài hát hoặc phim nào được thêm.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="qn-table">
                    <thead>
                        <tr>
                            <th>Ảnh bìa</th>
                            <th>Loại</th>
                            <th>Định dạng</th>
                            <th>Tiêu đề / Mô tả</th>
                            <th>Thể loại</th>
                            <th>Người đăng</th>
                            <th>Phát thử</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($musicList as $item): 
                            $thumbUrl = '';
                            if (!empty($item['cover_image'])) {
                                $thumbUrl = (strpos($item['cover_image'], 'http') === 0) ? $item['cover_image'] : ('/tkb/' . ltrim($item['cover_image'], '/'));
                            } elseif ($item['type'] === 'youtube' && !empty($item['youtube_id'])) {
                                $thumbUrl = 'https://img.youtube.com/vi/' . $item['youtube_id'] . '/hqdefault.jpg';
                            }
                        ?>
                            <tr>
                                <td>
                                    <?php if ($thumbUrl): ?>
                                        <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="Cover" style="width: 54px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">
                                    <?php else: ?>
                                        <div style="width: 54px; height: 38px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                                            <i class="fa-solid <?= ($item['media_type'] ?? 'audio') === 'video' ? 'fa-video' : 'fa-music' ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($item['media_type'] ?? 'audio') === 'video'): ?>
                                        <span class="qn-badge badge-video"><i class="fa-solid fa-video"></i> Phim / Video</span>
                                    <?php else: ?>
                                        <span class="qn-badge badge-audio"><i class="fa-solid fa-music"></i> Nhạc / Audio</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['type'] === 'youtube'): ?>
                                        <span class="qn-badge badge-yt"><i class="fa-brands fa-youtube"></i> YouTube</span>
                                    <?php else: ?>
                                        <span class="qn-badge badge-file"><i class="fa-solid fa-file"></i> File Upload</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($item['title']) ?></strong>
                                    <?php if (!empty($item['artist_or_description'])): ?>
                                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                            <?= htmlspecialchars($item['artist_or_description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="qn-badge badge-genre">
                                        <?= strtoupper(htmlspecialchars($item['genre'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 12.5px; color: #475569; font-weight: 600;">
                                        <?= htmlspecialchars($item['teacher_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($item['type'] === 'youtube'): ?>
                                        <a href="<?= htmlspecialchars($item['youtube_url']) ?>" target="_blank" class="qn-btn" style="padding: 4px 10px; font-size: 12px; background: #fee2e2; color: #dc2626;">
                                            <i class="fa-brands fa-youtube"></i> Xem
                                        </a>
                                    <?php else: ?>
                                        <?php if (($item['media_type'] ?? 'audio') === 'video'): ?>
                                            <a href="/tkb/<?= htmlspecialchars($item['file_path']) ?>" target="_blank" class="qn-btn" style="padding: 4px 10px; font-size: 12px; background: #dbeafe; color: #1d4ed8;">
                                                <i class="fa-solid fa-play"></i> Xem video
                                            </a>
                                        <?php else: ?>
                                            <audio controls style="height: 30px; max-width: 180px;">
                                                <source src="/tkb/<?= htmlspecialchars($item['file_path']) ?>">
                                            </audio>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="?action=delete&id=<?= $item['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa bài hát/phim này?');" class="qn-btn" style="padding: 6px 12px; font-size: 12px; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca;">
                                        <i class="fa-solid fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function switchType(type) {
    document.getElementById('typeInput').value = type;
    const tabYt = document.getElementById('tabYtBtn');
    const tabFile = document.getElementById('tabFileBtn');
    const groupYt = document.getElementById('groupYoutube');
    const groupFile = document.getElementById('groupFile');
    const ytInput = document.getElementById('ytUrlInput');
    const fileInput = document.getElementById('mediaFileInput');

    if (type === 'youtube') {
        tabYt.classList.add('active');
        tabFile.classList.remove('active');
        groupYt.style.display = 'block';
        groupFile.style.display = 'none';
        ytInput.required = true;
        fileInput.required = false;
    } else {
        tabFile.classList.add('active');
        tabYt.classList.remove('active');
        groupFile.style.display = 'block';
        groupYt.style.display = 'none';
        fileInput.required = true;
        ytInput.required = false;
    }
}
</script>

</body>
</html>
