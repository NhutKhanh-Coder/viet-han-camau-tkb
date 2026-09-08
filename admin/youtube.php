<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$db = getDB();

// Helper: Extract YouTube ID
function getYoutubeId($url) {
    if (empty($url)) return '';
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/|youtube\.com/shorts/)([^"&?/=\s]{11})%i';
    if (preg_match($pattern, $url, $match)) {
        return $match[1];
    }
    return '';
}

// Function: Real Live YouTube Search via YouTube Web Scraper
function searchRealYoutube($query) {
    $url = "https://www.youtube.com/results?search_query=" . urlencode($query);
    $opts = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\nAccept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7\r\n",
            "timeout" => 7
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];
    $context = stream_context_create($opts);
    $html = @file_get_contents($url, false, $context);
    if (!$html) return [];
    
    if (preg_match('/ytInitialData\s*=\s*({.+?});<\/script>/s', $html, $matches)) {
        $data = json_decode($matches[1], true);
        $results = [];
        $contents = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];
        foreach ($contents as $section) {
            $itemSection = $section['itemSectionRenderer']['contents'] ?? [];
            foreach ($itemSection as $item) {
                if (isset($item['videoRenderer'])) {
                    $v = $item['videoRenderer'];
                    $yt_id = $v['videoId'] ?? '';
                    if (!empty($yt_id)) {
                        $results[] = [
                            'id' => 'yt_live_' . $yt_id,
                            'yt_id' => $yt_id,
                            'title' => $v['title']['runs'][0]['text'] ?? 'Video YouTube',
                            'channel' => $v['ownerText']['runs'][0]['text'] ?? 'YouTube Channel',
                            'avatar' => $v['channelThumbnailSupportedRenderers']['channelThumbnailWithLinkRenderer']['thumbnail']['thumbnails'][0]['url'] ?? ('https://ui-avatars.com/api/?name=' . urlencode($v['ownerText']['runs'][0]['text'] ?? 'YT') . '&background=ef4444&color=fff'),
                            'views' => $v['viewCountText']['simpleText'] ?? ($v['shortViewCountText']['simpleText'] ?? 'Xem trực tiếp'),
                            'time' => $v['publishedTimeText']['simpleText'] ?? 'Gần đây',
                            'duration' => $v['lengthText']['simpleText'] ?? 'Video',
                            'desc' => $v['detailedMetadataSnippets'][0]['snippetText']['runs'][0]['text'] ?? 'Video tìm kiếm từ YouTube toàn cầu.',
                            'subject' => 'YouTube Search'
                        ];
                    }
                }
            }
        }
        return $results;
    }
    return [];
}

// Handle AJAX Search API
if (isset($_GET['api']) && $_GET['api'] === 'search') {
    header('Content-Type: application/json; charset=utf-8');
    $q = trim($_GET['q'] ?? '');
    if (empty($q)) {
        echo json_encode(['status' => 'empty', 'results' => []]);
        exit;
    }
    $results = searchRealYoutube($q);
    echo json_encode(['status' => 'success', 'query' => $q, 'results' => $results]);
    exit;
}

// Auto-migrate: Add video_url to lessons table if not exists
@$db->query("ALTER TABLE `lessons` ADD COLUMN `video_url` VARCHAR(500) NULL DEFAULT NULL AFTER `tieu_de`");

// Fetch subjects and teachers for dropdowns
$res_m = $db->query("SELECT id, ten_mon, ma_mon, khoa FROM mon_hoc ORDER BY ten_mon");
$subjects = $res_m ? $res_m->fetch_all(MYSQLI_ASSOC) : [];
if (empty($subjects)) {
    $db->query("INSERT INTO mon_hoc (ma_mon, ten_mon, so_tin_chi, khoa) VALUES ('01', 'Lập Trình Web', 2, 'Công Nghệ Thông Tin')");
    $res_m2 = $db->query("SELECT id, ten_mon, ma_mon, khoa FROM mon_hoc ORDER BY ten_mon");
    $subjects = $res_m2 ? $res_m2->fetch_all(MYSQLI_ASSOC) : [];
}

$res_g = $db->query("SELECT id, ho_ten, ma_gv, khoa FROM giang_vien ORDER BY ho_ten");
$gvList = $res_g ? $res_g->fetch_all(MYSQLI_ASSOC) : [];

