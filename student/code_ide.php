<?php
require_once '../config.php';
require_once '../includes/code_access.php';

if (!isLoggedIn()) {
    header("Location: /tkb/login.php");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Fetch student or user info
$db = getDB();
$sv_id = $_SESSION['student_id'] ?? ($_SESSION['user_id'] ?? 0);
$lop = '';
if ($sv_id > 0) {
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $sv_id);
        $stmt->execute();
        $sv = $stmt->get_result()->fetch_assoc();
        $lop = $sv['lop'] ?? '';
        $stmt->close();
    }
}

$now_str = date('Y-m-d H:i:s');
$code_access = getStudentCodeAccess($db, $sv_id, $lop);
$active_session = $code_access['active_session'];
$last_completed_session = $code_access['last_completed_session'];
$practice_mode = $code_access['mode'] === 'practice';
$practice_available_at = $code_access['practice_available_at'];
$is_cooldown = !$code_access['allowed'] && $last_completed_session !== null && $practice_available_at !== null;

// Also fetch the nearest upcoming session (if any) to display a nice countdown to the student!
$stmt_next = $db->prepare("
    SELECT * FROM practice_sessions 
    WHERE (lop = ? OR lop = 'ALL') 
      AND is_enabled = 1 
      AND start_time > ? 
    ORDER BY start_time ASC 
    LIMIT 1
");
$stmt_next->bind_param("ss", $lop, $now_str);
$stmt_next->execute();
$next_session = $stmt_next->get_result()->fetch_assoc();
$stmt_next->close();

// Keep the latest score visible on the coding-practice page after the IDE closes.
$latest_practice_result = null;
$stmt_result = $db->prepare("
    SELECT sess.mo_ta, sess.end_time, sub.diem, sub.nhan_xet, sub.submitted_at
    FROM practice_submissions sub
    JOIN practice_sessions sess ON sess.id = sub.session_id
    WHERE sub.student_id = ?
      AND sub.diem IS NOT NULL
    ORDER BY sub.submitted_at DESC, sess.end_time DESC
    LIMIT 1
");
$stmt_result->bind_param("i", $sv_id);
$stmt_result->execute();
$latest_practice_result = $stmt_result->get_result()->fetch_assoc();
$stmt_result->close();

$current_session_submission = null;
if ($active_session) {
    $stmt_current_sub = $db->prepare("SELECT submitted_at FROM practice_submissions WHERE student_id = ? AND session_id = ? LIMIT 1");
    $stmt_current_sub->bind_param("ii", $sv_id, $active_session['id']);
    $stmt_current_sub->execute();
    $current_session_submission = $stmt_current_sub->get_result()->fetch_assoc();
    $stmt_current_sub->close();
}

$db->close();

$is_allowed = $code_access['allowed'] || isset($_GET['storage_id']) || isset($_GET['mode']) || isset($_GET['new']);

if (!$is_allowed):
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống đang khóa - Cổng sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body style="background: #0f172a; color: #f8fafc; font-family: 'Outfit', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; padding: 20px;">
    <div style="background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 40px; text-align: center; max-width: 500px; width: 100%; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.5); backdrop-filter: blur(12px);">
        <div style="width: 80px; height: 80px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 25px;">
            <i class="fa-solid fa-lock"></i>
        </div>
        
        <h2 style="font-size: 22px; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 10px; text-transform: uppercase; color: #f8fafc;">
            <?= $is_cooldown ? 'IDE Đang Chuẩn Bị Mở Lại' : 'IDE Thực Hành Đang Khóa' ?>
        </h2>
        <p style="color: #94a3b8; font-size: 14.5px; line-height: 1.6; margin-bottom: 25px;">
            <?php if ($is_cooldown): ?>
                Buổi <strong><?= htmlspecialchars($last_completed_session['mo_ta'] ?: 'kiểm tra/thi') ?></strong> đã kết thúc. Hệ thống giữ bài làm trong 5 phút trước khi mở lại IDE để bạn luyện Code.
            <?php else: ?>
                Hiện tại hệ thống thực hành lập trình đã được khóa bởi giáo viên. Lớp của bạn (<strong><?= htmlspecialchars($lop) ?></strong>) chưa đến giờ làm bài thi/kiểm tra.
            <?php endif; ?>
        </p>

        <?php if ($is_cooldown): ?>
            <div style="background: rgba(245, 158, 11, 0.10); border: 1px solid rgba(245, 158, 11, 0.28); border-radius: 12px; padding: 15px; text-align: center; margin-bottom: 22px; color: #fde68a; font-size: 13.5px;">
                <i class="fa-solid fa-clock"></i> <strong id="practice-unlock-countdown">Đang tính thời gian mở lại...</strong>
            </div>
        <?php endif; ?>

        <?php if ($next_session): ?>
            <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 15px; text-align: left; margin-bottom: 30px;">
                <div style="font-weight: 700; font-size: 13px; color: #3b82f6; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-calendar-days"></i> Lịch thực hành sắp tới
                </div>
                <div style="font-weight: 700; color: #f1f5f9; font-size: 15px; margin-bottom: 4px;"><?= htmlspecialchars($next_session['mo_ta']) ?></div>
                <div style="font-size: 13px; color: #94a3b8; display: flex; flex-direction: column; gap: 3px; margin-top: 5px;">
                    <span>Bắt đầu: <strong><?= date('H:i (d/m/Y)', strtotime($next_session['start_time'])) ?></strong></span>
                    <span>Kết thúc: <strong><?= date('H:i (d/m/Y)', strtotime($next_session['end_time'])) ?></strong></span>
                </div>
            </div>
        <?php else: ?>
            <div style="background: rgba(148, 163, 184, 0.05); border: 1px solid rgba(148, 163, 184, 0.1); border-radius: 12px; padding: 15px; text-align: center; margin-bottom: 30px; color: #94a3b8; font-size: 13.5px;">
                <i class="fa-solid fa-circle-info"></i> Chưa có lịch thực hành tiếp theo được tạo.
            </div>
        <?php endif; ?>

        <?php if ($latest_practice_result): ?>
            <div style="background: rgba(16, 185, 129, 0.10); border: 1px solid rgba(16, 185, 129, 0.28); border-radius: 12px; padding: 16px; text-align: left; margin-bottom: 22px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px;">
                    <div style="font-size:13px; font-weight:800; color:#34d399; text-transform:uppercase; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-circle-check"></i> Kết quả giáo viên đã chấm
                    </div>
                    <div style="font-size:24px; line-height:1; font-weight:900; color:#f8fafc; white-space:nowrap;">
                        <?= number_format((float)$latest_practice_result['diem'], 1) ?><span style="font-size:13px; color:#94a3b8;">/10</span>
                    </div>
                </div>
                <div style="font-size:14px; color:#f1f5f9; font-weight:700; margin-bottom:6px;">
                    <?= htmlspecialchars($latest_practice_result['mo_ta'] ?: 'Bài thực hành Code') ?>
                </div>
                <?php if (trim((string)$latest_practice_result['nhan_xet']) !== ''): ?>
                    <div style="font-size:13px; color:#cbd5e1; line-height:1.5;">
                        <strong style="color:#94a3b8;">Nhận xét:</strong> <?= nl2br(htmlspecialchars($latest_practice_result['nhan_xet'])) ?>
                    </div>
                <?php else: ?>
                    <div style="font-size:13px; color:#94a3b8; font-style:italic;">Giáo viên chưa để lại nhận xét.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="/tkb/student/tien_do.php#ket-qua-code" class="btn" style="padding: 12px 18px; background: #334155; color: white; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 14px;">
                <i class="fa-solid fa-chart-line"></i> Xem tất cả kết quả
            </a>
            <a href="/tkb/student/dashboard.php" class="btn" style="padding: 12px 24px; background: var(--accent); color: white; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 14px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                <i class="fa-solid fa-house"></i> Về bảng điều khiển
            </a>
        </div>
    </div>
    <?php if ($is_cooldown): ?>
        <script>
            (function () {
                const unlockAt = <?= (int)$practice_available_at * 1000 ?>;
                const countdown = document.getElementById('practice-unlock-countdown');

                function updateCountdown() {
                    const remaining = unlockAt - Date.now();
                    if (remaining <= 0) {
                        window.location.reload();
                        return;
                    }

                    const minutes = Math.floor(remaining / 60000);
                    const seconds = Math.floor((remaining % 60000) / 1000);
                    countdown.textContent = `IDE sẽ tự mở chế độ luyện Code sau ${minutes}:${String(seconds).padStart(2, '0')}`;
                }

                updateCountdown();
                window.setInterval(updateCountdown, 1000);
            })();
        </script>
    <?php endif; ?>
</body>
</html>
<?php 
exit();
endif;

$storage_id = (int)($_GET['storage_id'] ?? 0);
$preloaded_code = null;
$preloaded_lang = null;
$preloaded_title = null;

if ($storage_id > 0) {
    $db_st = getDB();
    $st_res = $db_st->query("SELECT * FROM student_code_storage WHERE id = $storage_id LIMIT 1");
    if ($st_res && ($st_row = $st_res->fetch_assoc())) {
        $preloaded_code = $st_row['ma_nguon'];
        $preloaded_lang = $st_row['ngon_ngu'];
        $preloaded_title = $st_row['ten_du_an'];

        // Robust decoding of preloaded_code (supports JSON, Base64-JSON, or raw text)
        $bundle = @json_decode($preloaded_code, true);
        if (!is_array($bundle)) {
            $b64 = @base64_decode($preloaded_code);
            if ($b64 && (strpos(trim($b64), '{') === 0 || strpos(trim($b64), '[') === 0)) {
                $bundle = @json_decode($b64, true);
            }
        }
        if (!is_array($bundle) || empty($bundle)) {
            $bundle = [];
            if (preg_match_all('/"([^"\r\n]+\.[a-zA-Z0-9]+)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $preloaded_code, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $fn = $m[1];
                    $fc = stripcslashes($m[2]);
                    $bundle[$fn] = $fc;
                }
            }
        }
        if (!is_array($bundle) || empty($bundle)) {
            if (!empty($preloaded_code) && strpos(trim($preloaded_code), '{') !== 0) {
                $lang_ext = strtolower($preloaded_lang ?: 'py');
                $default_fn = ($lang_ext === 'html') ? 'index.html' : (($lang_ext === 'php') ? 'index.php' : 'main.' . $lang_ext);
                $bundle[$default_fn] = $preloaded_code;
            }
        }

        // Merge additional files from student_code_files table (only small ones to prevent memory exhaustion)
        try {
            $fres = $db_st->query("SELECT file_path, IF(LENGTH(file_content) < 50000, file_content, '__LAZY_FETCH__') as file_content FROM student_code_files WHERE storage_id = $storage_id");
            if ($fres && $fres->num_rows > 0) {
                $accumulated = 0;
                while ($fr = $fres->fetch_assoc()) {
                    $fc = $fr['file_content'];
                    if ($fc !== '__LAZY_FETCH__') {
                        $accumulated += strlen($fc);
                        if ($accumulated > 100000) { // Limit total initial payload to ~100KB
                            $fc = '__LAZY_FETCH__';
                        }
                    }
                    $bundle[$fr['file_path']] = $fc;
                }
            }
        } catch (Throwable $e) {}

        $json_out = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json_out === false) {
            $sanitized = [];
            foreach ($bundle as $k => $v) {
                $clean_k = is_string($k) ? mb_convert_encoding($k, 'UTF-8', 'UTF-8') : $k;
                $clean_v = is_string($v) ? mb_convert_encoding($v, 'UTF-8', 'UTF-8') : $v;
                $sanitized[$clean_k] = $clean_v;
            }
            $json_out = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        $preloaded_code = $json_out ?: '{}';
        // Optimize: do NOT dump megabytes into inline HTML script header to prevent InfinityFree 64KB truncation!
        $inline_code = (is_string($preloaded_code) && strlen($preloaded_code) < 30000) ? $preloaded_code : null;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VS Code IDE - Cổng sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <script>
        window.preloadedStorageId = <?= (int)$storage_id ?>;
        window.preloadedCodeData = <?= json_encode([
            'code' => $inline_code,
            'lang' => $preloaded_lang,
            'title' => $preloaded_title
        ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    </script>
    <!-- Load JSZip for downloading project as zip -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- Load Ace Editor from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ext-language_tools.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ext-emmet.js"></script>
    <style>
        /* VS Code Layout Styling */
        .vscode-window {
            background: #1e1e1e;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid #3c3c3c;
            display: flex;
            flex-direction: column;
            height: 720px;
            margin-top: 15px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            position: relative;
        }
        
        .vscode-title-bar {
            background: #3c3c3c;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            color: #cccccc;
            font-size: 12.5px;
            border-bottom: 1px solid #2d2d2d;
            user-select: none;
            gap: 10px;
        }
        
        .window-controls {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-shrink: 0;
        }
        
        .window-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-red { background: #ff5f56; }
        .dot-yellow { background: #ffbd2e; }
        .dot-green { background: #27c93f; }

        .menu-items {
            display: flex;
            gap: 15px;
            margin-left: 20px;
        }
        
        .menu-item {
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            transition: 0.1s;
        }
        
        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        /* Menu item dropdown styles */
        .menu-item-container {
            position: relative;
            display: inline-block;
        }
        .dropdown-menu-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: #252526;
            min-width: 190px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            z-index: 1000;
            border: 1px solid #3c3c3c;
            border-radius: 4px;
            margin-top: 4px;
            padding: 4px 0;
        }
        .dropdown-menu-content a {
            color: #cccccc;
            padding: 8px 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            transition: background 0.1s;
            text-align: left;
        }
        .dropdown-menu-content a:hover {
            background: #d91b43;
            color: #ffffff;
        }
        .dropdown-menu-content .dropdown-divider {
            height: 1px;
            background: #3c3c3c;
            margin: 4px 0;
        }
        .menu-item-container.active .dropdown-menu-content {
            display: block;
        }
        
        .window-title {
            font-size: 12px;
            color: #8c8c8c;
            flex: 1;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
            padding: 0 10px;
        }
        
        .vscode-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        
        /* Activity Bar */
        .vscode-activity-bar {
            width: 50px;
            background: #333333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-right: 1px solid #252526;
            flex-shrink: 0;
            user-select: none;
        }
        
        .activity-icons {
            display: flex;
            flex-direction: column;
            gap: 18px;
            width: 100%;
            align-items: center;
        }
        
        .activity-icon {
            color: #858585;
            font-size: 20px;
            cursor: pointer;
            width: 100%;
            text-align: center;
            padding: 10px 0;
            border-left: 3px solid transparent;
            transition: all 0.15s ease;
            position: relative;
        }
        
        .activity-icon:hover {
            color: #ffffff;
        }
        
        .activity-icon.active {
            color: #ffffff;
            border-left-color: #d91b43;
        }

        .activity-tooltip {
            display: none;
            position: absolute;
            left: 55px;
            top: 50%;
            transform: translateY(-50%);
            background: #252526;
            color: #e1e1e1;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            z-index: 100;
            border: 1px solid #3c3c3c;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        .activity-icon:hover .activity-tooltip {
            display: block;
        }
        
        /* Sidebar (Explorer) */
        .vscode-sidebar {
            width: 210px;
            background: #252526;
            border-right: 1px solid #1e1e1e;
            display: flex;
            flex-direction: column;
            color: #bbbbbb;
            font-size: 12px;
            flex-shrink: 0;
            user-select: none;
            transition: width 0.2s ease;
        }
        
        .sidebar-header {
            padding: 12px 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 10.5px;
            color: #858585;
            border-bottom: 1px solid #1e1e1e;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .explorer-action-icon {
            cursor: pointer;
            color: #858585;
            font-size: 13.5px;
            transition: color 0.15s ease;
            padding: 2px 4px;
            border-radius: 3px;
        }
        .explorer-action-icon:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.08);
        }
        
        .explorer-section {
            padding: 10px 0;
            overflow-y: auto;
            flex: 1;
        }
        
        .explorer-folder {
            padding: 4px 15px;
            font-weight: 700;
            color: #e1e1e1;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            font-size: 11px;
        }
        
        .explorer-files {
            display: flex;
            flex-direction: column;
            margin-top: 5px;
        }

        /* Folder tree node styling */
        .tree-folder-row {
            padding: 4px 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            color: #e1e1e1;
            font-weight: 600;
            font-size: 12px;
            user-select: none;
            transition: background 0.1s;
        }
        .tree-folder-row:hover {
            background: #2a2d2e;
        }
        .tree-folder-row .folder-arrow {
            font-size: 10px;
            width: 12px;
            text-align: center;
            color: #858585;
            transition: transform 0.15s ease;
        }
        .tree-folder-row .folder-arrow.collapsed {
            transform: rotate(-90deg);
        }
        .tree-folder-children {
            overflow: hidden;
        }
        .tree-folder-children.collapsed {
            display: none;
        }

        /* Dynamic Explorer File Row Styling */
        .explorer-file-row {
            padding: 6px 15px 6px 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.15s ease;
            color: #aaaaaa;
        }
        .explorer-file-row:hover {
            background: #2a2d2e;
            color: #ffffff;
        }
        .explorer-file-row.active {
            background: #37373d;
            color: #ffffff;
            font-weight: 600;
        }
        .explorer-file-title {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
        }
        .explorer-file-delete {
            visibility: hidden;
            font-size: 14px;
            padding: 0 4px;
            border-radius: 3px;
            color: #858585;
            cursor: pointer;
            line-height: 1;
            transition: all 0.1s ease;
        }
        .explorer-file-row:hover .explorer-file-delete {
            visibility: visible;
        }
        .explorer-file-delete:hover {
            background: rgba(255,255,255,0.15);
            color: #ff5f56;
        }
        
        .file-icon {
            font-size: 13.5px;
            width: 14px;
            text-align: center;
        }
        .file-icon.python { color: #3572A5; }
        .file-icon.javascript { color: #f1e05a; }
        .file-icon.php { color: #4F5D95; }
        .file-icon.html { color: #e34c26; }
        .file-icon.css { color: #264de4; }
        .file-icon.c { color: #a8b9cc; }
        .file-icon.cpp { color: #f34b7d; }
        .file-icon.java { color: #b07219; }

        /* Main code area */
        .vscode-main-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #1e1e1e;
            overflow: hidden;
        }
        
        /* Tabs bar */
        .vscode-tabs-bar {
            background: #2d2d2d;
            height: 35px;
            display: flex;
            border-bottom: 1px solid #1e1e1e;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .vscode-tabs-bar::-webkit-scrollbar {
            display: none;
        }
        
        .vscode-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #2d2d2d;
            color: #858585;
            padding: 0 16px;
            font-size: 12.5px;
            cursor: pointer;
            border-right: 1px solid #252526;
            border-top: 2px solid transparent;
            user-select: none;
            height: 100%;
            transition: all 0.15s ease;
        }
        
        .vscode-tab:hover {
            background: #2b2b2b;
            color: #e1e1e1;
        }
        
        .vscode-tab.active {
            background: #1e1e1e;
            color: #ffffff;
            border-top-color: #d91b43;
        }
        
        .vscode-tab-close {
            font-size: 10px;
            padding: 2px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            margin-left: 4px;
        }
        .vscode-tab-close:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        /* Top editor toolbar for Actions */
        .vscode-editor-toolbar {
            background: #1e1e1e;
            height: 40px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 15px;
            border-bottom: 1px solid #2d2d2d;
            gap: 10px;
        }

        .editor-action-btn {
            background: #2a2d2e;
            border: 1px solid #3c3c3c;
            color: #e1e1e1;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .editor-action-btn:hover {
            background: #37373d;
            color: #ffffff;
            border-color: #555;
        }
        .editor-action-btn.btn-run {
            background: #d91b43;
            border-color: transparent;
            color: #fff;
        }
        .editor-action-btn.btn-run:hover {
            background: #a8122e;
        }
        .editor-action-btn.btn-submit {
            background: #059669;
            border-color: transparent;
            color: #fff;
        }
        .editor-action-btn.btn-submit:hover {
            background: #047857;
        }
        .editor-action-btn.btn-submit.submitted {
            background: #1d4ed8;
        }
        .editor-action-btn.btn-exam {
            background: #2563eb;
            border-color: transparent;
            color: #fff;
            text-decoration: none;
        }
        .editor-action-btn.btn-exam:hover {
            background: #1d4ed8;
            color: #fff;
        }
        
        /* Editor Pane */
        .vscode-editor-pane {
            flex: 1;
            position: relative;
            min-height: 150px;
        }
        #editor {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            font-size: 14px;
        }
        
        /* Console Terminal Panel */
        .vscode-terminal-pane {
            height: 240px;
            background: #1e1e1e;
            border-top: 1px solid #2d2d2d;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: height 0.15s ease-in-out;
        }
        .vscode-terminal-pane.collapsed {
            height: 35px !important;
        }
        .vscode-terminal-pane.collapsed .terminal-body {
            display: none !important;
        }
        
        .terminal-header {
            background: #252526;
            height: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            border-bottom: 1px solid #2d2d2d;
            user-select: none;
        }
        
        .terminal-tabs {
            display: flex;
            gap: 15px;
            height: 100%;
            align-items: center;
        }
        
        .terminal-tab {
            font-size: 11px;
            font-weight: 700;
            color: #858585;
            text-transform: uppercase;
            cursor: pointer;
            padding: 4px 4px;
            border-bottom: 2px solid transparent;
            transition: all 0.15s ease;
        }
        
        .terminal-tab:hover {
            color: #e1e1e1;
        }
        
        .terminal-tab.active {
            color: #ffffff;
            border-bottom-color: #d91b43;
        }
        
        .terminal-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .terminal-btn {
            background: transparent;
            border: none;
            color: #858585;
            cursor: pointer;
            font-size: 12.5px;
            padding: 4px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        
        .terminal-btn:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.08);
        }
        
        .terminal-body {
            flex: 1;
            overflow-y: auto;
            position: relative;
            background: #0f172a;
        }

        .terminal-output {
            color: #e2e8f0;
            font-family: 'Courier New', Courier, monospace;
            padding: 15px;
            font-size: 13px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .terminal-stderr {
            color: #f87171;
        }
        .terminal-system-info {
            color: #38bdf8;
            font-weight: 700;
            border-bottom: 1px dashed rgba(255,255,255,0.1);
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .terminal-input-container {
            padding: 15px;
            display: none;
            height: 100%;
            background: #1e1e1e;
        }

        .terminal-textarea {
            width: 100%;
            height: 100%;
            background: #0f172a;
            border: 1px solid #3c3c3c;
            color: #f1f5f9;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            padding: 10px 14px;
            outline: none;
            resize: none;
            border-radius: 6px;
        }
        .terminal-textarea:focus {
            border-color: #d91b43;
        }

        .preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: #ffffff;
            display: none;
        }
        
        /* Status Bar */
        .vscode-status-bar {
            background: #d91b43;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            color: #ffffff;
            font-size: 11.5px;
            user-select: none;
            flex-shrink: 0;
            font-family: sans-serif;
        }
        
        .status-left, .status-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        .status-item:hover {
            background: rgba(255,255,255,0.15);
            padding: 2px 4px;
            margin: 0 -4px;
            border-radius: 3px;
        }
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        /* ===== BREADCRUMB BAR ===== */
        .vscode-breadcrumb-bar {
            background: #1e1e1e;
            height: 22px;
            display: flex;
            align-items: center;
            padding: 0 12px;
            font-size: 11.5px;
            color: #858585;
            border-bottom: 1px solid #2d2d2d;
            user-select: none;
            gap: 2px;
            overflow: hidden;
        }
        .breadcrumb-item {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #cccccc;
            cursor: pointer;
            padding: 1px 4px;
            border-radius: 3px;
            transition: background 0.1s;
            white-space: nowrap;
        }
        .breadcrumb-item:hover {
            background: rgba(255,255,255,0.08);
            color: #ffffff;
        }
        .breadcrumb-sep {
            color: #555;
            font-size: 10px;
            margin: 0 1px;
        }

        /* ===== WELCOME TAB ===== */
        .vscode-welcome {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #1e1e1e;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            color: #cccccc;
            z-index: 5;
            padding: 30px 20px;
            user-select: none;
            overflow-y: auto;
        }
        .welcome-logo {
            font-size: 72px;
            color: #0098ff;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 20px rgba(0,152,255,0.3));
        }
        .welcome-title {
            font-size: 22px;
            font-weight: 300;
            color: #ffffff;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }
        .welcome-subtitle {
            font-size: 13px;
            color: #858585;
            margin-bottom: 30px;
        }
        .welcome-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 260px;
        }
        .welcome-action-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #858585;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .welcome-action {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 12px;
            color: #3794ff;
            font-size: 13px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.15s;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
        }
        .welcome-action:hover {
            background: rgba(55,148,255,0.1);
            color: #5babff;
        }
        .welcome-action i {
            width: 16px;
            text-align: center;
            font-size: 14px;
        }
        .welcome-shortcuts {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            width: 260px;
        }
        .welcome-shortcut {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #858585;
            padding: 3px 0;
        }
        .welcome-shortcut kbd {
            background: #37373d;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            color: #cccccc;
            border: 1px solid #555;
            font-family: inherit;
        }

        /* ===== COMMAND PALETTE ===== */
        .cmd-palette-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            display: none;
            justify-content: center;
            padding-top: 80px;
        }
        .cmd-palette-overlay.active {
            display: flex;
        }
        .cmd-palette {
            width: 520px;
            max-height: 350px;
            background: #252526;
            border: 1px solid #454545;
            border-radius: 6px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.6);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: cmdSlideDown 0.12s ease-out;
        }
        @keyframes cmdSlideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .cmd-palette-input {
            background: #3c3c3c;
            border: none;
            color: #ffffff;
            font-size: 14px;
            padding: 10px 14px;
            outline: none;
            font-family: inherit;
            border-bottom: 1px solid #454545;
        }
        .cmd-palette-input::placeholder {
            color: #858585;
        }
        .cmd-palette-list {
            overflow-y: auto;
            flex: 1;
            padding: 4px 0;
        }
        .cmd-palette-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 14px;
            color: #cccccc;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.08s;
        }
        .cmd-palette-item:hover,
        .cmd-palette-item.selected {
            background: #062f4a;
            color: #ffffff;
        }
        .cmd-palette-item i {
            width: 16px;
            text-align: center;
            color: #858585;
            font-size: 13px;
        }
        .cmd-palette-item:hover i,
        .cmd-palette-item.selected i {
            color: #cccccc;
        }
        .cmd-palette-item .cmd-shortcut {
            margin-left: auto;
            font-size: 11px;
            color: #666;
        }
        .cmd-palette-item:hover .cmd-shortcut {
            color: #999;
        }

        /* ===== CONTEXT MENU ===== */
        .ctx-menu {
            position: fixed;
            background: #252526;
            border: 1px solid #454545;
            border-radius: 5px;
            min-width: 200px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.5);
            z-index: 10001;
            padding: 4px 0;
            display: none;
            animation: ctxFadeIn 0.1s ease-out;
        }
        @keyframes ctxFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
        .ctx-menu.active {
            display: block;
        }
        .ctx-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 20px 6px 12px;
            color: #cccccc;
            font-size: 12.5px;
            cursor: pointer;
            transition: background 0.08s;
            white-space: nowrap;
        }
        .ctx-menu-item:hover {
            background: #094771;
            color: #ffffff;
        }
        .ctx-menu-item i {
            width: 14px;
            text-align: center;
            font-size: 12px;
            color: #858585;
        }
        .ctx-menu-item:hover i {
            color: #cccccc;
        }
        .ctx-menu-item .ctx-shortcut {
            margin-left: auto;
            font-size: 11px;
            color: #666;
            padding-left: 20px;
        }
        .ctx-menu-divider {
            height: 1px;
            background: #454545;
            margin: 4px 0;
        }

        /* ===== SEARCH PANEL ===== */
        #sidebar-search-content {
            display: none;
            flex-direction: column;
            height: 100%;
        }
        .search-input-box {
            background: #3c3c3c;
            border: 1px solid #3c3c3c;
            color: #cccccc;
            padding: 6px 10px;
            font-size: 12.5px;
            outline: none;
            border-radius: 3px;
            font-family: inherit;
            width: 100%;
            transition: border-color 0.15s;
        }
        .search-input-box:focus {
            border-color: #007acc;
        }
        .search-results {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }
        .search-result-file {
            padding: 4px 14px;
            font-size: 12px;
            font-weight: 700;
            color: #e1e1e1;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .search-result-file:hover {
            background: #2a2d2e;
        }
        .search-result-line {
            padding: 3px 14px 3px 30px;
            font-size: 11.5px;
            color: #aaaaaa;
            cursor: pointer;
            transition: background 0.1s;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .search-result-line:hover {
            background: #2a2d2e;
            color: #ffffff;
        }
        .search-result-line .search-highlight {
            background: rgba(234, 179, 8, 0.35);
            color: #fff;
            padding: 0 1px;
            border-radius: 2px;
        }
        .search-match-count {
            font-size: 11px;
            color: #858585;
            padding: 4px 14px;
        }

        /* ===== DRAG RESIZE HANDLES ===== */
        .resize-handle-h {
            width: 4px;
            cursor: col-resize;
            background: transparent;
            position: relative;
            flex-shrink: 0;
            z-index: 10;
            transition: background 0.15s;
        }
        .resize-handle-h:hover,
        .resize-handle-h.active {
            background: #007acc;
        }
        .resize-handle-v {
            height: 4px;
            cursor: row-resize;
            background: transparent;
            position: relative;
            flex-shrink: 0;
            z-index: 10;
            transition: background 0.15s;
        }
        .resize-handle-v:hover,
        .resize-handle-v.active {
            background: #007acc;
        }

        /* ===== MODIFIED INDICATOR ===== */
        .vscode-tab .modified-dot {
            display: none;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cccccc;
            margin-left: 4px;
            flex-shrink: 0;
        }
        .vscode-tab.modified .modified-dot {
            display: inline-block;
        }
        .vscode-tab.modified .vscode-tab-close {
            display: none;
        }

        /* ===== TOAST NOTIFICATION ===== */
        .toast-notification {
            position: fixed;
            bottom: 40px;
            right: 20px;
            background: #252526;
            border: 1px solid #454545;
            color: #cccccc;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
            box-shadow: 0 4px 16px rgba(0,0,0,0.5);
            z-index: 10002;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: toastIn 0.2s ease-out;
            pointer-events: none;
        }
        @keyframes toastIn {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ===== INLINE RENAME INPUT ===== */
        .explorer-rename-input {
            background: #3c3c3c;
            border: 1px solid #007acc;
            color: #ffffff;
            padding: 2px 6px;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            border-radius: 2px;
            width: calc(100% - 30px);
        }

        /* ===== QUICK OPEN (Ctrl+P) ===== */
        .quick-open-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            display: none;
            justify-content: center;
            padding-top: 80px;
        }
        .quick-open-overlay.active {
            display: flex;
        }

        /* ===== SIDEBAR HIDDEN STATE ===== */
        .vscode-sidebar.hidden {
            display: none !important;
        }

        /* ===== VSCODE EXTRA PANELS & BADGES ===== */
        .activity-icon { position: relative; }
        .activity-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: #007acc;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 4px;
            border-radius: 8px;
            line-height: 1;
        }

        /* Git / Source Control Sidebar */
        #sidebar-git-content, #sidebar-debug-content, #sidebar-extensions-content {
            display: none;
            flex-direction: column;
            height: 100%;
        }
        .git-commit-box {
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-bottom: 1px solid #3c3c3c;
        }
        .git-commit-input {
            background: #3c3c3c;
            border: 1px solid #3c3c3c;
            color: #ffffff;
            font-size: 12px;
            padding: 6px 8px;
            outline: none;
            border-radius: 3px;
            font-family: inherit;
            resize: vertical;
            min-height: 50px;
        }
        .git-commit-input:focus { border-color: #007acc; }
        .btn-git-commit {
            background: #0e639c;
            color: #ffffff;
            border: none;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 3px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.15s;
        }
        .btn-git-commit:hover { background: #1177bb; }
        .git-changes-header {
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 700;
            color: #bbbbbb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #252526;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .git-change-item {
            display: flex;
            align-items: center;
            padding: 4px 12px;
            font-size: 12px;
            color: #cccccc;
            cursor: pointer;
            gap: 8px;
        }
        .git-change-item:hover { background: #2a2d2e; }
        .git-status-badge {
            margin-left: auto;
            font-size: 11px;
            font-weight: 700;
            width: 14px;
            height: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 2px;
        }
        .git-status-M { color: #e2c08d; }
        .git-status-U { color: #73c991; }
        .git-status-D { color: #c74e39; }

        /* Debug Sidebar */
        .debug-section {
            padding: 10px;
            border-bottom: 1px solid #3c3c3c;
        }
        .debug-tree-header {
            font-size: 11px;
            font-weight: 700;
            color: #bbbbbb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .debug-var-item {
            font-size: 11.5px;
            color: #aaaaaa;
            padding: 3px 0 3px 12px;
            font-family: 'Courier New', monospace;
        }
        .debug-var-item span.var-name { color: #9cdcfe; }
        .debug-var-item span.var-val { color: #ce9178; }

        /* Extensions Sidebar */
        .ext-search-box {
            padding: 10px;
            border-bottom: 1px solid #3c3c3c;
        }
        .ext-card {
            display: flex;
            gap: 10px;
            padding: 10px 12px;
            border-bottom: 1px solid #2d2d2d;
            transition: background 0.15s;
        }
        .ext-card:hover { background: #2a2d2e; }
        .ext-icon {
            width: 36px;
            height: 36px;
            background: #007acc;
            color: #ffffff;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .ext-info {
            flex: 1;
            min-width: 0;
        }
        .ext-name {
            font-size: 12.5px;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ext-publisher {
            font-size: 11px;
            color: #858585;
            margin-top: 1px;
        }
        .ext-desc {
            font-size: 11px;
            color: #aaaaaa;
            margin-top: 3px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .btn-ext-install {
            background: #0e639c;
            color: #ffffff;
            border: none;
            padding: 3px 8px;
            font-size: 11px;
            border-radius: 3px;
            cursor: pointer;
            margin-top: 5px;
            transition: background 0.15s;
        }
        .btn-ext-install:hover { background: #1177bb; }
        .btn-ext-install.installed {
            background: #3c3c3c;
            color: #858585;
        }

        /* Outline & Timeline Accordion */
        .explorer-accordion-header {
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 700;
            color: #bbbbbb;
            background: #252526;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            border-top: 1px solid #3c3c3c;
            user-select: none;
            text-transform: uppercase;
        }
        .explorer-accordion-header:hover { color: #ffffff; }
        .outline-item {
            padding: 3px 12px 3px 24px;
            font-size: 11.5px;
            color: #cccccc;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .outline-item:hover { background: #2a2d2e; color: #ffffff; }

    </style>
</head>
<body>
    <?php include '../includes/student_nav.php'; ?>

    <div class="page-header" style="margin-bottom: 10px;">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-code" style="color:var(--accent)"></i> VS Code IDE Lập Trình</h1>
            <p style="color: var(--text2); margin-top: 5px;">Môi trường thực hành lập trình trực quan, hỗ trợ viết web đa tệp tin (HTML, CSS, JS) và import chéo.</p>
        </div>
        <?php if ($latest_practice_result): ?>
            <a href="/tkb/student/tien_do.php#ket-qua-code" style="align-self:center; background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.25); color:#059669; border-radius:10px; padding:9px 13px; text-decoration:none; font-size:13px; font-weight:800; white-space:nowrap;">
                <i class="fa-solid fa-circle-check"></i> Điểm đã chấm: <?= number_format((float)$latest_practice_result['diem'], 1) ?>/10
            </a>
        <?php endif; ?>
    </div>

    <?php if ($practice_mode): ?>
        <div style="margin: 0 0 15px; padding: 12px 15px; border-radius: 10px; background: rgba(16, 185, 129, 0.10); border: 1px solid rgba(16, 185, 129, 0.25); color: #047857; font-size: 13.5px; font-weight: 600;">
            <i class="fa-solid fa-laptop-code"></i> Chế độ luyện Code đã được mở lại sau buổi kiểm tra/thi. Bài làm trong chế độ này chỉ để luyện tập, không được nộp để chấm điểm.
        </div>
    <?php elseif ($active_session): ?>
        <div style="margin: 0 0 15px; padding: 12px 15px; border-radius: 10px; background: rgba(59, 130, 246, 0.10); border: 1px solid rgba(59, 130, 246, 0.25); color: #1d4ed8; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div>
                <i class="fa-solid fa-graduation-cap"></i>
                Đang trong phiên <strong><?= htmlspecialchars($active_session['mo_ta'] ?: 'Thi/Thực hành') ?></strong>.
                <?php if ($current_session_submission): ?>
                    <span style="color:#ef4444; font-weight: bold;"> Đã nộp bài lúc <?= date('H:i (d/m/Y)', strtotime($current_session_submission['submitted_at'])) ?> — bài làm đã nộp không được phép chỉnh sửa hoặc nộp lại.</span>
                <?php else: ?>
                    Bạn có thể nộp bài sớm cho giáo viên bất cứ lúc nào trước khi hết giờ.
                <?php endif; ?>
            </div>
            <?php if (!empty($active_session['de_file_path'])): ?>
                <a href="/tkb/api/get_practice_exam.php?session_id=<?= intval($active_session['id']) ?>" target="_blank" rel="noopener" style="background:#2563eb; color:#fff; border-radius:8px; padding:8px 12px; text-decoration:none; font-size:13px; white-space:nowrap;">
                    <i class="fa-solid fa-file-lines"></i> Xem đề: <?= htmlspecialchars($active_session['de_file_name'] ?: 'Mở tệp đề') ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- IDE Window Simulator -->
    <div class="vscode-window">
        <!-- Title Bar -->
        <div class="vscode-title-bar">
            <div class="window-controls">
                <span class="window-dot dot-red"></span>
                <span class="window-dot dot-yellow"></span>
                <span class="window-dot dot-green"></span>
                
                <div class="menu-items">
                    <div class="menu-item-container">
                        <span class="menu-item" onclick="toggleMenuDropdown(event, 'file-dropdown')">File</span>
                        <div class="dropdown-menu-content" id="file-dropdown">
                            <a href="#" onclick="createNewFile(); return false;"><i class="fa-solid fa-file-circle-plus"></i> New File...</a>
                            <a href="#" onclick="createNewFolder(); return false;"><i class="fa-solid fa-folder-plus"></i> New Folder...</a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="openLocalFolder(); return false;"><i class="fa-solid fa-folder-open"></i> Open Folder (Local)...</a>
                            <a href="#" onclick="saveCurrentFileToLocal(); return false;"><i class="fa-solid fa-floppy-disk"></i> Save Current File <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+S</span></a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="downloadProjectZip(); return false;"><i class="fa-solid fa-file-zipper"></i> Download Project (.zip)</a>
                            <a href="#" onclick="clearEditor(); virtualFiles={}; openTabs=[]; renderExplorer(); renderTabs(); return false;"><i class="fa-solid fa-trash-can"></i> Clear Workspace</a>
                        </div>
                    </div>
                    <div class="menu-item-container">
                        <span class="menu-item" onclick="toggleMenuDropdown(event, 'edit-dropdown')">Edit</span>
                        <div class="dropdown-menu-content" id="edit-dropdown">
                            <a href="#" onclick="editor.undo(); return false;"><i class="fa-solid fa-rotate-left"></i> Undo <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+Z</span></a>
                            <a href="#" onclick="editor.redo(); return false;"><i class="fa-solid fa-rotate-right"></i> Redo <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+Y</span></a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="editor.execCommand('cut'); return false;"><i class="fa-solid fa-scissors"></i> Cut <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+X</span></a>
                            <a href="#" onclick="editor.execCommand('copy'); return false;"><i class="fa-solid fa-copy"></i> Copy <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+C</span></a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="editor.execCommand('find'); return false;"><i class="fa-solid fa-magnifying-glass"></i> Find <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+F</span></a>
                            <a href="#" onclick="editor.execCommand('replace'); return false;"><i class="fa-solid fa-right-left"></i> Replace <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+H</span></a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="toggleLineComment(); return false;"><i class="fa-solid fa-comment-slash"></i> Toggle Line Comment <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+/</span></a>
                            <a href="#" onclick="formatDocument(); return false;"><i class="fa-solid fa-wand-magic-sparkles"></i> Format Document <span style="margin-left:auto;font-size:10px;color:#666;">Shift+Alt+F</span></a>
                        </div>
                    </div>
                    <div class="menu-item-container">
                        <span class="menu-item" onclick="toggleMenuDropdown(event, 'selection-dropdown')">Selection</span>
                        <div class="dropdown-menu-content" id="selection-dropdown">
                            <a href="#" onclick="editor.selectAll(); return false;"><i class="fa-solid fa-object-group"></i> Select All <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+A</span></a>
                            <a href="#" onclick="editor.selectMoreLines(1); return false;"><i class="fa-solid fa-arrow-down-short-wide"></i> Expand Selection</a>
                        </div>
                    </div>
                    <div class="menu-item-container">
                        <span class="menu-item" onclick="toggleMenuDropdown(event, 'view-dropdown')">View</span>
                        <div class="dropdown-menu-content" id="view-dropdown">
                            <a href="#" onclick="openCommandPalette(); return false;"><i class="fa-solid fa-terminal"></i> Command Palette... <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+Shift+P</span></a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="toggleSidebarSection('explorer'); return false;"><i class="fa-regular fa-copy"></i> Explorer <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+Shift+E</span></a>
                            <a href="#" onclick="toggleSidebarSection('search'); return false;"><i class="fa-solid fa-magnifying-glass"></i> Search <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+Shift+F</span></a>
                            <a href="#" onclick="toggleSidebarSection('git'); return false;"><i class="fa-solid fa-code-branch"></i> Source Control <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+Shift+G</span></a>
                            <a href="#" onclick="toggleSidebarSection('debug'); return false;"><i class="fa-solid fa-bug"></i> Run & Debug <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+Shift+D</span></a>
                            <a href="#" onclick="toggleSidebarSection('extensions'); return false;"><i class="fa-solid fa-cubes"></i> Extensions <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+Shift+X</span></a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="toggleSidebarVisibility(); return false;"><i class="fa-solid fa-columns"></i> Toggle Primary Side Bar <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+B</span></a>
                            <a href="#" onclick="toggleTerminalPane(); return false;"><i class="fa-solid fa-terminal"></i> Toggle Panel <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+`</span></a>
                            <a href="#" onclick="toggleWordWrap(); return false;"><i class="fa-solid fa-align-left"></i> Toggle Word Wrap <span style="margin-left:auto;font-size:10px;color:#666;">Alt+Z</span></a>
                        </div>
                    </div>
                    <div class="menu-item-container">
                        <span class="menu-item" onclick="toggleMenuDropdown(event, 'go-dropdown')">Go</span>
                        <div class="dropdown-menu-content" id="go-dropdown">
                            <a href="#" onclick="openQuickOpen(); return false;"><i class="fa-solid fa-file-lines"></i> Go to File... <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+P</span></a>
                            <a href="#" onclick="gotoLinePrompt(); return false;"><i class="fa-solid fa-arrow-down-1-9"></i> Go to Line/Column... <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+G</span></a>
                        </div>
                    </div>
                    <div class="menu-item-container">
                        <span class="menu-item" onclick="toggleMenuDropdown(event, 'run-dropdown')">Run</span>
                        <div class="dropdown-menu-content" id="run-dropdown">
                            <a href="#" onclick="runCode(); return false;"><i class="fa-solid fa-play" style="color:#10b981;"></i> Start Debugging / Run <span style="margin-left:auto;font-size:10px;color:#666;">Ctrl+Enter</span></a>
                            <a href="#" onclick="runCode(); return false;"><i class="fa-solid fa-forward-step"></i> Run Without Debugging</a>
                        </div>
                    </div>
                    <div class="menu-item-container">
                        <span class="menu-item" onclick="toggleMenuDropdown(event, 'terminal-dropdown')">Terminal</span>
                        <div class="dropdown-menu-content" id="terminal-dropdown">
                            <a href="#" onclick="toggleTerminalPane(); switchTerminalTab('terminal'); return false;"><i class="fa-solid fa-terminal"></i> New Terminal</a>
                            <a href="#" onclick="clearConsole(); return false;"><i class="fa-solid fa-ban"></i> Clear Terminal Output</a>
                        </div>
                    </div>
                    <div class="menu-item-container">
                        <span class="menu-item" onclick="toggleMenuDropdown(event, 'help-dropdown')">Help</span>
                        <div class="dropdown-menu-content" id="help-dropdown">
                            <a href="#" onclick="clearEditor(); return false;"><i class="fa-solid fa-circle-info"></i> Welcome Screen</a>
                            <a href="#" onclick="openCommandPalette(); return false;"><i class="fa-solid fa-keyboard"></i> Keyboard Shortcuts Reference</a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="alert('Visual Studio Code Web IDE Simulator v2.0 - Trường Cao Đẳng Cà Mau Portal'); return false;"><i class="fa-solid fa-code"></i> About VS Code IDE</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="window-title" id="vscode-window-title">Trường Cao Đẳng Cà Mau Portal - index.html</div>
            <div style="font-size: 11px; opacity: 0.6;"><i class="fa-solid fa-cloud-sun"></i> Connected</div>
        </div>

        <!-- Body -->
        <div class="vscode-body">
            <!-- Activity Bar (Far Left) -->
            <div class="vscode-activity-bar">
                <div class="activity-icons">
                    <div class="activity-icon active" onclick="toggleSidebarSection('explorer')" id="act-explorer">
                        <i class="fa-regular fa-copy"></i>
                        <span class="activity-tooltip">Explorer (Ctrl+Shift+E)</span>
                    </div>
                    <div class="activity-icon" onclick="toggleSidebarSection('search')" id="act-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span class="activity-tooltip">Search (Ctrl+Shift+F)</span>
                    </div>
                    <div class="activity-icon" onclick="toggleSidebarSection('git')" id="act-git">
                        <i class="fa-solid fa-code-branch"></i>
                        <span class="activity-badge" id="git-changes-badge" style="display:none;">0</span>
                        <span class="activity-tooltip">Source Control (Ctrl+Shift+G)</span>
                    </div>
                    <div class="activity-icon" onclick="toggleSidebarSection('debug')" id="act-debug">
                        <i class="fa-solid fa-bug"></i>
                        <span class="activity-tooltip">Run & Debug (Ctrl+Shift+D)</span>
                    </div>
                    <div class="activity-icon" onclick="toggleSidebarSection('extensions')" id="act-extensions">
                        <i class="fa-solid fa-cubes"></i>
                        <span class="activity-tooltip">Extensions (Ctrl+Shift+X)</span>
                    </div>
                    <div class="activity-icon" onclick="toggleSidebarSection('settings')" id="act-settings">
                        <i class="fa-solid fa-sliders"></i>
                        <span class="activity-tooltip">Editor Settings</span>
                    </div>
                </div>
                <div>
                    <div class="activity-icon" onclick="showAccountInfo()">
                        <i class="fa-solid fa-circle-user"></i>
                        <span class="activity-tooltip" style="left:55px;">Account</span>
                    </div>
                    <div class="activity-icon" onclick="resetTemplate()">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                        <span class="activity-tooltip" style="left:55px;">Reset File Template</span>
                    </div>
                    <div class="activity-icon" onclick="openCommandPalette()">
                        <i class="fa-solid fa-gear"></i>
                        <span class="activity-tooltip" style="left:55px;">Manage (Settings/Shortcuts)</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Explorer / Settings Pane) -->
            <div class="vscode-sidebar" id="vscode-sidebar">
                <!-- EXPLORER TAB -->
                <div id="sidebar-explorer-content" style="display: flex; flex-direction: column; height: 100%;">
                    <div class="sidebar-header">
                        <span>EXPLORER: WORKSPACE</span>
                        <div style="display:flex; gap: 8px;">
                            <span class="explorer-action-icon" onclick="triggerFileUpload()" title="Tải tệp (Ảnh, Audio, Video, Code)..."><i class="fa-solid fa-cloud-arrow-up"></i></span>
                            <span class="explorer-action-icon" onclick="createNewFile()" title="Tạo File Mới..."><i class="fa-solid fa-file-circle-plus"></i></span>
                            <span class="explorer-action-icon" onclick="createNewFolder()" title="Tạo Thư Mục Mới..."><i class="fa-solid fa-folder-plus"></i></span>
                            <i class="fa-solid fa-ellipsis" style="color:#858585; cursor:default;"></i>
                        </div>
                    </div>
                    
                    <div class="explorer-section">
                        <div class="explorer-folder">
                            <i class="fa-solid fa-angle-down"></i> <i class="fa-solid fa-folder-open" style="color: #e2b73c;"></i> STUDENT_PROJECT
                        </div>
                        
                        <!-- File explorer rows rendered dynamically -->
                        <div class="explorer-files"></div>
                        
                        <div style="padding: 8px 12px; display:flex; gap:6px;">
                            <button onclick="createNewFile()" style="background:transparent; border:1px dashed #555; color:#858585; flex:1; padding:5px; border-radius:4px; cursor:pointer; font-size:10px; font-weight:700; transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:4px; outline:none;" onmouseover="this.style.borderColor='#bbb';this.style.color='#fff';" onmouseout="this.style.borderColor='#555';this.style.color='#858585';">
                                <i class="fa-solid fa-plus"></i> File
                            </button>
                            <button onclick="createNewFolder()" style="background:transparent; border:1px dashed #555; color:#858585; flex:1; padding:5px; border-radius:4px; cursor:pointer; font-size:10px; font-weight:700; transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:4px; outline:none;" onmouseover="this.style.borderColor='#bbb';this.style.color='#fff';" onmouseout="this.style.borderColor='#555';this.style.color='#858585';">
                                <i class="fa-solid fa-folder-plus"></i> Folder
                            </button>
                            <button onclick="triggerFileUpload()" style="background:transparent; border:1px dashed #555; color:#858585; flex:1; padding:5px; border-radius:4px; cursor:pointer; font-size:10px; font-weight:700; transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:4px; outline:none;" onmouseover="this.style.borderColor='#3b82f6';this.style.color='#fff';" onmouseout="this.style.borderColor='#555';this.style.color='#858585';" title="Tải ảnh, video, âm thanh hoặc code từ máy tính">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                            </button>
                        </div>

                        <!-- OUTLINE SECTION -->
                        <div class="explorer-accordion-header" onclick="toggleAccordion('outline')">
                            <i class="fa-solid fa-chevron-down" id="outline-accordion-icon"></i> OUTLINE
                        </div>
                        <div id="explorer-outline-content" style="max-height: 200px; overflow-y: auto;">
                            <div style="padding:8px 12px; font-size:11px; color:#858585;">No symbols found in active file</div>
                        </div>

                        <!-- TIMELINE SECTION -->
                        <div class="explorer-accordion-header" onclick="toggleAccordion('timeline')">
                            <i class="fa-solid fa-chevron-down" id="timeline-accordion-icon"></i> TIMELINE
                        </div>
                        <div id="explorer-timeline-content" style="padding:6px 12px; font-size:11px; color:#858585;">
                            <div style="display:flex; align-items:center; gap:6px; padding:3px 0;"><i class="fa-solid fa-history" style="color:#007acc;"></i> File Saved (Local Snapshot)</div>
                        </div>
                    </div>
                    <input type="file" id="input-upload-file" multiple style="display:none;" onchange="handleFileUpload(event)">
                </div>

                <!-- SOURCE CONTROL / GIT TAB -->
                <div id="sidebar-git-content">
                    <div class="sidebar-header">
                        <span>SOURCE CONTROL: GIT</span>
                        <div style="display:flex; gap:8px;">
                            <span class="explorer-action-icon" onclick="commitGitChanges()" title="Commit & Sync"><i class="fa-solid fa-check"></i></span>
                            <span class="explorer-action-icon" onclick="renderGitPanel()" title="Refresh Status"><i class="fa-solid fa-rotate-right"></i></span>
                        </div>
                    </div>
                    <div class="git-commit-box">
                        <textarea class="git-commit-input" id="git-commit-msg" placeholder="Message (Ctrl+Enter to commit)"></textarea>
                        <button class="btn-git-commit" onclick="commitGitChanges()"><i class="fa-solid fa-check"></i> Commit & Sync to 'main'</button>
                    </div>
                    <div class="git-changes-header">
                        <span>CHANGES</span>
                        <span id="git-changes-count" style="font-weight:700;">0</span>
                    </div>
                    <div id="git-changes-list" style="flex:1; overflow-y:auto;"></div>
                </div>

                <!-- RUN & DEBUG TAB -->
                <div id="sidebar-debug-content">
                    <div class="sidebar-header">
                        <span>RUN AND DEBUG</span>
                    </div>
                    <div class="debug-section">
                        <div style="margin-bottom:10px;">
                            <label style="color:#858585; display:block; margin-bottom:5px; font-size:11px; font-weight:700;">LAUNCH CONFIGURATION</label>
                            <select id="debug-config-select" class="control-select" style="width:100%; border-color:#3c3c3c;">
                                <option value="web">Run Web (HTML/PHP Live Server)</option>
                                <option value="python">Python: Current File</option>
                                <option value="c_cpp">C/C++: GCC Build & Debug</option>
                                <option value="java">Java: Launch Main Class</option>
                            </select>
                        </div>
                        <button class="btn-git-commit" style="background:#388e3c;" onclick="runCode()"><i class="fa-solid fa-play"></i> Start Debugging (F5)</button>
                    </div>
                    <div style="flex:1; overflow-y:auto;">
                        <div class="debug-section">
                            <div class="debug-tree-header"><i class="fa-solid fa-code-branch"></i> VARIABLES</div>
                            <div id="debug-variables-list">
                                <div class="debug-var-item"><span class="var-name">activeFile:</span> <span class="var-val">"index.html"</span></div>
                                <div class="debug-var-item"><span class="var-name">virtualFilesCount:</span> <span class="var-val">3</span></div>
                                <div class="debug-var-item"><span class="var-name">environment:</span> <span class="var-val">"PHP/Vite Web Simulator"</span></div>
                            </div>
                        </div>
                        <div class="debug-section">
                            <div class="debug-tree-header"><i class="fa-solid fa-layer-group"></i> CALL STACK</div>
                            <div style="font-size:11.5px; color:#aaaaaa; padding-left:12px;">main() [index.html:1]</div>
                        </div>
                        <div class="debug-section">
                            <div class="debug-tree-header"><i class="fa-solid fa-circle-dot" style="color:#f43f5e;"></i> BREAKPOINTS</div>
                            <div style="font-size:11px; color:#858585; padding-left:12px;">Uncaught Exceptions (Enabled)</div>
                        </div>
                    </div>
                </div>

                <!-- EXTENSIONS TAB -->
                <div id="sidebar-extensions-content">
                    <div class="sidebar-header">
                        <span>EXTENSIONS: MARKETPLACE</span>
                    </div>
                    <div class="ext-search-box">
                        <input type="text" class="search-input-box" id="ext-search-input" placeholder="Search Extensions in Marketplace" oninput="filterExtensions()">
                    </div>
                    <div id="ext-list-container" style="flex:1; overflow-y:auto;"></div>
                </div>

                <!-- SETTINGS TAB -->
                <div id="sidebar-settings-content" style="display: none; flex-direction: column; height: 100%;">
                    <div class="sidebar-header">
                        <span>EDITOR SETTINGS</span>
                    </div>
                    <div style="padding: 15px; display:flex; flex-direction:column; gap:15px;">
                        <div>
                            <label style="color:#858585; display:block; margin-bottom:5px; font-weight:700;">FONT SIZE</label>
                            <select id="setting-font-size" class="control-select" style="width:100%; border-color:#3c3c3c;" onchange="changeFontSize()">
                                <option value="12px">12px</option>
                                <option value="13px">13px</option>
                                <option value="14px" selected>14px</option>
                                <option value="16px">16px</option>
                                <option value="18px">18px</option>
                            </select>
                        </div>
                        <div>
                            <label style="color:#858585; display:block; margin-bottom:5px; font-weight:700;">EDITOR THEME</label>
                            <select id="setting-theme" class="control-select" style="width:100%; border-color:#3c3c3c;" onchange="changeTheme()">
                                <option value="ace/theme/monokai" selected>Monokai Dark</option>
                                <option value="ace/theme/tomorrow_night">Tomorrow Night</option>
                                <option value="ace/theme/dracula">Dracula Dark</option>
                                <option value="ace/theme/twilight">Twilight</option>
                                <option value="ace/theme/chrome">Chrome Light</option>
                            </select>
                        </div>
                        <div style="margin-top:10px; border-top:1px solid #3c3c3c; padding-top:15px; font-size:11px; color:#858585;">
                            <p style="font-weight:700; color:#e1e1e1; margin-bottom:8px;">KEYBOARD SHORTCUTS</p>
                            <div style="display:flex; flex-direction:column; gap:5px;">
                                <div style="display:flex; justify-content:space-between;"><span>Run Code</span> <kbd style="background:#37373d; padding:2px 5px; border-radius:3px;">Ctrl+Enter</kbd></div>
                                <div style="display:flex; justify-content:space-between;"><span>Save File</span> <kbd style="background:#37373d; padding:2px 5px; border-radius:3px;">Ctrl+S</kbd></div>
                                <div style="display:flex; justify-content:space-between;"><span>Command Palette</span> <kbd style="background:#37373d; padding:2px 5px; border-radius:3px;">Ctrl+Shift+P</kbd></div>
                                <div style="display:flex; justify-content:space-between;"><span>Quick Open</span> <kbd style="background:#37373d; padding:2px 5px; border-radius:3px;">Ctrl+P</kbd></div>
                                <div style="display:flex; justify-content:space-between;"><span>Toggle Sidebar</span> <kbd style="background:#37373d; padding:2px 5px; border-radius:3px;">Ctrl+B</kbd></div>
                                <div style="display:flex; justify-content:space-between;"><span>Toggle Terminal</span> <kbd style="background:#37373d; padding:2px 5px; border-radius:3px;">Ctrl+`</kbd></div>
                                <div style="display:flex; justify-content:space-between;"><span>Find</span> <kbd style="background:#37373d; padding:2px 5px; border-radius:3px;">Ctrl+F</kbd></div>
                                <div style="display:flex; justify-content:space-between;"><span>Replace</span> <kbd style="background:#37373d; padding:2px 5px; border-radius:3px;">Ctrl+H</kbd></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEARCH TAB -->
                <div id="sidebar-search-content">
                    <div class="sidebar-header">
                        <span>SEARCH</span>
                    </div>
                    <div style="padding: 10px;">
                        <input type="text" class="search-input-box" id="search-input" placeholder="Search" oninput="performSearch()">
                        <div style="margin-top:6px;">
                            <input type="text" class="search-input-box" id="search-replace-input" placeholder="Replace" style="margin-top:4px;">
                        </div>
                    </div>
                    <div class="search-match-count" id="search-match-count"></div>
                    <div class="search-results" id="search-results"></div>
                </div>
            </div>

            <!-- Resize Handle: Sidebar <-> Editor -->
            <div class="resize-handle-h" id="sidebar-resize-handle"></div>

            <!-- Main area (Tabs + Editor + Terminal) -->
            <div class="vscode-main-area">
                <!-- Editor Tabs -->
                <div class="vscode-tabs-bar" id="vscode-tabs-bar"></div>

                <!-- Breadcrumb Bar -->
                <div class="vscode-breadcrumb-bar" id="vscode-breadcrumb-bar">
                    <span class="breadcrumb-item"><i class="fa-solid fa-folder" style="color:#e2b73c;font-size:11px;"></i> STUDENT_PROJECT</span>
                </div>

                <!-- Editor Action Buttons Toolbar -->
                <div class="vscode-editor-toolbar">
                    <?php if ($active_session && !empty($active_session['de_file_path'])): ?>
                    <a href="/tkb/api/get_practice_exam.php?session_id=<?= intval($active_session['id']) ?>" target="_blank" rel="noopener" class="editor-action-btn btn-exam">
                        <i class="fa-solid fa-file-lines"></i> Xem đề bài
                    </a>
                    <?php endif; ?>
                    <?php if ($active_session): ?>
                    <button type="button" class="editor-action-btn btn-submit<?= $current_session_submission ? ' submitted' : '' ?>" id="btn-submit-exam" onclick="earlySubmitProject()" <?= $current_session_submission ? 'disabled style="opacity: 0.6; cursor: not-allowed;"' : '' ?>>
                        <i class="fa-solid fa-paper-plane"></i> <span id="btn-submit-exam-text"><?= $current_session_submission ? 'Đã nộp bài' : 'Nộp bài sớm' ?></span>
                    </button>
                    <?php endif; ?>
                    <button class="editor-action-btn" onclick="formatDocument()" title="Format Document (Shift+Alt+F)">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Format
                    </button>
                    <button class="editor-action-btn" onclick="downloadCode()">
                        <i class="fa-solid fa-download"></i> Tải về (.file)
                    </button>
                    <button class="editor-action-btn" id="btn-open-tab" onclick="openPreviewInNewTab()" style="display:none;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Mở tab mới
                    </button>
                    <button class="editor-action-btn btn-run" onclick="runCode()">
                        <i class="fa-solid fa-play" id="run-icon"></i> <span id="run-text">Chạy Code (Ctrl+Enter)</span>
                    </button>
                </div>

                <!-- Ace Editor Area -->
                <div class="vscode-editor-pane">
                    <div id="editor"></div>
                    <div id="media-preview" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: #151515; align-items: center; justify-content: center; flex-direction: column; color: #cccccc; overflow: auto; padding: 20px; user-select: none; z-index: 10;"></div>
                    <!-- Welcome Screen -->
                    <div class="vscode-welcome" id="vscode-welcome">
                        <div class="welcome-logo"><i class="fa-solid fa-code"></i></div>
                        <div class="welcome-title">Visual Studio Code</div>
                        <div class="welcome-subtitle">Editing evolved — Trường Cao Đẳng Cà Mau Portal</div>
                        <div class="welcome-actions">
                            <div class="welcome-action-title">Start</div>
                            <button class="welcome-action" onclick="createNewFile()">
                                <i class="fa-solid fa-file-circle-plus"></i> New File...
                            </button>
                            <button class="welcome-action" onclick="openLocalFolder()">
                                <i class="fa-solid fa-folder-open"></i> Open Folder...
                            </button>
                            <button class="welcome-action" onclick="resetAndLoadTemplate()">
                                <i class="fa-solid fa-clone"></i> Start with Template...
                            </button>
                        </div>
                        <div class="welcome-shortcuts">
                            <div class="welcome-action-title" style="margin-top:16px;">Keyboard Shortcuts</div>
                            <div class="welcome-shortcut"><span>Show All Commands</span> <kbd>Ctrl+Shift+P</kbd></div>
                            <div class="welcome-shortcut"><span>Quick Open File</span> <kbd>Ctrl+P</kbd></div>
                            <div class="welcome-shortcut"><span>Toggle Terminal</span> <kbd>Ctrl+`</kbd></div>
                            <div class="welcome-shortcut"><span>Toggle Sidebar</span> <kbd>Ctrl+B</kbd></div>
                            <div class="welcome-shortcut"><span>Run Code</span> <kbd>Ctrl+Enter</kbd></div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Panel (Console / Input / Preview) -->
                <!-- Resize Handle: Editor <-> Terminal -->
                <div class="resize-handle-v" id="terminal-resize-handle"></div>

                <div class="vscode-terminal-pane">
                    <!-- Console Header tabs -->
                    <div class="terminal-header">
                        <div class="terminal-tabs">
                            <span class="terminal-tab active" onclick="switchTerminalTab('terminal')" id="term-tab-terminal">Output / Terminal</span>
                            <span class="terminal-tab" onclick="switchTerminalTab('input')" id="term-tab-input">Input (stdin)</span>
                            <span class="terminal-tab" onclick="switchTerminalTab('preview')" id="term-tab-preview" style="display:none;">Live Web Preview</span>
                        </div>
                        <div class="terminal-actions">
                            <span id="run-status-badge" style="font-size:11px; color:#a8b9cc;"><i class="fa-solid fa-circle-notch"></i> Sẵn sàng</span>
                            <button class="terminal-btn" onclick="clearConsole()"><i class="fa-solid fa-ban"></i> Clear Output</button>
                            <button class="terminal-btn" onclick="copyConsoleOutput()"><i class="fa-solid fa-copy"></i> Copy</button>
                            <button class="terminal-btn" id="btn-toggle-terminal" onclick="toggleTerminalPane()"><i class="fa-solid fa-chevron-down"></i> <span id="toggle-terminal-text">Thu nhỏ</span></button>
                        </div>
                    </div>

                    <!-- Panel Terminal Body -->
                    <div class="terminal-body">
                        <!-- Output Area -->
                        <div class="terminal-output" id="terminal-output-content">
                            <div class="terminal-system-info">[Hệ Thống] Trình biên dịch ảo VS Code đã sẵn sàng. Viết code và bấm "Chạy Code" để xem kết quả.</div>
                        </div>

                        <!-- Stdin Input Area -->
                        <div class="terminal-input-container" id="terminal-input-content">
                            <textarea id="stdin" class="terminal-textarea" placeholder="Nhập dữ liệu đầu vào Standard Input (stdin) cho chương trình tại đây nếu mã nguồn của bạn yêu cầu dữ liệu nhập (ví dụ: lệnh input(), scanf, cin, scanner)..."></textarea>
                        </div>

                        <!-- HTML live browser frame -->
                        <iframe id="preview-frame" class="preview-iframe"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Bar -->
        <div class="vscode-status-bar">
            <div class="status-left">
                <div class="status-item" style="background:#c6143c; padding: 0 8px;"><i class="fa-solid fa-code-branch"></i> main</div>
                <div class="status-item" id="status-running-status"><i class="fa-solid fa-check"></i> Ready</div>
                <div class="status-item" id="status-errors" style="cursor:pointer;" onclick="toggleTerminalPane()"><i class="fa-solid fa-circle-xmark" style="color:#f14c4c;"></i> 0 <i class="fa-solid fa-triangle-exclamation" style="color:#cca700;"></i> 0</div>
                <div class="status-item" id="session-title-container" style="background:#1e293b; padding: 0 12px; font-weight:600; display:none; border-radius:3px;">
                    <i class="fa-solid fa-graduation-cap" style="color:var(--accent)"></i> 
                    <span id="session-title-label">Phiên thi: ...</span>
                </div>
                <div class="status-item" id="session-countdown-container" style="background:#0f172a; padding: 0 12px; font-weight:700; color:#f59e0b; display:none; border-radius:3px; align-items:center;">
                    <i class="fa-solid fa-clock" style="margin-right:3px; animation: pulse 1.5s infinite;"></i> 
                    <span id="session-countdown-label">Còn lại: --:--</span>
                </div>
            </div>
            <div class="status-right">
                <div class="status-item" id="status-cursor">Ln 1, Col 1</div>
                <div class="status-item" id="status-indent" onclick="toggleIndentation()" style="cursor:pointer;">Spaces: 4</div>
                <div class="status-item">UTF-8</div>
                <div class="status-item" id="status-eol">LF</div>
                <div class="status-item" id="status-lang-badge" style="font-weight: 700;">Plain Text</div>
                <div class="status-item" style="cursor:pointer;" onclick="openCommandPalette()"><i class="fa-solid fa-bell"></i></div>
            </div>
        </div>
    </div>

    <!-- Command Palette Overlay -->
    <div class="cmd-palette-overlay" id="cmd-palette-overlay" onclick="closeCommandPalette(event)">
        <div class="cmd-palette" onclick="event.stopPropagation()">
            <input type="text" class="cmd-palette-input" id="cmd-palette-input" placeholder="> Type a command..." oninput="filterCommandPalette()" onkeydown="handleCmdPaletteKey(event)">
            <div class="cmd-palette-list" id="cmd-palette-list"></div>
        </div>
    </div>

    <!-- Quick Open Overlay -->
    <div class="quick-open-overlay" id="quick-open-overlay" onclick="closeQuickOpen(event)">
        <div class="cmd-palette" onclick="event.stopPropagation()">
            <input type="text" class="cmd-palette-input" id="quick-open-input" placeholder="Search files by name..." oninput="filterQuickOpen()" onkeydown="handleQuickOpenKey(event)">
            <div class="cmd-palette-list" id="quick-open-list"></div>
        </div>
    </div>

    <!-- Context Menus -->
    <div class="ctx-menu" id="ctx-menu-editor"></div>
    <div class="ctx-menu" id="ctx-menu-explorer"></div>

    <script>
        // Default templates mapping
        const templates = {
            python: `# Lập trình Python 3
name = input("Nhập tên của bạn: ")
print(f"Chào mừng {name} đến với hệ thống Thực hành Lập trình!")
print("Dưới đây là bình phương các số từ 1 đến 5:")
for i in range(1, 6):
    print(f"{i} bình phương là {i**2}")
`,
            javascript: `// Lập trình JavaScript (Node.js runtime)
console.log("Chào mừng bạn đến với Javascript Runtime!");
const numbers = [1, 2, 3, 4, 5];
const squared = numbers.map(x => x * x);
console.log("Danh sách bình phương của các số:", squared);
`,
            php: `<?php echo "<?php"; ?>
// Lập trình PHP kết nối MySQL (Database: student_sandbox)
include '_db_config.php'; // Tự động tạo bởi hệ thống

echo "=== Danh sách sản phẩm từ Database ===\\n\\n";

$rows = db_query("SELECT * FROM san_pham ORDER BY id");
if (empty($rows)) {
    echo "Không có sản phẩm nào trong database.\\n";
} else {
    foreach ($rows as $row) {
        echo $row['id'] . ". " . $row['ten_sp'] . " - " . number_format($row['gia']) . " VNĐ\\n";
        echo "   Mô tả: " . $row['mo_ta'] . "\\n\\n";
    }
    echo "Tổng: " . count($rows) . " sản phẩm.\\n";
}

$conn->close();
`,
            html: `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang Web Thực Hành Đa Phương Tiện</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Liên kết stylesheet ảo style.css -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1><i class="fa-solid fa-layer-group"></i> Dự Án Web Đa Phương Tiện</h1>
        <p>Hệ thống hỗ trợ hiển thị Ảnh, Video và Âm Thanh đầy đủ.</p>
        
        <!-- 1. Hình ảnh Avatar -->
        <div class="media-box">
            <h3><i class="fa-solid fa-image"></i> 1. Hình ảnh Avatar (avatar.jpg):</h3>
            <img src="avatar.jpg" alt="Avatar" class="avatar-img">
        </div>

        <!-- 2. Âm thanh Audio -->
        <div class="media-box">
            <h3><i class="fa-solid fa-music"></i> 2. Nhạc nền Audio (bg_music.mp3):</h3>
            <audio controls src="bg_music.mp3" class="media-player"></audio>
        </div>

        <!-- 3. Video mẫu -->
        <div class="media-box">
            <h3><i class="fa-solid fa-film"></i> 3. Video mẫu (intro_video.mp4):</h3>
            <video controls src="intro_video.mp4" class="media-player"></video>
        </div>

        <button class="btn" onclick="sayHello()">Bấm thử JavaScript</button>
    </div>

    <!-- Liên kết script ảo script.js -->
    <script src="script.js"><\/script>
</body>
</html>
`,
            css: `/* CSS Stylesheet cho trang Web đa phương tiện */
body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
    color: #f8fafc;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
    padding: 20px;
    box-sizing: border-box;
}
.container {
    background: rgba(30, 41, 59, 0.95);
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    text-align: center;
    max-width: 450px;
    width: 100%;
    border: 1px solid rgba(255,255,255,0.1);
}
h1 {
    color: #38bdf8;
    font-size: 20px;
    margin-bottom: 5px;
}
p {
    color: #94a3b8;
    font-size: 13px;
    margin-bottom: 20px;
}
.media-box {
    background: #0f172a;
    padding: 14px;
    border-radius: 10px;
    margin-bottom: 15px;
    border: 1px solid #334155;
    text-align: left;
}
.media-box h3 {
    font-size: 13px;
    color: #e2e8f0;
    margin: 0 0 10px 0;
}
.avatar-img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
    margin: 0 auto;
    border: 3px solid #3b82f6;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}
.media-player {
    width: 100%;
    border-radius: 6px;
    outline: none;
}
.btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 10px;
    width: 100%;
}
.btn:hover {
    background: #2563eb;
}
`,
            js: `// Tập lệnh JavaScript cho trang Web
function sayHello() {
    alert("Xin chào! Mã JavaScript từ file script.js ảo đã chạy thành công.");
}
`,
            c: `// Lập trình C (GCC)
#include <stdio.h>

int main() {
    char name[100];
    printf("Nhập tên của bạn: ");
    if (scanf("%99s", name) == 1) {
        printf("Xin chào %s! Chúc bạn thực hành lập trình C tốt.\\n", name);
    } else {
        printf("Xin chào lập trình viên C!\\n");
    }
    return 0;
}
`,
            cpp: `// Lập trình C++ (G++)
#include <iostream>
#include <string>
using namespace std;

int main() {
    string name;
    cout << "Nhập tên của bạn: ";
    if (cin >> name) {
        cout << "Chào mừng " << name << " đến với lập trình C++!" << endl;
    } else {
        cout << "Chào mừng bạn đến với C++!" << endl;
    }
    return 0;
}
`,
            java: `// Lập trình Java (Mặc định Class chính là Main)
import java.util.Scanner;

public class Main {
    public static void main(String[] args) {
        System.out.println("Chào mừng bạn đến với lập trình Java!");
        Scanner scanner = new Scanner(System.in);
        System.out.print("Nhập tên của bạn: ");
        if (scanner.hasNextLine()) {
            String name = scanner.nextLine();
            System.out.println("Xin chào, " + name + "! Chúc bạn học tốt Java.");
        }
        scanner.close();
    }
}
`
        };

        // File extensions mapping to Ace Editor mode and syntax highlight labels
        const extMapping = {
            py: { lang: 'python', mode: 'ace/mode/python', label: 'Python' },
            js: { lang: 'javascript', mode: 'ace/mode/javascript', label: 'JavaScript' },
            php: { lang: 'php', mode: 'ace/mode/php', label: 'PHP' },
            html: { lang: 'html', mode: 'ace/mode/html', label: 'HTML Preview' },
            htm: { lang: 'html', mode: 'ace/mode/html', label: 'HTML Preview' },
            css: { lang: 'css', mode: 'ace/mode/css', label: 'CSS Stylesheet' },
            c: { lang: 'c', mode: 'ace/mode/c_cpp', label: 'C' },
            cpp: { lang: 'cpp', mode: 'ace/mode/c_cpp', label: 'C++' },
            java: { lang: 'java', mode: 'ace/mode/java', label: 'Java' },
            txt: { lang: 'text', mode: 'ace/mode/text', label: 'Plain Text' }
        };

        // Virtual Files system in memory
        let virtualFiles = {};
        let openTabs = [];
        let activeFile = '';
        let lastDeployUrl = ''; // Last deployed web URL
        let modifiedFiles = {}; // Track files with unsaved changes
        let cmdPaletteSelectedIndex = 0;
        let quickOpenSelectedIndex = 0;

        // Open last deployed preview in a new browser tab
        function openPreviewInNewTab() {
            if (lastDeployUrl) {
                window.open(lastDeployUrl, '_blank');
            } else {
                alert('Chưa có dự án web nào được triển khai. Hãy bấm "Chạy Code" trước!');
            }
        }

        // Get file configuration info based on extension
        function getFileInfo(fileName) {
            const ext = fileName.split('.').pop().toLowerCase();
            return extMapping[ext] || { lang: 'text', mode: 'ace/mode/text', label: 'Plain Text' };
        }

        // Get file FontAwesome icon based on extension
        function getFileIcon(fileName) {
            const ext = fileName.split('.').pop().toLowerCase();
            switch (ext) {
                case 'py': return 'fa-brands fa-python file-icon python';
                case 'js': return 'fa-brands fa-js file-icon javascript';
                case 'php': return 'fa-brands fa-php file-icon php';
                case 'html':
                case 'htm': return 'fa-brands fa-html5 file-icon html';
                case 'css': return 'fa-brands fa-css3-alt file-icon css';
                case 'c':
                case 'cpp': return 'fa-solid fa-file-code file-icon cpp';
                case 'java': return 'fa-brands fa-java file-icon java';
                default: return 'fa-regular fa-file file-icon';
            }
        }

        // Initialize Ace Editor with autocomplete and snippets
        ace.require('ace/ext/language_tools');
        let editor = ace.edit("editor");
        editor.setTheme("ace/theme/monokai");
        editor.session.setMode("ace/mode/text");
        editor.setValue("", -1);
        editor.setReadOnly(true);
        editor.setShowPrintMargin(false);
        editor.setOptions({
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true,
            enableSnippets: true,
            showGutter: true,
            highlightActiveLine: true,
            highlightSelectedWord: true,
            displayIndentGuides: true,
            fadeFoldWidgets: true,
            fontSize: '14px',
            scrollPastEnd: 0.5,
            animatedScroll: true
        });
        try { editor.setOption('enableEmmet', true); } catch(e) {}

        // Track cursor position in status bar
        editor.selection.on("changeCursor", function() {
            const cursor = editor.getCursorPosition();
            const statusCursor = document.getElementById('status-cursor');
            if (statusCursor) {
                statusCursor.innerText = `Ln ${cursor.row + 1}, Col ${cursor.column + 1}`;
            }
        });

        // Track content changes for modified indicator
        editor.on('change', function() {
            if (activeFile && !hasSubmitted) {
                modifiedFiles[activeFile] = true;
                updateTabModifiedState();
            }
        });

        // Keyboard shortcut: Ctrl + Enter = Run Code
        editor.commands.addCommand({
            name: 'runCodeShortcut',
            bindKey: {win: 'Ctrl-Enter',  mac: 'Command-Enter'},
            exec: function() { runCode(); },
            readOnly: true
        });

        // Keyboard shortcut: Ctrl+S = Save (toast notification)
        editor.commands.addCommand({
            name: 'saveFile',
            bindKey: {win: 'Ctrl-S', mac: 'Command-S'},
            exec: function() {
                if (activeFile && !hasSubmitted) {
                    virtualFiles[activeFile] = editor.getValue();
                    delete modifiedFiles[activeFile];
                    updateTabModifiedState();
                    showToast('<i class="fa-solid fa-check" style="color:#10b981;"></i> File saved: ' + activeFile);
                }
            }
        });

        // Keyboard shortcut: Ctrl+P = Quick Open
        editor.commands.addCommand({
            name: 'quickOpen',
            bindKey: {win: 'Ctrl-P', mac: 'Command-P'},
            exec: function() { openQuickOpen(); }
        });

        // Keyboard shortcut: Ctrl+Shift+P = Command Palette
        editor.commands.addCommand({
            name: 'commandPalette',
            bindKey: {win: 'Ctrl-Shift-P', mac: 'Command-Shift-P'},
            exec: function() { openCommandPalette(); }
        });

        // Keyboard shortcut: Ctrl+B = Toggle Sidebar
        editor.commands.addCommand({
            name: 'toggleSidebar',
            bindKey: {win: 'Ctrl-B', mac: 'Command-B'},
            exec: function() { toggleSidebarVisibility(); }
        });

        // Keyboard shortcut: Ctrl+` = Toggle Terminal
        editor.commands.addCommand({
            name: 'toggleTerminal',
            bindKey: {win: 'Ctrl-`', mac: 'Command-`'},
            exec: function() { toggleTerminalPane(); }
        });

        // Keyboard shortcut: Alt+Z = Toggle Word Wrap
        editor.commands.addCommand({
            name: 'toggleWordWrap',
            bindKey: {win: 'Alt-Z', mac: 'Option-Z'},
            exec: function() { toggleWordWrap(); }
        });

        // Keyboard shortcut: Shift+Alt+F = Format Document
        editor.commands.addCommand({
            name: 'formatDocument',
            bindKey: {win: 'Shift-Alt-F', mac: 'Shift-Option-F'},
            exec: function() { formatDocument(); }
        });

        // Keyboard shortcut: Ctrl+/ = Toggle Comment
        editor.commands.addCommand({
            name: 'toggleComment',
            bindKey: {win: 'Ctrl-/', mac: 'Command-/'},
            exec: function() { toggleLineComment(); }
        });

        // Global keyboard shortcuts (outside editor)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                openCommandPalette();
            } else if (e.ctrlKey && !e.shiftKey && e.key === 'p') {
                e.preventDefault();
                openQuickOpen();
            } else if (e.ctrlKey && e.shiftKey && e.key === 'E') {
                e.preventDefault();
                toggleSidebarSection('explorer');
            } else if (e.ctrlKey && e.shiftKey && e.key === 'F') {
                e.preventDefault();
                toggleSidebarSection('search');
            } else if (e.ctrlKey && e.shiftKey && e.key === 'G') {
                e.preventDefault();
                toggleSidebarSection('git');
            } else if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                toggleSidebarSection('debug');
            } else if (e.ctrlKey && e.shiftKey && e.key === 'X') {
                e.preventDefault();
                toggleSidebarSection('extensions');
            } else if (e.key === 'F5') {
                e.preventDefault();
                runCode();
            } else if (e.key === 'Escape') {
                closeCommandPalette();
                closeQuickOpen();
                closeAllContextMenus();
            } else if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                toggleSidebarVisibility();
            } else if (e.altKey && e.key === 'z') {
                e.preventDefault();
                toggleWordWrap();
            }
        });

        // Toggles sidebar sections (Explorer, Search, Git, Debug, Extensions, Settings)
        function toggleSidebarSection(section) {
            document.querySelectorAll('.activity-icon').forEach(icon => icon.classList.remove('active'));
            const explorerCont = document.getElementById('sidebar-explorer-content');
            const settingsCont = document.getElementById('sidebar-settings-content');
            const searchCont = document.getElementById('sidebar-search-content');
            const gitCont = document.getElementById('sidebar-git-content');
            const debugCont = document.getElementById('sidebar-debug-content');
            const extCont = document.getElementById('sidebar-extensions-content');
            const sidebar = document.getElementById('vscode-sidebar');

            // Show sidebar if hidden
            sidebar.classList.remove('hidden');
            
            explorerCont.style.display = 'none';
            settingsCont.style.display = 'none';
            searchCont.style.display = 'none';
            gitCont.style.display = 'none';
            debugCont.style.display = 'none';
            extCont.style.display = 'none';

            if (section === 'explorer') {
                document.getElementById('act-explorer').classList.add('active');
                explorerCont.style.display = 'flex';
                updateOutline();
            } else if (section === 'search') {
                document.getElementById('act-search').classList.add('active');
                searchCont.style.display = 'flex';
                setTimeout(() => document.getElementById('search-input').focus(), 100);
            } else if (section === 'git') {
                document.getElementById('act-git').classList.add('active');
                gitCont.style.display = 'flex';
                renderGitPanel();
            } else if (section === 'debug') {
                document.getElementById('act-debug').classList.add('active');
                debugCont.style.display = 'flex';
            } else if (section === 'extensions') {
                document.getElementById('act-extensions').classList.add('active');
                extCont.style.display = 'flex';
                renderExtensionsPanel();
            } else {
                document.getElementById('act-settings').classList.add('active');
                settingsCont.style.display = 'flex';
            }
        }

        // Switch bottom Terminal Panel Tab
        function switchTerminalTab(tabType) {
            document.querySelectorAll('.terminal-tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById('term-tab-' + tabType).classList.add('active');

            const outContent = document.getElementById('terminal-output-content');
            const inputContent = document.getElementById('terminal-input-content');
            const previewFrame = document.getElementById('preview-frame');

            outContent.style.display = 'none';
            inputContent.style.display = 'none';
            previewFrame.style.display = 'none';

            if (tabType === 'terminal') {
                outContent.style.display = 'block';
            } else if (tabType === 'input') {
                inputContent.style.display = 'block';
            } else if (tabType === 'preview') {
                previewFrame.style.display = 'block';
            }
        }

        // Collapsed folder state tracker
        let collapsedFolders = {};

        // Build a tree structure from flat file paths
        function buildTree(files) {
            const tree = { __files: [] };
            Object.keys(files).sort((a, b) => {
                // folders first, then files, alphabetically
                const aParts = a.split('/');
                const bParts = b.split('/');
                if (aParts.length !== bParts.length) return bParts.length - aParts.length;
                return a.localeCompare(b);
            }).forEach(filePath => {
                const parts = filePath.split('/');
                let current = tree;
                for (let i = 0; i < parts.length - 1; i++) {
                    if (!current[parts[i]]) current[parts[i]] = { __files: [] };
                    current = current[parts[i]];
                }
                current.__files.push(filePath);
            });
            return tree;
        }

        // Render a folder node recursively
        function renderTreeNode(node, folderPath, depth) {
            let html = '';
            const indent = (depth * 16) + 24; // px indent per level
            
            // Render subfolders first
            const folderKeys = Object.keys(node).filter(k => k !== '__files').sort();
            folderKeys.forEach(folderName => {
                const fullPath = folderPath ? folderPath + '/' + folderName : folderName;
                const isCollapsed = collapsedFolders[fullPath] === true;
                const arrowClass = isCollapsed ? 'collapsed' : '';
                const folderIcon = isCollapsed ? 'fa-folder' : 'fa-folder-open';
                
                html += `<div class="tree-folder-row" style="padding-left:${indent}px" onclick="toggleFolder('${fullPath}')">
                    <i class="fa-solid fa-chevron-right folder-arrow ${arrowClass}"></i>
                    <i class="fa-solid ${folderIcon}" style="color:#e2b73c; font-size:13px;"></i>
                    <span>${folderName}</span>
                </div>`;
                html += `<div class="tree-folder-children ${isCollapsed ? 'collapsed' : ''}" id="folder-${fullPath.replace(/\//g, '-')}">`;
                html += renderTreeNode(node[folderName], fullPath, depth + 1);
                html += `</div>`;
            });
            
            // Render files
            if (node.__files) {
                node.__files.sort((a, b) => {
                    const aName = a.split('/').pop();
                    const bName = b.split('/').pop();
                    return aName.localeCompare(bName);
                }).forEach(filePath => {
                    const fileName = filePath.split('/').pop();
                    const isActive = (filePath === activeFile);
                    const activeClass = isActive ? 'active' : '';
                    const iconClass = getFileIcon(fileName);
                    const safeId = filePath.replace(/[^a-zA-Z0-9]/g, '_');
                    
                    html += `<div class="explorer-file-row ${activeClass}" id="file-row-${safeId}" style="padding-left:${indent}px" onclick="openFile('${filePath}')">
                        <div class="explorer-file-title">
                            <i class="${iconClass}"></i>
                            <span>${fileName}</span>
                        </div>
                        <span class="explorer-file-delete" onclick="deleteFile(event, '${filePath}')" title="Xóa tệp tin">&times;</span>
                    </div>`;
                });
            }
            return html;
        }

        // Toggle folder collapsed state
        function toggleFolder(folderPath) {
            collapsedFolders[folderPath] = !collapsedFolders[folderPath];
            renderExplorer();
        }

        // Render Explorer Sidebar with folder tree
        function renderExplorer() {
            const container = document.querySelector('.explorer-files');
            if (!container) return;
            const tree = buildTree(virtualFiles);
            container.innerHTML = renderTreeNode(tree, '', 0);
        }

        // Render Tabs bar dynamically (with modified dot indicator)
        function renderTabs() {
            const tabsBar = document.getElementById('vscode-tabs-bar');
            tabsBar.innerHTML = '';

            openTabs.forEach(tab => {
                const isActive = (tab === activeFile);
                const tabBaseName = tab.split('/').pop();
                const iconClass = getFileIcon(tabBaseName);
                const activeClass = isActive ? 'active' : '';
                const modifiedClass = modifiedFiles[tab] ? 'modified' : '';

                tabsBar.innerHTML += `
                    <div class="vscode-tab ${activeClass} ${modifiedClass}" id="tab-${tab.replace(/[^a-zA-Z0-9]/g,'_')}" onclick="openFile('${tab}')" title="${tab}">
                        <i class="${iconClass}"></i> ${tabBaseName}
                        <span class="modified-dot"></span>
                        <span class="vscode-tab-close" onclick="closeTab(event, '${tab}')">&times;</span>
                    </div>
                `;
            });
        }

        // Update tab modified visual state
        function updateTabModifiedState() {
            openTabs.forEach(tab => {
                const safeId = tab.replace(/[^a-zA-Z0-9]/g, '_');
                const tabEl = document.getElementById('tab-' + safeId);
                if (tabEl) {
                    if (modifiedFiles[tab]) {
                        tabEl.classList.add('modified');
                    } else {
                        tabEl.classList.remove('modified');
                    }
                }
            });
        }

        // Clear editor state when no files exist
        function clearEditor() {
            activeFile = '';
            editor.setValue('', -1);
            editor.setReadOnly(true);
            document.getElementById('vscode-window-title').innerText = 'Trường Cao Đẳng Cà Mau Portal';
            document.getElementById('status-lang-badge').innerText = 'Plain Text';
            // Show welcome screen
            const welcome = document.getElementById('vscode-welcome');
            if (welcome) welcome.style.display = 'flex';
            // Reset breadcrumb
            updateBreadcrumb('');
            renderExplorer();
            renderTabs();
        }

        // Check if file is binary (image, audio, or video)
        function isBinaryFile(fileName) {
            const ext = fileName.split('.').pop().toLowerCase();
            return ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv'].includes(ext);
        }

        function getBinaryType(fileName) {
            const ext = fileName.split('.').pop().toLowerCase();
            if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'].includes(ext)) {
                return 'image';
            }
            if (['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'].includes(ext)) {
                return 'audio';
            }
            if (['mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv'].includes(ext)) {
                return 'video';
            }
            return null;
        }

        function getBinaryPlaceholder(fileName) {
            const type = getBinaryType(fileName);
            const ext = fileName.split('.').pop().toLowerCase();
            const baseName = fileName.split('/').pop();
            const cleanLabel = baseName.split('.')[0].toUpperCase();

            if (type === 'image') {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 400;
                    canvas.height = 400;
                    const ctx = canvas.getContext('2d');

                    // Background gradient
                    const bgGrad = ctx.createLinearGradient(0, 0, 400, 400);
                    bgGrad.addColorStop(0, '#1e293b');
                    bgGrad.addColorStop(0.5, '#0f172a');
                    bgGrad.addColorStop(1, '#1e1b4b');
                    ctx.fillStyle = bgGrad;
                    if (ctx.roundRect) {
                        ctx.roundRect(0, 0, 400, 400, 24);
                    } else {
                        ctx.rect(0, 0, 400, 400);
                    }
                    ctx.fill();

                    // Avatar Circle gradient
                    const circleGrad = ctx.createLinearGradient(135, 85, 265, 215);
                    circleGrad.addColorStop(0, '#3b82f6');
                    circleGrad.addColorStop(1, '#8b5cf6');
                    ctx.fillStyle = circleGrad;
                    ctx.beginPath();
                    ctx.arc(200, 145, 65, 0, Math.PI * 2);
                    ctx.fill();

                    // Avatar silhouette head + shoulders
                    ctx.fillStyle = '#ffffff';
                    ctx.beginPath();
                    ctx.arc(200, 125, 25, 0, Math.PI * 2);
                    ctx.fill();

                    ctx.beginPath();
                    ctx.arc(200, 205, 45, Math.PI, 0);
                    ctx.fill();

                    // Text labels
                    ctx.fillStyle = '#f8fafc';
                    ctx.font = 'bold 22px "Segoe UI", sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(baseName, 200, 270);

                    ctx.fillStyle = '#38bdf8';
                    ctx.font = '600 14px "Segoe UI", sans-serif';
                    ctx.fillText('Avatar Photo (' + cleanLabel + ')', 200, 305);

                    const mime = (ext === 'png') ? 'image/png' : 'image/jpeg';
                    return canvas.toDataURL(mime, 0.85);
                } catch (e) {
                    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400"><rect width="100%" height="100%" fill="#0f172a"/><text x="200" y="200" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-size="20">${baseName}</text></svg>`;
                    return 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svg)));
                }
            }
            return '';
        }

        // Open virtual file in editor
        function openFile(fileName) {
            if (!fileName) return;
            editor.setReadOnly(hasSubmitted);
            if (fileName !== activeFile && virtualFiles[activeFile] !== undefined) {
                // Save current code before switching
                if (!hasSubmitted) {
                    virtualFiles[activeFile] = editor.getValue();
                }
            }

            activeFile = fileName;

            // Highlight explorer list item
            renderExplorer();

            // Handle open tabs array
            if (!openTabs.includes(fileName)) {
                openTabs.push(fileName);
            }
            renderTabs();

            // Set Editor Value and mode or Media Preview
            const mediaPreview = document.getElementById('media-preview');
            const editorDiv = document.getElementById('editor');
            const fileInfo = getFileInfo(fileName);
            
            if (isBinaryFile(fileName)) {
                editorDiv.style.display = 'none';
                mediaPreview.style.display = 'flex';
                
                const binaryType = getBinaryType(fileName);
                let fileData = virtualFiles[fileName];
                if (!fileData || fileData.trim() === '' || fileData.startsWith('data:image/svg+xml')) {
                    fileData = getBinaryPlaceholder(fileName);
                    virtualFiles[fileName] = fileData;
                }
                
                if (binaryType === 'image') {
                    mediaPreview.innerHTML = `
                        <div style="text-align: center; max-width: 95%; max-height: 95%; overflow: auto;">
                            <img src="${fileData || ''}" style="max-width: 100%; max-height: 48vh; object-fit: contain; border: 1px dashed #555; background: #252526; padding: 4px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.5);" alt="${fileName}">
                            <div style="margin-top: 12px; font-size: 13px; color: #858585;">
                                <i class="fa-solid fa-image"></i> ${fileName}
                            </div>
                            <button onclick="triggerFileUploadForName('${fileName}')" style="margin-top: 12px; background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px; transition: background 0.2s;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Thay tệp thật từ máy tính
                            </button>
                        </div>
                    `;
                } else if (binaryType === 'audio') {
                    mediaPreview.innerHTML = `
                        <div style="text-align: center; background: #252526; border: 1px solid #3c3c3c; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.4); width: 80%; max-width: 400px;">
                            <div style="font-size: 48px; color: #d91b43; margin-bottom: 20px;"><i class="fa-solid fa-music fa-bounce"></i></div>
                            <div style="font-size: 14px; font-weight: bold; margin-bottom: 15px; word-break: break-all; color: #fff;">${fileName}</div>
                            <audio controls src="${fileData || ''}" style="width: 100%; outline: none; margin-bottom: 15px;"></audio>
                            <br>
                            <button onclick="triggerFileUploadForName('${fileName}')" style="background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px;">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Thay tệp audio thật từ máy tính
                            </button>
                        </div>
                    `;
                } else if (binaryType === 'video') {
                    mediaPreview.innerHTML = `
                        <div style="text-align: center; background: #252526; border: 1px solid #3c3c3c; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.4); max-width: 90%; max-height: 90%; overflow: auto;">
                            <div style="font-size: 14px; font-weight: bold; margin-bottom: 15px; word-break: break-all; color: #fff;">
                                <i class="fa-solid fa-film" style="color: #3b82f6;"></i> ${fileName}
                            </div>
                            <video controls src="${fileData || ''}" style="max-width: 100%; max-height: 45vh; border-radius: 8px; border: 1px solid #444; outline: none; margin-bottom: 15px;"></video>
                            <br>
                            <button onclick="triggerFileUploadForName('${fileName}')" style="background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px;">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Thay tệp video thật từ máy tính
                            </button>
                        </div>
                    `;
                }
                
                editor.setValue("", -1);
                editor.setReadOnly(true);
            } else {
                mediaPreview.style.display = 'none';
                editorDiv.style.display = 'block';
                editor.setReadOnly(hasSubmitted);
                
                editor.session.setMode(fileInfo.mode);
                editor.setValue(virtualFiles[fileName] || "", -1);
            }

            // Update title and badges
            document.getElementById('vscode-window-title').innerText = `Trường Cao Đẳng Cà Mau Portal - ${fileName}`;
            document.getElementById('status-lang-badge').innerText = fileInfo.label;

            // Hide welcome screen
            const welcome = document.getElementById('vscode-welcome');
            if (welcome) welcome.style.display = 'none';

            // Update breadcrumb
            updateBreadcrumb(fileName);

            // Show appropriate bottom tab (Live preview for HTML/PHP web, Terminal for others)
            const previewTab = document.getElementById('term-tab-preview');
            const btnOpenTab = document.getElementById('btn-open-tab');
            if (fileInfo.lang === 'html' || fileInfo.lang === 'php') {
                previewTab.style.display = 'inline-block';
                switchTerminalTab('preview');
                btnOpenTab.style.display = lastDeployUrl ? 'inline-flex' : 'none';
                runCode();
            } else {
                previewTab.style.display = 'none';
                btnOpenTab.style.display = 'none';
                switchTerminalTab('terminal');
            }
        }

        // Create new virtual file (supports folder paths like css/style.css)
        function createNewFile() {
            if (hasSubmitted) {
                alert("Bạn đã nộp bài rồi. Không được phép chỉnh sửa hoặc tạo tệp mới.");
                return;
            }
            const fileName = prompt("Nhập tên tệp tin mới (hỗ trợ đường dẫn thư mục)\n\nVí dụ: test.py, css/style.css, pages/about.html");
            if (!fileName) return;

            const cleanName = fileName.trim().replace(/\\/g, '/');
            if (cleanName === '') return;

            // Validate: allow letters, numbers, dots, dashes, underscores, and forward slashes
            if (!/^[a-zA-Z0-9_\.\-\/]+$/.test(cleanName) || cleanName.includes('..')) {
                alert("Tên tệp tin không hợp lệ! Chỉ chấp nhận ký tự chữ, số, dấu chấm, gạch ngang, gạch dưới và dấu /.");
                return;
            }

            if (virtualFiles[cleanName] !== undefined) {
                alert("Tệp tin này đã tồn tại!");
                return;
            }

            // Create template matching extension
            let defaultContent = "";
            const fileInfo = getFileInfo(cleanName);
            if (isBinaryFile(cleanName)) {
                if (confirm(`"${cleanName}" là tệp đa phương tiện (ảnh/âm thanh/video).\n\nBạn có muốn chọn tệp thực tế từ máy tính để nạp vào dự án không?`)) {
                    triggerFileUploadForName(cleanName);
                    return;
                }
                defaultContent = getBinaryPlaceholder(cleanName);
            } else if (fileInfo.lang === 'python') defaultContent = "# Python code\n";
            else if (fileInfo.lang === 'javascript') defaultContent = "// JavaScript code\n";
            else if (fileInfo.lang === 'php') defaultContent = "<" + "?php\n";
            else if (fileInfo.lang === 'html') defaultContent = "<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n</body>\n</html>";
            else if (fileInfo.lang === 'css') defaultContent = "/* CSS Stylesheet */\n";

            virtualFiles[cleanName] = defaultContent;

            if (!openTabs.includes(cleanName)) {
                openTabs.push(cleanName);
            }

            renderExplorer();
            openFile(cleanName);
        }

        function triggerFileUploadForName(targetFileName) {
            const input = document.createElement('input');
            input.type = 'file';
            input.onchange = async (e) => {
                const files = e.target.files;
                if (!files || files.length === 0) return;
                const file = files[0];
                if (isBinaryFile(file.name) || isBinaryFile(targetFileName)) {
                    const reader = new FileReader();
                    const dataUrl = await new Promise((resolve) => {
                        reader.onload = () => resolve(reader.result);
                        reader.readAsDataURL(file);
                    });
                    virtualFiles[targetFileName] = dataUrl;
                } else {
                    const text = await file.text();
                    virtualFiles[targetFileName] = text;
                }
                renderExplorer();
                openFile(targetFileName);
                const hasWebFiles = Object.keys(virtualFiles).some(f => f.endsWith('.html') || f.endsWith('.htm') || f.endsWith('.php'));
                if (hasWebFiles) runCode();
            };
            input.click();
        }

        // Create new virtual folder
        function createNewFolder() {
            if (hasSubmitted) {
                alert("Bạn đã nộp bài rồi. Không được phép chỉnh sửa hoặc tạo thư mục mới.");
                return;
            }
            const folderName = prompt("Nhập tên thư mục mới (ví dụ: css, pages, includes):");
            if (!folderName) return;

            const cleanName = folderName.trim().replace(/\\/g, '/');
            if (cleanName === '') return;

            if (!/^[a-zA-Z0-9_\-\/]+$/.test(cleanName) || cleanName.includes('..')) {
                alert("Tên thư mục không hợp lệ!");
                return;
            }

            // Create a placeholder file inside the folder
            const placeholder = cleanName.replace(/\/$/, '');
            // Just expand the folder path in collapsed state tracker
            collapsedFolders[placeholder] = false;
            
            // Create a .gitkeep-like empty file to materialize the folder
            const keepFile = placeholder + '/.gitkeep';
            if (!virtualFiles[keepFile]) {
                virtualFiles[keepFile] = '';
            }
            renderExplorer();
            
            alert(`Thư mục "${placeholder}" đã được tạo! Bạn có thể tạo file bên trong bằng cách nhập đường dẫn, ví dụ: ${placeholder}/index.php`);
        }

        // Delete virtual file
        function deleteFile(e, fileName) {
            e.stopPropagation(); // prevent opening file
            if (hasSubmitted) {
                alert("Bạn đã nộp bài rồi. Không được phép chỉnh sửa hoặc xóa tệp.");
                return;
            }
            
            if (confirm(`Bạn có chắc muốn xóa tệp tin ${fileName}?`)) {
                if (fileName === activeFile) {
                    virtualFiles[activeFile] = editor.getValue();
                }

                // Delete from VFS list
                delete virtualFiles[fileName];
                openTabs = openTabs.filter(t => t !== fileName);

                const remainingFiles = Object.keys(virtualFiles);
                if (activeFile === fileName) {
                    if (remainingFiles.length > 0) {
                        openFile(remainingFiles[0]);
                    } else {
                        clearEditor();
                    }
                } else {
                    renderExplorer();
                    renderTabs();
                }
            }
        }

        // Close file tab
        function closeTab(e, fileName) {
            e.stopPropagation(); // prevent parent click
            
            if (fileName === activeFile) {
                if (!hasSubmitted) {
                    virtualFiles[activeFile] = editor.getValue();
                }
            }

            openTabs = openTabs.filter(t => t !== fileName);

            if (activeFile === fileName) {
                if (openTabs.length > 0) {
                    openFile(openTabs[openTabs.length - 1]);
                } else {
                    const remaining = Object.keys(virtualFiles);
                    if (remaining.length > 0) {
                        openFile(remaining[0]);
                    } else {
                        clearEditor();
                    }
                }
            } else {
                renderTabs();
            }
        }

        // Change settings
        function changeTheme() {
            const theme = document.getElementById('setting-theme').value;
            editor.setTheme(theme);
        }

        function changeFontSize() {
            const size = document.getElementById('setting-font-size').value;
            editor.setFontSize(size);
        }

        // Clear terminal output console
        function clearConsole() {
            document.getElementById('terminal-output-content').innerHTML = `<div style="color: #64748b; font-style: italic;">[Terminal cleared]</div>`;
        }

        // Toggle terminal pane collapse/expand
        function toggleTerminalPane() {
            const pane = document.querySelector('.vscode-terminal-pane');
            const btn = document.getElementById('btn-toggle-terminal');
            const isCollapsed = pane.classList.toggle('collapsed');
            
            const icon = btn.querySelector('i');
            const text = document.getElementById('toggle-terminal-text');
            
            if (isCollapsed) {
                icon.className = 'fa-solid fa-chevron-up';
                text.innerText = 'Mở rộng';
            } else {
                icon.className = 'fa-solid fa-chevron-down';
                text.innerText = 'Thu nhỏ';
            }
            if (window.editor) {
                editor.resize();
            }
        }

        // Reset current file to base template
        function resetTemplate() {
            if (hasSubmitted) {
                alert("Bạn đã nộp bài rồi. Không được phép chỉnh sửa bài hoặc khôi phục mẫu.");
                return;
            }
            if (confirm(`Bạn có chắc muốn khôi phục mã nguồn mẫu ban đầu cho ${activeFile}?`)) {
                const fileInfo = getFileInfo(activeFile);
                if (templates[fileInfo.lang] !== undefined) {
                    virtualFiles[activeFile] = templates[fileInfo.lang];
                    editor.setValue(virtualFiles[activeFile], -1);

                    if (fileInfo.lang === 'html') {
                        runCode();
                    }
                } else {
                    editor.setValue("", -1);
                }
            }
        }

        // Download active file
        function downloadCode() {
            const code = editor.getValue();
            const element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(code));
            element.setAttribute('download', activeFile);
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        }

        // Resolve css, js, images, audio, and video tags client-side for HTML previewing
        function resolveVirtualAssets(htmlCode) {
            if (!htmlCode) return '';
            let resolved = htmlCode;
            
            // Auto-fix unclosed <link ... tags missing closing '>'
            resolved = resolved.replace(/(<link\s+[^>]*?href=["'][^"']*["'])(?=\s*[\r\n]|\s*<)/gi, '$1>');

            // 1. Resolve CSS links: <link rel="stylesheet" href="filename.css">
            const cssRegex = /<link[^>]+href=["']([^"']+\.css)["'][^>]*>/gi;
            resolved = resolved.replace(cssRegex, (match, fileName) => {
                const cleanName = fileName.split('/').pop().trim();
                const foundKey = Object.keys(virtualFiles).find(k => k === cleanName || k.endsWith('/' + cleanName));
                if (foundKey && virtualFiles[foundKey] !== undefined) {
                    return `<style>\n/* Auto-linked from: ${cleanName} */\n${virtualFiles[foundKey]}\n</style>`;
                }
                return match;
            });

            // 2. Resolve JS scripts: matches script tags with src="filename.js"
            const jsRegex = /<script[^>]+src=["']([^"']+\.js)["'][^>]*>\s*<\/script>/gi;
            resolved = resolved.replace(jsRegex, (match, fileName) => {
                const cleanName = fileName.split('/').pop().trim();
                const foundKey = Object.keys(virtualFiles).find(k => k === cleanName || k.endsWith('/' + cleanName));
                if (foundKey && virtualFiles[foundKey] !== undefined) {
                    return `<script>\n/* Auto-linked from: ${cleanName} */\n${virtualFiles[foundKey]}\n<\/script>`;
                }
                return match;
            });

            // 3. Resolve CSS url(...) references for images & audio/fonts inside <style> tags or inline styles
            const urlRegex = /url\(\s*['"]?([^'")]+\.(?:png|jpg|jpeg|gif|webp|svg|ico|bmp|mp3|wav|ogg))['"]?\s*\)/gi;
            resolved = resolved.replace(urlRegex, (match, fileName) => {
                const cleanName = fileName.split('/').pop().trim();
                const foundKey = Object.keys(virtualFiles).find(k => k === cleanName || k.endsWith('/' + cleanName));
                if (foundKey && virtualFiles[foundKey] && typeof virtualFiles[foundKey] === 'string' && virtualFiles[foundKey].startsWith('data:')) {
                    return `url("${virtualFiles[foundKey]}")`;
                }
                return match;
            });

            // 4. Resolve HTML src="..." and href="..." for images, audio, video, source tags
            const srcRegex = /\b(src|href)=["']([^"']+\.(?:png|jpg|jpeg|gif|webp|svg|ico|bmp|mp3|wav|ogg|m4a|mp4|webm))["']/gi;
            resolved = resolved.replace(srcRegex, (match, attr, fileName) => {
                const cleanName = fileName.split('/').pop().trim();
                const foundKey = Object.keys(virtualFiles).find(k => k === cleanName || k.endsWith('/' + cleanName));
                if (foundKey && virtualFiles[foundKey] && typeof virtualFiles[foundKey] === 'string' && virtualFiles[foundKey].startsWith('data:')) {
                    return `${attr}="${virtualFiles[foundKey]}"`;
                }
                return match;
            });

            return resolved;
        }

        async function uploadBinaryChunks(deployId, filesObj) {
            if (!deployId || !filesObj) return;
            const binaryFiles = {};
            for (const path in filesObj) {
                if (isBinaryFile(path)) {
                    const val = filesObj[path];
                    if (typeof val === 'string' && val.startsWith('data:')) {
                        binaryFiles[path] = val;
                    }
                }
            }

            const filePaths = Object.keys(binaryFiles);
            if (filePaths.length === 0) return;

            let currentBatch = {};
            let currentSize = 0;

            for (const path of filePaths) {
                const content = binaryFiles[path];
                if (currentSize > 0 && currentSize + content.length > 1500000) {
                    const fd = new FormData();
                    fd.append('deploy_id', deployId);
                    fd.append('binary_files', JSON.stringify(currentBatch));
                    try {
                        await fetch('/tkb/api/upload_binary.php', { method: 'POST', body: fd });
                    } catch (e) {}
                    currentBatch = {};
                    currentSize = 0;
                }
                currentBatch[path] = content;
                currentSize += content.length;
            }

            if (Object.keys(currentBatch).length > 0) {
                const fd = new FormData();
                fd.append('deploy_id', deployId);
                fd.append('binary_files', JSON.stringify(currentBatch));
                try {
                    await fetch('/tkb/api/upload_binary.php', { method: 'POST', body: fd });
                } catch (e) {}
            }
        }

        // Execute code
        async function runCode() {
            // If no active file, try to auto-pick an entry file from virtualFiles
            if (!activeFile) {
                const files = Object.keys(virtualFiles);
                const auto = files.find(f => f === 'index.html') ||
                             files.find(f => f === 'index.php') ||
                             files.find(f => f.endsWith('.html')) ||
                             files.find(f => f.endsWith('.php')) ||
                             files[0];
                if (auto) {
                    openFile(auto);
                } else {
                    return;
                }
            }

            // Auto expand terminal if collapsed
            const pane = document.querySelector('.vscode-terminal-pane');
            if (pane && pane.classList.contains('collapsed')) {
                toggleTerminalPane();
            }

            const currentCode = editor ? editor.getValue() : '';
            if (activeFile && !hasSubmitted && !isBinaryFile(activeFile)) {
                virtualFiles[activeFile] = currentCode; // save current editor state
            }

            // Auto-fix any binary files that are empty
            for (const path in virtualFiles) {
                if (isBinaryFile(path) && (!virtualFiles[path] || virtualFiles[path].trim() === '')) {
                    virtualFiles[path] = getBinaryPlaceholder(path);
                }
            }

            // Smartly select the executable target file
            let targetFile = activeFile;
            let fileInfo = getFileInfo(targetFile || '');

            const executableLangs = ['python', 'javascript', 'php', 'html', 'c', 'cpp', 'java'];
            if (!executableLangs.includes(fileInfo.lang)) {
                const files = Object.keys(virtualFiles);
                const smartFile = files.find(f => f === 'index.html' || f.endsWith('/index.html') || f === 'index.php' || f.endsWith('/index.php') || f === 'main.py' || f.endsWith('/main.py') || f === 'main.cpp' || f === 'app.py') ||
                                  files.find(f => {
                                      const info = getFileInfo(f);
                                      return executableLangs.includes(info.lang);
                                  });
                if (smartFile) {
                    targetFile = smartFile;
                    fileInfo = getFileInfo(targetFile);
                    openFile(targetFile);
                }
            }
            
            // Determine if the current workspace is a Web project (contains HTML or PHP)
            const hasWebFiles = Object.keys(virtualFiles).some(f => f.endsWith('.html') || f.endsWith('.htm') || f.endsWith('.php'));
            const isWebRun = hasWebFiles || fileInfo.lang === 'html' || fileInfo.lang === 'php';

            const runIcon = document.getElementById('run-icon');
            const runText = document.getElementById('run-text');
            const runStatusBadge = document.getElementById('run-status-badge');
            const statusRunning = document.getElementById('status-running-status');

            // Web projects (HTML/PHP) deploy to Apache for real server-side rendering
            if (isWebRun) {
                // Determine appropriate entry file for preview
                let entryFile = targetFile || activeFile;
                if (!entryFile || isBinaryFile(entryFile) || (fileInfo.lang !== 'html' && fileInfo.lang !== 'php')) {
                    const files = Object.keys(virtualFiles);
                    entryFile = files.find(f => f === 'index.php' || f.endsWith('/index.php')) ||
                                files.find(f => f === 'index.html' || f.endsWith('/index.html')) ||
                                files.find(f => f.endsWith('about.php')) ||
                                files.find(f => f.endsWith('home.php')) ||
                                files.find(f => f.endsWith('login.php')) ||
                                files.find(f => f.endsWith('main.php')) ||
                                files.find(f => f.endsWith('.php') && !f.includes('config') && !f.includes('include') && !f.includes('db.')) ||
                                files.find(f => f.endsWith('.html')) ||
                                files.find(f => f.endsWith('.php')) ||
                                files.find(f => !isBinaryFile(f)) ||
                                activeFile;
                }

                // Instant client-side HTML preview rendering (0ms latency)
                const previewFrame = document.getElementById('preview-frame');
                const rawEntryCode = virtualFiles[entryFile] || (activeFile ? virtualFiles[activeFile] : '') || currentCode;
                if (rawEntryCode && (entryFile.endsWith('.html') || entryFile.endsWith('.htm') || !entryFile.includes('.'))) {
                    const instantHtml = resolveVirtualAssets(rawEntryCode);
                    previewFrame.removeAttribute('src');
                    previewFrame.srcdoc = instantHtml;
                    switchTerminalTab('preview');
                }

                runStatusBadge.innerHTML = '<span style="color: #3b82f6;"><i class="fa-solid fa-rocket fa-spin"></i> Deploying...</span>';
                statusRunning.innerHTML = '<i class="fa-solid fa-rocket fa-spin"></i> Deploying...';
                runIcon.className = 'fa-solid fa-circle-notch fa-spin';
                runText.innerText = 'Deploying...';

                try {
                    const deployableFiles = {};
                    let currentPayloadSize = 0;

                    // First pass: add all code & text files
                    for (const path in virtualFiles) {
                        if (!isBinaryFile(path)) {
                            deployableFiles[path] = virtualFiles[path];
                            currentPayloadSize += (virtualFiles[path] || '').length;
                        }
                    }

                    // Second pass: add binary images while under 4.5MB total POST payload
                    for (const path in virtualFiles) {
                        if (isBinaryFile(path)) {
                            const val = virtualFiles[path];
                            if (typeof val === 'string' && val.startsWith('data:')) {
                                if (currentPayloadSize + val.length < 4500000) {
                                    deployableFiles[path] = val;
                                    currentPayloadSize += val.length;
                                } else {
                                    deployableFiles[path] = '__SERVER_LOCAL_FILE__';
                                }
                            } else {
                                deployableFiles[path] = val || '__SERVER_LOCAL_FILE__';
                            }
                        }
                    }

                    const formData = new FormData();
                    formData.append('active_file', entryFile);
                    if (window.preloadedStorageId && window.preloadedStorageId > 0) {
                        formData.append('storage_id', window.preloadedStorageId);
                    }
                    formData.append('virtual_files', JSON.stringify(deployableFiles));
                    if (directoryHandle) {
                        formData.append('project_name', directoryHandle.name);
                    }

                    const response = await fetch('/tkb/api/deploy_web.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const responseText = await response.text();
                    let result;
                    try {
                        const jsonStart = responseText.indexOf('{');
                        const jsonEnd = responseText.lastIndexOf('}');
                        if (jsonStart !== -1 && jsonEnd !== -1 && jsonEnd > jsonStart) {
                            result = JSON.parse(responseText.substring(jsonStart, jsonEnd + 1));
                        } else {
                            result = JSON.parse(responseText);
                        }
                    } catch (jsonErr) {
                        throw new Error("Phản hồi server rỗng hoặc chứa HTML lỗi: " + responseText.substring(0, 200));
                    }

                    if (result.error) {
                        const outContent = document.getElementById('terminal-output-content');
                        outContent.innerHTML = `<div class="terminal-stderr">Lỗi triển khai:<br>${escapeHtml(result.error)}</div>`;
                        switchTerminalTab('terminal');
                        runStatusBadge.innerHTML = '<span style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Error</span>';
                        statusRunning.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Error';
                    } else {
                        // Success - load URL into iframe
                        lastDeployUrl = result.url;
                        const previewFrame = document.getElementById('preview-frame');

                        if (result.html_content) {
                            // Inject HTML directly with a <base> tag so relative paths resolve to deployed URL
                            const baseUrl = window.location.origin + result.url.substring(0, result.url.lastIndexOf('/') + 1);
                            const injected = result.html_content.replace(
                                /(<head[^>]*>)/i,
                                `$1<base href="${baseUrl}">`
                            );
                            previewFrame.removeAttribute('src');
                            previewFrame.srcdoc = injected;
                        } else {
                            previewFrame.removeAttribute('srcdoc');
                            previewFrame.src = result.url;
                        }

                        // Background upload all binary images & videos in chunks, then refresh iframe
                        if (result.deploy_id && !result.html_content) {
                            uploadBinaryChunks(result.deploy_id, virtualFiles).then(() => {
                                try {
                                    previewFrame.contentWindow.location.reload();
                                } catch(e) {
                                    previewFrame.src = result.url;
                                }
                            });
                        }

                        // Show preview tab and open new tab button
                        const previewTab = document.getElementById('term-tab-preview');
                        previewTab.style.display = 'inline-block';
                        switchTerminalTab('preview');
                        document.getElementById('btn-open-tab').style.display = 'inline-flex';

                        runStatusBadge.innerHTML = '<span style="color: #10b981;"><i class="fa-solid fa-globe"></i> Live on Apache</span>';
                        statusRunning.innerHTML = '<i class="fa-solid fa-globe"></i> Live on Apache';
                    }
                } catch (error) {
                    const outContent = document.getElementById('terminal-output-content');
                    outContent.innerHTML = `<div class="terminal-stderr">Lỗi kết nối server:<br>${escapeHtml(error.message)}</div>`;
                    switchTerminalTab('terminal');
                    runStatusBadge.innerHTML = '<span style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Failed</span>';
                    statusRunning.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Failed';
                } finally {
                    runIcon.className = 'fa-solid fa-play';
                    runText.innerText = 'Chạy Code (Ctrl+Enter)';
                }
                return;
            }

            // A standalone JavaScript file can run safely in the preview iframe.
            if (fileInfo.lang === 'javascript') {
                const previewFrame = document.getElementById('preview-frame');
                const previewTab = document.getElementById('term-tab-preview');
                const outContent = document.getElementById('terminal-output-content');
                const encodedCode = btoa(unescape(encodeURIComponent(currentCode)));
                const encodedInput = btoa(unescape(encodeURIComponent(document.getElementById('stdin').value || '')));

                previewFrame.removeAttribute('src');
                previewFrame.srcdoc = `<!doctype html><html lang="vi"><head><meta charset="utf-8"><style>body{font:14px system-ui,sans-serif;padding:16px;color:#111827}#code-output{white-space:pre-wrap;background:#f8fafc;border-radius:8px;padding:12px;margin-top:12px}</style></head><body><div id="app"></div><pre id="code-output"></pre><script>
const output = document.getElementById('code-output');
const stdin = decodeURIComponent(escape(atob('${encodedInput}')));
const write = (...values) => output.textContent += values.map(value => typeof value === 'object' ? JSON.stringify(value) : String(value)).join(' ') + '\\n';
console.log = console.info = console.warn = write;
window.addEventListener('error', event => write('Lỗi: ' + event.message));
try { (new Function('stdin', decodeURIComponent(escape(atob('${encodedCode}')))))(stdin); } catch (error) { write('Lỗi: ' + error.message); }
<\/script></body></html>`;
                previewTab.style.display = 'inline-block';
                switchTerminalTab('preview');
                document.getElementById('btn-open-tab').style.display = 'none';
                outContent.innerHTML = '<div class="terminal-system-info">[Hoàn thành] JavaScript đang chạy trong khung xem trước của trình duyệt.</div>';
                runStatusBadge.innerHTML = '<span style="color: #10b981;"><i class="fa-solid fa-globe"></i> Browser JavaScript</span>';
                statusRunning.innerHTML = '<i class="fa-solid fa-globe"></i> Browser JavaScript';
                return;
            }

            // Standalone server-side execution (Python, C, C++, Java, PHP)
            if (!['python', 'javascript', 'c', 'cpp', 'java', 'php'].includes(fileInfo.lang)) {
                const files = Object.keys(virtualFiles);
                const altFile = files.find(f => ['python', 'c', 'cpp', 'java', 'php', 'javascript', 'html'].includes(getFileInfo(f).lang));
                if (altFile) {
                    openFile(altFile);
                    setTimeout(() => { if (typeof runCode === 'function') runCode(); }, 150);
                    return;
                }
                const outContent = document.getElementById('terminal-output-content');
                if (outContent) {
                    outContent.innerHTML = `<div class="terminal-system-info">[Hướng dẫn] Vui lòng chọn một tệp tin mã nguồn (.py, .cpp, .c, .java, .php, .html, .js) để chạy.</div>`;
                    switchTerminalTab('terminal');
                }
                return;
            }

            const stdin = document.getElementById('stdin').value;
            const outContent = document.getElementById('terminal-output-content');

            // Show running status
            runIcon.className = 'fa-solid fa-circle-notch fa-spin';
            runText.innerText = 'Running...';
            runStatusBadge.innerHTML = '<span style="color: #3b82f6;"><i class="fa-solid fa-spinner fa-spin"></i> Executing...</span>';
            statusRunning.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Executing...';
            outContent.innerHTML = `<div class="terminal-system-info">[Hệ Thống] Đang gửi toàn bộ file ảo lên server và chạy tệp '${activeFile}'...</div>`;
            
            switchTerminalTab('terminal');

            try {
            const deployableFiles = {};
            let currentPayloadSize = 0;

            // First pass: add all code & text files
            for (const path in virtualFiles) {
                if (!isBinaryFile(path)) {
                    deployableFiles[path] = virtualFiles[path];
                    currentPayloadSize += (virtualFiles[path] || '').length;
                }
            }

            // Second pass: add binary images while under 4.5MB total POST payload
            for (const path in virtualFiles) {
                if (isBinaryFile(path)) {
                    const val = virtualFiles[path];
                    if (typeof val === 'string' && val.startsWith('data:')) {
                        if (currentPayloadSize + val.length < 4500000) {
                            deployableFiles[path] = val;
                            currentPayloadSize += val.length;
                        } else {
                            deployableFiles[path] = '__SERVER_LOCAL_FILE__';
                        }
                    } else {
                        deployableFiles[path] = val || '__SERVER_LOCAL_FILE__';
                    }
                }
            }

                const formData = new FormData();
                formData.append('language', fileInfo.lang);
                formData.append('active_file', activeFile);
                formData.append('stdin', stdin);
                formData.append('virtual_files', JSON.stringify(deployableFiles));
                if (directoryHandle) {
                    formData.append('project_name', directoryHandle.name);
                }

                const response = await fetch('/tkb/api/run_code.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    let errText = `HTTP Error Code ${response.status}`;
                    try {
                        const rawText = await response.text();
                        const jsonStart = rawText.indexOf('{');
                        const jsonEnd = rawText.lastIndexOf('}');
                        if (jsonStart !== -1 && jsonEnd !== -1 && jsonEnd > jsonStart) {
                            const parsed = JSON.parse(rawText.substring(jsonStart, jsonEnd + 1));
                            if (parsed.error) errText = parsed.error;
                        }
                    } catch(e) {}
                    throw new Error(errText);
                }

                const responseText = await response.text();
                let result;
                try {
                    const jsonStart = responseText.indexOf('{');
                    const jsonEnd = responseText.lastIndexOf('}');
                    if (jsonStart !== -1 && jsonEnd !== -1 && jsonEnd > jsonStart) {
                        result = JSON.parse(responseText.substring(jsonStart, jsonEnd + 1));
                    } else {
                        result = JSON.parse(responseText);
                    }
                } catch (jsonErr) {
                    throw new Error("Phản hồi server rỗng hoặc chứa HTML lỗi: " + responseText.substring(0, 200));
                }

                if (result.error) {
                    outContent.innerHTML = `<div class="terminal-stderr">Lỗi hệ thống:<br>${escapeHtml(result.error)}</div>`;
                    runStatusBadge.innerHTML = '<span style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Error</span>';
                    statusRunning.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Error';
                } else {
                    let outHtml = `<div class="terminal-system-info">[Hoàn thành - Thời gian chạy: ${result.time}ms]</div>`;
                    
                    if (result.stdout) {
                        outHtml += `<div>${escapeHtml(result.stdout)}</div>`;
                    }
                    
                    if (result.stderr) {
                        outHtml += `<div class="terminal-stderr">${escapeHtml(result.stderr)}</div>`;
                    }
                    
                    if (!result.stdout && !result.stderr) {
                        outHtml += `<div style="opacity: 0.5; font-style: italic;">[Chương trình chạy hoàn tất và không in gì ra màn hình]</div>`;
                    }

                    outContent.innerHTML = outHtml;
                    outContent.scrollTop = outContent.scrollHeight; // auto scroll

                    runStatusBadge.innerHTML = '<span style="color: #10b981;"><i class="fa-solid fa-check"></i> Success</span>';
                    statusRunning.innerHTML = '<i class="fa-solid fa-check"></i> Success';
                }
            } catch (error) {
                console.error(error);
                outContent.innerHTML = `<div class="terminal-stderr">Lỗi kết nối máy chủ:<br>${escapeHtml(error.message)}</div>`;
                runStatusBadge.innerHTML = '<span style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Connection Failed</span>';
                statusRunning.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Connection Failed';
            } finally {
                // Restore button icon
                runIcon.className = 'fa-solid fa-play';
                runText.innerText = 'Chạy Code (Ctrl+Enter)';
            }
        }

        // Copy content
        function copyConsoleOutput() {
            const fileInfo = getFileInfo(activeFile);
            let textToCopy = "";
            
            if (fileInfo.lang === 'html') {
                textToCopy = editor.getValue();
            } else {
                const outContent = document.getElementById('terminal-output-content');
                textToCopy = outContent.innerText;
            }

            navigator.clipboard.writeText(textToCopy).then(() => {
                alert('Đã sao chép nội dung vào Clipboard!');
            }).catch(err => {
                alert('Lỗi sao chép: ' + err);
            });
        }

        // Toggle menu dropdown
        function toggleMenuDropdown(e, dropdownId) {
            e.stopPropagation();
            const dropdown = document.getElementById(dropdownId);
            const parent = dropdown.parentElement;
            
            // Close other dropdowns
            document.querySelectorAll('.menu-item-container').forEach(container => {
                if (container !== parent) {
                    container.classList.remove('active');
                }
            });
            
            parent.classList.toggle('active');
        }
        
        // Close menu dropdowns on outside click
        window.addEventListener('click', function() {
            document.querySelectorAll('.menu-item-container').forEach(container => {
                container.classList.remove('active');
            });
        });

        // Open Local Folder using showDirectoryPicker
        let directoryHandle = null;

        async function openLocalFolder() {
            if (hasSubmitted) {
                alert("Bạn đã nộp bài rồi. Không được phép chỉnh sửa bài hoặc mở thư mục mới.");
                return;
            }
            try {
                // Trigger browser directory picker
                directoryHandle = await window.showDirectoryPicker();
                
                // Clear current workspace
                virtualFiles = {};
                openTabs = [];
                activeFile = '';
                
                // Read all files recursively
                await readDirectoryRecursive(directoryHandle, '');
                
                // Render explorer and tabs
                renderExplorer();
                
                const remainingFiles = Object.keys(virtualFiles);
                if (remainingFiles.length > 0) {
                    // Try to find index.html or main.py to open first
                    let firstFile = remainingFiles.find(f => f.endsWith('index.html') || f.endsWith('main.py')) || remainingFiles[0];
                    openFile(firstFile);
                } else {
                    clearEditor();
                }
                
                alert(`Đã mở thư mục "${directoryHandle.name}" thành công với ${remainingFiles.length} tệp tin!`);
            } catch (err) {
                if (err.name !== 'AbortError') {
                    alert('Lỗi khi mở thư mục: ' + err.message);
                }
            }
        }

        function triggerFileUpload() {
            if (hasSubmitted) {
                alert("Bạn đã nộp bài rồi. Không được phép chỉnh sửa hoặc tải tệp mới lên.");
                return;
            }
            const input = document.getElementById('input-upload-file');
            if (input) input.click();
        }

        async function handleFileUpload(e) {
            const files = e.target.files;
            if (!files || files.length === 0) return;
            await processUploadedFiles(files);
            e.target.value = '';
        }

        async function processUploadedFiles(files) {
            let lastUploadedName = '';
            for (const file of files) {
                if (file.size > 20 * 1024 * 1024) {
                    alert(`Tệp "${file.name}" quá lớn (>20MB). Hệ thống chỉ nhận tệp <20MB.`);
                    continue;
                }
                const fileName = file.name;
                if (isBinaryFile(fileName)) {
                    const reader = new FileReader();
                    const dataUrl = await new Promise((resolve) => {
                        reader.onload = () => resolve(reader.result);
                        reader.readAsDataURL(file);
                    });
                    virtualFiles[fileName] = dataUrl;
                } else {
                    const text = await file.text();
                    virtualFiles[fileName] = text;
                }
                lastUploadedName = fileName;
            }
            renderExplorer();
            if (lastUploadedName) {
                openFile(lastUploadedName);
            }
            const hasWebFiles = Object.keys(virtualFiles).some(f => f.endsWith('.html') || f.endsWith('.htm') || f.endsWith('.php'));
            if (hasWebFiles) {
                runCode();
            }
        }

        async function readDirectoryRecursive(dirHandle, path) {
            const ignoredDirs = ['.git', '.vscode', 'node_modules', 'vendor'];
            const ignoredExts = ['pdf', 'docx', 'doc', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', '7z', 'tar', 'gz'];
            
            for await (const entry of dirHandle.values()) {
                const relativePath = path ? path + '/' + entry.name : entry.name;
                if (entry.kind === 'file') {
                    const ext = entry.name.split('.').pop().toLowerCase();
                    if (ignoredExts.includes(ext)) {
                        continue;
                    }
                    const file = await entry.getFile();
                    if (file.size > 15 * 1024 * 1024) { // Skip files larger than 15MB
                        continue;
                    }
                    
                    if (isBinaryFile(entry.name)) {
                        const reader = new FileReader();
                        const dataUrlPromise = new Promise((resolve) => {
                            reader.onload = () => resolve(reader.result);
                        });
                        reader.readAsDataURL(file);
                        virtualFiles[relativePath] = await dataUrlPromise;
                    } else {
                        const text = await file.text();
                        virtualFiles[relativePath] = text;
                    }
                } else if (entry.kind === 'directory') {
                    if (ignoredDirs.includes(entry.name)) {
                        continue;
                    }
                    await readDirectoryRecursive(entry, relativePath);
                }
            }
        }

        // Save active editor file back to opened Local Folder
        async function saveCurrentFileToLocal() {
            if (!directoryHandle) {
                alert('Chưa mở thư mục cục bộ nào. Vui lòng chọn "Open Folder (Local)..." trước.');
                return;
            }
            if (!activeFile) {
                alert('Không có tệp tin nào đang mở để lưu.');
                return;
            }
            
            try {
                const parts = activeFile.split('/');
                let currentDir = directoryHandle;
                
                for (let i = 0; i < parts.length - 1; i++) {
                    currentDir = await currentDir.getDirectoryHandle(parts[i], { create: true });
                }
                
                const fileHandle = await currentDir.getFileHandle(parts[parts.length - 1], { create: true });
                const writable = await fileHandle.createWritable();
                await writable.write(editor.getValue());
                await writable.close();
                
                alert(`Đã lưu tệp tin "${activeFile}" thành công vào ổ đĩa!`);
            } catch (err) {
                alert('Lỗi khi lưu tệp tin: ' + err.message);
            }
        }

        // Download project as ZIP
        function downloadProjectZip() {
            const fileKeys = Object.keys(virtualFiles);
            if (fileKeys.length === 0) {
                alert('Không có tệp tin nào trong dự án để tải về.');
                return;
            }
            
            if (typeof JSZip === 'undefined') {
                alert('Thư viện nén ZIP chưa sẵn sàng. Vui lòng tải lại trang.');
                return;
            }
            
            const zip = new JSZip();
            fileKeys.forEach(path => {
                const content = virtualFiles[path];
                if (isBinaryFile(path) && typeof content === 'string' && content.startsWith('data:')) {
                    const base64Data = content.split(';base64,')[1];
                    if (base64Data) {
                        zip.file(path, base64Data, { base64: true });
                    } else {
                        zip.file(path, content);
                    }
                } else {
                    zip.file(path, content);
                }
            });
            
            zip.generateAsync({ type: 'blob' }).then(function(content) {
                const url = window.URL.createObjectURL(content);
                const a = document.createElement('a');
                a.href = url;
                a.download = (directoryHandle ? directoryHandle.name : 'student_project') + '.zip';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }).catch(err => {
                alert('Lỗi tạo tệp nén ZIP: ' + err.message);
            });
        }

        // Countdown and auto-submit logic
        let countdownInterval = null;
        let isSubmitting = false;
        const sessionEndTime = <?= isset($active_session) ? strtotime($active_session['end_time']) * 1000 : 'null' ?>;
        const sessionId = <?= isset($active_session) ? intval($active_session['id']) : 'null' ?>;
        const sessionTitle = <?= isset($active_session) ? json_encode($active_session['mo_ta']) : 'null' ?>;
        let hasSubmitted = <?= $current_session_submission ? 'true' : 'false' ?>;

        function updateSubmitButtonStatus() {
            const btn = document.getElementById('btn-submit-exam');
            const label = document.getElementById('btn-submit-exam-text');
            if (!btn || !label) return;
            btn.classList.add('submitted');
            label.textContent = 'Đã nộp bài';
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';
            if (editor) {
                editor.setReadOnly(true);
            }
        }

        function hideSubmitOverlay() {
            const overlay = document.getElementById('auto-submit-overlay');
            if (overlay) {
                overlay.remove();
            }
            isSubmitting = false;
        }

        async function submitProject(overlayMessage, options = {}) {
            const { isEarly = false, redirectOnSuccess = false } = options;

            if (!sessionId) {
                alert('Không tìm thấy phiên thi để nộp bài.');
                return false;
            }

            if (hasSubmitted) {
                alert('Bạn đã nộp bài rồi. Không được phép chỉnh sửa hoặc nộp lại.');
                return false;
            }

            if (isSubmitting) {
                return false;
            }

            if (isEarly) {
                const confirmText = 'Bạn có chắc chắn muốn nộp bài thực hành? Sau khi nộp, bạn sẽ không thể chỉnh sửa bài làm của mình nữa.';
                if (!confirm(confirmText)) {
                    return false;
                }
            }

            isSubmitting = true;
            showGlobalOverlayMessage(overlayMessage);

            try {
                if (activeFile && editor) {
                    virtualFiles[activeFile] = editor.getValue();
                }

                if (typeof JSZip === 'undefined') {
                    throw new Error("Không tìm thấy thư viện nén JSZip.");
                }

                const zip = new JSZip();
                let fileCount = 0;
                const ignoredDirs = ['.git', '.vscode', 'node_modules', 'vendor'];
                const ignoredExts = ['pdf', 'docx', 'doc', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', '7z', 'tar', 'gz'];
                
                for (const path in virtualFiles) {
                    const filename = path.split('/').pop();
                    const ext = filename.split('.').pop().toLowerCase();
                    
                    const pathParts = path.split('/');
                    const isIgnoredDir = pathParts.some(part => ignoredDirs.includes(part));
                    
                    if (isIgnoredDir || ignoredExts.includes(ext)) {
                        continue;
                    }
                    
                    const content = virtualFiles[path];
                    if (isBinaryFile(filename) && typeof content === 'string' && content.startsWith('data:')) {
                        const base64Data = content.split(';base64,')[1];
                        if (base64Data) {
                            zip.file(path, base64Data, { base64: true });
                        } else {
                            zip.file(path, content);
                        }
                    } else {
                        zip.file(path, content);
                    }
                    fileCount++;
                }

                if (fileCount === 0) {
                    throw new Error("Không có tệp tin nào để nén.");
                }

                const zipBlob = await zip.generateAsync({ type: 'blob' });

                const formData = new FormData();
                formData.append('session_id', sessionId);
                formData.append('zip_file', zipBlob, 'exam_project.zip');

                const response = await fetch('/tkb/api/submit_practice.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    hasSubmitted = true;
                    updateSubmitButtonStatus();
                    
                    if (countdownInterval) {
                        clearInterval(countdownInterval);
                    }
                    const countdownContainer = document.getElementById('session-countdown-container');
                    const countdownLabel = document.getElementById('session-countdown-label');
                    if (countdownContainer && countdownLabel) {
                        countdownLabel.innerHTML = "ĐÃ NỘP BÀI";
                        countdownContainer.style.color = "#10b981";
                    }

                    if (redirectOnSuccess) {
                        showGlobalOverlayMessage("Nộp bài thành công! Hệ thống sẽ chuyển về bảng điều khiển sau 3 giây...");
                        setTimeout(() => {
                            window.location.href = '/tkb/student/dashboard.php';
                        }, 3000);
                    } else {
                        showGlobalOverlayMessage(result.message || "Nộp bài thành công!");
                        setTimeout(hideSubmitOverlay, 2500);
                    }
                    return true;
                }

                throw new Error(result.error || "Lỗi lưu file nộp bài.");
            } catch (err) {
                console.error("Submit failed:", err);
                showGlobalOverlayMessage(`Nộp bài thất bại: ${err.message}. Vui lòng thử lại hoặc liên hệ giáo viên.`);
                if (redirectOnSuccess) {
                    setTimeout(() => {
                        window.location.href = '/tkb/student/dashboard.php';
                    }, 5000);
                } else {
                    setTimeout(hideSubmitOverlay, 4000);
                }
                return false;
            }
        }

        async function earlySubmitProject() {
            await submitProject("Đang nén và nộp bài cho giáo viên...", { isEarly: true });
        }

        async function autoSubmitProject() {
            await submitProject("Hết giờ làm bài! Đang tự động nén và nộp bài thi...", { redirectOnSuccess: true });
        }

        function startSessionCountdown() {
            if (!sessionEndTime || !sessionId) return;
            
            const titleContainer = document.getElementById('session-title-container');
            const titleLabel = document.getElementById('session-title-label');
            const countdownContainer = document.getElementById('session-countdown-container');
            const countdownLabel = document.getElementById('session-countdown-label');
            
            if (titleContainer && titleLabel) {
                titleLabel.textContent = `Thi/Thực hành: ${sessionTitle}`;
                titleContainer.style.display = 'inline-flex';
            }
            if (countdownContainer) {
                countdownContainer.style.display = 'inline-flex';
            }

            if (hasSubmitted) {
                if (countdownLabel) {
                    countdownLabel.innerHTML = "ĐÃ NỘP BÀI";
                    countdownContainer.style.color = "#10b981";
                }
                return;
            }
            
            countdownInterval = setInterval(async () => {
                const now = new Date().getTime();
                const distance = sessionEndTime - now;
                
                if (distance <= 0) {
                    clearInterval(countdownInterval);
                    countdownLabel.innerHTML = "HẾT GIỜ!";
                    countdownContainer.style.color = "#ef4444";
                    
                    await autoSubmitProject();
                } else {
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    const minStr = minutes < 10 ? '0' + minutes : minutes;
                    const secStr = seconds < 10 ? '0' + seconds : seconds;
                    
                    countdownLabel.innerHTML = `Còn lại: ${minStr}:${secStr}`;
                    
                    if (distance < 120000) {
                        countdownContainer.style.color = "#f43f5e";
                    } else {
                        countdownContainer.style.color = "#f59e0b";
                    }
                }
            }, 1000);
        }

        function showGlobalOverlayMessage(msg) {
            let overlay = document.getElementById('auto-submit-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'auto-submit-overlay';
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100vw';
                overlay.style.height = '100vh';
                overlay.style.background = 'rgba(15, 23, 42, 0.9)';
                overlay.style.zIndex = '99999';
                overlay.style.display = 'flex';
                overlay.style.flexDirection = 'column';
                overlay.style.alignItems = 'center';
                overlay.style.justifyContent = 'center';
                overlay.style.color = '#fff';
                overlay.style.fontFamily = "'Outfit', sans-serif";
                overlay.style.padding = '20px';
                overlay.style.textAlign = 'center';
                
                const spinner = document.createElement('div');
                spinner.style.marginBottom = '20px';
                spinner.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" style="font-size: 48px; color: var(--accent);"></i>';
                overlay.appendChild(spinner);
                
                const textNode = document.createElement('h3');
                textNode.id = 'auto-submit-text';
                textNode.style.fontWeight = '700';
                textNode.style.fontSize = '20px';
                overlay.appendChild(textNode);
                
                document.body.appendChild(overlay);
            }
            
            document.getElementById('auto-submit-text').textContent = msg;
        }

        // ===== BREADCRUMB BAR =====
        function updateBreadcrumb(filePath) {
            const bar = document.getElementById('vscode-breadcrumb-bar');
            if (!bar) return;
            if (!filePath) {
                bar.innerHTML = '<span class="breadcrumb-item"><i class="fa-solid fa-folder" style="color:#e2b73c;font-size:11px;"></i> STUDENT_PROJECT</span>';
                return;
            }
            const parts = filePath.split('/');
            let html = '<span class="breadcrumb-item" onclick="toggleSidebarSection(\'explorer\')"><i class="fa-solid fa-folder" style="color:#e2b73c;font-size:11px;"></i> STUDENT_PROJECT</span>';
            parts.forEach((part, i) => {
                html += '<i class="fa-solid fa-chevron-right breadcrumb-sep"></i>';
                const isLast = (i === parts.length - 1);
                const icon = isLast ? getFileIcon(part) : 'fa-solid fa-folder';
                const iconStyle = isLast ? '' : 'style="color:#e2b73c;font-size:11px;"';
                html += `<span class="breadcrumb-item"><i class="${icon}" ${iconStyle}></i> ${part}</span>`;
            });
            bar.innerHTML = html;
        }

        // ===== TOAST NOTIFICATIONS =====
        function showToast(html, duration = 2000) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = html;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // ===== COMMAND PALETTE =====
        const commandPaletteCommands = [
            { label: 'New File...', icon: 'fa-solid fa-file-circle-plus', shortcut: '', action: () => { closeCommandPalette(); createNewFile(); } },
            { label: 'New Folder...', icon: 'fa-solid fa-folder-plus', shortcut: '', action: () => { closeCommandPalette(); createNewFolder(); } },
            { label: 'Open Folder (Local)...', icon: 'fa-solid fa-folder-open', shortcut: '', action: () => { closeCommandPalette(); openLocalFolder(); } },
            { label: 'Quick Open File', icon: 'fa-solid fa-file-lines', shortcut: 'Ctrl+P', action: () => { closeCommandPalette(); openQuickOpen(); } },
            { label: 'Run Code', icon: 'fa-solid fa-play', shortcut: 'Ctrl+Enter', action: () => { closeCommandPalette(); runCode(); } },
            { label: 'Download Current File', icon: 'fa-solid fa-download', shortcut: '', action: () => { closeCommandPalette(); downloadCode(); } },
            { label: 'Download Project (.zip)', icon: 'fa-solid fa-file-zipper', shortcut: '', action: () => { closeCommandPalette(); downloadProjectZip(); } },
            { label: 'Toggle Sidebar', icon: 'fa-solid fa-columns', shortcut: 'Ctrl+B', action: () => { closeCommandPalette(); toggleSidebarVisibility(); } },
            { label: 'Toggle Terminal', icon: 'fa-solid fa-terminal', shortcut: 'Ctrl+`', action: () => { closeCommandPalette(); toggleTerminalPane(); } },
            { label: 'Search in Files', icon: 'fa-solid fa-magnifying-glass', shortcut: 'Ctrl+Shift+F', action: () => { closeCommandPalette(); toggleSidebarSection('search'); } },
            { label: 'Explorer: Show File Explorer', icon: 'fa-regular fa-copy', shortcut: '', action: () => { closeCommandPalette(); toggleSidebarSection('explorer'); } },
            { label: 'Theme: Monokai Dark', icon: 'fa-solid fa-palette', shortcut: '', action: () => { closeCommandPalette(); editor.setTheme('ace/theme/monokai'); document.getElementById('setting-theme').value = 'ace/theme/monokai'; } },
            { label: 'Theme: Dracula Dark', icon: 'fa-solid fa-palette', shortcut: '', action: () => { closeCommandPalette(); editor.setTheme('ace/theme/dracula'); document.getElementById('setting-theme').value = 'ace/theme/dracula'; } },
            { label: 'Theme: Tomorrow Night', icon: 'fa-solid fa-palette', shortcut: '', action: () => { closeCommandPalette(); editor.setTheme('ace/theme/tomorrow_night'); document.getElementById('setting-theme').value = 'ace/theme/tomorrow_night'; } },
            { label: 'Theme: Chrome Light', icon: 'fa-solid fa-palette', shortcut: '', action: () => { closeCommandPalette(); editor.setTheme('ace/theme/chrome'); document.getElementById('setting-theme').value = 'ace/theme/chrome'; } },
            { label: 'Font Size: 12px', icon: 'fa-solid fa-text-height', shortcut: '', action: () => { closeCommandPalette(); editor.setFontSize('12px'); } },
            { label: 'Font Size: 14px', icon: 'fa-solid fa-text-height', shortcut: '', action: () => { closeCommandPalette(); editor.setFontSize('14px'); } },
            { label: 'Font Size: 16px', icon: 'fa-solid fa-text-height', shortcut: '', action: () => { closeCommandPalette(); editor.setFontSize('16px'); } },
            { label: 'Font Size: 18px', icon: 'fa-solid fa-text-height', shortcut: '', action: () => { closeCommandPalette(); editor.setFontSize('18px'); } },
            { label: 'Reset Template', icon: 'fa-solid fa-arrow-rotate-left', shortcut: '', action: () => { closeCommandPalette(); resetTemplate(); } },
            { label: 'Clear Workspace', icon: 'fa-solid fa-trash-can', shortcut: '', action: () => { closeCommandPalette(); virtualFiles={}; openTabs=[]; renderExplorer(); renderTabs(); clearEditor(); } },
            { label: 'Clear Terminal Output', icon: 'fa-solid fa-ban', shortcut: '', action: () => { closeCommandPalette(); clearConsole(); } },
            { label: 'Settings: Open Editor Settings', icon: 'fa-solid fa-sliders', shortcut: '', action: () => { closeCommandPalette(); toggleSidebarSection('settings'); } },
            { label: 'Start with Template...', icon: 'fa-solid fa-clone', shortcut: '', action: () => { closeCommandPalette(); resetAndLoadTemplate(); } }
        ];

        function openCommandPalette() {
            const overlay = document.getElementById('cmd-palette-overlay');
            overlay.classList.add('active');
            const input = document.getElementById('cmd-palette-input');
            input.value = '';
            cmdPaletteSelectedIndex = 0;
            filterCommandPalette();
            setTimeout(() => input.focus(), 50);
        }

        function closeCommandPalette(e) {
            document.getElementById('cmd-palette-overlay').classList.remove('active');
        }

        function filterCommandPalette() {
            const query = document.getElementById('cmd-palette-input').value.toLowerCase().replace(/^>\s*/, '');
            const list = document.getElementById('cmd-palette-list');
            const filtered = commandPaletteCommands.filter(cmd => cmd.label.toLowerCase().includes(query));
            cmdPaletteSelectedIndex = 0;
            list.innerHTML = filtered.map((cmd, i) => `
                <div class="cmd-palette-item ${i === 0 ? 'selected' : ''}" data-index="${i}" onclick="executeCmdPaletteItem(${i})" onmouseenter="selectCmdPaletteItem(${i})">
                    <i class="${cmd.icon}"></i>
                    <span>${cmd.label}</span>
                    ${cmd.shortcut ? `<span class="cmd-shortcut">${cmd.shortcut}</span>` : ''}
                </div>
            `).join('');
        }

        function selectCmdPaletteItem(index) {
            cmdPaletteSelectedIndex = index;
            document.querySelectorAll('#cmd-palette-list .cmd-palette-item').forEach((el, i) => {
                el.classList.toggle('selected', i === index);
            });
        }

        function executeCmdPaletteItem(index) {
            const query = document.getElementById('cmd-palette-input').value.toLowerCase().replace(/^>\s*/, '');
            const filtered = commandPaletteCommands.filter(cmd => cmd.label.toLowerCase().includes(query));
            if (filtered[index]) filtered[index].action();
        }

        function handleCmdPaletteKey(e) {
            const items = document.querySelectorAll('#cmd-palette-list .cmd-palette-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                cmdPaletteSelectedIndex = Math.min(cmdPaletteSelectedIndex + 1, items.length - 1);
                selectCmdPaletteItem(cmdPaletteSelectedIndex);
                items[cmdPaletteSelectedIndex]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                cmdPaletteSelectedIndex = Math.max(cmdPaletteSelectedIndex - 1, 0);
                selectCmdPaletteItem(cmdPaletteSelectedIndex);
                items[cmdPaletteSelectedIndex]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                executeCmdPaletteItem(cmdPaletteSelectedIndex);
            } else if (e.key === 'Escape') {
                closeCommandPalette();
            }
        }

        // ===== QUICK OPEN (Ctrl+P) =====
        function openQuickOpen() {
            const overlay = document.getElementById('quick-open-overlay');
            overlay.classList.add('active');
            const input = document.getElementById('quick-open-input');
            input.value = '';
            quickOpenSelectedIndex = 0;
            filterQuickOpen();
            setTimeout(() => input.focus(), 50);
        }

        function closeQuickOpen(e) {
            document.getElementById('quick-open-overlay').classList.remove('active');
        }

        function filterQuickOpen() {
            const query = document.getElementById('quick-open-input').value.toLowerCase();
            const list = document.getElementById('quick-open-list');
            const files = Object.keys(virtualFiles).filter(f => !f.endsWith('.gitkeep'));
            const filtered = files.filter(f => f.toLowerCase().includes(query));
            quickOpenSelectedIndex = 0;
            list.innerHTML = filtered.map((file, i) => {
                const baseName = file.split('/').pop();
                const iconClass = getFileIcon(baseName);
                return `<div class="cmd-palette-item ${i === 0 ? 'selected' : ''}" data-index="${i}" onclick="quickOpenFile(${i})" onmouseenter="selectQuickOpenItem(${i})">
                    <i class="${iconClass}"></i>
                    <span>${baseName}</span>
                    <span class="cmd-shortcut" style="color:#858585;">${file}</span>
                </div>`;
            }).join('');
            if (filtered.length === 0) {
                list.innerHTML = '<div style="padding:12px;color:#858585;text-align:center;font-size:13px;">No matching files found</div>';
            }
        }

        function selectQuickOpenItem(index) {
            quickOpenSelectedIndex = index;
            document.querySelectorAll('#quick-open-list .cmd-palette-item').forEach((el, i) => {
                el.classList.toggle('selected', i === index);
            });
        }

        function quickOpenFile(index) {
            const query = document.getElementById('quick-open-input').value.toLowerCase();
            const files = Object.keys(virtualFiles).filter(f => !f.endsWith('.gitkeep') && f.toLowerCase().includes(query));
            if (files[index]) {
                closeQuickOpen();
                openFile(files[index]);
            }
        }

        function handleQuickOpenKey(e) {
            const items = document.querySelectorAll('#quick-open-list .cmd-palette-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                quickOpenSelectedIndex = Math.min(quickOpenSelectedIndex + 1, items.length - 1);
                selectQuickOpenItem(quickOpenSelectedIndex);
                items[quickOpenSelectedIndex]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                quickOpenSelectedIndex = Math.max(quickOpenSelectedIndex - 1, 0);
                selectQuickOpenItem(quickOpenSelectedIndex);
                items[quickOpenSelectedIndex]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                quickOpenFile(quickOpenSelectedIndex);
            } else if (e.key === 'Escape') {
                closeQuickOpen();
            }
        }

        // ===== CONTEXT MENU =====
        function showContextMenu(menuId, x, y, items) {
            closeAllContextMenus();
            const menu = document.getElementById(menuId);
            menu.innerHTML = items.map(item => {
                if (item.divider) return '<div class="ctx-menu-divider"></div>';
                return `<div class="ctx-menu-item" onclick="${item.onclick}">
                    <i class="${item.icon}"></i>
                    <span>${item.label}</span>
                    ${item.shortcut ? `<span class="ctx-shortcut">${item.shortcut}</span>` : ''}
                </div>`;
            }).join('');

            // Position menu, ensuring it stays within viewport
            const menuWidth = 220;
            const menuHeight = items.length * 32;
            const posX = (x + menuWidth > window.innerWidth) ? x - menuWidth : x;
            const posY = (y + menuHeight > window.innerHeight) ? y - menuHeight : y;
            menu.style.left = Math.max(0, posX) + 'px';
            menu.style.top = Math.max(0, posY) + 'px';
            menu.classList.add('active');
        }

        function closeAllContextMenus() {
            document.querySelectorAll('.ctx-menu').forEach(m => m.classList.remove('active'));
        }

        // Editor right-click context menu
        function setupEditorContextMenu() {
            const editorEl = document.getElementById('editor');
            if (!editorEl) return;
            editorEl.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                showContextMenu('ctx-menu-editor', e.clientX, e.clientY, [
                    { label: 'Cut', icon: 'fa-solid fa-scissors', shortcut: 'Ctrl+X', onclick: "editor.execCommand('cut'); closeAllContextMenus();" },
                    { label: 'Copy', icon: 'fa-solid fa-copy', shortcut: 'Ctrl+C', onclick: "editor.execCommand('copy'); closeAllContextMenus();" },
                    { label: 'Paste', icon: 'fa-solid fa-paste', shortcut: 'Ctrl+V', onclick: "document.execCommand('paste'); closeAllContextMenus();" },
                    { divider: true },
                    { label: 'Select All', icon: 'fa-solid fa-object-group', shortcut: 'Ctrl+A', onclick: "editor.selectAll(); closeAllContextMenus();" },
                    { divider: true },
                    { label: 'Find', icon: 'fa-solid fa-magnifying-glass', shortcut: 'Ctrl+F', onclick: "editor.execCommand('find'); closeAllContextMenus();" },
                    { label: 'Replace', icon: 'fa-solid fa-right-left', shortcut: 'Ctrl+H', onclick: "editor.execCommand('replace'); closeAllContextMenus();" },
                    { divider: true },
                    { label: 'Command Palette...', icon: 'fa-solid fa-terminal', shortcut: 'Ctrl+Shift+P', onclick: "closeAllContextMenus(); openCommandPalette();" }
                ]);
            });
        }

        // Explorer right-click context menu
        function setupExplorerContextMenu() {
            const explorerSection = document.querySelector('.explorer-section');
            if (!explorerSection) return;
            explorerSection.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                // Find clicked file row
                const fileRow = e.target.closest('.explorer-file-row');
                const clickedFile = fileRow ? fileRow.getAttribute('onclick')?.match(/openFile\('([^']+)'\)/)?.[1] : null;
                
                const items = [
                    { label: 'New File...', icon: 'fa-solid fa-file-circle-plus', shortcut: '', onclick: "closeAllContextMenus(); createNewFile();" },
                    { label: 'New Folder...', icon: 'fa-solid fa-folder-plus', shortcut: '', onclick: "closeAllContextMenus(); createNewFolder();" },
                    { divider: true }
                ];

                if (clickedFile) {
                    items.push(
                        { label: 'Rename...', icon: 'fa-solid fa-pen', shortcut: 'F2', onclick: `closeAllContextMenus(); renameFile('${clickedFile}');` },
                        { label: 'Delete', icon: 'fa-solid fa-trash', shortcut: 'Delete', onclick: `closeAllContextMenus(); deleteFileDirectly('${clickedFile}');` },
                        { divider: true },
                        { label: 'Copy Path', icon: 'fa-solid fa-clipboard', shortcut: '', onclick: `closeAllContextMenus(); navigator.clipboard.writeText('${clickedFile}'); showToast('<i class=\"fa-solid fa-clipboard\" style=\"color:#3794ff;\"></i> Path copied: ${clickedFile}');` }
                    );
                }

                items.push(
                    { label: 'Upload File...', icon: 'fa-solid fa-cloud-arrow-up', shortcut: '', onclick: "closeAllContextMenus(); triggerFileUpload();" }
                );

                showContextMenu('ctx-menu-explorer', e.clientX, e.clientY, items);
            });
        }

        // ===== RENAME FILE =====
        function renameFile(oldName) {
            if (hasSubmitted) {
                alert("Bạn đã nộp bài rồi. Không được phép chỉnh sửa.");
                return;
            }
            const newName = prompt(`Đổi tên tệp "${oldName}" thành:`, oldName);
            if (!newName || newName === oldName) return;
            const cleanName = newName.trim().replace(/\\/g, '/');
            if (!/^[a-zA-Z0-9_.\-\/]+$/.test(cleanName)) {
                alert('Tên tệp không hợp lệ!');
                return;
            }
            if (virtualFiles[cleanName] !== undefined) {
                alert('Tệp này đã tồn tại!');
                return;
            }
            // Transfer content
            virtualFiles[cleanName] = virtualFiles[oldName];
            delete virtualFiles[oldName];
            // Update tabs
            openTabs = openTabs.map(t => t === oldName ? cleanName : t);
            // Update modified tracking
            if (modifiedFiles[oldName]) {
                modifiedFiles[cleanName] = true;
                delete modifiedFiles[oldName];
            }
            if (activeFile === oldName) {
                activeFile = cleanName;
            }
            renderExplorer();
            renderTabs();
            if (activeFile === cleanName) {
                openFile(cleanName);
            }
            showToast(`<i class="fa-solid fa-pen" style="color:#3794ff;"></i> Renamed: ${oldName} → ${cleanName}`);
        }

        function deleteFileDirectly(fileName) {
            if (hasSubmitted) {
                alert("Bạn đã nộp bài rồi. Không được phép xóa tệp.");
                return;
            }
            if (confirm(`Bạn có chắc muốn xóa tệp tin ${fileName}?`)) {
                delete virtualFiles[fileName];
                openTabs = openTabs.filter(t => t !== fileName);
                if (activeFile === fileName) {
                    const remaining = Object.keys(virtualFiles);
                    if (remaining.length > 0) {
                        openFile(remaining[0]);
                    } else {
                        clearEditor();
                    }
                } else {
                    renderExplorer();
                    renderTabs();
                }
            }
        }

        // ===== SEARCH IN FILES =====
        function performSearch() {
            const query = document.getElementById('search-input').value;
            const resultsContainer = document.getElementById('search-results');
            const countEl = document.getElementById('search-match-count');
            
            if (!query || query.length < 2) {
                resultsContainer.innerHTML = '';
                countEl.textContent = '';
                return;
            }

            let totalMatches = 0;
            let html = '';
            const queryLower = query.toLowerCase();

            Object.keys(virtualFiles).sort().forEach(filePath => {
                if (isBinaryFile(filePath)) return;
                const content = virtualFiles[filePath] || '';
                const lines = content.split('\n');
                let fileMatches = [];
                
                lines.forEach((line, lineIdx) => {
                    if (line.toLowerCase().includes(queryLower)) {
                        fileMatches.push({ lineNum: lineIdx + 1, text: line.trim() });
                        totalMatches++;
                    }
                });

                if (fileMatches.length > 0) {
                    const baseName = filePath.split('/').pop();
                    const iconClass = getFileIcon(baseName);
                    html += `<div class="search-result-file" onclick="openFile('${filePath}')">
                        <i class="${iconClass}"></i> ${baseName}
                        <span style="color:#858585;font-weight:400;margin-left:auto;font-size:11px;">${fileMatches.length}</span>
                    </div>`;
                    fileMatches.slice(0, 10).forEach(match => {
                        const highlighted = match.text.replace(
                            new RegExp(escapeRegExp(query), 'gi'),
                            '<span class="search-highlight">$&</span>'
                        );
                        html += `<div class="search-result-line" onclick="openFile('${filePath}'); editor.gotoLine(${match.lineNum});">
                            <span style="color:#858585;margin-right:6px;">${match.lineNum}:</span>${highlighted}
                        </div>`;
                    });
                }
            });

            resultsContainer.innerHTML = html || '<div style="padding:12px;color:#858585;text-align:center;font-size:13px;">No results found</div>';
            countEl.textContent = totalMatches > 0 ? `${totalMatches} results in ${Object.keys(virtualFiles).length} files` : '';
        }

        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        // ===== SIDEBAR VISIBILITY TOGGLE =====
        function toggleSidebarVisibility() {
            const sidebar = document.getElementById('vscode-sidebar');
            const handle = document.getElementById('sidebar-resize-handle');
            sidebar.classList.toggle('hidden');
            if (handle) handle.style.display = sidebar.classList.contains('hidden') ? 'none' : '';
            if (window.editor) editor.resize();
        }

        // ===== INDENTATION TOGGLE =====
        function toggleIndentation() {
            const current = editor.getOption('tabSize');
            const next = current === 4 ? 2 : 4;
            editor.setOption('tabSize', next);
            document.getElementById('status-indent').textContent = `Spaces: ${next}`;
            showToast(`<i class="fa-solid fa-indent" style="color:#3794ff;"></i> Tab size: ${next} spaces`);
        }

        // ===== DRAG RESIZE HANDLES =====
        function initResizeHandles() {
            // Sidebar horizontal resize
            const sidebarHandle = document.getElementById('sidebar-resize-handle');
            const sidebar = document.getElementById('vscode-sidebar');
            if (sidebarHandle && sidebar) {
                let isDragging = false;
                sidebarHandle.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    isDragging = true;
                    sidebarHandle.classList.add('active');
                    document.body.style.cursor = 'col-resize';
                    document.body.style.userSelect = 'none';
                    
                    const onMouseMove = (e) => {
                        if (!isDragging) return;
                        const activityBarWidth = 50;
                        const newWidth = Math.max(150, Math.min(450, e.clientX - sidebar.getBoundingClientRect().left));
                        sidebar.style.width = newWidth + 'px';
                        if (window.editor) editor.resize();
                    };
                    
                    const onMouseUp = () => {
                        isDragging = false;
                        sidebarHandle.classList.remove('active');
                        document.body.style.cursor = '';
                        document.body.style.userSelect = '';
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                        if (window.editor) editor.resize();
                    };
                    
                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            }

            // Terminal vertical resize
            const termHandle = document.getElementById('terminal-resize-handle');
            const termPane = document.querySelector('.vscode-terminal-pane');
            if (termHandle && termPane) {
                let isDragging = false;
                termHandle.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    isDragging = true;
                    termHandle.classList.add('active');
                    document.body.style.cursor = 'row-resize';
                    document.body.style.userSelect = 'none';
                    
                    const mainArea = document.querySelector('.vscode-main-area');
                    const onMouseMove = (e) => {
                        if (!isDragging) return;
                        const mainRect = mainArea.getBoundingClientRect();
                        const newHeight = Math.max(80, Math.min(500, mainRect.bottom - e.clientY));
                        termPane.style.height = newHeight + 'px';
                        if (window.editor) editor.resize();
                    };
                    
                    const onMouseUp = () => {
                        isDragging = false;
                        termHandle.classList.remove('active');
                        document.body.style.cursor = '';
                        document.body.style.userSelect = '';
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                        if (window.editor) editor.resize();
                    };
                    
                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            }
        }

        // ===== LOAD TEMPLATE (Start with Template) =====
        function resetAndLoadTemplate() {
            if (hasSubmitted) {
                alert("Bạn đã nộp bài rồi.");
                return;
            }
            virtualFiles = {};
            openTabs = [];
            modifiedFiles = {};
            activeFile = '';

            // Load all default template files
            virtualFiles['index.html'] = templates.html;
            virtualFiles['style.css'] = templates.css;
            virtualFiles['script.js'] = templates.js;

            renderExplorer();
            openFile('index.html');
            showToast('<i class="fa-solid fa-clone" style="color:#10b981;"></i> Template loaded: HTML + CSS + JS');
        }

        // ===== ACCORDION TOGGLE =====
        function toggleAccordion(id) {
            const content = document.getElementById('explorer-' + id + '-content');
            const icon = document.getElementById(id + '-accordion-icon');
            if (content) {
                const isHidden = content.style.display === 'none';
                content.style.display = isHidden ? 'block' : 'none';
                if (icon) icon.className = isHidden ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-right';
            }
        }

        // ===== SHOW ACCOUNT INFO =====
        function showAccountInfo() {
            showToast('<i class="fa-solid fa-circle-user" style="color:#007acc;"></i> Account: Sinh viên (Trường Cao Đẳng Cà Mau)');
        }

        // Escape HTML tags
        function escapeHtml(text) {
            if (!text) return "";
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function parseJsonWithRegexFallback(rawStr) {
            if (!rawStr) return {};
            if (typeof rawStr === 'object' && rawStr !== null && !Array.isArray(rawStr)) return rawStr;
            let trimmed = (typeof rawStr === 'string') ? rawStr.trim() : '';

            if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) {
                try {
                    let b64 = decodeURIComponent(escape(atob(trimmed)));
                    if (b64.startsWith('{') || b64.startsWith('[')) trimmed = b64;
                } catch(e1) {
                    try {
                        let b642 = atob(trimmed);
                        if (b642.startsWith('{') || b642.startsWith('[')) trimmed = b642;
                    } catch(e2){}
                }
            }

            if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
                try {
                    let res = JSON.parse(trimmed);
                    if (typeof res === 'string') res = JSON.parse(res);
                    if (res && typeof res === 'object' && !Array.isArray(res) && Object.keys(res).length > 0) {
                        return res;
                    }
                } catch(jsonErr) {}
            }

            // Regex extraction fallback for truncated JSON string
            const bundle = {};
            const regex = /"([^"\r\n]+\.[a-zA-Z0-9]+)"\s*:\s*"((?:[^"\\]|\\.)*)"/g;
            let match;
            while ((match = regex.exec(trimmed)) !== null) {
                try {
                    const fn = match[1];
                    let fc = match[2];
                    try { fc = JSON.parse('"' + fc + '"'); } catch(e){}
                    bundle[fn] = fc;
                } catch(e){}
            }
            return bundle;
        }

        // Initialize lists on load
        document.addEventListener('DOMContentLoaded', () => {
            renderExplorer();
            renderTabs();
            clearEditor();

            console.log('[DEBUG] preloadedCodeData:', window.preloadedCodeData);

            if (window.preloadedCodeData && window.preloadedCodeData.code) {
                let raw = window.preloadedCodeData.code;
                let loadedJson = false;

                try {
                    let parsed = parseJsonWithRegexFallback(raw);

                    if (parsed && typeof parsed === 'object' && !Array.isArray(parsed) && Object.keys(parsed).length > 0) {
                        virtualFiles = parsed;
                        renderExplorer();
                        const fileKeys = Object.keys(virtualFiles);
                        const mainKey = fileKeys.find(k => k.toLowerCase() === 'index.html' || k.toLowerCase().endsWith('/index.html') || k.toLowerCase() === 'index.php' || k.toLowerCase().endsWith('/index.php') || k.toLowerCase() === 'main.py' || k.toLowerCase() === 'main.cpp' || k.toLowerCase() === 'main.php' || k.toLowerCase() === 'app.py') ||
                                        fileKeys.find(k => k.toLowerCase().endsWith('.php') && !k.includes('config') && !k.includes('include') && !k.includes('db.')) ||
                                        fileKeys.find(k => k.toLowerCase().endsWith('.html') || k.toLowerCase().endsWith('.htm')) ||
                                        fileKeys.find(k => !isBinaryFile(k)) ||
                                        fileKeys[0];
                        if (mainKey) {
                            openFile(mainKey);
                        }
                        showToast('<i class="fa-solid fa-folder-open" style="color:#a855f7;"></i> Đã nạp bài làm: ' + escapeHtml(window.preloadedCodeData.title || 'Dự án'));
                        loadedJson = true;
                    }
                } catch(e) {
                    console.error('Parse preloaded error:', e);
                }

                if (!loadedJson) {
                    let filename = 'main.py';
                    const l = (window.preloadedCodeData.lang || '').toLowerCase();
                    if (l === 'cpp' || l === 'c') filename = 'main.cpp';
                    else if (l === 'java') filename = 'Main.java';
                    else if (l === 'php') filename = 'main.php';
                    else if (l === 'html') filename = 'index.html';

                    virtualFiles[filename] = (typeof raw === 'string') ? raw : JSON.stringify(raw, null, 2);
                    renderExplorer();
                    openFile(filename);
                    showToast('<i class="fa-solid fa-folder-open" style="color:#a855f7;"></i> Đã nạp bài làm: ' + escapeHtml(window.preloadedCodeData.title || 'Kho Code'));
                }

                // Tự động chạy xem trước Live Preview đối với bài làm Web HTML & PHP
                setTimeout(() => {
                    const activeInfo = typeof getFileInfo === 'function' ? getFileInfo(activeFile) : null;
                    if (activeInfo && (activeInfo.lang === 'html' || activeInfo.lang === 'php') && typeof runCode === 'function') {
                        runCode();
                    }
                }, 800);
            }

            // Check if user imported code from AI Workspace (Antigravity Bridge)
            try {
                const rawImport = localStorage.getItem('vkc_ai_import_code');
                if (rawImport) {
                    const imp = JSON.parse(rawImport);
                    if (imp && imp.content && (Date.now() - (imp.timestamp || 0) < 600000)) {
                        const fname = imp.filename || 'ai_code.py';
                        virtualFiles[fname] = imp.content;
                        renderExplorer();
                        openFile(fname);
                        showToast('<i class="fa-solid fa-wand-magic-sparkles" style="color:#10b981;"></i> Đã nạp mã nguồn từ AI Workspace: ' + escapeHtml(fname));
                        localStorage.removeItem('vkc_ai_import_code');

                        // Auto-run if web file
                        if (fname.endsWith('.html') || fname.endsWith('.php')) {
                            setTimeout(() => {
                                if (typeof runCode === 'function') runCode();
                            }, 700);
                        }
                    }
                }
            } catch(e) {
                console.error('Error importing from AI Workspace:', e);
            }

            async function fetchMissingLazyFiles(storageId) {
                const missingFiles = [];
                for (const fn in virtualFiles) {
                    if (virtualFiles[fn] === '__LAZY_FETCH__') missingFiles.push(fn);
                }
                if (missingFiles.length === 0) return;

                let hasNew = false;
                for (const fn of missingFiles) {
                    try {
                        const r = await fetch(`/tkb/api/code_storage_api.php?action=get&id=${storageId}&mode=raw&filename=${encodeURIComponent(fn)}`);
                        if (r.ok) {
                            const content = await r.text();
                            if (content && content !== 'File not found') {
                                virtualFiles[fn] = content;
                                hasNew = true;
                            }
                        }
                    } catch(e) {
                        console.warn('Lazy fetch error for', fn, e);
                    }
                }
                if (hasNew) {
                    renderExplorer();
                    if (typeof runCode === 'function') runCode();
                }
            }

            // Fallback AJAX loader for large projects or when preloaded inline JSON was empty
            if ((!virtualFiles || Object.keys(virtualFiles).length === 0) && window.preloadedStorageId && window.preloadedStorageId > 0) {
                showToast('🚀 Đang tải bài làm từ cơ sở dữ liệu...');
                fetch('/tkb/api/code_storage_api.php?action=get&id=' + window.preloadedStorageId)
                    .then(r => r.json())
                    .then(res => {
                        if (res && res.success && res.data && res.data.ma_nguon) {
                            let rawStr = res.data.ma_nguon;
                            let parsedObj = parseJsonWithRegexFallback(rawStr);
                            if (parsedObj && typeof parsedObj === 'object' && !Array.isArray(parsedObj) && Object.keys(parsedObj).length > 0) {
                                virtualFiles = parsedObj;
                                renderExplorer();
                                const fileKeys = Object.keys(virtualFiles);
                                const mainKey = fileKeys.find(k => k.toLowerCase() === 'about.php' || k.toLowerCase().endsWith('/about.php') || k.toLowerCase() === 'index.html' || k.toLowerCase().endsWith('/index.html') || k.toLowerCase() === 'index.php' || k.toLowerCase().endsWith('/index.php') || k.toLowerCase() === 'main.py' || k.toLowerCase() === 'main.cpp' || k.toLowerCase() === 'main.php' || k.toLowerCase() === 'app.py') ||
                                                fileKeys.find(k => k.toLowerCase().endsWith('.php') && !k.includes('config') && !k.includes('include') && !k.includes('db.')) ||
                                                fileKeys.find(k => k.toLowerCase().endsWith('.html') || k.toLowerCase().endsWith('.htm')) ||
                                                fileKeys.find(k => !isBinaryFile(k)) ||
                                                fileKeys[0];
                                if (mainKey) openFile(mainKey);
                                showToast('✅ Đã nạp thành công ' + (res.data.total_file_count || fileKeys.length) + ' tệp dự án!');

                                fetchMissingLazyFiles(window.preloadedStorageId);

                                setTimeout(() => {
                                    const activeInfo = typeof getFileInfo === 'function' ? getFileInfo(activeFile) : null;
                                    if (activeInfo && (activeInfo.lang === 'html' || activeInfo.lang === 'php') && typeof runCode === 'function') {
                                        runCode();
                                    }
                                }, 500);
                            }
                        } else {
                            let debugMsg = res ? (res.message || JSON.stringify(res)) : 'res is null/undefined';
                            showToast('❌ Không thể tải mã nguồn: ' + debugMsg);
                        }
                    })
                    .catch(err => {
                        console.error('AJAX project fallback load error:', err);
                        showToast('❌ Lỗi kết nối hoặc phân tích dữ liệu: ' + err.message);
                    });
            }

            if (hasSubmitted) {
                updateSubmitButtonStatus();
            }
            startSessionCountdown();
            initResizeHandles();
            setupEditorContextMenu();
            setupExplorerContextMenu();

            // Close context menus on click elsewhere
            document.addEventListener('click', () => closeAllContextMenus());

            // Setup drag-and-drop file upload on the IDE window
            const windowContainer = document.querySelector('.vscode-window');
            if (windowContainer) {
                windowContainer.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
                windowContainer.addEventListener('drop', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (hasSubmitted) return;
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        await processUploadedFiles(e.dataTransfer.files);
                    }
                });
            }
        });
    </script>
</body>
</html>
