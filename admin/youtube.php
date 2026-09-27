<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

// Helper: Extract YouTube ID from any format (standard, short, embed, shorts, or raw 11-char ID)
function getYoutubeId($url) {
    if (empty($url)) return '';
    $url = trim($url);
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/|youtube\.com/shorts/)([^"&?/=\s]{11})%i';
    if (preg_match($pattern, $url, $match)) {
        return $match[1];
    }
    return '';
}

// Function: Real Live YouTube Search via YouTube Web Scraper (Robust non-regex parsing)
function searchRealYoutube($query) {
    $query = trim($query);
    if (empty($query)) return [];

    // If query is directly a YouTube URL or 11-char ID, fetch its metadata via oEmbed
    $directId = getYoutubeId($query);
    if (!empty($directId)) {
        $oembedUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$directId}&format=json";
        $opts = [
            "http" => [
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\r\n",
                "timeout" => 6
            ],
            "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
        ];
        $oembedJson = @file_get_contents($oembedUrl, false, stream_context_create($opts));
        $oe = $oembedJson ? json_decode($oembedJson, true) : null;
        return [[
            'id' => 'yt_live_' . $directId,
            'yt_id' => $directId,
            'title' => $oe['title'] ?? ('Video YouTube (' . $directId . ')'),
            'channel' => $oe['author_name'] ?? 'YouTube Channel',
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($oe['author_name'] ?? 'YT') . '&background=ef4444&color=fff',
            'views' => 'Phát trực tiếp',
            'time' => 'Mới nhất',
            'duration' => 'Video',
            'desc' => 'Được tìm kiếm trực tiếp từ liên kết hoặc mã ID YouTube: ' . $directId,
            'subject' => 'YouTube'
        ]];
    }

    $url = "https://www.youtube.com/results?search_query=" . urlencode($query);
    $html = false;

    // Method 1: cURL first (fast & reliable on hosting)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $html = curl_exec($ch);
        curl_close($ch);
    }

    // Method 2: file_get_contents fallback
    if (!$html) {
        $opts = [
            "http" => [
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\r\nAccept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7\r\n",
                "timeout" => 6
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ];
        $html = @file_get_contents($url, false, stream_context_create($opts));
    }

    if (!$html) return [];

    // Parse ytInitialData safely using string positions (avoids PCRE backtrack limit completely)
    $marker = 'ytInitialData';
    $pos = strpos($html, $marker);
    if ($pos === false) return [];

    $bracePos = strpos($html, '{', $pos);
    if ($bracePos === false) return [];

    $scriptEnd = strpos($html, '</script>', $bracePos);
    if ($scriptEnd === false) return [];

    $jsonStr = substr($html, $bracePos, $scriptEnd - $bracePos);
    $jsonStr = rtrim(trim($jsonStr), ';');
    $data = json_decode($jsonStr, true);
    if (!$data) return [];

    $results = [];
    $contents = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];

    foreach ($contents as $section) {
        $itemSection = $section['itemSectionRenderer']['contents'] ?? [];
        foreach ($itemSection as $item) {
            if (isset($item['videoRenderer'])) {
                $v = $item['videoRenderer'];
                $yt_id = $v['videoId'] ?? '';
                if (!empty($yt_id)) {
                    // Extract channel avatar
                    $avatar = '';
                    if (!empty($v['avatar']['decoratedAvatarViewModel']['avatar']['avatarViewModel']['image']['sources'][0]['url'])) {
                        $avatar = $v['avatar']['decoratedAvatarViewModel']['avatar']['avatarViewModel']['image']['sources'][0]['url'];
                    } elseif (!empty($v['channelThumbnailSupportedRenderers']['channelThumbnailWithLinkRenderer']['thumbnail']['thumbnails'][0]['url'])) {
                        $avatar = $v['channelThumbnailSupportedRenderers']['channelThumbnailWithLinkRenderer']['thumbnail']['thumbnails'][0]['url'];
                    }

                    // Extract title
                    $title = $v['title']['runs'][0]['text'] ?? ($v['title']['simpleText'] ?? 'Video YouTube');

                    // Extract channel
                    $channel = $v['ownerText']['runs'][0]['text'] ?? ($v['longBylineText']['runs'][0]['text'] ?? 'YouTube Channel');
                    if (empty($avatar)) {
                        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($channel) . '&background=ef4444&color=fff';
                    }

                    // Extract description
                    $desc = '';
                    if (!empty($v['detailedMetadataSnippets'][0]['snippetText']['runs'])) {
                        foreach ($v['detailedMetadataSnippets'][0]['snippetText']['runs'] as $r) {
                            $desc .= $r['text'] ?? '';
                        }
                    }
                    if (empty($desc)) {
                        $desc = 'Video YouTube được phát trực tuyến với độ phân giải cao.';
                    }

                    $views = $v['viewCountText']['simpleText'] ?? ($v['shortViewCountText']['simpleText'] ?? 'Lượt xem trực tiếp');
                    $time = $v['publishedTimeText']['simpleText'] ?? 'Gần đây';
                    $duration = $v['lengthText']['simpleText'] ?? 'Video';

                    $results[] = [
                        'id' => 'yt_live_' . $yt_id,
                        'yt_id' => $yt_id,
                        'title' => $title,
                        'channel' => $channel,
                        'avatar' => $avatar,
                        'views' => $views,
                        'time' => $time,
                        'duration' => $duration,
                        'desc' => $desc,
                        'subject' => 'YouTube'
                    ];
                }
            }
            if (count($results) >= 24) break 2;
        }
    }
    return $results;
}

