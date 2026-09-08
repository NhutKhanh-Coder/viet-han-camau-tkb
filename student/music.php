<?php
require_once __DIR__ . '/../config.php';
requireStudent();

$db = getDB();

// Tự động tạo hoặc nâng cấp bảng music_library
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
  `views` INT DEFAULT 128,
  `likes` INT DEFAULT 15,
  `teacher_id` INT DEFAULT 0,
  `teacher_name` VARCHAR(100) DEFAULT 'Giảng viên',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// AJAX HÀM TÌM KIẾM YOUTUBE VỚI TRANG KẾT QUẢ VÔ TẬN (INFINITE YOUTUBE PAGINATION)
if (isset($_GET['action']) && $_GET['action'] === 'yt_search' && isset($_GET['q'])) {
    header('Content-Type: application/json; charset=utf-8');
    $query = trim($_GET['q']);
    $page = max(1, (int)($_GET['page'] ?? 1));

    if (empty($query)) {
        echo json_encode([]);
        exit;
    }

    // Biến tấu từ khóa theo trang để lấy hàng trăm video như YouTube
    $actualQuery = $query;
    if ($page > 1) {
        $suffixes = [' mới nhất', ' full hd', ' trọn bộ', ' playlist', ' tập mới', ' hay nhất'];
        $sufIndex = ($page - 2) % count($suffixes);
        $actualQuery .= $suffixes[$sufIndex];
    }

    $url = "https://www.youtube.com/results?search_query=" . urlencode($actualQuery);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    $html = curl_exec($ch);
    curl_close($ch);

    $results = [];
    $pos = strpos($html, 'ytInitialData = ');
    if ($pos !== false) {
        $start = $pos + strlen('ytInitialData = ');
        $end = strpos($html, ';</script>', $start);
        if ($end !== false) {
            $jsonStr = substr($html, $start, $end - $start);
            $data = json_decode($jsonStr, true);
            $sections = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];

            foreach ($sections as $sec) {
                $items = $sec['itemSectionRenderer']['contents'] ?? [];
                foreach ($items as $item) {
                    if (isset($item['videoRenderer'])) {
                        $v = $item['videoRenderer'];
                        $videoId = $v['videoId'] ?? '';
                        $title = $v['title']['runs'][0]['text'] ?? '';
                        $channel = $v['ownerText']['runs'][0]['text'] ?? 'YouTube Channel';
                        $views = $v['viewCountText']['simpleText'] ?? ($v['shortViewCountText']['simpleText'] ?? 'Xem trực tiếp');
                        $thumbnail = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
                        $avatar = $v['channelThumbnailSupportedRenderers']['channelThumbnailWithLinkRenderer']['thumbnail']['thumbnails'][0]['url'] ?? '';

                        if ($videoId && $title) {
                            $results[] = [
                                'id' => $videoId,
                                'title' => $title,
                                'channel' => $channel,
                                'avatar' => $avatar,
                                'views' => $views,
                                'thumbnail' => $thumbnail
                            ];
                        }
                    }
                    if (count($results) >= 40) break 2;
                }
            }
        }
    }
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
    exit;
}

// Tăng lượt xem cho nhạc/phim do giảng viên chia sẻ
if (isset($_POST['action']) && $_POST['action'] === 'inc_view' && isset($_POST['id'])) {
    $vId = (int)$_POST['id'];
    $db->query("UPDATE music_library SET views = views + 1 WHERE id = $vId");
    echo json_encode(['success' => true]);
    exit;
}

$teacherMedia = $db->query("SELECT * FROM music_library ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/student_nav.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VKC Tube — Xem Video Vô Tận Như YouTube</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* =========================================================
   VKC TUBE – Unlimited YouTube Search Engine & Cinema Player
   ========================================================= */

:root {
    --yt-bg: #0f0f0f;
    --yt-card-bg: #1f1f1f;
    --yt-hover-bg: #272727;
    --yt-border: #3f3f3f;
    --yt-red: #ff0000;
    --yt-text: #f1f1f1;
    --yt-text-muted: #aaa;
    --yt-chip-bg: rgba(255,255,255,0.1);
    --yt-chip-active: #ffffff;
    --yt-chip-active-text: #0f0f0f;
}

.yt-container {
    background: var(--yt-bg);
    min-height: 100vh;
    color: var(--yt-text);
    padding: 16px 20px 60px;
    font-family: 'Outfit', -apple-system, Roboto, sans-serif;
    border-radius: 16px;
}

/* --- Top Header Bar --- */
.yt-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.yt-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    cursor: pointer;
}

