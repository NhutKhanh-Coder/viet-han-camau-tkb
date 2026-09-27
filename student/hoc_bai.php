<?php
require_once '../config.php';
requireStudent();
$db = getDB();
$sv_id = $_SESSION['student_id'] ?? 0;

// Helper: Extract YouTube ID
function getYoutubeId($url) {
    if (empty($url)) return '';
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/=\s]{11})%i';
    if (preg_match($pattern, $url, $match)) {
        return $match[1];
    }
    return '';
}

// Helper: Extract first YouTube link from text
function extractFirstYoutubeUrl($text) {
    if (empty($text)) return '';
    if (preg_match('~(https?://(?:www\.)?(?:youtube\.com/(?:watch\?v=|embed/|v/|shorts/)|youtu\.be/)[\w\-]{11}[^\s<"]*)~i', $text, $match)) {
        return $match[1];
    }
    return '';
}

// Helper: Get File Type details (Extension, Icon, Color, Name)
function getDocTypeInfo($link, $title = '') {
    $yt = getYoutubeId($link);
    if ($yt) {
        return [
            'type' => 'youtube',
            'label' => 'Video YouTube',
            'icon' => 'fa-brands fa-youtube',
            'color' => '#ef4444',
            'bg' => 'rgba(239, 68, 68, 0.12)',
            'can_preview' => true
        ];
    }
    
    $clean_link = parse_url($link, PHP_URL_PATH);
    $ext = strtolower(pathinfo($clean_link, PATHINFO_EXTENSION));
    
    switch ($ext) {
        case 'docx':
        case 'doc':
            return [
                'type' => 'word',
                'ext' => $ext,
                'label' => 'Văn bản Word (.' . $ext . ')',
                'icon' => 'fa-solid fa-file-word',
                'color' => '#2563eb',
                'bg' => 'rgba(37, 99, 235, 0.12)',
                'can_preview' => true
            ];
        case 'pdf':
            return [
                'type' => 'pdf',
                'ext' => $ext,
                'label' => 'Tài liệu PDF',
                'icon' => 'fa-solid fa-file-pdf',
                'color' => '#dc2626',
                'bg' => 'rgba(220, 38, 38, 0.12)',
                'can_preview' => true
            ];
        case 'pptx':
        case 'ppt':
            return [
                'type' => 'powerpoint',
                'ext' => $ext,
                'label' => 'Bài thuyết trình PowerPoint',
                'icon' => 'fa-solid fa-file-powerpoint',
                'color' => '#ea580c',
                'bg' => 'rgba(234, 88, 12, 0.12)',
                'can_preview' => true
            ];
        case 'xlsx':
        case 'xls':
        case 'csv':
            return [
                'type' => 'excel',
                'ext' => $ext,
                'label' => 'Bảng tính Excel',
                'icon' => 'fa-solid fa-file-excel',
                'color' => '#16a34a',
                'bg' => 'rgba(22, 163, 74, 0.12)',
                'can_preview' => true
            ];
        case 'zip':
        case 'rar':
        case '7z':
            return [
                'type' => 'archive',
                'ext' => $ext,
                'label' => 'Tệp nén (' . strtoupper($ext) . ')',
                'icon' => 'fa-solid fa-file-zipper',
                'color' => '#9333ea',
                'bg' => 'rgba(147, 51, 234, 0.12)',
                'can_preview' => false
            ];
        default:
            return [
                'type' => 'file',
                'ext' => $ext ?: 'file',
                'label' => $ext ? strtoupper($ext) : 'Tài liệu học tập',
                'icon' => 'fa-solid fa-file-lines',
                'color' => '#e11d48',
                'bg' => 'rgba(225, 29, 72, 0.1)',
                'can_preview' => in_array($ext, ['png', 'jpg', 'jpeg', 'txt'])
            ];
    }
}

// Fetch student info
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $sv_id);
$stmt->execute();
$sv = $stmt->get_result()->fetch_assoc();
$khoa = $sv['khoa'] ?? '';

// Fetch all subjects available for this student (by department, schedule, or existing lessons/docs)
$stmt_mon = $db->prepare("
    SELECT DISTINCT m.id, m.ten_mon, m.ma_mon,
           (SELECT COUNT(*) FROM lessons l WHERE l.mon_hoc_id = m.id) as total_lessons,
           (SELECT COUNT(*) FROM tai_lieu d WHERE d.mon_hoc_id = m.id) as total_docs
    FROM mon_hoc m
    WHERE LOWER(m.khoa) = LOWER(?)
       OR m.id IN (SELECT mon_hoc_id FROM thoi_khoa_bieu WHERE LOWER(khoa) = LOWER(?))
       OR m.id IN (SELECT DISTINCT mon_hoc_id FROM lessons)
       OR m.id IN (SELECT DISTINCT mon_hoc_id FROM tai_lieu)
    ORDER BY m.id ASC
");
$stmt_mon->bind_param("ss", $khoa, $khoa);
$stmt_mon->execute();
$subjects = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);