// Handle AJAX Search API directly (no DB connection needed)
if (isset($_GET['api']) && $_GET['api'] === 'search') {
    header('Content-Type: application/json; charset=utf-8');
    $q = trim($_GET['q'] ?? '');
    if (empty($q)) {
        echo json_encode(['status' => 'empty', 'results' => []]);
        exit;
    }
    $results = searchRealYoutube($q);
    echo json_encode([
        'status' => count($results) > 0 ? 'success' : 'not_found',
        'query' => $q,
        'count' => count($results),
        'results' => $results
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = getDB();

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

// Initial Curated YouTube Educational & Entertainment Videos
$default_youtube_library = [
    [
        'id' => 'yt_1',
        'yt_id' => 'R6plN3FvzFY',
        'title' => 'Tổng quan về khóa học HTML CSS tại F8 | Học lập trình web cơ bản',
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
        'channel' => 'Programming with Mosh',
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
        'yt_id' => 'TslBGnENTFw',
        'title' => 'Hiểu toàn bộ MySQL Database trong 1 giờ 42 phút | MySQL Course',
        'channel' => 'Database Academy',
        'avatar' => 'https://ui-avatars.com/api/?name=DB&background=7c3aed&color=fff',
        'category' => 'db',
        'views' => '380 N lượt xem',
        'time' => '6 tháng trước',
        'duration' => '01:42:30',
        'desc' => "Truy vấn dữ liệu SELECT, JOIN các bảng, GROUP BY, thiết kế cơ sở dữ liệu quan hệ MySQL.",
        'subject' => 'Cơ Sở Dữ Liệu SQL'
    ],
    [
        'id' => 'yt_7',
        'yt_id' => 'RGOj5yH7evk',
        'title' => 'Thành thạo Git & GitHub quản lý mã nguồn dự án chuyên nghiệp',
        'channel' => 'freeCodeCamp.org',
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
        'yt_id' => 'rFZHOHl-L8A',
        'title' => 'Lofi Hip Hop Radio - Âm nhạc tập trung học tập & viết Code 24/7',
        'channel' => 'Lofi Girl Official',
        'avatar' => 'https://ui-avatars.com/api/?name=LF&background=db2777&color=fff',
        'category' => 'music',
        'views' => '8.5 Tr lượt xem',
        'time' => 'Trực tiếp',
        'duration' => 'LIVE',
        'desc' => "Nhạc Lofi thư giãn, tăng sự tập trung cao độ khi học bài, làm đồ án và lập trình phần mềm.",
        'subject' => 'Giải Trí & Học Tập'
    ],
    [
        'id' => 'yt_9',
        'yt_id' => '8jLOx1hD3_o',
        'title' => 'C++ Programming Course - Lập trình C++ từ Cơ bản đến Nâng cao',
        'channel' => 'freeCodeCamp.org',
        'avatar' => 'https://ui-avatars.com/api/?name=CC&background=2563eb&color=fff',
        'category' => 'skills',
        'views' => '620 N lượt xem',
        'time' => '7 tháng trước',
        'duration' => '10:15:30',
        'desc' => "Lập trình C++ căn bản, con trỏ, cấu trúc dữ liệu và giải thuật chuẩn sinh viên CNTT.",
        'subject' => 'Lập Trình C++'
    ],
    [
        'id' => 'yt_10',
        'yt_id' => '7S_tz1z_5bA',
        'title' => 'SQL Course for Beginners [Toàn bộ Khóa học SQL Cơ bản]',
        'channel' => 'Programming with Mosh',
        'avatar' => 'https://ui-avatars.com/api/?name=PM&background=059669&color=fff',
        'category' => 'db',
        'views' => '4.1 Tr lượt xem',
        'time' => '1 năm trước',
        'duration' => '04:18:25',
        'desc' => "Khóa học SQL và cơ sở dữ liệu quan hệ hoàn chỉnh từ Mosh Hamedani.",
        'subject' => 'Cơ Sở Dữ Liệu SQL'
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
    <title>YouTube Admin - Xem Mọi Video - Trường Cao Đẳng Cà Mau</title>
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        /* AUTHENTIC YOUTUBE DUAL THEME */
        :root {
            /* Default: Dark YouTube Studio Theme */
            --yt-bg: #0c0717;
            --yt-card-bg: #140d27;
            --yt-card-hover: rgba(168, 85, 247, 0.15);
            --yt-border: rgba(168, 85, 247, 0.2);
            --yt-text: #f3e8ff;
            --yt-text-muted: #a79bb7;
            --yt-red: #ff0000;
            --yt-red-hover: #cc0000;
            --yt-chip-bg: rgba(255, 255, 255, 0.08);
            --yt-chip-border: rgba(168, 85, 247, 0.2);
            --yt-chip-text: #d8b4fe;
            --yt-chip-active: #7c3aed;
            --yt-chip-active-text: #ffffff;
            --yt-input-bg: #100922;
            --yt-input-border: rgba(168, 85, 247, 0.3);
            --yt-search-btn-bg: rgba(255, 255, 255, 0.06);
            --yt-mic-bg: rgba(255, 255, 255, 0.06);
            --yt-dropdown-bg: #140d27;
            --yt-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }

        body.adm-light-mode {
            /* Light Mode */
            --yt-bg: #ffffff;
            --yt-card-bg: #ffffff;
            --yt-card-hover: #f8fafc;
            --yt-border: #e2e8f0;
            --yt-text: #0f172a;
            --yt-text-muted: #64748b;
            --yt-chip-bg: #f1f5f9;
            --yt-chip-border: #e2e8f0;
            --yt-chip-text: #334155;
            --yt-chip-active: #0f172a;
            --yt-chip-active-text: #ffffff;
            --yt-input-bg: #ffffff;
            --yt-input-border: #cbd5e1;
            --yt-search-btn-bg: #f8fafc;
            --yt-mic-bg: #f1f5f9;
            --yt-dropdown-bg: #ffffff;
            --yt-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .yt-admin-container {
            background-color: var(--yt-bg) !important;
            color: var(--yt-text) !important;
            min-height: calc(100vh - 68px);
            padding: 16px 28px 40px;
            box-sizing: border-box;
            font-family: 'Roboto', 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
            transition: background 0.3s ease, color 0.3s ease;
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
            font-size: 21px;
            font-weight: 800;
            color: var(--yt-text);
            letter-spacing: -0.5px;
            text-decoration: none;
            cursor: pointer;
            user-select: none;
        }
        .yt-brand-logo i {
            color: var(--yt-red);
            font-size: 28px;
        }
        .yt-brand-logo span.badge-vn {
            font-size: 10px;
            color: var(--yt-text-muted);
            font-weight: 700;
            margin-top: -10px;
        }

        .yt-search-container {
            flex: 1;
            max-width: 660px;
            display: flex;
            align-items: center;
            position: relative;
        }
        .yt-search-input-wrap {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
        }
        .yt-search-input {
            width: 100%;
            padding: 11px 40px 11px 18px;
            background: var(--yt-input-bg);
            border: 1px solid var(--yt-input-border);
            border-right: none;
            border-radius: 40px 0 0 40px;
            color: var(--yt-text);
            font-size: 14.5px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .yt-search-input:focus {
            border-color: #7c3aed;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.06);
        }
        .yt-clear-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--yt-text-muted);
            font-size: 15px;
            cursor: pointer;
            display: none;
            padding: 4px;
            line-height: 1;
        }
        .yt-clear-btn:hover {
            color: var(--yt-text);
        }

        .yt-search-btn {
            background: var(--yt-search-btn-bg);
            border: 1px solid var(--yt-input-border);
            border-radius: 0 40px 40px 0;
            padding: 11px 24px;
            color: var(--yt-text-muted);
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
        }
        .yt-search-btn:hover {
            color: var(--yt-text);
        }

        .yt-mic-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--yt-mic-bg);
            border: 1px solid var(--yt-input-border);
            color: var(--yt-text);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 12px;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .yt-mic-btn:hover {
            color: #7c3aed;
        }
        .yt-mic-btn.recording {
            background: var(--yt-red);
            color: #ffffff;
            animation: pulse-red 1.2s infinite ease-in-out;
        }
        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7); }
            70% { transform: scale(1.08); box-shadow: 0 0 0 10px rgba(255, 0, 0, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 0, 0, 0); }
        }

        /* AUTOCOMPLETE SUGGESTION DROPDOWN */
        .yt-suggest-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 56px;
            background: var(--yt-dropdown-bg);
            border: 1px solid var(--yt-border);
            border-radius: 14px;
            box-shadow: var(--yt-shadow);
            z-index: 1000;
            overflow: hidden;
            display: none;
        }
        .yt-suggest-item {
            padding: 9px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 14px;
            color: var(--yt-text);
            cursor: pointer;
            transition: background 0.15s;
        }
        .yt-suggest-item:hover, .yt-suggest-item.selected {
            background: var(--yt-card-hover);
        }
        .yt-suggest-item i {
            color: var(--yt-text-muted);
            font-size: 13px;
        }

        .yt-create-btn {
            background: var(--yt-mic-bg);
            color: var(--yt-text);
            border: 1px solid var(--yt-border);
            padding: 9px 18px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .yt-create-btn:hover {
            border-color: #7c3aed;
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
            padding: 8px 16px;
            background: var(--yt-chip-bg);
            color: var(--yt-chip-text);
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            border: 1px solid var(--yt-chip-border);
            transition: all 0.2s;
            user-select: none;
        }
        .yt-chip:hover {
            background: var(--yt-card-hover);
            color: var(--yt-text);
        }
        .yt-chip.active {
            background: var(--yt-chip-active) !important;
            color: var(--yt-chip-active-text) !important;
            border-color: var(--yt-chip-active) !important;
            font-weight: 700;
        }

        /* YOUTUBE VIDEO GRID & CARDS */
        .yt-video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(295px, 1fr));
            gap: 24px 18px;
        }
        .yt-card {
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s;
            position: relative;
        }
        .yt-card:hover {
            transform: translateY(-4px);
        }
        .yt-thumb-wrap {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            background: #f1f5f9;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
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
            transform: scale(1.05);
        }
        .yt-play-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 2;
        }
        .yt-card:hover .yt-play-overlay {
            opacity: 1;
        }
        .yt-play-overlay i {
            width: 50px;
            height: 50px;
            background: var(--yt-red);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 18px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            transition: transform 0.2s ease;
            padding-left: 4px;
        }
        .yt-card:hover .yt-play-overlay i {
            transform: scale(1.12);
        }
        .yt-duration-pill {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.85);
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            letter-spacing: 0.3px;
        }
        .yt-subject-tag {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(225, 29, 72, 0.9);
            backdrop-filter: blur(4px);
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 5px;
            text-transform: uppercase;
        }

        .yt-meta-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .yt-channel-av {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            background: #e2e8f0;
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
            color: #0f172a;
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
            gap: 5px;
        }
        .yt-channel-name i.fa-circle-check {
            font-size: 11px;
            color: #94a3b8;
        }
        .yt-sub-info {
            font-size: 12px;
            color: var(--yt-text-muted);
        }

        /* YOUTUBE WATCH PAGE (THEATER & CINEMA VIEW) */
        #ytWatchView {
            display: none;
        }
        .yt-watch-layout {
            display: grid;
            grid-template-columns: 1fr 410px;
            gap: 24px;
            transition: all 0.3s;
        }
        .yt-watch-layout.theater-mode {
            grid-template-columns: 1fr;
        }
        .yt-watch-layout.theater-mode #ytPlayerBox {
            padding-bottom: 52%;
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
            box-shadow: 0 10px 35px rgba(0,0,0,0.15);
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
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 14px;
            line-height: 1.3;
        }

        .yt-watch-actions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 14px;
            border-bottom: 1px solid #e2e8f0;
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
            width: 46px;
            height: 46px;
            border-radius: 50%;
            overflow: hidden;
        }
        .yt-channel-block-av img { width: 100%; height: 100%; object-fit: cover; }
        
        .yt-btn-pill {
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: background 0.2s, transform 0.1s;
            text-decoration: none;
        }
        .yt-btn-pill:hover {
            background: #e2e8f0;
        }
        .yt-btn-pill.active {
            background: #e0e7ff;
            color: #4f46e5;
            border-color: #c7d2fe;
        }
        .yt-subscribe-btn {
            background: #0f172a;
            color: #ffffff;
            border-radius: 20px;
            padding: 9px 18px;
            font-weight: 800;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .yt-subscribe-btn:hover {
            background: #334155;
        }

        .yt-desc-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 13.5px;
            color: #1e293b;
            line-height: 1.6;
            margin-bottom: 24px;
            white-space: pre-wrap;
            position: relative;
        }

        /* UP NEXT SIDEBAR CARDS */
        .yt-side-card {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            border-radius: 10px;
            padding: 6px;
        }
        .yt-side-card:hover {
            background: #f1f5f9;
            transform: translateX(2px);
        }
        .yt-side-thumb {
            position: relative;
            width: 150px;
            height: 84px;
            border-radius: 8px;
            overflow: hidden;
            background: #e2e8f0;
            flex-shrink: 0;
        }
        .yt-side-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .yt-side-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
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
            padding: 60px 20px;
            color: #64748b;
            font-size: 15px;
            font-weight: 600;
        }
        .yt-spinner {
            width: 44px;
            height: 44px;
            border: 3px solid #e2e8f0;
            border-top-color: var(--yt-red);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* TOAST NOTIFICATION */
        #ytToast {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #ffffff;
            color: #0f172a;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            border: 1px solid #e2e8f0;
            z-index: 99999;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #ytToast.show {
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>
<body class="admin-portal <?= (isset($_COOKIE['adm_theme']) && $_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : '' ?>">
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="main-content">
    <div class="yt-admin-container">
        
        <!-- YOUTUBE TOP BAR -->
        <div class="yt-top-bar">
            <div onclick="showFeedView()" class="yt-brand-logo" title="Về trang chủ YouTube Admin">
                <i class="fa-brands fa-youtube"></i>
                <span>YouTube <span class="badge-vn">VN</span></span>
            </div>

            <div class="yt-search-container">
                <div class="yt-search-input-wrap">
                    <input type="text" id="ytMainSearch" class="yt-search-input" placeholder="Tìm kiếm bất kỳ video nào trên YouTube (Faptv, Doraemon, Lofi, Sơn Tùng, Lập trình...)" autocomplete="off">
                    <button type="button" id="ytClearBtn" class="yt-clear-btn" title="Xóa tìm kiếm"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <button type="button" id="ytSearchBtn" class="yt-search-btn" title="Tìm kiếm">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <button type="button" id="ytMicBtn" class="yt-mic-btn" title="Tìm kiếm bằng giọng nói">
                    <i class="fa-solid fa-microphone"></i>
                </button>

                <!-- Real-time Suggestions Dropdown -->
                <div id="ytSuggestDropdown" class="yt-suggest-dropdown"></div>
            </div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <button type="button" class="yt-create-btn" onclick="openAddVideoModal()">
                    <i class="fa-solid fa-video" style="color:var(--yt-red);"></i>
                    <span>Tạo bài giảng</span>
                </button>
                <button type="button" class="yt-mic-btn" title="Làm mới YouTube" onclick="location.reload()">
                    <i class="fa-solid fa-rotate"></i>
                </button>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if ($msg): 
            $parts = explode(':', $msg);
            $type = $parts[0];
            $text = $parts[1] ?? '';
        ?>
            <div style="padding: 12px 18px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; font-size: 13.5px; background: <?= $type === 'success' ? '#f0fdf4' : '#fef2f2' ?>; color: <?= $type === 'success' ? '#166534' : '#991b1b' ?>; border: 1px solid <?= $type === 'success' ? '#bbf7d0' : '#fecaca' ?>;">
                <i class="fa-solid <?= $type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i> <?= htmlspecialchars($text) ?>
            </div>
        <?php endif; ?>

        <!-- VIEW A: YOUTUBE HOME FEED (GRID VIEW) -->
        <div id="ytFeedView">
            
            <!-- Category Chips Bar -->
            <div class="yt-chips-row">
                <button class="yt-chip active" onclick="searchByChip('Tất cả', this)">Tất cả</button>
                <button class="yt-chip" onclick="searchByChip('FAPtv', this)">😂 FAPtv &amp; Hài Kịch</button>
                <button class="yt-chip" onclick="searchByChip('Doraemon tiếng việt', this)">🐱 Doraemon</button>
                <button class="yt-chip" onclick="searchByChip('Lập trình Web full course', this)">💻 Lập trình Web</button>
                <button class="yt-chip" onclick="searchByChip('JavaScript cơ bản nâng cao', this)">⚡ JavaScript</button>
                <button class="yt-chip" onclick="searchByChip('Python AI Machine Learning', this)">🐍 Python &amp; AI</button>
                <button class="yt-chip" onclick="searchByChip('Khóa học SQL MySQL', this)">💾 Cơ sở dữ liệu</button>
                <button class="yt-chip" onclick="searchByChip('Git GitHub', this)">🐙 Git &amp; GitHub</button>
                <button class="yt-chip" onclick="searchByChip('Nhạc Lofi Chill thư giãn học bài', this)">🎵 Nhạc Lofi</button>
                <button class="yt-chip" onclick="searchByChip('Sơn Tùng M-TP', this)">🎤 Sơn Tùng M-TP</button>
                <button class="yt-chip" onclick="searchByChip('Review Phim hay', this)">🎬 Review Phim</button>
                <button class="yt-chip" onclick="searchByChip('Tin tức Việt Nam 24h', this)">📰 Tin Tức 24h</button>
            </div>

            <!-- Search Heading info -->
            <div id="searchHeading" style="display:none; font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 18px; align-items:center; justify-content:space-between;">
                <div>
                    Kết quả tìm kiếm YouTube: <span id="searchKeyword" style="color:var(--yt-red);"></span>
                    <span id="searchCount" style="font-size:13px; color:#64748b; font-weight:normal; margin-left:8px;"></span>
                </div>
                <button type="button" class="yt-btn-pill" style="font-size:12px; padding:5px 12px;" onclick="resetToAll()">
                    <i class="fa-solid fa-arrow-rotate-left"></i> Xem danh sách mặc định
                </button>
            </div>

            <!-- Loading Spinner -->
            <div class="yt-loading-indicator" id="ytLoading">
                <div class="yt-spinner"></div>
                <div>Đang tìm kiếm video trực tiếp từ YouTube toàn cầu...</div>
            </div>

            <!-- YouTube Video Grid -->
            <div class="yt-video-grid" id="mainVideoGrid">
                <!-- Injected via JS -->
            </div>

        </div>

        <!-- VIEW B: YOUTUBE WATCH PAGE (CINEMA THEATER + COMMENTS + SIDEBAR) -->
        <div id="ytWatchView">
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 14px; flex-wrap:wrap; gap:10px;">
                <button type="button" class="yt-btn-pill" onclick="showFeedView()">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại trang chủ YouTube
                </button>

                <div style="display:flex; gap:10px;">
                    <button type="button" class="yt-btn-pill" id="toggleTheaterBtn" onclick="toggleTheaterMode()">
                        <i class="fa-solid fa-tv"></i> Chế độ rạp chiếu
                    </button>
                </div>
            </div>

            <div class="yt-watch-layout" id="watchLayout">
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
                                <div style="font-weight: 800; font-size: 15px; color: #0f172a; display:flex; align-items:center; gap:6px;">
                                    <span id="w_channel_name">Tên Kênh</span>
                                    <i class="fa-solid fa-circle-check" style="font-size:11px; color:#94a3b8;" title="Đã xác minh"></i>
                                </div>
                                <div style="font-size: 11.5px; color: var(--yt-text-muted);">Kênh chính thức &bull; YouTube</div>
                            </div>
                            <button type="button" class="yt-subscribe-btn" id="subBtn" onclick="toggleSubscribe()">
                                Đăng ký
                            </button>
                        </div>

                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <div style="display: inline-flex; border-radius: 20px; overflow: hidden; background: #f1f5f9; border: 1px solid #e2e8f0;">
                                <button type="button" class="yt-btn-pill" id="likeBtn" style="border-radius: 0; border: none; border-right: 1px solid #e2e8f0;" onclick="toggleLike()">
                                    <i class="fa-regular fa-thumbs-up"></i> <span id="w_like_count">1.4K</span>
                                </button>
                                <button type="button" class="yt-btn-pill" id="dislikeBtn" style="border-radius: 0; border: none;" onclick="toggleDislike()">
                                    <i class="fa-regular fa-thumbs-down"></i>
                                </button>
                            </div>
                            <button type="button" class="yt-btn-pill" onclick="copyCurrentVideoUrl()">
                                <i class="fa-solid fa-share"></i> Chia sẻ
                            </button>
                            <button type="button" class="yt-btn-pill" style="background: var(--yt-red); color: #fff; border-color: var(--yt-red);" onclick="quickSaveWatchVideoToSubject()">
                                <i class="fa-solid fa-plus"></i> Lưu Vào Môn Học
                            </button>
                            <a id="w_direct_yt_link" href="#" target="_blank" class="yt-btn-pill" title="Mở trực tiếp trên YouTube.com">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> YouTube
                            </a>
                        </div>
                    </div>

                    <!-- Description Box -->
                    <div class="yt-desc-box">
                        <div style="font-weight: 800; font-size: 13.5px; color: #0f172a; margin-bottom: 8px;">
                            <span id="w_views_count">1.2 Tr lượt xem</span> &bull; <span id="w_time_ago">Gần đây</span> &bull; <span style="color:#0284c7;" id="w_subject_tag">#YouTube</span>
                        </div>
                        <div id="w_desc_text"></div>
                    </div>

                    <!-- Comments Section -->
                    <div style="margin-top: 30px;">
                        <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 16px; display:flex; align-items:center; gap:10px;">
                            <span>Bình luận</span> <span style="font-size: 13px; color:#64748b;" id="commentCounter">Bình luận</span>
                        </h3>

                        <!-- Add Comment Bar -->
                        <div style="display: flex; gap: 14px; margin-bottom: 24px;">
                            <img src="/tkb/assets/img/avatar_khanh.png" onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=ea580c&color=fff'" style="width: 40px; height: 40px; border-radius: 50%;" alt="Admin">
                            <div style="flex: 1;">
                                <input type="text" id="newCommentInput" placeholder="Viết bình luận với tư cách Quản trị viên..." style="width: 100%; background: transparent; border: none; border-bottom: 1px solid #cbd5e1; color: #0f172a; padding: 7px 0; font-size: 14px; outline: none;" onfocus="this.style.borderColor='#0f172a'" onblur="this.style.borderColor='#cbd5e1'" onkeyup="if(event.key==='Enter') postComment()">
                                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                                    <button type="button" class="yt-btn-pill" onclick="document.getElementById('newCommentInput').value=''">Hủy</button>
                                    <button type="button" class="yt-btn-pill" style="background:#0f172a; color:#ffffff; font-weight:800; border-color:#0f172a;" onclick="postComment()">Bình luận</button>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Comments List -->
                        <div id="commentsList"></div>
                    </div>
                </div>

                <!-- Right: Up Next / Related Videos List -->
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 14px; display:flex; align-items:center; justify-content:space-between;">
                        <span>Video tiếp theo</span>
                        <span style="font-size:12px; color:#64748b; font-weight:normal;">Tự động phát</span>
                    </h3>
                    <div id="relatedVideosList"></div>
                </div>
            </div>

        </div>

    </div>

    <!-- Toast Notification Element -->
    <div id="ytToast"><i class="fa-solid fa-circle-check" style="color:#10b981;"></i> <span id="ytToastMsg">Thông báo</span></div>

    <!-- Modal Add Video to Subject -->
    <div id="addVideoModal" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;" onclick="if(event.target===this) closeAddVideoModal()">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; width:650px; max-width:95vw; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.15); color:#0f172a;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                <h3 style="font-size:17px; font-weight:800; color:#0f172a; margin:0; display:flex; align-items:center; gap:8px;">
                    <i class="fa-brands fa-youtube" style="color:var(--yt-red);"></i> Xuất Bản Video Vào Môn Học
                </h3>
                <button type="button" onclick="closeAddVideoModal()" style="background:none; border:none; color:#64748b; font-size:22px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form method="POST" action="?action=add_video">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:5px;">Học phần / Môn học *</label>
                        <select name="mon_hoc_id" id="m_mon_hoc_select" style="width:100%; padding:10px; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; color:#0f172a; font-size:13px; outline:none;" required>
                            <?php foreach ($subjects as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['ten_mon']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:5px;">Giảng viên phụ trách</label>
                        <select name="giang_vien_id" style="width:100%; padding:10px; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; color:#0f172a; font-size:13px; outline:none;">
                            <option value="0">-- Quản trị viên / Hệ thống --</option>
                            <?php foreach ($gvList as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['ho_ten']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:5px;">Tiêu đề bài giảng YouTube *</label>
                    <input type="text" name="tieu_de" id="m_title_input" style="width:100%; padding:10px; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; color:#0f172a; font-size:13.5px; outline:none; box-sizing:border-box;" placeholder="Ví dụ: Bài 1: Lập trình HTML & CSS cơ bản" required>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:5px;">Link Video YouTube *</label>
                    <input type="text" name="video_url" id="m_video_url_input" style="width:100%; padding:10px; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; color:#0f172a; font-size:13.5px; outline:none; box-sizing:border-box;" placeholder="https://www.youtube.com/watch?v=..." required>
                </div>

                <div style="margin-bottom:18px;">
                    <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:5px;">Mô tả bài học &amp; Hướng dẫn sinh viên</label>
                    <textarea name="noi_dung" id="m_desc_input" rows="4" style="width:100%; padding:10px; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; color:#0f172a; font-size:13px; outline:none; box-sizing:border-box;" placeholder="Nhập tóm tắt nội dung lý thuyết, ghi chú, dặn dò..."></textarea>
                </div>

                <button type="submit" style="width:100%; padding:12px; background:var(--yt-red); color:#fff; border:none; border-radius:8px; font-weight:800; font-size:14px; cursor:pointer;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Xuất Bản Lên Hệ Thống
                </button>
            </form>
        </div>
    </div>


    <script>
        const initialVideos = <?= json_encode($all_initial_videos, JSON_UNESCAPED_UNICODE) ?>;
        let currentVideosList = [...initialVideos];
        let currentWatchVideo = null;
        let isTheater = false;
        let suggestTimeout = null;

        const mainSearchInput = document.getElementById('ytMainSearch');
        const searchBtn = document.getElementById('ytSearchBtn');
        const clearBtn = document.getElementById('ytClearBtn');
        const micBtn = document.getElementById('ytMicBtn');
        const suggestDropdown = document.getElementById('ytSuggestDropdown');

        // Thumbnail Fallback Handlers
        function handleThumbLoad(img, ytId) {
            // YouTube serves a 120x90 image containing 3 gray dots when thumbnail is missing/deleted
            if (img.naturalWidth === 120 && img.naturalHeight === 90) {
                img.onerror = null;
                img.src = 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=640&auto=format&fit=crop&q=80';
            }
        }

        function handleThumbError(img, ytId) {
            img.onerror = null;
            if (ytId && !img.dataset.triedBackup) {
                img.dataset.triedBackup = '1';
                img.src = 'https://i.ytimg.com/vi/' + encodeURIComponent(ytId) + '/hqdefault.jpg';
                return;
            }
            img.src = 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=640&auto=format&fit=crop&q=80';
        }

        // Render Video Grid
        function renderVideoGrid(videos) {
            const grid = document.getElementById('mainVideoGrid');
            grid.innerHTML = '';

            if (!videos || videos.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 70px 20px; color: #64748b;">
                        <i class="fa-brands fa-youtube" style="font-size: 56px; color: #cbd5e1; margin-bottom: 16px; display:block;"></i>
                        <h3 style="font-size: 18px; color: #0f172a; margin: 0 0 8px;">Không tìm thấy video phù hợp</h3>
                        <p style="font-size: 14px; color:#64748b; margin: 0 0 16px;">Bạn có thể thử tìm với từ khóa khác như "Faptv", "Doraemon", "Sơn Tùng", "Lập trình" hoặc dán trực tiếp link YouTube.</p>
                        <button type="button" class="yt-btn-pill" onclick="resetToAll()">Quay lại danh sách video ban đầu</button>
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
                        <img src="https://img.youtube.com/vi/${vid.yt_id}/hqdefault.jpg" class="yt-thumb-img" alt="Thumbnail" onload="handleThumbLoad(this, '${vid.yt_id}')" onerror="handleThumbError(this, '${vid.yt_id}')">
                        <div class="yt-play-overlay"><i class="fa-solid fa-play"></i></div>
                        <span class="yt-duration-pill">${vid.duration || 'Video'}</span>
                        ${vid.subject ? `<span class="yt-subject-tag">${escapeHtml(vid.subject)}</span>` : ''}
                    </div>
                    <div class="yt-meta-row">
                        <div class="yt-channel-av">
                            <img src="${avatarSrc}" alt="Avatar" onerror="this.src='https://ui-avatars.com/api/?name=YT&background=ef4444&color=fff'">
                        </div>
                        <div class="yt-info-box">
                            <h3 class="yt-video-title" title="${escapeHtml(vid.title)}">${escapeHtml(vid.title)}</h3>
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

        // Render Up Next / Related Videos Sidebar
        function renderRelatedVideos(currentId) {
            const sideList = document.getElementById('relatedVideosList');
            sideList.innerHTML = '';

            const related = currentVideosList.filter(v => v.yt_id !== currentId).slice(0, 15);
            if (related.length === 0) {
                // If current list only had 1, pull from initialVideos
                initialVideos.filter(v => v.yt_id !== currentId).slice(0, 15).forEach(rv => related.push(rv));
            }

            related.forEach(rv => {
                const sideCard = document.createElement('div');
                sideCard.className = 'yt-side-card';
                sideCard.onclick = () => openWatchPage(rv);
                sideCard.innerHTML = `
                    <div class="yt-side-thumb">
                        <img src="https://img.youtube.com/vi/${rv.yt_id}/mqdefault.jpg" alt="Thumb" onload="handleThumbLoad(this, '${rv.yt_id}')" onerror="handleThumbError(this, '${rv.yt_id}')">
                        <span class="yt-duration-pill" style="font-size: 10px; padding: 1px 5px; bottom:4px; right:4px;">${rv.duration || 'Video'}</span>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h4 class="yt-side-title">${escapeHtml(rv.title)}</h4>
                        <div style="font-size: 12px; color: var(--yt-text-muted);">${escapeHtml(rv.channel)}</div>
                        <div style="font-size: 11px; color: #888;">${rv.views || 'Xem nhiều'} &bull; ${rv.time || 'Mới'}</div>
                    </div>
                `;
                sideList.appendChild(sideCard);
            });
        }

        // Parse YouTube ID helper
        function parseYtId(url) {
            if (!url) return null;
            url = url.trim();
            if (/^[a-zA-Z0-9_-]{11}$/.test(url)) return url;
            const vMatch = url.match(/[?&]v=([a-zA-Z0-9_-]{11})/);
            if (vMatch) return vMatch[1];
            const pathMatch = url.match(/(?:youtu\.be\/|shorts\/|embed\/|\/v\/)([a-zA-Z0-9_-]{11})/);
            if (pathMatch) return pathMatch[1];
            return null;
        }

        // Open Watch Page
        function openWatchPage(videoData, pushState = true) {
            currentWatchVideo = videoData;
            if (pushState) {
                try {
                    window.history.pushState({ v: videoData.yt_id }, '', '?v=' + videoData.yt_id);
                } catch(e) {}
            }
            
            document.getElementById('w_video_title').innerText = videoData.title;
            document.getElementById('w_channel_name').innerText = videoData.channel;
            document.getElementById('w_channel_av').src = videoData.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(videoData.channel)}&background=ea580c&color=fff`;
            document.getElementById('w_views_count').innerText = videoData.views || 'Lượt xem trực tiếp';
            document.getElementById('w_time_ago').innerText = videoData.time || 'Gần đây';
            document.getElementById('w_subject_tag').innerText = videoData.subject ? '#' + videoData.subject.replace(/\s+/g, '') : '#YouTube';
            document.getElementById('w_desc_text').innerText = videoData.desc || 'Video học tập đào tạo và giải trí chất lượng cao từ YouTube.';
            document.getElementById('w_direct_yt_link').href = 'https://www.youtube.com/watch?v=' + videoData.yt_id;

            // Load iframe with autoplay & allowfullscreen
            document.getElementById('ytPlayerBox').innerHTML = `<iframe src="https://www.youtube-nocookie.com/embed/${videoData.yt_id}?autoplay=1&rel=0&enablejsapi=1" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;

            // Reset interaction buttons
            document.getElementById('likeBtn').classList.remove('active');
            document.getElementById('dislikeBtn').classList.remove('active');
            document.getElementById('w_like_count').innerText = Math.floor(Math.random() * 20 + 2) + '.' + Math.floor(Math.random() * 9) + 'K';
            
            const subBtn = document.getElementById('subBtn');
            subBtn.innerText = 'Đăng ký';
            subBtn.style.background = '#0f172a';
            subBtn.style.color = '#ffffff';
            subBtn.style.border = 'none';

            // Load comments for this video
            loadComments(videoData.yt_id);

            // Render Related Videos
            renderRelatedVideos(videoData.yt_id);

            // Switch to Watch View
            document.getElementById('ytFeedView').style.display = 'none';
            document.getElementById('ytWatchView').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Show Feed View
        function showFeedView(pushState = true) {
            if (pushState) {
                try {
                    window.history.pushState({}, '', window.location.pathname);
                } catch(e) {}
            }
            document.getElementById('ytPlayerBox').innerHTML = '';
            document.getElementById('ytWatchView').style.display = 'none';
            document.getElementById('ytFeedView').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Toggle Theater Mode
        function toggleTheaterMode() {
            isTheater = !isTheater;
            const layout = document.getElementById('watchLayout');
            const btn = document.getElementById('toggleTheaterBtn');
            if (isTheater) {
                layout.classList.add('theater-mode');
                btn.classList.add('active');
                btn.innerHTML = '<i class="fa-solid fa-compress"></i> Thu gọn chế độ xem';
            } else {
                layout.classList.remove('theater-mode');
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fa-solid fa-tv"></i> Chế độ rạp chiếu';
            }
        }

        // Execute YouTube Search
        function executeYtSearch(customQuery) {
            hideSuggestions();
            const query = (customQuery !== undefined ? customQuery : mainSearchInput.value).trim();
            if (!query) {
                resetToAll();
                return;
            }

            // If user pasted a direct YouTube link or 11-char ID
            const directYtId = parseYtId(query);
            if (directYtId) {
                openWatchPage({
                    yt_id: directYtId,
                    title: 'Video YouTube (' + directYtId + ')',
                    channel: 'YouTube Video',
                    avatar: 'https://ui-avatars.com/api/?name=YT&background=ef4444&color=fff',
                    views: 'Trực tiếp',
                    time: 'Hôm nay',
                    desc: 'Video phát từ liên kết hoặc ID trực tiếp: ' + query,
                    subject: 'Xem Trực Tiếp'
                });
                return;
            }

            showFeedView();
            document.getElementById('searchHeading').style.display = 'flex';
            document.getElementById('searchKeyword').innerText = '"' + query + '"';
            document.getElementById('searchCount').innerText = '(Đang tải...)';
            document.getElementById('ytLoading').style.display = 'block';
            document.getElementById('mainVideoGrid').style.display = 'none';

            // 1. Primary API: Fast direct yt_search.php (proven on production)
            fetch('/tkb/api/yt_search.php?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(results => {
                    if (Array.isArray(results) && results.length > 0) {
                        displaySearchResults(results, query);
                    } else {
                        // 2. Secondary API fallback: ?api=search
                        fetchFallbackSamePage(query);
                    }
                })
                .catch(err => {
                    fetchFallbackSamePage(query);
                });
        }

        function fetchFallbackSamePage(query) {
            fetch('?api=search&q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    if (data && data.results && data.results.length > 0) {
                        displaySearchResults(data.results, query);
                    } else {
                        fallbackLocal(query);
                    }
                })
                .catch(() => fallbackLocal(query));
        }

        function displaySearchResults(results, query) {
            document.getElementById('ytLoading').style.display = 'none';
            document.getElementById('mainVideoGrid').style.display = 'grid';
            document.getElementById('searchCount').innerText = `(${results.length} video tìm thấy)`;
            currentVideosList = results;
            renderVideoGrid(results);
        }

        function fallbackLocal(query) {
            document.getElementById('ytLoading').style.display = 'none';
            document.getElementById('mainVideoGrid').style.display = 'grid';
            const lower = query.toLowerCase();
            const filtered = initialVideos.filter(v => v.title.toLowerCase().includes(lower) || v.channel.toLowerCase().includes(lower));
            document.getElementById('searchCount').innerText = `(${filtered.length} kết quả)`;
            currentVideosList = filtered;
            renderVideoGrid(filtered);
        }

        // Reset to initial library
        function resetToAll() {
            mainSearchInput.value = '';
            clearBtn.style.display = 'none';
            document.getElementById('searchHeading').style.display = 'none';
            document.querySelectorAll('.yt-chip').forEach(c => c.classList.remove('active'));
            document.querySelector('.yt-chip')?.classList.add('active');
            currentVideosList = [...initialVideos];
            renderVideoGrid(currentVideosList);
            showFeedView();
        }

        // Search by category chip
        function searchByChip(keyword, btn) {
            document.querySelectorAll('.yt-chip').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');

            if (keyword === 'Tất cả') {
                resetToAll();
                return;
            }

            mainSearchInput.value = keyword;
            clearBtn.style.display = 'block';
            executeYtSearch(keyword);
        }

        // YouTube Autocomplete Suggestions via JSONP
        function fetchSuggestions(query) {
            if (!query || query.trim().length === 0) {
                hideSuggestions();
                return;
            }
            const oldScript = document.getElementById('yt-suggest-script');
            if (oldScript) oldScript.remove();

            window.handleYtSuggest = function(data) {
                if (data && data[1] && data[1].length > 0) {
                    renderSuggestions(data[1].map(item => Array.isArray(item) ? item[0] : item));
                } else {
                    hideSuggestions();
                }
            };

            const script = document.createElement('script');
            script.id = 'yt-suggest-script';
            script.src = 'https://suggestqueries.google.com/complete/search?client=youtube&ds=yt&q=' + encodeURIComponent(query) + '&callback=handleYtSuggest';
            document.body.appendChild(script);
        }

        function renderSuggestions(suggestions) {
            suggestDropdown.innerHTML = '';
            suggestions.slice(0, 8).forEach((sug, idx) => {
                const item = document.createElement('div');
                item.className = 'yt-suggest-item';
                item.innerHTML = `<i class="fa-solid fa-magnifying-glass"></i> <span>${escapeHtml(sug)}</span>`;
                item.onclick = () => {
                    mainSearchInput.value = sug;
                    hideSuggestions();
                    executeYtSearch(sug);
                };
                suggestDropdown.appendChild(item);
            });
            suggestDropdown.style.display = 'block';
        }

        function hideSuggestions() {
            suggestDropdown.style.display = 'none';
        }

        // Search input event listeners
        mainSearchInput.addEventListener('input', (e) => {
            const val = e.target.value;
            clearBtn.style.display = val ? 'block' : 'none';
            clearTimeout(suggestTimeout);
            if (val.trim()) {
                suggestTimeout = setTimeout(() => fetchSuggestions(val), 200);
            } else {
                hideSuggestions();
            }
        });

        mainSearchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                executeYtSearch();
            } else if (e.key === 'Escape') {
                hideSuggestions();
            }
        });

        searchBtn.addEventListener('click', () => executeYtSearch());

        clearBtn.addEventListener('click', () => {
            mainSearchInput.value = '';
            clearBtn.style.display = 'none';
            hideSuggestions();
            mainSearchInput.focus();
        });

        // Close suggestions dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.yt-search-container')) {
                hideSuggestions();
            }
        });

        // Voice Search using Web Speech API
        micBtn.addEventListener('click', () => {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                showToast('Trình duyệt của bạn chưa hỗ trợ giọng nói. Vui lòng nhập từ khóa tìm kiếm.');
                return;
            }
            const recognition = new SpeechRecognition();
            recognition.lang = 'vi-VN';
            recognition.continuous = false;

            recognition.onstart = () => {
                micBtn.classList.add('recording');
                showToast('Đang lắng nghe giọng nói...');
            };
            recognition.onresult = (e) => {
                const text = e.results[0][0].transcript;
                mainSearchInput.value = text;
                clearBtn.style.display = 'block';
                showToast('Đã nhận diện: "' + text + '"');
                executeYtSearch(text);
            };
            recognition.onerror = (e) => {
                micBtn.classList.remove('recording');
                showToast('Không nhận diện được giọng nói. Hãy thử lại.');
            };
            recognition.onend = () => {
                micBtn.classList.remove('recording');
            };
            recognition.start();
        });

        // Like / Dislike / Subscribe actions
        function toggleLike() {
            const btn = document.getElementById('likeBtn');
            const dbtn = document.getElementById('dislikeBtn');
            btn.classList.toggle('active');
            dbtn.classList.remove('active');
            if (btn.classList.contains('active')) {
                showToast('Đã thêm vào danh sách video bạn thích!');
            }
        }

        function toggleDislike() {
            const btn = document.getElementById('dislikeBtn');
            const lbtn = document.getElementById('likeBtn');
            btn.classList.toggle('active');
            lbtn.classList.remove('active');
        }

        function toggleSubscribe() {
            const btn = document.getElementById('subBtn');
            if (btn.innerText === 'Đăng ký') {
                btn.innerText = 'Đã đăng ký ✓';
                btn.style.background = '#f1f5f9';
                btn.style.color = '#0f172a';
                btn.style.border = '1px solid #e2e8f0';
                showToast('Đã đăng ký kênh thành công!');
            } else {
                btn.innerText = 'Đăng ký';
                btn.style.background = '#0f172a';
                btn.style.color = '#ffffff';
                btn.style.border = 'none';
                showToast('Đã hủy đăng ký kênh.');
            }
        }

        // Copy URL
        function copyCurrentVideoUrl() {
            if (currentWatchVideo) {
                const url = 'https://www.youtube.com/watch?v=' + currentWatchVideo.yt_id;
                navigator.clipboard.writeText(url);
                showToast('Đã sao chép liên kết video: ' + url);
            }
        }

        // Quick Save Watch Video
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

        // Comments System with localStorage Persistence
        function loadComments(ytId) {
            const list = document.getElementById('commentsList');
            list.innerHTML = '';
            
            // Saved user comments
            const stored = JSON.parse(localStorage.getItem('yt_comments_' + ytId) || '[]');
            const defaultComments = [
                { author: 'Nobita', text: 'Video giảng rất dễ hiểu và hữu ích, cảm ơn thầy cô ạ! 👏', time: '2 giờ trước', av: 'https://ui-avatars.com/api/?name=Nobita&background=2563eb&color=fff' },
                { author: 'Doraemon ✓', text: 'Các bạn sinh viên nhớ ghi chép và thực hành đầy đủ nhé! 🔔', time: '1 ngày trước', av: 'https://ui-avatars.com/api/?name=Doraemon&background=0284c7&color=fff' }
            ];

            const allComments = [...stored, ...defaultComments];
            document.getElementById('commentCounter').innerText = allComments.length + ' bình luận';

            allComments.forEach(c => {
                const item = document.createElement('div');
                item.className = 'yt-comment-item';
                item.style.display = 'flex';
                item.style.gap = '12px';
                item.style.marginBottom = '18px';
                item.innerHTML = `
                    <img src="${c.av}" style="width: 38px; height: 38px; border-radius: 50%;" alt="${escapeHtml(c.author)}">
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:3px;">
                            ${escapeHtml(c.author)} &bull; <span style="font-weight:normal; font-size:11.5px; color:#64748b;">${c.time}</span>
                        </div>
                        <div style="font-size:13.5px; color:#334155; line-height:1.45;">${escapeHtml(c.text)}</div>
                    </div>
                `;
                list.appendChild(item);
            });
        }

        function postComment() {
            const input = document.getElementById('newCommentInput');
            const val = input.value.trim();
            if (!val || !currentWatchVideo) return;

            const ytId = currentWatchVideo.yt_id;
            const stored = JSON.parse(localStorage.getItem('yt_comments_' + ytId) || '[]');
            stored.unshift({
                author: 'Quản Trị Viên (Admin)',
                text: val,
                time: 'Vừa xong',
                av: '/tkb/assets/img/avatar_khanh.png'
            });
            localStorage.setItem('yt_comments_' + ytId, JSON.stringify(stored));

            input.value = '';
            loadComments(ytId);
            showToast('Đã gửi bình luận của bạn!');
        }

        // Toast Helper
        function showToast(msg) {
            const t = document.getElementById('ytToast');
            document.getElementById('ytToastMsg').innerText = msg;
            t.classList.add('show');
            clearTimeout(window.toastTimer);
            window.toastTimer = setTimeout(() => {
                t.classList.remove('show');
            }, 3000);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        // Expose functions to window for inline onclick handlers
        window.searchByChip = searchByChip;
        window.executeYtSearch = executeYtSearch;
        window.openWatchPage = openWatchPage;
        window.showFeedView = showFeedView;
        window.toggleTheaterMode = toggleTheaterMode;
        window.toggleLike = toggleLike;
        window.toggleDislike = toggleDislike;
        window.toggleSubscribe = toggleSubscribe;
        window.copyCurrentVideoUrl = copyCurrentVideoUrl;
        window.quickSaveWatchVideoToSubject = quickSaveWatchVideoToSubject;
        window.openAddVideoModal = openAddVideoModal;
        window.closeAddVideoModal = closeAddVideoModal;
        window.postComment = postComment;
        window.resetToAll = resetToAll;

        // Initialize Feed & check URL query params
        function initYtApp() {
            const urlParams = new URLSearchParams(window.location.search);
            const q = urlParams.get('q');
            const v = urlParams.get('v');
            if (v) {
                const found = currentVideosList.find(x => x.yt_id === v);
                if (found) {
                    openWatchPage(found, false);
                } else {
                    openWatchPage({
                        id: 'yt_' + v,
                        yt_id: v,
                        title: 'Video YouTube (' + v + ')',
                        channel: 'YouTube Video',
                        avatar: 'https://ui-avatars.com/api/?name=YT&background=ef4444&color=fff',
                        views: 'Trực tiếp',
                        time: 'Mới nhất',
                        duration: 'Video',
                        desc: 'Video phát từ liên kết hoặc mã ID YouTube: ' + v,
                        subject: 'YouTube'
                    }, false);
                }
            } else if (q) {
                mainSearchInput.value = q;
                clearBtn.style.display = 'block';
                executeYtSearch(q);
            } else {
                renderVideoGrid(currentVideosList);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initYtApp);
        } else {
            initYtApp();
        }
    </script>
</div><!-- .main-content -->
</body>
</html>