.yt-brand-icon {
    width: 38px; height: 38px;
    background: var(--yt-red);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px;
    box-shadow: 0 4px 14px rgba(255, 0, 0, 0.4);
}

.yt-brand-title {
    font-size: 22px;
    font-weight: 900;
    color: #fff;
    letter-spacing: -0.5px;
}

.yt-brand-title span { color: var(--yt-red); }

.yt-search-box {
    flex: 1;
    max-width: 600px;
    display: flex;
    align-items: center;
    background: #121212;
    border: 1px solid var(--yt-border);
    border-radius: 50px;
    overflow: hidden;
    transition: border-color 0.2s;
}

.yt-search-box:focus-within {
    border-color: #3ea6ff;
    box-shadow: 0 0 0 1px #3ea6ff;
}

.yt-search-input {
    flex: 1;
    background: transparent;
    border: none;
    padding: 10px 18px;
    color: #fff;
    font-size: 15px;
    outline: none;
}

.yt-search-btn {
    padding: 10px 22px;
    background: rgba(255,255,255,0.08);
    border: none;
    border-left: 1px solid var(--yt-border);
    color: #fff;
    font-size: 15px;
    cursor: pointer;
    transition: background 0.2s;
}

.yt-search-btn:hover { background: rgba(255,255,255,0.16); }

