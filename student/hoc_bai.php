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

// Fetch student info
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $sv_id);
$stmt->execute();
$sv = $stmt->get_result()->fetch_assoc();
$khoa = $sv['khoa'] ?? '';

// Fetch all subjects in student's department
$subjects = [];
if ($khoa) {
    $stmt_mon = $db->prepare("
        SELECT DISTINCT m.id, m.ten_mon, m.ma_mon
        FROM thoi_khoa_bieu tkb
        JOIN mon_hoc m ON tkb.mon_hoc_id = m.id
        WHERE LOWER(tkb.khoa) = LOWER(?)
        ORDER BY m.ten_mon
    ");
    $stmt_mon->bind_param("s", $khoa);
    $stmt_mon->execute();
    $subjects = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);
}

$selected_mon_id = (int)($_GET['mon_hoc_id'] ?? ($subjects[0]['id'] ?? 0));

// Fetch lessons for selected subject
$lessons = [];
if ($selected_mon_id) {
    $stmt_less = $db->prepare("
        SELECT l.*, g.ho_ten as gv_name
        FROM lessons l
        LEFT JOIN giang_vien g ON l.giang_vien_id = g.id
        WHERE l.mon_hoc_id = ?
        ORDER BY l.id ASC
    ");
    $stmt_less->bind_param("i", $selected_mon_id);
    $stmt_less->execute();
    $lessons = $stmt_less->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch documents for selected subject
$documents = [];
if ($selected_mon_id) {
    $stmt_doc = $db->prepare("
        SELECT d.*, g.ho_ten as gv_name
        FROM tai_lieu d
        LEFT JOIN giang_vien g ON d.giang_vien_id = g.id
        WHERE d.mon_hoc_id = ?
        ORDER BY d.id DESC
    ");
    $stmt_doc->bind_param("i", $selected_mon_id);
    $stmt_doc->execute();
    $documents = $stmt_doc->get_result()->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Học bài - Cổng sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .subject-sidebar {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .subject-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 12px;
            text-decoration: none;
            color: var(--text);
            transition: all 0.2s ease;
        }
        .subject-item:hover, .subject-item.active {
            border-color: var(--accent);
            background: rgba(217, 27, 67, 0.02);
            transform: translateX(4px);
        }
        .lesson-item {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .lesson-item:hover {
            border-color: var(--accent);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .doc-item {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .video-responsive-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            background: #000;
            box-shadow: 0 6px 24px rgba(0,0,0,0.3);
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
    <?php include '../includes/student_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-book-open" style="color:var(--accent)"></i> Hệ Thống Học Tập Lý Thuyết &amp; Video</h1>
            <p style="color: var(--text2); margin-top: 5px;">Học các bài giảng trực tuyến, xem video hướng dẫn YouTube và tải về học liệu do Giảng viên chia sẻ</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2.2fr; gap: 30px; align-items: start;">
        <!-- Left: Subject List -->
        <div class="subject-sidebar">
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-list-ul"></i> Môn học của bạn</span>
                </div>
                <div class="card-body" style="padding: 15px;">
                    <?php if (empty($subjects)): ?>
                        <p style="text-align: center; color: var(--text2);">Chưa có lịch học môn nào.</p>
                    <?php else: foreach ($subjects as $sub): ?>
                        <a href="?mon_hoc_id=<?= $sub['id'] ?>" class="subject-item <?= $selected_mon_id === $sub['id'] ? 'active' : '' ?>">
                            <div style="background: rgba(217, 27, 67, 0.08); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 16px; flex-shrink: 0;">
                                <i class="fa-solid fa-book"></i>
                            </div>
                            <div>
                                <span style="font-weight: 700; font-size: 13.5px;"><?= htmlspecialchars($sub['ten_mon']) ?></span>
                                <div style="font-size: 10.5px; color: var(--text2); margin-top: 2px;">Mã: <?= htmlspecialchars($sub['ma_mon']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Lessons & Docs -->
        <div>
            <?php if (!$selected_mon_id): ?>
                <div class="card" style="padding: 50px; text-align: center; color: var(--text2);">
                    Chọn môn học bên trái để xem nội dung bài học.
                </div>
            <?php else: ?>
                <!-- Section: Lessons List -->
                <div style="margin-bottom: 30px;">
                    <div style="border-left: 4px solid var(--accent); padding-left: 12px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
                        <h2 style="font-size: 18px; font-weight: 800; color: var(--text); text-transform: uppercase; margin:0;">Bài giảng trực tuyến</h2>
                        <span style="font-size:12px; color:var(--text2);"><?= count($lessons) ?> bài học</span>
                    </div>
                    
                    <?php if (empty($lessons)): ?>
                        <div class="card" style="padding: 30px; text-align: center; color: var(--text2);">
                            <i class="fa-solid fa-box-open" style="font-size: 28px; color: var(--accent); margin-bottom: 10px; display: block;"></i>
                            Giảng viên chưa biên soạn bài giảng trực tuyến nào cho môn học này.
                        </div>
                    <?php else: foreach ($lessons as $index => $less): 
                        $v_url = $less['video_url'] ?? '';
                        $yt_id = getYoutubeId($v_url);
                        if (!$yt_id) {
                            $extracted_url = extractFirstYoutubeUrl($less['noi_dung'] ?? '');
                            $yt_id = getYoutubeId($extracted_url);
                        }
                    ?>
                        <div class="lesson-item" 
                            data-title="<?= htmlspecialchars($less['tieu_de'], ENT_QUOTES, 'UTF-8') ?>" 
                            data-content="<?= htmlspecialchars($less['noi_dung'], ENT_QUOTES, 'UTF-8') ?>" 
                            data-video="<?= htmlspecialchars($yt_id, ENT_QUOTES, 'UTF-8') ?>"
                            data-gv="<?= htmlspecialchars($less['gv_name'] ?? 'Giảng viên', ENT_QUOTES, 'UTF-8') ?>"
                            data-date="<?= date('d/m/Y H:i', strtotime($less['created_at'])) ?>"
                            onclick="showFullLessonModal(this.getAttribute('data-title'), this.getAttribute('data-content'), this.getAttribute('data-video'), this.getAttribute('data-gv'), this.getAttribute('data-date'))">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                                        <span style="font-size: 11.5px; color: var(--accent); font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Bài <?= $index + 1 ?></span>
                                        <?php if ($yt_id): ?>
                                            <span class="badge-youtube"><i class="fa-brands fa-youtube"></i> Có Video bài giảng</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 style="font-size: 16px; font-weight: 800; color: var(--text); margin: 4px 0;"><?= htmlspecialchars($less['tieu_de']) ?></h3>
                                    <div style="font-size: 11.5px; color: var(--text2); display:flex; gap:12px; margin-top:6px;">
                                        <span><i class="fa-solid fa-user-tie" style="color:var(--accent);"></i> <?= htmlspecialchars($less['gv_name'] ?? 'TBA') ?></span>
                                        <span><i class="fa-regular fa-clock"></i> <?= date('d/m/Y', strtotime($less['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <?php if ($yt_id): ?>
                                        <span style="color:#ef4444; font-size:18px;"><i class="fa-brands fa-youtube"></i></span>
                                    <?php endif; ?>
                                    <div style="color: var(--accent); font-size: 16px;"><i class="fa-solid fa-chevron-right"></i></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Section: Document Files List -->
                <div>
                    <div style="border-left: 4px solid var(--accent); padding-left: 12px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
                        <h2 style="font-size: 18px; font-weight: 800; color: var(--text); text-transform: uppercase; margin:0;">Tài liệu tải về &amp; Video tham khảo</h2>
                        <span style="font-size:12px; color:var(--text2);"><?= count($documents) ?> học liệu</span>
                    </div>
                    
                    <?php if (empty($documents)): ?>
                        <div class="card" style="padding: 30px; text-align: center; color: var(--text2);">
                            <i class="fa-solid fa-box-open" style="font-size: 28px; color: var(--accent); margin-bottom: 10px; display: block;"></i>
                            Giảng viên chưa tải lên tài liệu hoặc video học tập nào.
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
                                    <div style="background: rgba(239, 68, 68, 0.12); width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 22px; flex-shrink:0;">
                                        <i class="fa-brands fa-youtube"></i>
                                    </div>
                                <?php else: ?>
                                    <div style="background: rgba(225, 29, 72, 0.08); width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 18px; flex-shrink:0;">
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
                                    <span style="font-size: 11px; color: var(--text2); display:block; margin-top:3px;">
                                        GV: <?= htmlspecialchars($doc['gv_name'] ?? 'TBA') ?> &bull; <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <?php if ($yt_id): ?>
                                    <button type="button" onclick="openYoutubeVideoModal('<?= $yt_id ?>', '<?= htmlspecialchars($doc['ten_tai_lieu'], ENT_QUOTES, 'UTF-8') ?>')" class="btn-ghost" style="color:#ef4444; border-color:rgba(239,68,68,0.3); padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor:pointer;">
                                        <i class="fa-solid fa-play"></i> Xem Video
                                    </button>
                                    <a href="<?= htmlspecialchars($doc_link) ?>" target="_blank" class="btn-ghost" style="padding: 8px 10px; border-radius: 8px; font-size: 12px; text-decoration: none;" title="Mở trên YouTube">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($doc_link) ?>" target="_blank" class="btn-ghost" style="padding: 8px 15px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-circle-down"></i> Tải về / Xem
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lesson Viewer Modal -->
    <div class="modal-overlay" id="lessonModal" onclick="if(event.target==this) toggleModal('lessonModal')">
        <div class="modal-box" style="width: 780px; max-width: 95vw;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px; border-bottom: 1px solid rgba(217, 27, 67, 0.2); padding-bottom: 12px;">
                <div>
                    <h3 class="modal-title" id="m_lesson_title" style="margin:0; font-size:17px;">Chi tiết bài học</h3>
                    <div id="m_lesson_meta" style="font-size:11.5px; color:var(--text2); margin-top:4px;"></div>
                </div>
                <button onclick="toggleModal('lessonModal')" style="background:none; border:none; color:var(--text2); font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- YouTube Video player container in modal -->
            <div id="m_lesson_video_box" style="display:none; margin-bottom: 16px;">
                <div class="video-responsive-wrapper" id="m_lesson_video_iframe"></div>
            </div>

            <div style="background:var(--bg3); border-radius:10px; padding:15px; border:1px solid var(--border);">
                <div style="font-weight:700; font-size:12.5px; color:var(--accent); margin-bottom:8px; text-transform:uppercase;">
                    <i class="fa-solid fa-align-left"></i> Nội dung lý thuyết bài học:
                </div>
                <div id="m_lesson_content" style="font-size:14.5px; color:var(--text); line-height:1.75; white-space:pre-wrap; max-height:350px; overflow-y:auto; padding-right:10px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Standalone Video Player Modal -->
    <div class="modal-overlay" id="videoModal" onclick="if(event.target==this) toggleModal('videoModal')">
        <div class="modal-box" style="width: 780px; max-width: 95vw;">
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
            } else {
                m.style.display = 'flex';
            }
        }

        function showFullLessonModal(title, jsonContent, ytId, gvName, dateStr) {
            document.getElementById('m_lesson_title').innerText = title;
            document.getElementById('m_lesson_meta').innerHTML = `<i class="fa-solid fa-user-tie"></i> GV: <b>${gvName || 'Giảng viên'}</b> &bull; <i class="fa-regular fa-clock"></i> Đăng ngày: ${dateStr || ''}`;
            
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
    </script>
</body>
</html>