$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'add_video') {
    $mid = (int)$_POST['mon_hoc_id'];
    $gvid = (int)($_POST['giang_vien_id'] ?? 0);
    $title = trim($_POST['tieu_de'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $content = trim($_POST['noi_dung'] ?? '');
    
    if (!empty($video_url) && !preg_match("~^(?:f|ht)tps?://~i", $video_url)) {
        $video_url = "https://" . $video_url;
    }
    
    if ($mid && $title && $video_url) {
        $stmt = $db->prepare("INSERT INTO lessons (giang_vien_id, mon_hoc_id, tieu_de, video_url, noi_dung) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iisss", $gvid, $mid, $title, $video_url, $content);
            if ($stmt->execute()) {
                $msg = "success:Đã xuất bản video vào môn học thành công!";
            } else {
                $msg = "error:Lỗi lưu video: " . $db->error;
            }
        }
    } else {
        $msg = "error:Vui lòng nhập đầy đủ thông tin.";
    }
}

// Fetch all saved YouTube videos in database
$db_videos = [];
$res_l = $db->query("
    SELECT l.id, l.tieu_de, l.video_url, l.noi_dung, l.created_at, m.ten_mon, g.ho_ten as gv_name 
    FROM lessons l 
    LEFT JOIN mon_hoc m ON l.mon_hoc_id = m.id 
    LEFT JOIN giang_vien g ON l.giang_vien_id = g.id 
    ORDER BY l.id DESC
");
if ($res_l) {
    while ($row = $res_l->fetch_assoc()) {
        $yt_id = getYoutubeId($row['video_url'] ?? '');
        if ($yt_id) {
            $db_videos[] = [
                'id' => 'db_' . $row['id'],
                'yt_id' => $yt_id,
                'title' => $row['tieu_de'],
                'channel' => $row['gv_name'] ?: 'Cao Đẳng Cà Mau',
                'avatar' => 'https://ui-avatars.com/api/?name=CDCM&background=ea580c&color=fff',
                'category' => 'db_saved',
                'views' => rand(150, 4800) . ' lượt xem',
                'time' => date('d/m/Y', strtotime($row['created_at'])),
                'duration' => '45:00',
                'desc' => $row['noi_dung'] ?: 'Video bài giảng đào tạo chính quy trường Cao Đẳng Cà Mau.',
                'subject' => $row['ten_mon'] ?? 'Môn học'
            ];
        }
    }
}

// Initial Curated YouTube Educational Videos
$default_youtube_library = [
    [
        'id' => 'yt_1',
        'yt_id' => 'zWSswpP23qM',
        'title' => 'Khóa học HTML & CSS từ số 0 đến làm chủ Website thực chiến',
        'channel' => 'F8 Official',
        'avatar' => 'https://ui-avatars.com/api/?name=F8&background=ea580c&color=fff',
        'category' => 'web',
        'views' => '1.2 Tr lượt xem',
        'time' => '1 năm trước',
        'duration' => '12:35:40',
        'desc' => "Khóa học HTML CSS từ cơ bản đến nâng cao dành cho người mới bắt đầu học lập trình web.",
        'subject' => 'Lập Trình Web'
    ],
    [
        'id' => 'yt_2',
        'yt_id' => 'MGhw6XliFgo',
        'title' => 'Lập trình JavaScript cơ bản cho người mới bắt đầu (Full Course)',
        'channel' => 'F8 Official',
        'avatar' => 'https://ui-avatars.com/api/?name=F8&background=ea580c&color=fff',
        'category' => 'js',
        'views' => '980 N lượt xem',
        'time' => '8 tháng trước',
        'duration' => '18:42:15',
        'desc' => "Toàn bộ kiến thức JavaScript nền tảng: Biến, Hàm, Mảng, Object, DOM Event, JSON, Fetch API và Async/Await.",
        'subject' => 'Lập Trình Web'
    ],
    [
        'id' => 'yt_3',
        'yt_id' => 'x0fSBAgBrOQ',
        'title' => 'Học React.js từ cơ bản đến nâng cao - Xây dựng Single Page App',
        'channel' => 'F8 Official',
        'avatar' => 'https://ui-avatars.com/api/?name=F8&background=ea580c&color=fff',
        'category' => 'web',
        'views' => '650 N lượt xem',
        'time' => '5 tháng trước',
        'duration' => '14:20:00',
        'desc' => "Tìm hiểu React Components, JSX, State & Props, React Hooks (useState, useEffect, useMemo), React Router v6.",
        'subject' => 'Lập Trình Web'
    ],
    [
        'id' => 'yt_4',
        'yt_id' => 'kqtD5dpn9C8',
        'title' => 'Khóa học Lập trình Python toàn diện cho người mới bắt đầu',
        'channel' => 'FreeCodeCamp VN',
        'avatar' => 'https://ui-avatars.com/api/?name=PY&background=0284c7&color=fff',
        'category' => 'python',
        'views' => '1.5 Tr lượt xem',
        'time' => '1 năm trước',
        'duration' => '06:14:07',
        'desc' => "Khóa học Python đầy đủ từ con số 0: Cú pháp, Kiểu dữ liệu, Lập trình hướng đối tượng OOP.",
        'subject' => 'Khoa Học Máy Tính'
    ],
    [
        'id' => 'yt_5',
        'yt_id' => 'M576WGiDBdQ',
        'title' => 'Lộ trình học Lập trình Web Fullstack 2026 chuẩn nghề nghiệp',
        'channel' => 'Hỏi Dân IT',
        'avatar' => 'https://ui-avatars.com/api/?name=HD&background=10b981&color=fff',
        'category' => 'web',
        'views' => '420 N lượt xem',
        'time' => '3 tháng trước',
        'duration' => '42:18',
        'desc' => "Chia sẻ kinh nghiệm định hướng nghề nghiệp, lộ trình Frontend, Backend, Database.",
        'subject' => 'Kỹ Năng Nghề Nghiệp'
    ],
    [
        'id' => 'yt_6',
        'yt_id' => 'HXV3zeRR3h4',
        'title' => 'Khóa học SQL và Cơ Sở Dữ Liệu MySQL cơ bản đến nâng cao',
        'channel' => 'Database Academy',
        'avatar' => 'https://ui-avatars.com/api/?name=DB&background=7c3aed&color=fff',
        'category' => 'db',
        'views' => '380 N lượt xem',
        'time' => '6 tháng trước',
        'duration' => '04:20:30',
        'desc' => "Truy vấn dữ liệu SELECT, JOIN các bảng, GROUP BY, thiết kế cơ sở dữ liệu quan hệ.",
        'subject' => 'Cơ Sở Dữ Liệu SQL'
    ],
    [
        'id' => 'yt_7',
        'yt_id' => 'RGOj5yH7evk',
        'title' => 'Thành thạo Git & GitHub quản lý mã nguồn dự án chuyên nghiệp',
        'channel' => 'Kteam Official',
        'avatar' => 'https://ui-avatars.com/api/?name=KT&background=ef4444&color=fff',
        'category' => 'skills',
        'views' => '510 N lượt xem',
        'time' => '1 năm trước',
        'duration' => '02:35:10',
        'desc' => "Hướng dẫn Git commit, push, pull, tạo nhánh branch, merge code và giải quyết conflict.",
        'subject' => 'Kỹ Năng IT'
    ],
    [
        'id' => 'yt_8',
        'yt_id' => 'jfKfPfyJRdk',
        'title' => 'Lofi Hip Hop Radio - Âm nhạc tập trung học tập & viết Code 24/7',
        'channel' => 'Lofi Girl Official',
        'avatar' => 'https://ui-avatars.com/api/?name=LF&background=db2777&color=fff',
        'category' => 'music',
        'views' => '8.5 Tr lượt xem',
        'time' => 'Trực tiếp',
        'duration' => 'LIVE',
        'desc' => "Nhạc Lofi thư giãn, tăng sự tập trung cao độ khi học bài, làm đồ án và lập trình phần mềm.",
        'subject' => 'Giải Trí & Học Tập'
    ]
];

$all_initial_videos = array_merge($db_videos, $default_youtube_library);

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Admin - Trường Cao Đẳng Cà Mau</title>
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        /* AUTHENTIC YOUTUBE DARK / MODERN THEME */
        :root {
            --yt-bg: #0f0f0f;
            --yt-card-bg: #181818;
            --yt-card-hover: #222222;
            --yt-border: rgba(255, 255, 255, 0.1);
            --yt-text: #f1f1f1;
            --yt-text-muted: #aaaaaa;
            --yt-red: #ff0000;
            --yt-chip-bg: rgba(255, 255, 255, 0.1);
            --yt-chip-active: #ffffff;
            --yt-chip-active-text: #0f0f0f;
        }

        .yt-admin-container {
            background-color: var(--yt-bg) !important;
            color: var(--yt-text) !important;
            min-height: calc(100vh - 68px);
            padding: 16px 28px 40px;
            box-sizing: border-box;
            font-family: 'Roboto', 'Plus Jakarta Sans', Arial, sans-serif;
        }

        /* YOUTUBE TOP ACTION / SEARCH BAR */
        .yt-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .yt-brand-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            text-decoration: none;
            cursor: pointer;
        }
        .yt-brand-logo i {
            color: var(--yt-red);
            font-size: 26px;
        }
        .yt-brand-logo span.badge-vn {
            font-size: 10px;
            color: #aaaaaa;
            font-weight: 700;
            margin-top: -10px;
        }

        .yt-search-container {
            flex: 1;
            max-width: 620px;
            display: flex;
            align-items: center;
        }
        .yt-search-input {
            width: 100%;
            padding: 10px 16px;
            background: #121212;
            border: 1px solid #303030;
            border-right: none;
            border-radius: 40px 0 0 40px;
            color: #ffffff;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        .yt-search-input:focus {
            border-color: #1c62b9;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.3);
        }
        .yt-search-btn {
            background: #222222;
            border: 1px solid #303030;
            border-radius: 0 40px 40px 0;
            padding: 10px 22px;
            color: #ffffff;
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .yt-search-btn:hover {
            background: #2a2a2a;
        }
        .yt-mic-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #222222;
            border: none;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 12px;
            cursor: pointer;
            font-size: 14px;
        }
        .yt-mic-btn:hover {
            background: #2a2a2a;
        }

        .yt-create-btn {
            background: #272727;
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .yt-create-btn:hover {
            background: #383838;
        }

        /* YOUTUBE CATEGORY CHIPS SCROLLER */
        .yt-chips-row {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 12px;
            margin-bottom: 20px;
            scrollbar-width: none;
        }
        .yt-chips-row::-webkit-scrollbar { display: none; }
        
        .yt-chip {
            padding: 7px 14px;
            background: var(--yt-chip-bg);
            color: var(--yt-text);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            user-select: none;
        }
        .yt-chip:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .yt-chip.active {
            background: var(--yt-chip-active) !important;
            color: var(--yt-chip-active-text) !important;
            font-weight: 700;
        }

        /* YOUTUBE VIDEO GRID & CARDS */
        .yt-video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 22px 18px;
        }
        .yt-card {
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .yt-card:hover {
            transform: translateY(-3px);
        }
        .yt-thumb-wrap {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            background: #202020;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .yt-thumb-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .yt-card:hover .yt-thumb-img {
            transform: scale(1.04);
        }
        .yt-duration-pill {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.85);
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            letter-spacing: 0.3px;
        }
        .yt-subject-tag {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(225, 29, 72, 0.88);
            backdrop-filter: blur(4px);
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 5px;
            text-transform: uppercase;
        }

        .yt-meta-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .yt-channel-av {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            background: #333;
        }
        .yt-channel-av img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .yt-info-box {
            flex: 1;
            min-width: 0;
        }
        .yt-video-title {
            font-size: 14.5px;
            font-weight: 700;
            color: #f1f1f1;
            line-height: 1.35;
            margin: 0 0 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .yt-channel-name {
            font-size: 12.5px;
            color: var(--yt-text-muted);
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .yt-channel-name i.fa-circle-check {
            font-size: 10px;
            color: #aaaaaa;
        }
        .yt-sub-info {
            font-size: 12px;
            color: var(--yt-text-muted);
        }

        /* YOUTUBE WATCH PAGE (THEATER VIEW) */
        #ytWatchView {
            display: none;
        }
        .yt-watch-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
        }
        @media (max-width: 1100px) {
            .yt-watch-layout { grid-template-columns: 1fr; }
        }

        .yt-player-box {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            border-radius: 16px;
            overflow: hidden;
            background: #000000;
            box-shadow: 0 10px 35px rgba(0,0,0,0.6);
            margin-bottom: 16px;
        }
        .yt-player-box iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .yt-watch-title {
            font-size: 19px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 12px;
            line-height: 1.3;
        }

        .yt-watch-actions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 14px;
            border-bottom: 1px solid #272727;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .yt-channel-block {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .yt-channel-block-av {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
        }
        .yt-channel-block-av img { width: 100%; height: 100%; object-fit: cover; }
        
        .yt-btn-pill {
            background: #272727;
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
            text-decoration: none;
        }
        .yt-btn-pill:hover {
            background: #383838;
        }
        .yt-subscribe-btn {
            background: #ffffff;
            color: #0f0f0f;
            border-radius: 20px;
            padding: 9px 18px;
            font-weight: 800;
            font-size: 13px;
            border: none;
            cursor: pointer;
        }

        .yt-desc-box {
            background: #272727;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 13.5px;
            color: #f1f1f1;
            line-height: 1.6;
            margin-bottom: 24px;
            white-space: pre-wrap;
        }

        /* UP NEXT SIDEBAR CARDS */
        .yt-side-card {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: background 0.2s;
            border-radius: 10px;
            padding: 4px;
        }
        .yt-side-card:hover {
            background: #222222;
        }
        .yt-side-thumb {
            position: relative;
            width: 150px;
            height: 84px;
            border-radius: 8px;
            overflow: hidden;
            background: #222;
            flex-shrink: 0;
        }
        .yt-side-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .yt-side-title {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
            margin: 0 0 3px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* LOADING SKELETON */
        .yt-loading-indicator {
            display: none;
            text-align: center;
            padding: 40px;
            color: #aaa;
            font-size: 15px;
            font-weight: 600;
        }
        .yt-spinner {
            width: 38px;
            height: 38px;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: var(--yt-red);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 12px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="admin-portal">
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="main-content">
    <div class="yt-admin-container">
        
        <!-- YOUTUBE TOP BAR (EXACT YOUTUBE HEADER) -->
        <div class="yt-top-bar">
            <div onclick="showFeedView()" class="yt-brand-logo">
                <i class="fa-brands fa-youtube"></i>
                <span>YouTube <span class="badge-vn">VN</span></span>
            </div>

            <div class="yt-search-container">
                <input type="text" id="ytMainSearch" class="yt-search-input" placeholder="Tìm kiếm bất kỳ video nào trên YouTube (ví dụ: doraemon, nhạc trẻ, lập trình...)" onkeyup="if(event.key==='Enter') executeYtSearch()">
                <button type="button" class="yt-search-btn" onclick="executeYtSearch()" title="Tìm kiếm">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <button type="button" class="yt-mic-btn" onclick="alert('Đang lắng nghe giọng nói tìm kiếm...')" title="Tìm kiếm bằng giọng nói">
                    <i class="fa-solid fa-microphone"></i>
                </button>
            </div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <button type="button" class="yt-create-btn" onclick="openAddVideoModal()">
                    <i class="fa-solid fa-video"></i>
                    <span>Tạo bài giảng</span>
                </button>
                <button type="button" class="yt-mic-btn" title="Thông báo" onclick="alert('Không có thông báo mới')">
                    <i class="fa-regular fa-bell"></i>
                </button>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if ($msg): 
            $parts = explode(':', $msg);
            $type = $parts[0];
            $text = $parts[1];
        ?>
            <div style="padding: 12px 18px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; font-size: 13.5px; background: <?= $type === 'success' ? '#064e3b' : '#7f1d1d' ?>; color: #ffffff; border: 1px solid <?= $type === 'success' ? '#059669' : '#dc2626' ?>;">
                <i class="fa-solid <?= $type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i> <?= htmlspecialchars($text) ?>
            </div>
        <?php endif; ?>

        <!-- VIEW A: YOUTUBE HOME FEED (GRID VIEW) -->
        <div id="ytFeedView">
            
            <!-- Category Chips Bar -->
            <div class="yt-chips-row">
                <button class="yt-chip active" onclick="searchByChip('Tất cả', this)">Tất cả</button>
                <button class="yt-chip" onclick="searchByChip('Doraemon', this)">🐱 Doraemon</button>
                <button class="yt-chip" onclick="searchByChip('Lập trình Web', this)">💻 Lập trình Web</button>
                <button class="yt-chip" onclick="searchByChip('JavaScript F8', this)">JavaScript</button>
                <button class="yt-chip" onclick="searchByChip('Python AI', this)">Python &amp; AI</button>
                <button class="yt-chip" onclick="searchByChip('Khóa học SQL', this)">Cơ sở dữ liệu SQL</button>
                <button class="yt-chip" onclick="searchByChip('Git GitHub', this)">Git &amp; Kỹ năng IT</button>
                <button class="yt-chip" onclick="searchByChip('Lofi Code Music', this)">Nhạc Lofi Code</button>
                <button class="yt-chip" onclick="searchByChip('Review Phim', this)">🎬 Review Phim</button>
                <button class="yt-chip" onclick="searchByChip('Hài Hước', this)">😂 Hài Hước</button>
            </div>

            <!-- Search Heading info -->
            <div id="searchHeading" style="display:none; font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 16px;">
                Kết quả tìm kiếm YouTube: <span id="searchKeyword" style="color:var(--yt-red);"></span>
            </div>

            <!-- Loading Spinner -->
            <div class="yt-loading-indicator" id="ytLoading">
                <div class="yt-spinner"></div>
                <div>Đang tìm kiếm video trực tiếp từ YouTube...</div>
            </div>

            <!-- YouTube Video Grid -->
            <div class="yt-video-grid" id="mainVideoGrid">
                <!-- Injected via JS or PHP initial library -->
            </div>

        </div>

        <!-- VIEW B: YOUTUBE WATCH PAGE (CINEMA THEATER + COMMENTS + SIDEBAR) -->
        <div id="ytWatchView">
            
            <div style="margin-bottom: 14px;">
                <button type="button" class="yt-btn-pill" onclick="showFeedView()">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại trang chủ YouTube
                </button>
            </div>

            <div class="yt-watch-layout">
                <!-- Left: Big Player & Video Details -->
                <div>
                    <div class="yt-player-box" id="ytPlayerBox"></div>

                    <h1 class="yt-watch-title" id="w_video_title">Tiêu đề video YouTube</h1>

                    <div class="yt-watch-actions-bar">
                        <div class="yt-channel-block">
                            <div class="yt-channel-block-av">
                                <img id="w_channel_av" src="" alt="Channel">
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 15px; color: #fff; display:flex; align-items:center; gap:5px;">
                                    <span id="w_channel_name">Tên Kênh</span>
                                    <i class="fa-solid fa-circle-check" style="font-size:11px; color:#aaa;"></i>
                                </div>
                                <div style="font-size: 11.5px; color: var(--yt-text-muted);">1.45 Tr người đăng ký</div>
                            </div>
                            <button type="button" class="yt-subscribe-btn" onclick="this.innerText = this.innerText==='Đăng ký'?'Đã đăng ký ✓':'Đăng ký'; this.style.background = this.innerText==='Đã đăng ký ✓'?'#383838':'#fff'; this.style.color = this.innerText==='Đã đăng ký ✓'?'#fff':'#0f0f0f';">
                                Đăng ký
                            </button>
                        </div>

                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <div style="display: inline-flex; border-radius: 20px; overflow: hidden; background: #272727;">
                                <button type="button" class="yt-btn-pill" style="border-radius: 0; border-right: 1px solid #383838;" onclick="alert('Đã thích video!')">
                                    <i class="fa-regular fa-thumbs-up"></i> <span id="w_like_count">2.8K</span>
                                </button>
                                <button type="button" class="yt-btn-pill" style="border-radius: 0;" onclick="alert('Không thích')">
                                    <i class="fa-regular fa-thumbs-down"></i>
                                </button>
                            </div>
                            <button type="button" class="yt-btn-pill" onclick="copyCurrentVideoUrl()">
                                <i class="fa-solid fa-share"></i> Chia sẻ
                            </button>
                            <button type="button" class="yt-btn-pill" style="background: var(--yt-red); color: #fff;" onclick="quickSaveWatchVideoToSubject()">
                                <i class="fa-solid fa-plus"></i> Lưu Vào Môn Học
                            </button>
                            <a id="w_direct_yt_link" href="#" target="_blank" class="yt-btn-pill" title="Mở trên YouTube">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Description Box -->
                    <div class="yt-desc-box">
                        <div style="font-weight: 800; font-size: 13px; color: #fff; margin-bottom: 6px;">
                            <span id="w_views_count">1.2 Tr lượt xem</span> &bull; <span id="w_time_ago">1 năm trước</span> &bull; <span style="color:#38bdf8;" id="w_subject_tag">#CaoDangCaMau</span>
                        </div>
                        <div id="w_desc_text"></div>
                    </div>

                    <!-- Comments Section -->
                    <div style="margin-top: 30px;">
                        <h3 style="font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 16px; display:flex; align-items:center; gap:10px;">
                            <span>Bình luận</span> <span style="font-size: 13px; color:#aaa;" id="commentCounter">24 bình luận</span>
                        </h3>

                        <!-- Add Comment Bar -->
                        <div style="display: flex; gap: 14px; margin-bottom: 24px;">
                            <img src="/tkb/assets/img/avatar_khanh.png" onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=ea580c&color=fff'" style="width: 40px; height: 40px; border-radius: 50%;" alt="Admin">
                            <div style="flex: 1;">
                                <input type="text" id="newCommentInput" placeholder="Viết bình luận của bạn..." style="width: 100%; background: transparent; border: none; border-bottom: 1px solid #444; color: #fff; padding: 6px 0; font-size: 13.5px; outline: none;" onfocus="this.style.borderColor='#fff'" onblur="this.style.borderColor='#444'">
                                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                                    <button type="button" class="yt-btn-pill" onclick="document.getElementById('newCommentInput').value=''">Hủy</button>
                                    <button type="button" class="yt-btn-pill" style="background:#fff; color:#0f0f0f; font-weight:800;" onclick="postComment()">Bình luận</button>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Comments List -->
                        <div id="commentsList">
                            <div class="yt-comment-item" style="display:flex; gap:12px; margin-bottom:18px;">
                                <img src="https://ui-avatars.com/api/?name=Nobita&background=2563eb&color=fff" style="width: 36px; height: 36px; border-radius: 50%;" alt="User">
                                <div>
                                    <div style="font-size:12.5px; font-weight:700; color:#fff; margin-bottom:2px;">Nobita &bull; <span style="font-weight:normal; font-size:11px; color:#aaa;">2 giờ trước</span></div>
                                    <div style="font-size:13px; color:#d1d5db; line-height:1.45;">Doraemon ơi cứu tớ với, bài tập khó quá! 😂</div>
                                </div>
                            </div>

                            <div class="yt-comment-item" style="display:flex; gap:12px; margin-bottom:18px;">
                                <img src="https://ui-avatars.com/api/?name=Doraemon&background=0284c7&color=fff" style="width: 36px; height: 36px; border-radius: 50%;" alt="User">
                                <div>
                                    <div style="font-size:12.5px; font-weight:700; color:#fff; margin-bottom:2px;">Doraemon ✓ &bull; <span style="font-weight:normal; font-size:11px; color:#aaa;">1 ngày trước</span></div>
                                    <div style="font-size:13px; color:#d1d5db; line-height:1.45;">Cậu hãy chăm chú xem hết video bài giảng để tự làm nhé Nobita! 🔔</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Up Next / Related Videos List -->
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #fff; margin: 0 0 14px;">Video đề xuất tiếp theo</h3>
                    <div id="relatedVideosList"></div>
                </div>
            </div>

        </div>

    </div>

    <!-- Modal Add Video to Subject -->
    <div id="addVideoModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); backdrop-filter:blur(6px); z-index:9999; align-items:center; justify-content:center;" onclick="if(event.target===this) closeAddVideoModal()">
        <div style="background:#1f1f1f; border:1px solid #333; border-radius:18px; width:650px; max-width:95vw; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.6); color:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:1px solid #333; padding-bottom:12px;">
                <h3 style="font-size:17px; font-weight:800; color:#fff; margin:0; display:flex; align-items:center; gap:8px;">
                    <i class="fa-brands fa-youtube" style="color:var(--yt-red);"></i> Xuất Bản Video Vào Môn Học
                </h3>
                <button type="button" onclick="closeAddVideoModal()" style="background:none; border:none; color:#aaa; font-size:22px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form method="POST" action="?action=add_video">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="font-size:12.5px; font-weight:700; color:#cbd5e1; display:block; margin-bottom:5px;">Học phần / Môn học *</label>
                        <select name="mon_hoc_id" id="m_mon_hoc_select" style="width:100%; padding:10px; background:#121212; border:1px solid #333; border-radius:8px; color:#fff; font-size:13px; outline:none;" required>
                            <?php foreach ($subjects as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['ten_mon']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12.5px; font-weight:700; color:#cbd5e1; display:block; margin-bottom:5px;">Giảng viên phụ trách</label>
                        <select name="giang_vien_id" style="width:100%; padding:10px; background:#121212; border:1px solid #333; border-radius:8px; color:#fff; font-size:13px; outline:none;">
                            <option value="0">-- Quản trị viên / Hệ thống --</option>
                            <?php foreach ($gvList as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['ho_ten']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-size:12.5px; font-weight:700; color:#cbd5e1; display:block; margin-bottom:5px;">Tiêu đề bài giảng YouTube *</label>
                    <input type="text" name="tieu_de" id="m_title_input" style="width:100%; padding:10px; background:#121212; border:1px solid #333; border-radius:8px; color:#fff; font-size:13.5px; outline:none; box-sizing:border-box;" placeholder="Ví dụ: Bài 1: Lập trình HTML & CSS cơ bản" required>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-size:12.5px; font-weight:700; color:#cbd5e1; display:block; margin-bottom:5px;">Link Video YouTube *</label>
                    <input type="text" name="video_url" id="m_video_url_input" style="width:100%; padding:10px; background:#121212; border:1px solid #333; border-radius:8px; color:#fff; font-size:13.5px; outline:none; box-sizing:border-box;" placeholder="https://www.youtube.com/watch?v=..." required>
                </div>

                <div style="margin-bottom:18px;">
                    <label style="font-size:12.5px; font-weight:700; color:#cbd5e1; display:block; margin-bottom:5px;">Mô tả bài học &amp; Hướng dẫn sinh viên</label>
                    <textarea name="noi_dung" id="m_desc_input" rows="4" style="width:100%; padding:10px; background:#121212; border:1px solid #333; border-radius:8px; color:#fff; font-size:13px; outline:none; box-sizing:border-box;" placeholder="Nhập tóm tắt nội dung lý thuyết, ghi chú, dặn dò..."></textarea>
                </div>

                <button type="submit" style="width:100%; padding:12px; background:var(--yt-red); color:#fff; border:none; border-radius:8px; font-weight:800; font-size:14px; cursor:pointer;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Xuất Bản Lên Hệ Thống
                </button>
            </form>
        </div>
    </div>

    <script>
        const initialVideos = <?= json_encode($all_initial_videos) ?>;
        let currentVideosList = [...initialVideos];
        let currentWatchVideo = null;

        function renderVideoGrid(videos) {
            const grid = document.getElementById('mainVideoGrid');
            grid.innerHTML = '';

            if (!videos || videos.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #888;">
                        <i class="fa-brands fa-youtube" style="font-size: 48px; color: #444; margin-bottom: 12px; display:block;"></i>
                        <h3 style="font-size: 17px; color: #fff; margin: 0 0 6px;">Không tìm thấy video nào</h3>
                        <p style="font-size: 13px; margin: 0;">Hãy thử tìm kiếm với từ khóa khác như "Doraemon", "Lập trình", "Nhạc trẻ"...</p>
                    </div>
                `;
                return;
            }

            videos.forEach(vid => {
                const card = document.createElement('div');
                card.className = 'yt-card';
                card.onclick = () => openWatchPage(vid);

                const avatarSrc = vid.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(vid.channel || 'YT')}&background=ef4444&color=fff`;

                card.innerHTML = `
                    <div class="yt-thumb-wrap">
                        <img src="https://img.youtube.com/vi/${vid.yt_id}/hqdefault.jpg" class="yt-thumb-img" alt="Thumbnail" onerror="this.src='https://img.youtube.com/vi/${vid.yt_id}/mqdefault.jpg'">
                        <span class="yt-duration-pill">${vid.duration || 'Video'}</span>
                        ${vid.subject ? `<span class="yt-subject-tag">${escapeHtml(vid.subject)}</span>` : ''}
                    </div>
                    <div class="yt-meta-row">
                        <div class="yt-channel-av">
                            <img src="${avatarSrc}" alt="Avatar">
                        </div>
                        <div class="yt-info-box">
                            <h3 class="yt-video-title">${escapeHtml(vid.title)}</h3>
                            <div class="yt-channel-name">
                                <span>${escapeHtml(vid.channel)}</span>
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="yt-sub-info">
                                <span>${vid.views || 'Trực tiếp'}</span> &bull; <span>${vid.time || 'Gần đây'}</span>
                            </div>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function renderRelatedVideos(currentId) {
            const sideList = document.getElementById('relatedVideosList');
            sideList.innerHTML = '';

            const related = currentVideosList.filter(v => v.yt_id !== currentId).slice(0, 10);
            related.forEach(rv => {
                const sideCard = document.createElement('div');
                sideCard.className = 'yt-side-card';
                sideCard.onclick = () => openWatchPage(rv);
                sideCard.innerHTML = `
                    <div class="yt-side-thumb">
                        <img src="https://img.youtube.com/vi/${rv.yt_id}/mqdefault.jpg" alt="Thumb">
                        <span class="yt-duration-pill" style="font-size: 10px; padding: 1px 4px;">${rv.duration || 'Video'}</span>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h4 class="yt-side-title">${escapeHtml(rv.title)}</h4>
                        <div style="font-size: 11.5px; color: var(--yt-text-muted);">${escapeHtml(rv.channel)}</div>
                        <div style="font-size: 11px; color: #888;">${rv.views || 'Xem nhiều'}</div>
                    </div>
                `;
                sideList.appendChild(sideCard);
            });
        }

        function parseYtId(url) {
            if (!url) return null;
            const regExp = /(?:youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/i;
            const match = url.match(regExp);
            return match ? match[1] : null;
        }

        function openWatchPage(videoData) {
            currentWatchVideo = videoData;
            
            document.getElementById('w_video_title').innerText = videoData.title;
            document.getElementById('w_channel_name').innerText = videoData.channel;
            document.getElementById('w_channel_av').src = videoData.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(videoData.channel)}&background=ea580c&color=fff`;
            document.getElementById('w_views_count').innerText = videoData.views || '1.2 Tr lượt xem';
            document.getElementById('w_time_ago').innerText = videoData.time || 'Gần đây';
            document.getElementById('w_subject_tag').innerText = videoData.subject ? '#' + videoData.subject.replace(/\s+/g, '') : '#CaoDangCaMau';
            document.getElementById('w_desc_text').innerText = videoData.desc || 'Video học tập đào tạo trường Cao Đẳng Cà Mau.';
            document.getElementById('w_direct_yt_link').href = 'https://www.youtube.com/watch?v=' + videoData.yt_id;

            // Load iframe with autoplay
            document.getElementById('ytPlayerBox').innerHTML = `<iframe src="https://www.youtube.com/embed/${videoData.yt_id}?autoplay=1&rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;

            renderRelatedVideos(videoData.yt_id);

            // Switch views
            document.getElementById('ytFeedView').style.display = 'none';
            document.getElementById('ytWatchView').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showFeedView() {
            document.getElementById('ytPlayerBox').innerHTML = '';
            document.getElementById('ytWatchView').style.display = 'none';
            document.getElementById('ytFeedView').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function executeYtSearch() {
            const query = document.getElementById('ytMainSearch').value.trim();
            if (!query) {
                document.getElementById('searchHeading').style.display = 'none';
                currentVideosList = [...initialVideos];
                renderVideoGrid(currentVideosList);
                showFeedView();
                return;
            }

            // Check if user pasted a direct YouTube link
            const ytId = parseYtId(query);
            if (ytId) {
                openWatchPage({
                    yt_id: ytId,
                    title: 'Video YouTube: ' + query,
                    channel: 'YouTube Video',
                    avatar: 'https://ui-avatars.com/api/?name=YT&background=ef4444&color=fff',
                    views: 'Trực tiếp',
                    time: 'Hôm nay',
                    desc: 'Được mở trực tiếp từ liên kết: ' + query,
                    subject: 'Xem Nhanh'
                });
                return;
            }

            showFeedView();
            document.getElementById('searchHeading').style.display = 'block';
            document.getElementById('searchKeyword').innerText = '"' + query + '"';
            document.getElementById('ytLoading').style.display = 'block';
            document.getElementById('mainVideoGrid').style.display = 'none';

            // Real Live YouTube Search API Call
            fetch('?api=search&q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    document.getElementById('ytLoading').style.display = 'none';
                    document.getElementById('mainVideoGrid').style.display = 'grid';

                    if (data && data.results && data.results.length > 0) {
                        currentVideosList = data.results;
                        renderVideoGrid(currentVideosList);
                    } else {
                        // Fallback filter local
                        const lower = query.toLowerCase();
                        const filtered = initialVideos.filter(v => v.title.toLowerCase().includes(lower) || v.channel.toLowerCase().includes(lower));
                        currentVideosList = filtered;
                        renderVideoGrid(filtered);
                    }
                })
                .catch(err => {
                    document.getElementById('ytLoading').style.display = 'none';
                    document.getElementById('mainVideoGrid').style.display = 'grid';
                    const lower = query.toLowerCase();
                    const filtered = initialVideos.filter(v => v.title.toLowerCase().includes(lower));
                    currentVideosList = filtered;
                    renderVideoGrid(filtered);
                });
        }

        function searchByChip(keyword, btn) {
            document.querySelectorAll('.yt-chip').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');

            if (keyword === 'Tất cả') {
                document.getElementById('ytMainSearch').value = '';
                document.getElementById('searchHeading').style.display = 'none';
                currentVideosList = [...initialVideos];
                renderVideoGrid(currentVideosList);
                showFeedView();
                return;
            }

            document.getElementById('ytMainSearch').value = keyword;
            executeYtSearch();
        }

        function copyCurrentVideoUrl() {
            if (currentWatchVideo) {
                const url = 'https://www.youtube.com/watch?v=' + currentWatchVideo.yt_id;
                navigator.clipboard.writeText(url);
                alert('Đã sao chép liên kết video: ' + url);
            }
        }

        function quickSaveWatchVideoToSubject() {
            if (currentWatchVideo) {
                document.getElementById('m_title_input').value = currentWatchVideo.title;
                document.getElementById('m_video_url_input').value = 'https://www.youtube.com/watch?v=' + currentWatchVideo.yt_id;
                document.getElementById('m_desc_input').value = currentWatchVideo.desc || '';
                document.getElementById('addVideoModal').style.display = 'flex';
            }
        }

        function openAddVideoModal() {
            document.getElementById('addVideoModal').style.display = 'flex';
        }

        function closeAddVideoModal() {
            document.getElementById('addVideoModal').style.display = 'none';
        }

        function postComment() {
            const input = document.getElementById('newCommentInput');
            const val = input.value.trim();
            if (!val) return;

            const list = document.getElementById('commentsList');
            const newComment = document.createElement('div');
            newComment.className = 'yt-comment-item';
            newComment.style.display = 'flex';
            newComment.style.gap = '12px';
            newComment.style.marginBottom = '18px';
            newComment.innerHTML = `
                <img src="/tkb/assets/img/avatar_khanh.png" onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=ea580c&color=fff'" style="width: 36px; height: 36px; border-radius: 50%;" alt="Admin">
                <div>
                    <div style="font-size:12.5px; font-weight:700; color:#fff; margin-bottom:2px;">Quản Trị Viên (Admin) &bull; <span style="font-weight:normal; font-size:11px; color:#aaa;">Vừa xong</span></div>
                    <div style="font-size:13px; color:#d1d5db; line-height:1.45;">${escapeHtml(val)}</div>
                </div>
            `;
            list.prepend(newComment);
            input.value = '';
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        // Initialize Feed on Load
        document.addEventListener('DOMContentLoaded', () => {
            renderVideoGrid(currentVideosList);
        });
    </script>
</div><!-- .main-content -->
</body>
</html>