/* --- Quick URL Paste Box --- */
.yt-paste-box {
    background: linear-gradient(135deg, rgba(255,0,0,0.15), rgba(255,0,0,0.05));
    border: 1px dashed rgba(255,0,0,0.4);
    border-radius: 14px;
    padding: 12px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.yt-paste-input {
    flex: 1;
    background: rgba(0,0,0,0.4);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 8px 12px;
    color: #fff;
    font-size: 13.5px;
    outline: none;
}

.yt-paste-btn {
    padding: 8px 16px;
    background: var(--yt-red);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
}

/* --- Category Chips Bar --- */
.yt-chips-bar {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 12px;
    margin-bottom: 24px;
    scrollbar-width: none;
}

.yt-chips-bar::-webkit-scrollbar { display: none; }

.yt-chip {
    padding: 8px 16px;
    background: var(--yt-chip-bg);
    color: var(--yt-text);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    border: none;
}

.yt-chip:hover { background: rgba(255,255,255,0.2); }

.yt-chip.active {
    background: var(--yt-chip-active);
    color: var(--yt-chip-active-text);
}

/* --- CHANNEL BANNER HEADER --- */
.yt-channel-banner {
    background: linear-gradient(135deg, #1f1f2e, #11111b);
    border: 1px solid #3f3f4e;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.5);
}

.yt-channel-avatar-box {
    width: 70px; height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--yt-red), #990000);
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; font-weight: 900; color: #fff;
    box-shadow: 0 4px 16px rgba(255,0,0,0.4);
    overflow: hidden;
    flex-shrink: 0;
}

/* --- CINEMA WATCH MODE LAYOUT --- */
.yt-watch-layout {
    display: none;
    grid-template-columns: 1fr 360px;
    gap: 24px;
    margin-bottom: 30px;
}

.yt-watch-layout.active { display: grid; }

.yt-player-container {
    background: #000;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0,0,0,0.6);
}

.yt-player-frame {
    position: relative;
    padding-top: 56.25%; /* 16:9 Aspect Ratio */
}

.yt-player-frame iframe, .yt-player-frame video {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    border: none;
}

.yt-audio-container {
    padding: 40px;
    text-align: center;
    background: #181818;
}

.yt-watch-details { padding: 16px 4px; }
.yt-watch-title { font-size: 19px; font-weight: 800; color: #fff; margin: 0 0 12px; line-height: 1.4; }

.yt-watch-channel-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 16px;
}

.yt-watch-author { display: flex; align-items: center; gap: 12px; cursor: pointer; }
.yt-watch-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: var(--yt-red); display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px; font-weight: 800; overflow: hidden;
}
.yt-watch-name { font-size: 15px; font-weight: 800; color: #fff; transition: color 0.2s; }
.yt-watch-name:hover { color: #3ea6ff; text-decoration: underline; }
.yt-watch-sub { font-size: 12px; color: var(--yt-text-muted); }

.yt-watch-actions { display: flex; align-items: center; gap: 10px; }
.yt-watch-btn {
    padding: 8px 16px; background: rgba(255,255,255,0.1);
    border: none; border-radius: 50px; color: #fff; font-size: 13.5px; font-weight: 600;
    cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background 0.2s;
}
.yt-watch-btn:hover { background: rgba(255,255,255,0.2); }
.yt-watch-btn.active { background: var(--yt-red); color: #fff; }
.yt-watch-btn-sub { background: #fff; color: #0f0f0f; font-weight: 800; }
.yt-watch-btn-sub:hover { background: #e5e5e5; }

.yt-watch-desc-box {
    background: var(--yt-card-bg); border-radius: 12px;
    padding: 14px 16px; font-size: 13.5px; line-height: 1.5; color: #ddd;
}
.yt-watch-desc-header { font-size: 12.5px; font-weight: 700; color: var(--yt-text-muted); margin-bottom: 8px; }

/* Sidebar Up Next */
.yt-sidebar { display: flex; flex-direction: column; gap: 14px; }
.yt-sidebar-title { font-size: 16px; font-weight: 800; color: #fff; margin: 0 0 6px; display: flex; align-items: center; gap: 8px; }

.yt-side-card { display: flex; gap: 10px; cursor: pointer; border-radius: 8px; padding: 4px; transition: background 0.2s; }
.yt-side-card:hover { background: var(--yt-hover-bg); }
.yt-side-thumb { width: 120px; height: 68px; border-radius: 8px; overflow: hidden; background: #000; flex-shrink: 0; }
.yt-side-thumb img { width: 100%; height: 100%; object-fit: cover; }
.yt-side-info { flex: 1; }
.yt-side-title { font-size: 13px; font-weight: 700; color: #fff; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 4px; }
.yt-side-channel { font-size: 11.5px; color: var(--yt-text-muted); }

/* --- Main Videos Grid --- */
.yt-sec-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.yt-sec-heading h2 { font-size: 18px; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px; }

.yt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.yt-card {
    display: flex;
    flex-direction: column;
    cursor: pointer;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.25s ease;
}

.yt-card:hover { transform: translateY(-3px); }

.yt-thumb-wrap {
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    background: #1f1f1f;
}

.yt-thumb-img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.yt-card:hover .yt-thumb-img { transform: scale(1.06); }

.yt-play-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,0.3);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.2s;
}

.yt-card:hover .yt-play-overlay { opacity: 1; }

.yt-play-btn-circle {
    width: 48px; height: 48px; border-radius: 50%;
    background: var(--yt-red); display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px; box-shadow: 0 4px 16px rgba(255, 0, 0, 0.5);
}

.yt-badge-duration {
    position: absolute; bottom: 8px; right: 8px;
    background: rgba(0, 0, 0, 0.8); color: #fff;
    padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700;
}

.yt-badge-type {
    position: absolute; top: 8px; left: 8px;
    background: var(--yt-red); color: #fff;
    padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 800;
    text-transform: uppercase;
}

.yt-badge-teacher { background: #f59e0b !important; }

.yt-card-details { display: flex; gap: 12px; padding: 12px 2px 4px; }
.yt-card-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, var(--yt-red), #990000);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 800; flex-shrink: 0; overflow: hidden; cursor: pointer;
    transition: transform 0.2s;
}
.yt-card-avatar:hover { transform: scale(1.1); }
.yt-avatar-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.yt-card-meta { flex: 1; }
.yt-card-title {
    font-size: 14.5px; font-weight: 700; color: #fff; line-height: 1.35; margin: 0 0 4px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.yt-card-author {
    font-size: 12.5px; color: var(--yt-text-muted); margin: 0 0 2px;
    display: inline-flex; align-items: center; gap: 4px; cursor: pointer; transition: color 0.2s;
}
.yt-card-author:hover { color: #3ea6ff; text-decoration: underline; }
.yt-card-author i { color: #aaa; font-size: 10px; }
.yt-card-stats { font-size: 12px; color: var(--yt-text-muted); }

/* Nút Xem Thêm Video Giống YouTube */
.yt-load-more-wrap {
    text-align: center;
    margin-top: 36px;
    margin-bottom: 20px;
}

.yt-load-more-btn {
    padding: 14px 36px;
    background: linear-gradient(135deg, #ff0000, #cc0000);
    color: #ffffff;
    border: none;
    border-radius: 50px;
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(255, 0, 0, 0.4);
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.yt-load-more-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 0, 0, 0.6);
    background: linear-gradient(135deg, #ff1a1a, #e60000);
}

.yt-loading {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: var(--yt-text-muted);
    font-size: 15px;
    font-weight: 600;
}

.yt-spinner {
    width: 36px; height: 36px;
    border: 3px solid rgba(255,255,255,0.1);
    border-top-color: var(--yt-red);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<div class="yt-container">

    <!-- Header Bar -->
    <div class="yt-header-bar">
        <a href="javascript:void(0)" onclick="closeWatchMode()" class="yt-brand">
            <div class="yt-brand-icon"><i class="fa-solid fa-play"></i></div>
            <div class="yt-brand-title">VKC<span>TUBE</span></div>
        </a>

        <!-- YouTube Search Engine Input -->
        <div class="yt-search-box">
            <input type="text" id="ytSearchInput" class="yt-search-input" placeholder="Tìm bài hát, phim hoặc Kênh YouTube (VD: FAPTV, Con An, Lofi...)..." onkeydown="if(event.key==='Enter') executeYtSearch()">
            <button class="yt-search-btn" onclick="executeYtSearch()"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
    </div>

    <!-- Quick Paste URL Box -->
    <div class="yt-paste-box">
        <i class="fa-brands fa-youtube" style="color:red; font-size:22px;"></i>
        <input type="text" id="ytPasteInput" class="yt-paste-input" placeholder="Dán link YouTube (VD: https://www.youtube.com/watch?v=...) hoặc nhập Mã Video để xem ngay...">
        <button class="yt-paste-btn" onclick="playPastedUrl()"><i class="fa-solid fa-play"></i> Xem Ngay</button>
    </div>

    <!-- Quick Category Chips -->
    <div class="yt-chips-bar">
        <button class="yt-chip active" onclick="showTeacherSection(this)"><i class="fa-solid fa-chalkboard-user"></i> Do Thầy Cô Đăng (<?= count($teacherMedia) ?>)</button>
        <button class="yt-chip" onclick="searchByChip('FAPTV', this)"><i class="fa-solid fa-tv"></i> Kênh FAPTV Phim Hài</button>
        <button class="yt-chip" onclick="searchByChip('Lofi Nhạc Học Bài', this)"><i class="fa-solid fa-headphones"></i> Nhạc Lofi Học Bài</button>
        <button class="yt-chip" onclick="searchByChip('Phim Chiếu Rạp Thuyết Minh', this)"><i class="fa-solid fa-film"></i> Phim Chiếu Rạp</button>
        <button class="yt-chip" onclick="searchByChip('V-Pop Hot Hits 2026', this)"><i class="fa-solid fa-fire"></i> V-Pop Hot Hits</button>
        <button class="yt-chip" onclick="searchByChip('Bài giảng lập trình CNTT', this)"><i class="fa-solid fa-code"></i> Bài Giảng CNTT</button>
        <button class="yt-chip" onclick="searchByChip('Nhạc Cổ Điển Tập Trung', this)"><i class="fa-solid fa-music"></i> Cổ Điển Tập Trung</button>
    </div>

    <!-- CHANNEL HEADER BANNER -->
    <div class="yt-channel-banner" id="channelHeaderBanner" style="display:none;">
        <div class="yt-channel-avatar-box" id="channelBannerAvatar">F</div>
        <div style="flex:1;">
            <h2 id="channelBannerTitle">Kênh YouTube</h2>
            <div style="font-size:13px; color:#aaa;" id="channelBannerSub">Tất cả video & danh sách phát mới nhất từ kênh này</div>
        </div>
        <button class="yt-watch-btn yt-watch-btn-sub" onclick="alert('Đã đăng ký kênh thành công!')">
            <i class="fa-brands fa-youtube" style="color:red;"></i> Đã Đăng Ký Kênh
        </button>
    </div>

    <!-- CINEMA WATCH MODE PLAYER -->
    <div class="yt-watch-layout" id="watchLayout">
        <div class="yt-watch-main">
            <div class="yt-player-container" id="mainPlayerBox">
                <!-- iFrame hoặc HTML5 Player sẽ chèn ở đây -->
            </div>

            <div class="yt-watch-details">
                <h1 class="yt-watch-title" id="watchTitle">Tiêu đề video</h1>

                <div class="yt-watch-channel-row">
                    <div class="yt-watch-author" onclick="searchYtChannel(currentItem ? currentItem.teacher_name : '')">
                        <div class="yt-watch-avatar" id="watchAvatar">Y</div>
                        <div>
                            <div class="yt-watch-name" id="watchAuthor">Tên Kênh YouTube</div>
                            <div class="yt-watch-sub">VKC Verified YouTube Engine</div>
                        </div>
                    </div>

                    <div class="yt-watch-actions">
                        <button class="yt-watch-btn" id="likeBtn" onclick="toggleLike()">
                            <i class="fa-solid fa-thumbs-up"></i> <span id="likeCount">120</span>
                        </button>
                        <button class="yt-watch-btn" onclick="alert('Đã sao chép liên kết chia sẻ video!')">
                            <i class="fa-solid fa-share"></i> Chia sẻ
                        </button>
                        <button class="yt-watch-btn" onclick="closeWatchMode()">
                            <i class="fa-solid fa-xmark"></i> Đóng Player
                        </button>
                    </div>
                </div>

                <div class="yt-watch-desc-box">
                    <div class="yt-watch-desc-header">
                        <span id="watchViews">1,000 lượt xem</span> • <span id="watchDate">Hôm nay</span>
                    </div>
                    <div id="watchDesc">Mô tả video...</div>
                </div>
            </div>
        </div>

        <!-- Sidebar Up Next -->
        <div class="yt-sidebar">
            <div class="yt-sidebar-title">
                <i class="fa-solid fa-list-ul" style="color:var(--yt-red);"></i> Video & Bài hát liên quan
            </div>
            <div id="sidebarList">
                <!-- Sidebar item cards -->
            </div>
        </div>
    </div>

    <!-- SECTION TITLE & MAIN GRID -->
    <div class="yt-sec-heading">
        <h2 id="sectionTitle"><i class="fa-solid fa-compact-disc" style="color:var(--yt-red);"></i> Danh Sách Nhạc & Phim Do Thầy Cô Chia Sẻ</h2>
    </div>

    <div class="yt-grid" id="mainGrid">
        <?php foreach ($teacherMedia as $item): 
            $ytId = $item['youtube_id'];
            $cover = $item['cover_image'];
            $thumb = '';
            if ($item['type'] === 'youtube' && $ytId) {
                $thumb = "https://img.youtube.com/vi/{$ytId}/hqdefault.jpg";
            } elseif ($cover) {
                $thumb = (strpos($cover, 'http') === 0) ? $cover : '/tkb/' . ltrim($cover, '/');
            } else {
                $thumb = '/tkb/assets/img/logo_vkc.jpg';
            }

            $itemJson = json_encode([
                'id' => $item['id'],
                'type' => $item['type'],
                'media_type' => $item['media_type'],
                'title' => $item['title'],
                'artist' => $item['artist_or_description'],
                'teacher_name' => $item['teacher_name'],
                'file_path' => '/tkb/' . ltrim($item['file_path'] ?? '', '/'),
                'youtube_id' => $ytId,
                'views' => $item['views'],
                'likes' => $item['likes'],
                'date' => date('d/m/Y', strtotime($item['created_at']))
            ], JSON_UNESCAPED_UNICODE);
        ?>
            <div class="yt-card" onclick="openWatch(<?= htmlspecialchars($itemJson, ENT_QUOTES, 'UTF-8') ?>)">
                <div class="yt-thumb-wrap">
                    <img src="<?= htmlspecialchars($thumb) ?>" class="yt-thumb-img" onerror="this.src='/tkb/assets/img/logo_vkc.jpg';">
                    <span class="yt-badge-type <?= $item['type']==='file' ? 'yt-badge-teacher' : '' ?>">
                        <?= $item['type']==='file' ? 'GIẢNG VIÊN' : 'YOUTUBE' ?>
                    </span>
                    <span class="yt-badge-duration"><?= strtoupper($item['media_type']) ?></span>
                    <div class="yt-play-overlay">
                        <div class="yt-play-btn-circle"><i class="fa-solid fa-play"></i></div>
                    </div>
                </div>

                <div class="yt-card-details">
                    <div class="yt-card-avatar" onclick="event.stopPropagation(); searchYtChannel('<?= htmlspecialchars($item['teacher_name']) ?>')">
                        <?= mb_substr($item['teacher_name'], 0, 1, 'UTF-8') ?>
                    </div>
                    <div class="yt-card-meta">
                        <h3 class="yt-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                        <div class="yt-card-author" onclick="event.stopPropagation(); searchYtChannel('<?= htmlspecialchars($item['teacher_name']) ?>')">
                            <?= htmlspecialchars($item['teacher_name']) ?> <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div class="yt-card-stats"><?= number_format($item['views']) ?> lượt xem • <?= date('d/m/Y', strtotime($item['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- NÚT TẢI THÊM VIDEO NHIỀU NHƯ YOUTUBE -->
    <div class="yt-load-more-wrap" id="loadMoreWrap" style="display:none;">
        <button class="yt-load-more-btn" id="btnLoadMore" onclick="loadMoreYtVideos()">
            <i class="fa-brands fa-youtube"></i> Xem Thêm 40+ Video Nữa (Như YouTube)
        </button>
    </div>

</div>

<script>
let currentSearchResults = [];
let teacherMediaData = <?= json_encode($teacherMedia, JSON_UNESCAPED_UNICODE) ?>;
let currentItem = null;
let liked = false;
let currentSearchQuery = '';
let currentSearchPage = 1;

// Mở giao diện phát video (Watch Cinema Mode)
function openWatch(item) {
    currentItem = item;
    liked = false;
    document.getElementById('likeBtn').classList.remove('active');

    const layout = document.getElementById('watchLayout');
    const playerBox = document.getElementById('mainPlayerBox');
    
    document.getElementById('watchTitle').textContent = item.title;
    document.getElementById('watchAuthor').innerHTML = `${item.teacher_name || item.channel || 'Kênh YouTube'} <i class="fa-solid fa-circle-check" style="color:#38bdf8;font-size:13px;"></i>`;
    document.getElementById('watchAvatar').innerHTML = item.avatar ? `<img src="${item.avatar}" class="yt-avatar-img">` : (item.teacher_name || item.channel || 'Y').charAt(0).toUpperCase();
    document.getElementById('watchViews').textContent = (typeof item.views === 'number' ? item.views + ' lượt xem' : item.views);
    document.getElementById('watchDate').textContent = item.date || 'Hôm nay';
    document.getElementById('watchDesc').textContent = item.artist || 'Phát trực tiếp trên hệ thống VKC Tube Engine.';
    document.getElementById('likeCount').textContent = item.likes || 120;

    if (item.type === 'youtube') {
        playerBox.innerHTML = `
            <div class="yt-player-frame">
                <iframe src="https://www.youtube.com/embed/${item.youtube_id}?autoplay=1&rel=0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        `;
    } else {
        if (item.media_type === 'video') {
            playerBox.innerHTML = `
                <div class="yt-player-frame">
                    <video src="${item.file_path}" controls autoplay></video>
                </div>
            `;
        } else {
            playerBox.innerHTML = `
                <div class="yt-audio-container">
                    <div style="font-size:48px;color:var(--yt-red);margin-bottom:14px;"><i class="fa-solid fa-music"></i></div>
                    <h3 style="color:#fff;margin-bottom:14px;">${item.title}</h3>
                    <audio src="${item.file_path}" controls autoplay></audio>
                </div>
            `;
        }
    }

    renderSidebar(item.id);
    layout.classList.add('active');
    layout.scrollIntoView({ behavior: 'smooth', block: 'start' });

    if (typeof item.id === 'number') {
        fetch('/tkb/student/music.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=inc_view&id=' + item.id
        }).catch(e => console.log(e));
    }
}

function closeWatchMode() {
    const layout = document.getElementById('watchLayout');
    const playerBox = document.getElementById('mainPlayerBox');
    playerBox.innerHTML = '';
    layout.classList.remove('active');
    
    const banner = document.getElementById('channelHeaderBanner');
    if (banner) banner.style.display = 'none';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderSidebar(currentId) {
    const container = document.getElementById('sidebarList');
    container.innerHTML = '';

    let list = currentSearchResults.length > 0 ? currentSearchResults : teacherMediaData.map(m => ({
        id: m.id,
        type: m.type,
        media_type: m.media_type || 'audio',
        title: m.title,
        artist: m.artist_or_description || 'Giảng viên chia sẻ',
        teacher_name: m.teacher_name || 'Giảng viên',
        youtube_id: m.youtube_id || '',
        file_path: '/tkb/' + (m.file_path || '').replace(/^\//, ''),
        thumbnail: m.cover_image ? (m.cover_image.startsWith('http') ? m.cover_image : '/tkb/' + m.cover_image.replace(/^\//, '')) : (m.youtube_id ? `https://img.youtube.com/vi/${m.youtube_id}/hqdefault.jpg` : '')
    }));

    list.filter(m => m.id != currentId).slice(0, 10).forEach(m => {
        const div = document.createElement('div');
        div.className = 'yt-side-card';
        div.onclick = () => openWatch(m);

        div.innerHTML = `
            <div class="yt-side-thumb">
                ${m.thumbnail ? `<img src="${m.thumbnail}">` : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;"><i class="fa-solid fa-play"></i></div>`}
            </div>
            <div class="yt-side-info">
                <div class="yt-side-title">${m.title}</div>
                <div class="yt-side-channel" onclick="event.stopPropagation(); searchYtChannel('${m.channel || m.teacher_name || 'YouTube Channel'}')">${m.channel || m.teacher_name || 'VKC Channel'} <i class="fa-solid fa-circle-check" style="font-size:10px;color:#aaa;"></i></div>
            </div>
        `;
        container.appendChild(div);
    });
}

function toggleLike() {
    liked = !liked;
    const btn = document.getElementById('likeBtn');
    const cnt = document.getElementById('likeCount');
    let num = parseInt(cnt.textContent || '120');

    if (liked) {
        btn.classList.add('active');
        cnt.textContent = num + 1;
    } else {
        btn.classList.remove('active');
        cnt.textContent = Math.max(0, num - 1);
    }
}

// Bấm vào Kênh YouTube để hiển thị tất cả video từ Kênh đó
function searchYtChannel(channelName, avatarUrl) {
    if (!channelName) return;
    closeWatchMode();
    document.getElementById('ytSearchInput').value = channelName;

    const banner = document.getElementById('channelHeaderBanner');
    if (banner) {
        banner.style.display = 'flex';
        document.getElementById('channelBannerTitle').innerHTML = `${channelName} <i class="fa-solid fa-circle-check" style="color:#38bdf8; font-size:18px;"></i>`;
        document.getElementById('channelBannerAvatar').innerHTML = avatarUrl ? `<img src="${avatarUrl}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : channelName.charAt(0).toUpperCase();
    }

    executeYtSearch(channelName);
}

// Tìm kiếm YouTube trực tiếp từ thanh tìm kiếm
function executeYtSearch(customQuery) {
    const q = customQuery || document.getElementById('ytSearchInput').value.trim();
    if (!q) return;

    currentSearchQuery = q;
    currentSearchPage = 1;

    if (!customQuery) {
        closeWatchMode();
    }

    document.querySelectorAll('.yt-chip').forEach(c => c.classList.remove('active'));

    const grid = document.getElementById('mainGrid');
    const title = document.getElementById('sectionTitle');
    title.innerHTML = `<i class="fa-brands fa-youtube" style="color:red;"></i> Kết Quả YouTube Trực Tiếp Cho: "${q}"`;
    
    grid.innerHTML = `
        <div class="yt-loading">
            <div class="yt-spinner"></div>
            Đang tìm hàng loạt video & bộ phim trên YouTube...
        </div>
    `;

    document.getElementById('loadMoreWrap').style.display = 'none';
    title.scrollIntoView({ behavior: 'smooth', block: 'start' });

    fetch('/tkb/student/music.php?action=yt_search&q=' + encodeURIComponent(q) + '&page=1')
        .then(r => r.json())
        .then(data => {
            grid.innerHTML = '';
            currentSearchResults = data.map(item => ({
                id: item.id,
                type: 'youtube',
                media_type: 'video',
                title: item.title,
                artist: 'Kênh: ' + item.channel,
                teacher_name: item.channel,
                channel: item.channel,
                youtube_id: item.id,
                views: item.views,
                likes: 120,
                avatar: item.avatar,
                thumbnail: item.thumbnail
            }));

            if (!data || data.length === 0) {
                grid.innerHTML = `
                    <div class="yt-empty">
                        <i class="fa-brands fa-youtube"></i>
                        <h2>Không Tìm Thấy Kết Quả</h2>
                        <p>Thử tìm kiếm với từ khóa hoặc tên Kênh khác xem sao bạn nhé!</p>
                    </div>
                `;
                return;
            }

            renderVideoCards(data, grid);
            document.getElementById('loadMoreWrap').style.display = 'block';
        })
        .catch(err => {
            grid.innerHTML = `<div class="yt-empty"><i class="fa-solid fa-triangle-exclamation"></i><h2>Lỗi Kết Nối</h2><p>Không thể tải dữ liệu YouTube. Vui lòng thử lại sau.</p></div>`;
        });
}

function renderVideoCards(items, container) {
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'yt-card';
        const cardData = {
            id: item.id,
            type: 'youtube',
            media_type: 'video',
            title: item.title,
            artist: 'Kênh: ' + item.channel + ' • ' + item.views,
            teacher_name: item.channel,
            channel: item.channel,
            youtube_id: item.id,
            views: item.views,
            likes: 120,
            avatar: item.avatar,
            date: 'YouTube'
        };

        const avatarHtml = item.avatar ? `<img src="${item.avatar}" class="yt-avatar-img" onerror="this.style.display='none';">` : (item.channel || 'Y').charAt(0);

        div.onclick = () => openWatch(cardData);
        div.innerHTML = `
            <div class="yt-thumb-wrap">
                <img src="${item.thumbnail}" class="yt-thumb-img" onerror="this.src='/tkb/assets/img/logo_vkc.jpg';">
                <span class="yt-badge-type">YOUTUBE</span>
                <span class="yt-badge-duration">HD</span>
                <div class="yt-play-overlay"><div class="yt-play-btn-circle"><i class="fa-solid fa-play"></i></div></div>
            </div>
            <div class="yt-card-details">
                <div class="yt-card-avatar" title="Bấm xem tất cả video Kênh ${item.channel}" onclick="event.stopPropagation(); searchYtChannel('${item.channel}', '${item.avatar}')">${avatarHtml}</div>
                <div class="yt-card-meta">
                    <h3 class="yt-card-title">${item.title}</h3>
                    <div class="yt-card-author" title="Bấm xem Kênh ${item.channel}" onclick="event.stopPropagation(); searchYtChannel('${item.channel}', '${item.avatar}')">${item.channel} <i class="fa-solid fa-circle-check"></i></div>
                    <div class="yt-card-stats">${item.views}</div>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

// TẢI THÊM 40+ VIDEO NỮA TỪ YOUTUBE
function loadMoreYtVideos() {
    if (!currentSearchQuery) return;
    currentSearchPage++;

    const btn = document.getElementById('btnLoadMore');
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Đang tải thêm video...`;
    btn.disabled = true;

    fetch('/tkb/student/music.php?action=yt_search&q=' + encodeURIComponent(currentSearchQuery) + '&page=' + currentSearchPage)
        .then(r => r.json())
        .then(data => {
            btn.innerHTML = `<i class="fa-brands fa-youtube"></i> Xem Thêm 40+ Video Nữa (Như YouTube)`;
            btn.disabled = false;

            if (data && data.length > 0) {
                const mapped = data.map(item => ({
                    id: item.id,
                    type: 'youtube',
                    media_type: 'video',
                    title: item.title,
                    artist: 'Kênh: ' + item.channel,
                    teacher_name: item.channel,
                    channel: item.channel,
                    youtube_id: item.id,
                    views: item.views,
                    likes: 120,
                    avatar: item.avatar,
                    thumbnail: item.thumbnail
                }));
                currentSearchResults = currentSearchResults.concat(mapped);
                renderVideoCards(data, document.getElementById('mainGrid'));
            } else {
                btn.innerHTML = `<i class="fa-solid fa-check"></i> Đã tải hết video liên quan`;
                btn.style.opacity = '0.6';
            }
        })
        .catch(err => {
            btn.innerHTML = `<i class="fa-brands fa-youtube"></i> Xem Thêm 40+ Video Nữa (Như YouTube)`;
            btn.disabled = false;
        });
}

function playPastedUrl() {
    const raw = document.getElementById('ytPasteInput').value.trim();
    if (!raw) {
        alert('Vui lòng nhập link YouTube hoặc Mã Video!');
        return;
    }

    let ytId = raw;
    if (raw.includes('v=')) {
        ytId = raw.split('v=')[1].split('&')[0];
    } else if (raw.includes('youtu.be/')) {
        ytId = raw.split('youtu.be/')[1].split('?')[0];
    }

    openWatch({
        id: ytId,
        type: 'youtube',
        media_type: 'video',
        title: 'Video YouTube Trực Tiếp (' + ytId + ')',
        artist: 'Phát trực tiếp qua đường dẫn YouTube',
        teacher_name: 'VKC Player Engine',
        youtube_id: ytId,
        views: 'Trực tiếp',
        likes: 999
    });
}

function searchByChip(query, chipEl) {
    document.getElementById('ytSearchInput').value = query;
    document.querySelectorAll('.yt-chip').forEach(c => c.classList.remove('active'));
    chipEl.classList.add('active');
    executeYtSearch();
}

function showTeacherSection(chipEl) {
    document.querySelectorAll('.yt-chip').forEach(c => c.classList.remove('active'));
    chipEl.classList.add('active');
    location.reload();
}
</script>

</div><!-- close main-content from nav -->
</body>
</html>