$total_all_lessons = 0;
$total_all_docs = 0;
foreach ($subjects as $s) {
    $total_all_lessons += (int)$s['total_lessons'];
    $total_all_docs += (int)$s['total_docs'];
}

// Subject filter parameter ('all' or subject ID)
$selected_param = $_GET['mon_hoc_id'] ?? 'all';
$selected_mon_id = $selected_param;
$filter_type = $_GET['type'] ?? 'all'; // all, docs, lessons

// Query Lessons & Documents based on selection
$lessons = [];
$documents = [];

if ($selected_mon_id === 'all') {
    // Fetch all lessons
    $res_less = $db->query("
        SELECT l.*, COALESCE(NULLIF(g.ho_ten, ''), 'Phan Ngọc Tuyến') as gv_name, m.ten_mon
        FROM lessons l
        LEFT JOIN giang_vien g ON l.giang_vien_id = g.id
        LEFT JOIN mon_hoc m ON l.mon_hoc_id = m.id
        ORDER BY l.mon_hoc_id ASC, l.id ASC
    ");
    $lessons = $res_less ? $res_less->fetch_all(MYSQLI_ASSOC) : [];

    // Fetch all documents
    $res_doc = $db->query("
        SELECT d.*, COALESCE(NULLIF(g.ho_ten, ''), 'Phan Ngọc Tuyến') as gv_name, m.ten_mon
        FROM tai_lieu d
        LEFT JOIN giang_vien g ON d.giang_vien_id = g.id
        LEFT JOIN mon_hoc m ON d.mon_hoc_id = m.id
        ORDER BY d.id DESC
    ");
    $documents = $res_doc ? $res_doc->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $mid = (int)$selected_mon_id;
    // Fetch lessons for selected subject
    $stmt_l = $db->prepare("
        SELECT l.*, COALESCE(NULLIF(g.ho_ten, ''), 'Phan Ngọc Tuyến') as gv_name, m.ten_mon
        FROM lessons l
        LEFT JOIN giang_vien g ON l.giang_vien_id = g.id
        LEFT JOIN mon_hoc m ON l.mon_hoc_id = m.id
        WHERE l.mon_hoc_id = ?
        ORDER BY l.id ASC
    ");
    $stmt_l->bind_param("i", $mid);
    $stmt_l->execute();
    $lessons = $stmt_l->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch documents for selected subject
    $stmt_d = $db->prepare("
        SELECT d.*, COALESCE(NULLIF(g.ho_ten, ''), 'Phan Ngọc Tuyến') as gv_name, m.ten_mon
        FROM tai_lieu d
        LEFT JOIN giang_vien g ON d.giang_vien_id = g.id
        LEFT JOIN mon_hoc m ON d.mon_hoc_id = m.id
        WHERE d.mon_hoc_id = ?
        ORDER BY d.id DESC
    ");
    $stmt_d->bind_param("i", $mid);
    $stmt_d->execute();
    $documents = $stmt_d->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Find subject title
$current_mon_name = "Tất cả môn học";
if ($selected_mon_id !== 'all') {
    foreach ($subjects as $sub) {
        if ($sub['id'] == $selected_mon_id) {
            $current_mon_name = $sub['ten_mon'];
            break;
        }
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài liệu &amp; Bài học - Cổng sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        :root {
            --doc-accent: var(--accent, #e11d48);
        }
        .subject-sidebar {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .subject-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: var(--bg2, #ffffff);
            border: 1.5px solid var(--border, #f1f5f9);
            border-radius: 14px;
            text-decoration: none;
            color: var(--text, #1e293b);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .subject-item:hover, .subject-item.active {
            border-color: var(--accent, #e11d48);
            background: rgba(225, 29, 72, 0.04);
            transform: translateX(4px);
            box-shadow: 0 4px 16px rgba(225, 29, 72, 0.08);
        }
        .subject-item.active {
            border-width: 2px;
        }
        .subject-badge {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            background: rgba(225, 29, 72, 0.08);
            color: var(--accent, #e11d48);
            margin-left: auto;
            white-space: nowrap;
        }
        
        /* Spotlight Card for latest materials */
        .spotlight-card {
            background: linear-gradient(135deg, rgba(225, 29, 72, 0.06), rgba(168, 85, 247, 0.08));
            border: 1.5px solid rgba(225, 29, 72, 0.25);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 8px 24px rgba(225, 29, 72, 0.05);
            animation: fadeIn 0.4s ease;
        }
        .spotlight-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 6px 16px rgba(225, 29, 72, 0.3);
            flex-shrink: 0;
        }
        .spotlight-body {
            flex: 1;
        }
        .spotlight-tag {
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--accent, #e11d48);
            display: inline-block;
            margin-bottom: 4px;
        }
        .spotlight-title {
            font-size: 16.5px;
            font-weight: 800;
            color: var(--text, #1e293b);
            margin: 0 0 4px 0;
        }
        .spotlight-meta {
            font-size: 12px;
            color: var(--text2, #64748b);
            margin: 0;
        }
        .spotlight-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }
        .btn-spotlight-view {
            background: var(--accent, #e11d48);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
        }
        .btn-spotlight-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(225, 29, 72, 0.35);
        }
        .btn-spotlight-dl {
            background: #ffffff;
            color: var(--text, #1e293b);
            border: 1.5px solid var(--border, #cbd5e1);
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-spotlight-dl:hover {
            border-color: var(--accent, #e11d48);
            color: var(--accent, #e11d48);
            transform: translateY(-2px);
        }

        /* Document Item Cards */
        .doc-card {
            background: var(--bg2, #ffffff);
            border: 1.5px solid var(--border, #f1f5f9);
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            transition: all 0.25s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .doc-card:hover {
            border-color: var(--accent, #e11d48);
            box-shadow: 0 6px 20px rgba(0,0,0,0.06);
            transform: translateY(-2px);
        }
        .doc-type-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .doc-card:hover .doc-type-icon {
            transform: scale(1.08);
        }

        /* Lesson Item Cards */
        .lesson-card {
            background: var(--bg2, #ffffff);
            border: 1.5px solid var(--border, #f1f5f9);
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 14px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .lesson-card:hover {
            border-color: var(--accent, #e11d48);
            box-shadow: 0 6px 20px rgba(225, 29, 72, 0.08);
            transform: translateY(-2px);
        }

        /* Navigation Filter Pills */
        .filter-pills {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-pill {
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            color: var(--text2, #64748b);
            background: var(--bg2, #ffffff);
            border: 1.5px solid var(--border, #e2e8f0);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .filter-pill:hover, .filter-pill.active {
            color: #ffffff;
            background: var(--accent, #e11d48);
            border-color: var(--accent, #e11d48);
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.2);
        }

        .badge-type {
            font-size: 11px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Video modal iframe wrapper */
        .video-responsive-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            background: #000;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }
        .video-responsive-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        /* Fullscreen Doc Preview Modal */
        .doc-preview-frame {
            width: 100%;
            height: 72vh;
            border: none;
            border-radius: 10px;
            background: #f8fafc;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 900px) {
            .page-layout-grid {
                grid-template-columns: 1fr !important;
            }
            .spotlight-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .spotlight-actions {
                width: 100%;
            }
            .btn-spotlight-view, .btn-spotlight-dl {
                flex: 1;
                justify-content: center;
            }
            .doc-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .doc-card-actions {
                width: 100%;
                display: flex;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/student_nav.php'; ?>

    <!-- Page Header -->
    <div class="page-header" style="margin-bottom: 25px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:15px;">
            <div>
                <h1 class="page-title" style="font-size: 24px; font-weight: 900; margin: 0; display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid fa-folder-open" style="color:var(--accent);"></i> Tài Liệu Học Tập &amp; Bài Giảng
                </h1>
                <p style="color: var(--text2); margin: 6px 0 0 0; font-size: 13.5px;">
                    Xem tài liệu, giáo trình, file bài giảng do Giảng viên chia sẻ và học trực tuyến các bài học có video hướng dẫn
                </p>
            </div>
            <div style="display:flex; gap:10px;">
                <span style="font-size:12px; background:rgba(225,29,72,0.08); color:var(--accent); font-weight:800; padding:6px 14px; border-radius:20px; border:1px solid rgba(225,29,72,0.2);">
                    <i class="fa-solid fa-graduation-cap"></i> Khoa: <?= htmlspecialchars($khoa ?: 'CNTT') ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Spotlight Banner if latest document exists -->
    <?php if (!empty($documents)): 
        $first_doc = $documents[0];
        $f_info = getDocTypeInfo($first_doc['link_download'], $first_doc['ten_tai_lieu']);
        $f_link = $first_doc['link_download'];
        if (!empty($f_link) && $f_link !== '#' && !preg_match("~^(?:f|ht)tps?://~i", $f_link) && !str_starts_with($f_link, '/')) {
            $f_link = "https://" . $f_link;
        }
    ?>
    <div class="spotlight-card">
        <div class="spotlight-icon">
            <i class="<?= $f_info['icon'] ?>"></i>
        </div>
        <div class="spotlight-body">
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="spotlight-tag"><i class="fa-solid fa-sparkles"></i> Tài Liệu Mới Nhất Từ Giảng Viên</span>
                <span style="font-size:10.5px; background:#e11d48; color:#fff; font-weight:800; padding:2px 7px; border-radius:4px;">MỚI</span>
            </div>
            <h3 class="spotlight-title"><?= htmlspecialchars($first_doc['ten_tai_lieu']) ?></h3>
            <p class="spotlight-meta">
                <i class="fa-solid fa-user-tie" style="color:var(--accent);"></i> Giảng viên: <b><?= htmlspecialchars($first_doc['gv_name'] ?? 'Phan Ngọc Tuyến') ?></b> &bull; 
                <i class="fa-solid fa-book" style="color:var(--accent);"></i> Môn học: <b><?= htmlspecialchars($first_doc['ten_mon'] ?? 'Chung') ?></b> &bull; 
                <i class="fa-regular fa-clock"></i> Ngày chia sẻ: <b><?= date('d/m/Y', strtotime($first_doc['created_at'])) ?></b>
            </p>
        </div>
        <div class="spotlight-actions">
            <?php if ($f_info['type'] === 'youtube'): 
                $yt_id = getYoutubeId($f_link);
            ?>
                <button type="button" onclick="openYoutubeVideoModal('<?= $yt_id ?>', '<?= htmlspecialchars($first_doc['ten_tai_lieu'], ENT_QUOTES, 'UTF-8') ?>')" class="btn-spotlight-view">
                    <i class="fa-solid fa-play"></i> Xem Video
                </button>
            <?php else: ?>
                <button type="button" onclick="previewOnlineDoc('<?= htmlspecialchars($f_link, ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($first_doc['ten_tai_lieu'], ENT_QUOTES, 'UTF-8') ?>', '<?= $f_info['ext'] ?? 'docx' ?>')" class="btn-spotlight-view">
                    <i class="fa-solid fa-eye"></i> Đọc trực tuyến
                </button>
                <a href="<?= htmlspecialchars($f_link) ?>" download class="btn-spotlight-dl" title="Tải tệp về máy tính / điện thoại">
                    <i class="fa-solid fa-download"></i> Tải về máy
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Grid Layout -->
    <div class="page-layout-grid" style="display: grid; grid-template-columns: 310px 1fr; gap: 28px; align-items: start;">
        
        <!-- Left Column: Subjects Sidebar -->
        <div class="subject-sidebar">
            <div class="card" style="padding: 18px; border-radius: 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1.5px solid var(--border);">
                    <span style="font-weight: 800; font-size: 14px; text-transform: uppercase; color: var(--text); letter-spacing: 0.5px;">
                        <i class="fa-solid fa-book-bookmark" style="color:var(--accent);"></i> Danh Mục Môn Học
                    </span>
                    <span style="font-size:11px; font-weight:700; color:var(--text2);"><?= count($subjects) ?> môn</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Option: Tất cả môn học -->
                    <a href="?mon_hoc_id=all&type=<?= $filter_type ?>" class="subject-item <?= $selected_mon_id === 'all' ? 'active' : '' ?>">
                        <div style="background: rgba(225, 29, 72, 0.1); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 17px; flex-shrink: 0;">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 800; font-size: 13.5px;">Tất cả môn học</div>
                            <div style="font-size: 11px; color: var(--text2); margin-top: 2px;">
                                <?= $total_all_docs ?> tài liệu &bull; <?= $total_all_lessons ?> bài
                            </div>
                        </div>
                    </a>

                    <!-- Subject Items -->
                    <?php if (empty($subjects)): ?>
                        <p style="text-align: center; color: var(--text2); font-size: 13px; padding: 15px 0;">Chưa có danh sách môn học.</p>
                    <?php else: foreach ($subjects as $sub): ?>
                        <a href="?mon_hoc_id=<?= $sub['id'] ?>&type=<?= $filter_type ?>" class="subject-item <?= (string)$selected_mon_id === (string)$sub['id'] ? 'active' : '' ?>">
                            <div style="background: rgba(14, 165, 233, 0.1); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #0284c7; font-size: 17px; flex-shrink: 0;">
                                <i class="fa-solid fa-book"></i>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 700; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($sub['ten_mon']) ?>">
                                    <?= htmlspecialchars($sub['ten_mon']) ?>
                                </div>
                                <div style="font-size: 10.5px; color: var(--text2); margin-top: 2px;">
                                    Mã: <?= htmlspecialchars($sub['ma_mon']) ?> &bull; <?= (int)$sub['total_docs'] ?> tài liệu
                                </div>
                            </div>
                            <?php if ($sub['total_docs'] > 0): ?>
                                <span class="subject-badge"><?= $sub['total_docs'] ?> tệp</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Quick Tips Box -->
            <div class="card" style="padding: 18px; border-radius: 16px; background: rgba(225, 29, 72, 0.02);">
                <div style="font-weight: 800; font-size: 13px; color: var(--accent); margin-bottom: 8px; display:flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-circle-info"></i> Hướng Dẫn Xem Tài Liệu
                </div>
                <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: var(--text2); line-height: 1.6;">
                    <li>Bấm <b>"Đọc trực tuyến"</b> để xem nhanh tài liệu Word / PDF ngay trên web.</li>
                    <li>Bấm <b>"Tải về máy"</b> để lưu tệp gốc về máy tính hoặc điện thoại.</li>
                    <li>Các bài giảng video có thể xem lại bất cứ lúc nào để ôn thi.</li>
                </ul>
            </div>
        </div>

        <!-- Right Column: Materials & Lessons -->
        <div>
            <!-- Filter Pills (All / Tài liệu / Bài giảng) -->
            <div class="filter-pills">
                <a href="?mon_hoc_id=<?= $selected_mon_id ?>&type=all" class="filter-pill <?= $filter_type === 'all' ? 'active' : '' ?>">
                    <i class="fa-solid fa-border-all"></i> Tất cả (<?= count($documents) + count($lessons) ?>)
                </a>
                <a href="?mon_hoc_id=<?= $selected_mon_id ?>&type=docs" class="filter-pill <?= $filter_type === 'docs' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-lines"></i> Tài liệu từ Giáo viên (<?= count($documents) ?>)
                </a>
                <a href="?mon_hoc_id=<?= $selected_mon_id ?>&type=lessons" class="filter-pill <?= $filter_type === 'lessons' ? 'active' : '' ?>">
                    <i class="fa-solid fa-video"></i> Bài giảng Video (<?= count($lessons) ?>)
                </a>
            </div>

            <!-- SECTION 1: DOCUMENTS & FILES FROM TEACHERS -->
            <?php if ($filter_type === 'all' || $filter_type === 'docs'): ?>
            <div style="margin-bottom: 35px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px; border-left: 4px solid var(--accent); padding-left: 12px;">
                    <div>
                        <h2 style="font-size: 18px; font-weight: 800; color: var(--text); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-folder-tree" style="color:var(--accent);"></i> Tài Liệu Học Tập Do Giảng Viên Gửi
                        </h2>
                        <span style="font-size: 12px; color: var(--text2);">
                            Học phần: <b><?= htmlspecialchars($current_mon_name) ?></b> &bull; <?= count($documents) ?> học liệu sẵn sàng
                        </span>
                    </div>
                </div>

                <?php if (empty($documents)): ?>
                    <div class="card" style="padding: 40px; text-align: center; color: var(--text2); border-radius: 14px;">
                        <i class="fa-solid fa-folder-open" style="font-size: 36px; color: var(--accent); margin-bottom: 12px; display: block; opacity: 0.8;"></i>
                        <span style="font-weight: 700; font-size: 15px; color: var(--text);">Chưa có tài liệu học tập nào cho môn này</span>
                        <p style="margin: 6px 0 0 0; font-size: 12.5px;">Giảng viên sẽ tải lên tài liệu tham khảo, slide và giáo trình trong thời gian tới.</p>
                    </div>
                <?php else: foreach ($documents as $doc): 
                    $doc_link = $doc['link_download'] ?? '#';
                    if (!empty($doc_link) && $doc_link !== '#' && !preg_match("~^(?:f|ht)tps?://~i", $doc_link) && !str_starts_with($doc_link, '/')) {
                        $doc_link = "https://" . $doc_link;
                    }
                    $doc_info = getDocTypeInfo($doc_link, $doc['ten_tai_lieu']);
                    $yt_id = getYoutubeId($doc_link);
                ?>
                    <div class="doc-card">
                        <div style="display: flex; align-items: center; gap: 16px; min-width: 0; flex: 1;">
                            <div class="doc-type-icon" style="background: <?= $doc_info['bg'] ?>; color: <?= $doc_info['color'] ?>;">
                                <i class="<?= $doc_info['icon'] ?>"></i>
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                                    <h4 style="font-size: 15px; font-weight: 800; color: var(--text); margin: 0; word-break: break-word;">
                                        <?= htmlspecialchars($doc['ten_tai_lieu']) ?>
                                    </h4>
                                    <span class="badge-type" style="background: <?= $doc_info['bg'] ?>; color: <?= $doc_info['color'] ?>; border: 1px solid <?= $doc_info['color'] ?>33;">
                                        <?= $doc_info['label'] ?>
                                    </span>
                                </div>
                                <div style="font-size: 12px; color: var(--text2); display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                                    <span><i class="fa-solid fa-user-tie" style="color:var(--accent);"></i> GV: <b><?= htmlspecialchars($doc['gv_name'] ?? 'Phan Ngọc Tuyến') ?></b></span>
                                    <span><i class="fa-solid fa-book" style="color:var(--accent);"></i> Môn: <b><?= htmlspecialchars($doc['ten_mon'] ?? 'Chung') ?></b></span>
                                    <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="doc-card-actions" style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                            <?php if ($yt_id): ?>
                                <button type="button" onclick="openYoutubeVideoModal('<?= $yt_id ?>', '<?= htmlspecialchars($doc['ten_tai_lieu'], ENT_QUOTES, 'UTF-8') ?>')" class="btn-spotlight-view" style="padding: 8px 14px; font-size: 12px;">
                                    <i class="fa-solid fa-play"></i> Xem Video
                                </button>
                                <a href="<?= htmlspecialchars($doc_link) ?>" target="_blank" class="btn-spotlight-dl" style="padding: 8px 12px;" title="Mở trên YouTube">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            <?php else: ?>
                                <button type="button" onclick="previewOnlineDoc('<?= htmlspecialchars($doc_link, ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($doc['ten_tai_lieu'], ENT_QUOTES, 'UTF-8') ?>', '<?= $doc_info['ext'] ?? 'docx' ?>')" class="btn-spotlight-view" style="padding: 8px 14px; font-size: 12px; background: <?= $doc_info['color'] ?>;">
                                    <i class="fa-solid fa-eye"></i> Đọc trực tuyến
                                </button>
                                <a href="<?= htmlspecialchars($doc_link) ?>" download class="btn-spotlight-dl" style="padding: 8px 14px; font-size: 12px;" title="Tải tệp về máy tính">
                                    <i class="fa-solid fa-cloud-arrow-down"></i> Tải về máy
                                </a>
                            <?php endif; ?>
                            <button type="button" onclick="copyLinkToClipboard('<?= htmlspecialchars($doc_link, ENT_QUOTES, 'UTF-8') ?>')" class="btn-spotlight-dl" style="padding: 8px 10px;" title="Sao chép liên kết">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <?php endif; ?>


            <!-- SECTION 2: ONLINE VIDEO LESSONS -->
            <?php if ($filter_type === 'all' || $filter_type === 'lessons'): ?>
            <div style="margin-bottom: 30px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px; border-left: 4px solid var(--accent); padding-left: 12px;">
                    <div>
                        <h2 style="font-size: 18px; font-weight: 800; color: var(--text); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-circle-play" style="color:var(--accent);"></i> Bài Giảng Trực Tuyến &amp; Video Lý Thuyết
                        </h2>
                        <span style="font-size: 12px; color: var(--text2);">
                            Học phần: <b><?= htmlspecialchars($current_mon_name) ?></b> &bull; <?= count($lessons) ?> bài học lý thuyết
                        </span>
                    </div>
                </div>

                <?php if (empty($lessons)): ?>
                    <div class="card" style="padding: 40px; text-align: center; color: var(--text2); border-radius: 14px;">
                        <i class="fa-solid fa-chalkboard-user" style="font-size: 36px; color: var(--accent); margin-bottom: 12px; display: block; opacity: 0.8;"></i>
                        <span style="font-weight: 700; font-size: 15px; color: var(--text);">Giảng viên chưa biên soạn bài giảng trực tuyến cho môn học này</span>
                        <p style="margin: 6px 0 0 0; font-size: 12.5px;">Vui lòng quay lại sau hoặc xem các tài liệu đính kèm bên trên.</p>
                    </div>
                <?php else: foreach ($lessons as $index => $less): 
                    $v_url = $less['video_url'] ?? '';
                    $yt_id = getYoutubeId($v_url);
                    if (!$yt_id) {
                        $extracted_url = extractFirstYoutubeUrl($less['noi_dung'] ?? '');
                        $yt_id = getYoutubeId($extracted_url);
                    }
                ?>
                    <div class="lesson-card" 
                        data-title="<?= htmlspecialchars($less['tieu_de'], ENT_QUOTES, 'UTF-8') ?>" 
                        data-content="<?= htmlspecialchars($less['noi_dung'], ENT_QUOTES, 'UTF-8') ?>" 
                        data-video="<?= htmlspecialchars($yt_id, ENT_QUOTES, 'UTF-8') ?>"
                        data-gv="<?= htmlspecialchars($less['gv_name'] ?? 'Phan Ngọc Tuyến', ENT_QUOTES, 'UTF-8') ?>"
                        data-mon="<?= htmlspecialchars($less['ten_mon'] ?? 'Lập Trình Web', ENT_QUOTES, 'UTF-8') ?>"
                        data-date="<?= date('d/m/Y H:i', strtotime($less['created_at'])) ?>"
                        onclick="showFullLessonModal(this.getAttribute('data-title'), this.getAttribute('data-content'), this.getAttribute('data-video'), this.getAttribute('data-gv'), this.getAttribute('data-date'), this.getAttribute('data-mon'))">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                                    <span style="font-size: 11px; background: rgba(225,29,72,0.1); color: var(--accent); font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase;">
                                        Bài <?= $index + 1 ?>
                                    </span>
                                    <span style="font-size: 11px; color: var(--text2); font-weight: 600;">
                                        <i class="fa-solid fa-book"></i> <?= htmlspecialchars($less['ten_mon'] ?? '') ?>
                                    </span>
                                    <?php if ($yt_id): ?>
                                        <span class="badge-type" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25);">
                                            <i class="fa-brands fa-youtube"></i> Có Video bài giảng
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 style="font-size: 15.5px; font-weight: 800; color: var(--text); margin: 0 0 6px 0;">
                                    <?= htmlspecialchars($less['tieu_de']) ?>
                                </h3>
                                <div style="font-size: 12px; color: var(--text2); display:flex; gap:16px; flex-wrap:wrap;">
                                    <span><i class="fa-solid fa-user-tie" style="color:var(--accent);"></i> Giảng viên: <b><?= htmlspecialchars($less['gv_name'] ?? 'Phan Ngọc Tuyến') ?></b></span>
                                    <span><i class="fa-regular fa-clock"></i> Đăng ngày: <?= date('d/m/Y', strtotime($less['created_at'])) ?></span>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:12px; flex-shrink: 0;">
                                <?php if ($yt_id): ?>
                                    <div style="background: rgba(239, 68, 68, 0.12); color: #ef4444; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="fa-solid fa-play"></i>
                                    </div>
                                <?php endif; ?>
                                <div style="color: var(--accent); font-size: 18px;">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- DOCUMENT PREVIEW MODAL (Online Word, PDF, Office Viewer) -->
    <div class="modal-overlay" id="docPreviewModal" onclick="if(event.target==this) toggleModal('docPreviewModal')" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center; padding:15px;">
        <div class="modal-box" style="width: 1000px; max-width: 96vw; background:var(--bg2, #ffffff); border-radius:18px; box-shadow:0 20px 60px rgba(0,0,0,0.3); border:1.5px solid var(--border); overflow:hidden; display:flex; flex-direction:column; max-height:92vh;">
            
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 22px; border-bottom:1.5px solid var(--border); background:var(--bg3, #f8fafc);">
                <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                    <div id="p_doc_icon" style="width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#2563eb; background:rgba(37,99,235,0.1); flex-shrink:0;">
                        <i class="fa-solid fa-file-word"></i>
                    </div>
                    <div style="min-width:0;">
                        <h3 id="p_doc_title" style="margin:0; font-size:16px; font-weight:800; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:600px;">
                            Xem tài liệu trực tuyến
                        </h3>
                        <div id="p_doc_subtitle" style="font-size:11.5px; color:var(--text2); margin-top:2px;">
                            Trình đọc tài liệu trực tuyến
                        </div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a id="p_doc_download_btn" href="#" download class="btn-spotlight-dl" style="padding: 7px 14px; font-size: 12px;">
                        <i class="fa-solid fa-download"></i> Tải về máy
                    </a>
                    <button onclick="toggleModal('docPreviewModal')" style="background:none; border:none; color:var(--text2); font-size:22px; cursor:pointer; width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Preview Frame Area -->
            <div style="padding:15px; flex:1; overflow:hidden; display:flex; flex-direction:column;">
                <iframe id="p_doc_iframe" class="doc-preview-frame" src="about:blank"></iframe>
                <div style="margin-top:10px; font-size:11.5px; color:var(--text2); text-align:center; display:flex; justify-content:center; gap:15px; align-items:center;">
                    <span><i class="fa-solid fa-shield-check" style="color:#10b981;"></i> Trình xem tệp Microsoft Office / Google Viewer an toàn</span>
                    <a id="p_doc_fallback_link" href="#" target="_blank" style="color:var(--accent); text-decoration:underline;">Mở trong tab mới</a>
                </div>
            </div>
        </div>
    </div>

    <!-- LESSON VIEWER MODAL -->
    <div class="modal-overlay" id="lessonModal" onclick="if(event.target==this) toggleModal('lessonModal')" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center; padding:15px;">
        <div class="modal-box" style="width: 850px; max-width: 95vw; background:var(--bg2, #ffffff); border-radius:18px; box-shadow:0 20px 60px rgba(0,0,0,0.3); border:1.5px solid var(--border); overflow:hidden; max-height:92vh; display:flex; flex-direction:column;">
            
            <div style="display:flex; justify-content:space-between; align-items:center; padding: 16px 22px; border-bottom: 1.5px solid var(--border); background:var(--bg3, #f8fafc);">
                <div>
                    <h3 class="modal-title" id="m_lesson_title" style="margin:0; font-size:17px; font-weight:800; color:var(--text);">Chi tiết bài học</h3>
                    <div id="m_lesson_meta" style="font-size:12px; color:var(--text2); margin-top:4px;"></div>
                </div>
                <button onclick="toggleModal('lessonModal')" style="background:none; border:none; color:var(--text2); font-size:22px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div style="padding: 20px; overflow-y:auto; flex:1;">
                <!-- YouTube Video player container in modal -->
                <div id="m_lesson_video_box" style="display:none; margin-bottom: 20px;">
                    <div class="video-responsive-wrapper" id="m_lesson_video_iframe"></div>
                </div>

                <div style="background:var(--bg3, #f8fafc); border-radius:12px; padding:18px; border:1.5px solid var(--border);">
                    <div style="font-weight:800; font-size:13px; color:var(--accent); margin-bottom:10px; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-book-open"></i> Nội dung lý thuyết bài học:
                    </div>
                    <div id="m_lesson_content" style="font-size:14.5px; color:var(--text); line-height:1.8; white-space:pre-wrap;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- STANDALONE YOUTUBE MODAL -->
    <div class="modal-overlay" id="videoModal" onclick="if(event.target==this) toggleModal('videoModal')" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center; padding:15px;">
        <div class="modal-box" style="width: 850px; max-width: 95vw; background:var(--bg2, #ffffff); border-radius:18px; box-shadow:0 20px 60px rgba(0,0,0,0.3); border:1.5px solid var(--border); overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding: 16px 22px; border-bottom: 1.5px solid var(--border);">
                <h3 class="modal-title" id="v_modal_title" style="margin:0; font-size:16px; font-weight:800; display:flex; align-items:center; gap:8px; color:var(--text);">
                    <i class="fa-brands fa-youtube" style="color:#ef4444;"></i> <span>Xem Video Học Liệu</span>
                </h3>
                <button onclick="toggleModal('videoModal')" style="background:none; border:none; color:var(--text2); font-size:22px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div style="padding: 20px;">
                <div class="video-responsive-wrapper" id="v_modal_iframe_box"></div>
            </div>
        </div>
    </div>

    <!-- Close nav container -->
    </div></div>

    <script>
        function toggleModal(id) {
            const m = document.getElementById(id);
            if (m.style.display === 'flex') {
                m.style.display = 'none';
                if (id === 'lessonModal') {
                    document.getElementById('m_lesson_video_iframe').innerHTML = '';
                }
                if (id === 'videoModal') {
                    document.getElementById('v_modal_iframe_box').innerHTML = '';
                }
                if (id === 'docPreviewModal') {
                    document.getElementById('p_doc_iframe').src = 'about:blank';
                }
            } else {
                m.style.display = 'flex';
            }
        }

        // Preview Online Document via Office Online Viewer or PDF
        function previewOnlineDoc(docUrl, docTitle, ext) {
            document.getElementById('p_doc_title').innerText = docTitle;
            document.getElementById('p_doc_subtitle').innerText = 'Đang xem tài liệu định dạng .' + (ext || 'docx');
            document.getElementById('p_doc_download_btn').href = docUrl;
            document.getElementById('p_doc_fallback_link').href = docUrl;

            // Update icon
            const iconBox = document.getElementById('p_doc_icon');
            if (ext === 'docx' || ext === 'doc') {
                iconBox.innerHTML = '<i class="fa-solid fa-file-word"></i>';
                iconBox.style.color = '#2563eb';
                iconBox.style.background = 'rgba(37,99,235,0.1)';
            } else if (ext === 'pdf') {
                iconBox.innerHTML = '<i class="fa-solid fa-file-pdf"></i>';
                iconBox.style.color = '#dc2626';
                iconBox.style.background = 'rgba(220,38,38,0.1)';
            } else {
                iconBox.innerHTML = '<i class="fa-solid fa-file-lines"></i>';
                iconBox.style.color = 'var(--accent)';
                iconBox.style.background = 'rgba(225,29,72,0.1)';
            }

            // Construct preview URL
            let absoluteUrl = docUrl;
            if (!docUrl.startsWith('http://') && !docUrl.startsWith('https://')) {
                // If relative, make absolute with production domain
                if (window.location.hostname === 'localhost') {
                    absoluteUrl = 'https://viethan.free.nf' + (docUrl.startsWith('/') ? '' : '/') + docUrl;
                } else {
                    absoluteUrl = window.location.origin + (docUrl.startsWith('/') ? '' : '/') + docUrl;
                }
            }

            let viewerUrl = '';
            if (ext === 'pdf') {
                viewerUrl = docUrl; // Browser native PDF viewer
            } else if (['docx', 'doc', 'pptx', 'ppt', 'xlsx', 'xls'].includes(ext)) {
                // Microsoft Office Online Viewer
                viewerUrl = 'https://view.officeapps.live.com/op/view.aspx?src=' + encodeURIComponent(absoluteUrl);
            } else {
                viewerUrl = 'https://docs.google.com/viewer?url=' + encodeURIComponent(absoluteUrl) + '&embedded=true';
            }

            document.getElementById('p_doc_iframe').src = viewerUrl;
            document.getElementById('docPreviewModal').style.display = 'flex';
        }

        function showFullLessonModal(title, jsonContent, ytId, gvName, dateStr, monName) {
            document.getElementById('m_lesson_title').innerText = title;
            document.getElementById('m_lesson_meta').innerHTML = `<i class="fa-solid fa-user-tie"></i> GV: <b>${gvName || 'Phan Ngọc Tuyến'}</b> &bull; <i class="fa-solid fa-book"></i> Môn: <b>${monName || ''}</b> &bull; <i class="fa-regular fa-clock"></i> Đăng ngày: ${dateStr || ''}`;
            
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
                videoFrame.innerHTML = `<iframe src="https://www.youtube.com/embed/${ytId}?rel=0&autoplay=1" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
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

        function copyLinkToClipboard(url) {
            let full = url;
            if (!url.startsWith('http')) {
                full = window.location.origin + (url.startsWith('/') ? '' : '/') + url;
            }
            navigator.clipboard.writeText(full).then(() => {
                alert('Đã sao chép liên kết tài liệu vào bộ nhớ tạm: ' + full);
            }).catch(() => {
                prompt('Sao chép liên kết dưới đây:', full);
            });
        }
    </script>
</body>
</html>
