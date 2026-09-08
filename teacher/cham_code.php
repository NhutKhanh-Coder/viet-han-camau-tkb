<?php
require_once '../config.php';
requireTeacher();
$db = getDB();

$sub_id = intval($_GET['submission_id'] ?? 0);
if ($sub_id <= 0) {
    die("<div style='color:red;font-weight:bold;padding:20px;text-align:center;'>Thiếu thông tin bài nộp!</div>");
}

// Fetch submission details
$stmt = $db->prepare("
    SELECT ps.*, s.ho_ten, s.ma_sv, s.lop, s.khoa, sess.mo_ta as session_title, sess.id as sess_id
    FROM practice_submissions ps
    JOIN students s ON ps.student_id = s.id
    JOIN practice_sessions sess ON ps.session_id = sess.id
    WHERE ps.id = ?
");
$stmt->bind_param("i", $sub_id);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sub) {
    die("<div style='color:red;font-weight:bold;padding:20px;text-align:center;'>Không tìm thấy bài nộp hoặc bạn không có quyền xem!</div>");
}

// Read and extract ZIP file to load into VFS (Virtual File System) on client side
$zip_file_path = '..' . str_replace('/tkb', '', $sub['file_path']);
$virtual_files = [];

if (file_exists($zip_file_path)) {
    $zip = new ZipArchive;
    if ($zip->open($zip_file_path) === TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            // Skip folders
            if (substr($filename, -1) === '/') {
                continue;
            }
            $content = $zip->getFromIndex($i);
            
            // Check if binary or text
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $is_binary = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'mp3', 'wav', 'ogg', 'm4a', 'mp4', 'webm']);
            
            if ($is_binary) {
                // Encode binary content as base64 data URL so browser can preview it!
                $mime = 'image/png';
                if ($ext === 'jpg' || $ext === 'jpeg') $mime = 'image/jpeg';
                elseif ($ext === 'gif') $mime = 'image/gif';
                elseif ($ext === 'webp') $mime = 'image/webp';
                elseif ($ext === 'svg') $mime = 'image/svg+xml';
                elseif ($ext === 'mp3') $mime = 'audio/mp3';
                elseif ($ext === 'wav') $mime = 'audio/wav';
                elseif ($ext === 'ogg') $mime = 'audio/ogg';
                elseif ($ext === 'mp4') $mime = 'video/mp4';
                
                $virtual_files[$filename] = 'data:' . $mime . ';base64,' . base64_encode($content);
            } else {
                $virtual_files[$filename] = $content;
            }
        }
        $zip->close();
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chấm bài: <?= htmlspecialchars($sub['ho_ten']) ?> - Hệ thống Giảng viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <!-- Load JSZip for downloading project as zip -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- Load Ace Editor from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ace.js"></script>
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
            padding: 0 15px;
            color: #cccccc;
            font-size: 12.5px;
            border-bottom: 1px solid #2d2d2d;
            user-select: none;
            position: relative;
        }
        
        .window-controls {
            display: flex;
            gap: 8px;
            align-items: center;
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
            background-color: #252526;
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.3);
            z-index: 1000;
            border: 1px solid #3c3c3c;
            border-radius: 4px;
            margin-top: 5px;
        }
        .dropdown-menu-content a {
            color: #cccccc;
            padding: 8px 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
        }
        .dropdown-menu-content a:hover {
            background-color: var(--accent);
            color: white;
        }
        .dropdown-divider {
            height: 1px;
            background-color: #3c3c3c;
            margin: 4px 0;
        }

        .vscode-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            background: #1e1e1e;
        }

        /* Activity Bar (Far Left) */
        .vscode-activity-bar {
            width: 48px;
            background: #333333;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-right: 1px solid #2d2d2d;
            flex-shrink: 0;
            user-select: none;
        }

        .activity-icons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
            align-items: center;
        }

        .activity-icon {
            font-size: 20px;
            color: #858585;
            cursor: pointer;
            width: 100%;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: 0.15s;
        }

        .activity-icon:hover {
            color: #ffffff;
        }

        .activity-icon.active {
            color: #ffffff;
            border-left: 2px solid var(--accent);
        }

        /* Activity Bar Tooltip */
        .activity-tooltip {
            visibility: hidden;
            background-color: #252526;
            color: #ffffff;
            text-align: center;
            padding: 5px 10px;
            border-radius: 4px;
            position: absolute;
            z-index: 1000;
            left: 50px;
            font-size: 11px;
            white-space: nowrap;
            box-shadow: 0px 4px 8px rgba(0,0,0,0.5);
            border: 1px solid #3c3c3c;
        }
        .activity-icon:hover .activity-tooltip {
            visibility: visible;
        }

        /* Sidebar Panel (File Explorer / Settings) */
        .vscode-sidebar {
            width: 250px;
            background: #252526;
            border-right: 1px solid #2d2d2d;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 10px 15px;
            font-size: 11px;
            font-weight: 700;
            color: #bbbbbb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #2d2d2d;
            user-select: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .explorer-section {
            padding: 10px 0;
            flex: 1;
            overflow-y: auto;
        }

        .explorer-folder {
            padding: 4px 15px;
            font-weight: 700;
            font-size: 12.5px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            user-select: none;
        }

        .explorer-files {
            margin-left: 15px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-top: 5px;
        }

        .explorer-file-item {
            padding: 6px 15px;
            font-size: 13px;
            color: #cccccc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            border-radius: 4px;
            margin: 0 8px;
            transition: 0.1s;
            user-select: none;
        }

        .explorer-file-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }

        .explorer-file-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-weight: 500;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .explorer-action-icon {
            cursor: pointer;
            color: #858585;
            padding: 2px;
            border-radius: 3px;
            font-size: 11px;
        }
        .explorer-action-icon:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }

        .file-actions {
            display: none;
            gap: 5px;
        }
        .explorer-file-item:hover .file-actions {
            display: flex;
        }

        /* Main Workspace Area */
        .vscode-main-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #1e1e1e;
        }

        /* File Tabs */
        .vscode-tabs-bar {
            height: 35px;
            background: #2d2d2d;
            display: flex;
            overflow-x: auto;
            border-bottom: 1px solid #252526;
            user-select: none;
            flex-shrink: 0;
        }
        
        .vscode-tabs-bar::-webkit-scrollbar {
            height: 3px;
        }

        .vscode-tab {
            padding: 0 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #2d2d2d;
            color: #969696;
            font-size: 12.5px;
            border-right: 1px solid #252526;
            cursor: pointer;
            height: 100%;
            position: relative;
            transition: 0.1s;
        }

        .vscode-tab:hover {
            background: #2b2b2b;
            color: #e1e1e1;
        }

        .vscode-tab.active {
            background: #1e1e1e;
            color: #ffffff;
            border-top: 1px solid var(--accent);
            height: calc(100% - 1px);
        }

        .close-tab-btn {
            font-size: 10px;
            opacity: 0.5;
            transition: 0.2s;
            border-radius: 50%;
            width: 14px;
            height: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .close-tab-btn:hover {
            opacity: 1;
            background: rgba(255,255,255,0.15);
        }

        /* Action Toolbar inside IDE */
        .vscode-editor-toolbar {
            height: 40px;
            background: #1e1e1e;
            border-bottom: 1px solid #2d2d2d;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 15px;
            gap: 10px;
            flex-shrink: 0;
        }

        .editor-action-btn {
            background: #333333;
            color: #cccccc;
            border: 1px solid #3c3c3c;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.15s;
            outline: none;
        }

        .editor-action-btn:hover {
            background: #444444;
            color: #ffffff;
            border-color: #555555;
        }

        .editor-action-btn.btn-run {
            background: var(--accent);
            color: #ffffff;
            border: none;
        }
        .editor-action-btn.btn-run:hover {
            background: var(--accent-dark);
        }

        /* Ace Editor Container */
        .vscode-editor-pane {
            flex: 1;
            position: relative;
            background: #1e1e1e;
        }

        #editor {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            font-size: 14px;
        }

        /* Terminal/Output Pane */
        .vscode-terminal-pane {
            height: 250px;
            background: #151515;
            border-top: 1px solid #2d2d2d;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: height 0.2s ease;
        }

        .terminal-header {
            height: 35px;
            background: #1e1e1e;
            border-bottom: 1px solid #2d2d2d;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 15px;
            user-select: none;
            flex-shrink: 0;
        }

        .terminal-tabs {
            display: flex;
            gap: 15px;
            height: 100%;
        }

        .terminal-tab {
            font-size: 11.5px;
            font-weight: 700;
            color: #858585;
            cursor: pointer;
            display: flex;
            align-items: center;
            height: 100%;
            border-bottom: 2px solid transparent;
            transition: 0.1s;
        }

        .terminal-tab:hover {
            color: #e1e1e1;
        }

        .terminal-tab.active {
            color: #ffffff;
            border-bottom-color: var(--accent);
        }

        .terminal-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .terminal-btn {
            background: transparent;
            border: none;
            color: #858585;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .terminal-btn:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.05);
        }

        .terminal-body {
            flex: 1;
            position: relative;
            overflow: hidden;
            background: #151515;
        }

        .terminal-textarea {
            width: 100%;
            height: 100%;
            background: #151515;
            color: #bbbbbb;
            border: none;
            padding: 12px 15px;
            font-family: "Courier New", Courier, monospace;
            font-size: 13.5px;
            line-height: 1.5;
            resize: none;
            outline: none;
            box-sizing: border-box;
        }

        .terminal-input-container {
            display: none;
            width: 100%;
            height: 100%;
        }

        .preview-iframe {
            display: none;
            width: 100%;
            height: 100%;
            border: none;
            background: white;
        }

        /* Status Bar */
        .vscode-status-bar {
            height: 22px;
            background: var(--accent);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 10px;
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
        
        /* Select controls */
        .control-select {
            background: #3c3c3c;
            color: #cccccc;
            border: 1px solid #3c3c3c;
            padding: 5px;
            border-radius: 4px;
            font-size: 12px;
            outline: none;
            cursor: pointer;
        }
        .control-select:focus {
            border-color: var(--accent);
        }
        
        /* CSS dialog overlay */
        .vscode-dialog-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .vscode-dialog-box {
            background: #252526;
            border: 1px solid #3c3c3c;
            border-radius: 8px;
            padding: 20px;
            width: 320px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
            font-family: sans-serif;
            color: #cccccc;
        }
        .vscode-dialog-box h4 {
            margin: 0 0 12px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
        }
        .vscode-dialog-input {
            width: 100%;
            background: #3c3c3c;
            border: 1px solid #3c3c3c;
            color: #fff;
            padding: 8px 10px;
            box-sizing: border-box;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
            outline: none;
        }
        .vscode-dialog-input:focus {
            border-color: var(--accent);
        }
        .vscode-dialog-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .dialog-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #3c3c3c;
            background: #333;
            color: #ccc;
        }
        .dialog-btn:hover {
            background: #444;
            color: #fff;
        }
        .dialog-btn-primary {
            background: var(--accent);
            color: #fff;
            border: none;
        }
        .dialog-btn-primary:hover {
            background: var(--accent-dark);
        }
        
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
    </style>
</head>
<body class="admin-portal">
    <?php include '../includes/teacher_nav.php'; ?>

    <div class="page-header" style="margin-bottom: 10px;">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-marker" style="color:var(--accent)"></i> Chấm bài Thực hành Code</h1>
            <p style="color: var(--text2); margin-top: 5px;">Xem cấu trúc file, nội dung code, chạy thử và chấm điểm bài nộp của sinh viên.</p>
        </div>
    </div>

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
                            <a href="#" onclick="downloadProjectZip(); return false;"><i class="fa-solid fa-file-zipper"></i> Download Project (.zip)</a>
                        </div>
                    </div>
                    <span class="menu-item">Edit</span>
                    <span class="menu-item">Selection</span>
                    <span class="menu-item">View</span>
                    <span class="menu-item">Run</span>
                    <span class="menu-item">Terminal</span>
                    <span class="menu-item">Help</span>
                </div>
            </div>
            <div class="window-title" id="vscode-window-title">Trường Cao Đẳng Cà Mau Grading Mode - index.html</div>
            <div style="font-size: 11px; opacity: 0.6;"><i class="fa-solid fa-graduation-cap"></i> Grading: <?= htmlspecialchars($sub['ho_ten']) ?></div>
        </div>

        <!-- Body -->
        <div class="vscode-body">
            <!-- Activity Bar (Far Left) -->
            <div class="vscode-activity-bar">
                <div class="activity-icons">
                    <div class="activity-icon active" onclick="toggleSidebarSection('explorer')" id="act-explorer">
                        <i class="fa-regular fa-file"></i>
                        <span class="activity-tooltip">Explorer</span>
                    </div>
                    <div class="activity-icon" onclick="toggleSidebarSection('grade')" id="act-grade">
                        <i class="fa-solid fa-marker" style="color: #f59e0b;"></i>
                        <span class="activity-tooltip">Chấm điểm</span>
                    </div>
                    <div class="activity-icon" onclick="toggleSidebarSection('settings')" id="act-settings">
                        <i class="fa-solid fa-sliders"></i>
                        <span class="activity-tooltip">Editor Settings</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Explorer / Settings Pane / Grade Pane) -->
            <div class="vscode-sidebar" id="vscode-sidebar">
                <!-- EXPLORER TAB -->
                <div id="sidebar-explorer-content" style="display: flex; flex-direction: column; height: 100%;">
                    <div class="sidebar-header">
                        <span>EXPLORER: WORKSPACE</span>
                        <div style="display:flex; gap: 8px;">
                            <span class="explorer-action-icon" onclick="createNewFile()" title="Tạo File Mới..."><i class="fa-solid fa-file-circle-plus"></i></span>
                            <span class="explorer-action-icon" onclick="createNewFolder()" title="Tạo Thư Mục Mới..."><i class="fa-solid fa-folder-plus"></i></span>
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
                        </div>
                    </div>
                </div>

                <!-- GRADE TAB -->
                <div id="sidebar-grade-content" style="display: none; flex-direction: column; height: 100%; color: #cccccc; overflow-y: auto;">
                    <div class="sidebar-header">
                        <span>CHẤM ĐIỂM & NHẬN XÉT</span>
                    </div>
                    <div style="padding: 15px; display:flex; flex-direction:column; gap:15px;">
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid #3c3c3c; border-radius: 8px; padding: 12px;">
                            <div style="font-size: 11px; text-transform: uppercase; color: #858585; font-weight: 600;">Học sinh</div>
                            <div style="font-weight: 700; color: #fff; font-size: 14px; margin-top: 2px;"><?= htmlspecialchars($sub['ho_ten']) ?></div>
                            <div style="font-size: 12px; color: #858585; margin-top: 2px;">MSSV: <?= htmlspecialchars($sub['ma_sv']) ?></div>
                            <div style="font-size: 12px; color: #858585; margin-top: 2px;">Lớp: <?= htmlspecialchars($sub['lop']) ?></div>
                            <div style="font-size: 11px; color: #858585; margin-top: 5px; border-top: 1px solid #3c3c3c; padding-top: 5px;">Ca thi: <?= htmlspecialchars($sub['session_title']) ?></div>
                        </div>
                        
                        <form id="grading-form" onsubmit="saveGrade(event)">
                            <div>
                                <label style="color:#858585; display:block; margin-bottom:5px; font-weight:700; font-size: 11.5px;">ĐIỂM SỐ (THANG ĐIỂM 10)</label>
                                <input type="number" step="0.1" min="0" max="10" class="control-select" id="grade-input" value="<?= $sub['diem'] !== null ? floatval($sub['diem']) : '' ?>" placeholder="Nhập điểm (vd: 8.5)" required style="width:100%; border-color:#3c3c3c; color:#fff; background:#252526; padding: 8px 10px; box-sizing: border-box; border-radius: 4px;">
                            </div>
                            
                            <div style="margin-top: 10px;">
                                <label style="color:#858585; display:block; margin-bottom:5px; font-weight:700; font-size: 11.5px;">NHẬN XÉT / GÓP Ý</label>
                                <textarea class="control-select" id="feedback-input" placeholder="Nhập lời phê, nhận xét bài làm..." style="width:100%; height:180px; border-color:#3c3c3c; color:#fff; background:#252526; padding: 8px 10px; box-sizing: border-box; border-radius: 4px; resize: vertical; font-family: sans-serif; font-size: 13px;"><?= htmlspecialchars($sub['nhan_xet'] ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn" style="width: 100%; margin-top: 20px; padding: 12px; background: var(--accent); color: white; border-radius: 6px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px;">
                                <i class="fa-solid fa-floppy-disk"></i> Lưu điểm & nhận xét
                            </button>
                            
                            <a href="/tkb/teacher/quanly_thuchanh.php?action=view_submissions&session_id=<?= $sub['sess_id'] ?>" class="btn btn-ghost" style="width: 100%; margin-top: 10px; padding: 12px; border-radius: 6px; text-decoration: none; text-align: center; display: block; box-sizing: border-box; font-size: 13px; color: #ccc; border: 1px solid #3c3c3c;">
                                <i class="fa-solid fa-arrow-left"></i> Quay lại ca thi
                            </a>
                        </form>
                    </div>
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
                    </div>
                </div>
            </div>

            <!-- Main area (Tabs + Editor + Terminal) -->
            <div class="vscode-main-area">
                <!-- Editor Tabs -->
                <div class="vscode-tabs-bar" id="vscode-tabs-bar"></div>

                <!-- Editor Action Buttons Toolbar -->
                <div class="vscode-editor-toolbar">
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
                </div>

                <!-- Bottom Panel (Console / Input / Preview) -->
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
                        <!-- Terminal Console logs -->
                        <textarea id="terminal-content" class="terminal-textarea" readonly placeholder="Output kết quả chương trình sẽ hiển thị ở đây sau khi chạy..."></textarea>

                        <!-- Stdin Input Area -->
                        <div class="terminal-input-container" id="terminal-input-content">
                            <textarea id="stdin" class="terminal-textarea" placeholder="Nhập dữ liệu đầu vào Standard Input (stdin) cho chương trình tại đây nếu mã nguồn của bạn yêu cầu..."></textarea>
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
                <div class="status-item" style="background:#c6143c; padding: 0 8px;"><i class="fa-solid fa-terminal"></i> Terminal</div>
                <div class="status-item" id="status-running-status"><i class="fa-solid fa-check"></i> Ready</div>
            </div>
            <div class="status-right">
                <div class="status-item">Ln 1, Col 1</div>
                <div class="status-item">Spaces: 4</div>
                <div class="status-item">UTF-8</div>
                <div class="status-item" id="status-lang-badge" style="font-weight: 700; text-transform: uppercase;">HTML Preview</div>
            </div>
        </div>
    </div>

    <!-- Hidden popups/dialogs inside IDE window simulator -->
    <div id="vscode-dialog-overlay" class="vscode-dialog-overlay" style="display: none;"></div>

    <script>
        // Load virtual files from PHP extracted ZIP array
        const virtualFiles = <?= json_encode($virtual_files) ?>;
        
        let activeFile = null;
        let openTabs = [];
        let editor = null;
        let lastDeployUrl = '';

        // Initialize Ace Editor
        editor = ace.edit("editor");
        editor.setTheme("ace/theme/monokai");
        editor.session.setMode("ace/mode/html");
        editor.setOptions({
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true,
            showPrintMargin: false,
            highlightActiveLine: true,
            tabSize: 4,
            useSoftTabs: true
        });

        // Track line and column numbers
        editor.selection.on("changeCursor", function() {
            const cursor = editor.getCursorPosition();
            const statusBarRight = document.querySelector(".vscode-status-bar .status-right .status-item:first-child");
            if (statusBarRight) {
                statusBarRight.textContent = `Ln ${cursor.row + 1}, Col ${cursor.column + 1}`;
            }
        });

        // Switch Sidebar section
        function toggleSidebarSection(section) {
            document.querySelectorAll('.activity-icon').forEach(icon => icon.classList.remove('active'));
            
            const contentExplorer = document.getElementById('sidebar-explorer-content');
            const contentSettings = document.getElementById('sidebar-settings-content');
            const contentGrade = document.getElementById('sidebar-grade-content');
            const sidebar = document.getElementById('vscode-sidebar');
            
            contentExplorer.style.display = 'none';
            contentSettings.style.display = 'none';
            contentGrade.style.display = 'none';
            
            if (section === 'explorer') {
                document.getElementById('act-explorer').classList.add('active');
                contentExplorer.style.display = 'flex';
                sidebar.style.display = 'block';
            } else if (section === 'settings') {
                document.getElementById('act-settings').classList.add('active');
                contentSettings.style.display = 'flex';
                sidebar.style.display = 'block';
            } else if (section === 'grade') {
                document.getElementById('act-grade').classList.add('active');
                contentGrade.style.display = 'flex';
                sidebar.style.display = 'block';
            }
        }

        // Save Grade via AJAX
        async function saveGrade(event) {
            event.preventDefault();
            const grade = document.getElementById('grade-input').value;
            const feedback = document.getElementById('feedback-input').value;
            
            try {
                const formData = new FormData();
                formData.append('submission_id', <?= $sub_id ?>);
                formData.append('diem', grade);
                formData.append('nhan_xet', feedback);
                
                const response = await fetch('/tkb/api/save_grade.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Lưu điểm và nhận xét thành công!');
                } else {
                    alert('Lỗi: ' + result.error);
                }
            } catch(err) {
                alert('Lỗi kết nối: ' + err.message);
            }
        }

        // Render File Tree Explorer
        function renderExplorer() {
            const container = document.querySelector('.explorer-files');
            container.innerHTML = '';
            
            const paths = Object.keys(virtualFiles).sort();
            
            // Render hierarchical tree (supports nested subfolders)
            const tree = {};
            paths.forEach(p => {
                const parts = p.split('/');
                let current = tree;
                parts.forEach((part, index) => {
                    if (!current[part]) {
                        current[part] = index === parts.length - 1 ? null : {};
                    }
                    current = current[part];
                });
            });
            
            function renderNode(node, parentPath, domParent) {
                const sortedKeys = Object.keys(node).sort((a,b) => {
                    const aIsDir = node[a] !== null;
                    const bIsDir = node[b] !== null;
                    if (aIsDir && !bIsDir) return -1;
                    if (!aIsDir && aIsDir) return 1;
                    return a.localeCompare(b);
                });
                
                sortedKeys.forEach(key => {
                    const path = parentPath ? parentPath + '/' + key : key;
                    const isDir = node[key] !== null;
                    
                    const row = document.createElement('div');
                    row.style.paddingLeft = '10px';
                    row.style.margin = '2px 0';
                    
                    if (isDir) {
                        const folderEl = document.createElement('div');
                        folderEl.className = 'explorer-folder';
                        folderEl.style.fontSize = '12px';
                        folderEl.style.padding = '4px 5px';
                        folderEl.innerHTML = `<i class="fa-solid fa-angle-down"></i> <i class="fa-solid fa-folder" style="color: #e2b73c;"></i> ${key}`;
                        row.appendChild(folderEl);
                        
                        const childrenContainer = document.createElement('div');
                        childrenContainer.style.borderLeft = '1px solid #3c3c3c';
                        childrenContainer.style.marginLeft = '8px';
                        row.appendChild(childrenContainer);
                        
                        renderNode(node[key], path, childrenContainer);
                        
                        folderEl.onclick = (e) => {
                            e.stopPropagation();
                            const icon = folderEl.querySelector('.fa-angle-down, .fa-angle-right');
                            if (childrenContainer.style.display === 'none') {
                                childrenContainer.style.display = 'block';
                                icon.className = 'fa-solid fa-angle-down';
                            } else {
                                childrenContainer.style.display = 'none';
                                icon.className = 'fa-solid fa-angle-right';
                            }
                        };
                    } else {
                        const fileEl = document.createElement('div');
                        fileEl.className = 'explorer-file-item' + (activeFile === path ? ' active' : '');
                        fileEl.style.padding = '4px 8px';
                        fileEl.style.margin = '0';
                        
                        const ext = key.split('.').pop().toLowerCase();
                        let fileIcon = '<i class="fa-regular fa-file-code"></i>';
                        if (ext === 'html') fileIcon = '<i class="fa-brands fa-html5" style="color:#e34f26;"></i>';
                        else if (ext === 'css') fileIcon = '<i class="fa-brands fa-css3-alt" style="color:#1572b6;"></i>';
                        else if (ext === 'js') fileIcon = '<i class="fa-brands fa-js" style="color:#f7df1e;"></i>';
                        else if (ext === 'php') fileIcon = '<i class="fa-brands fa-php" style="color:#777bb4;"></i>';
                        else if (['png','jpg','jpeg','gif','webp','svg'].includes(ext)) fileIcon = '<i class="fa-regular fa-file-image" style="color:#4caf50;"></i>';
                        
                        fileEl.innerHTML = `
                            <span class="file-info">${fileIcon} ${key}</span>
                            <span class="file-actions">
                                <i class="fa-regular fa-trash-can text-danger explorer-action-icon" onclick="deleteExplorerFile(event, '${path}')"></i>
                            </span>
                        `;
                        
                        fileEl.onclick = () => openFile(path);
                        row.appendChild(fileEl);
                    }
                    domParent.appendChild(row);
                });
            }
            
            renderNode(tree, '', container);
        }

        // Open a file
        function openFile(path) {
            // Save active editor file back to memory first
            if (activeFile && editor) {
                virtualFiles[activeFile] = editor.getValue();
            }
            
            activeFile = path;
            
            // Add tab if not already present
            if (!openTabs.includes(path)) {
                openTabs.push(path);
            }
            
            renderTabs();
            renderExplorer();
            
            // Detect extension
            const ext = path.split('.').pop().toLowerCase();
            const isBinary = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'mp3', 'wav', 'ogg', 'm4a', 'mp4', 'webm'].includes(ext);
            
            const mediaPreview = document.getElementById('media-preview');
            const editorDiv = document.getElementById('editor');
            
            if (isBinary) {
                editorDiv.style.display = 'none';
                mediaPreview.style.display = 'flex';
                
                const content = virtualFiles[path];
                
                if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'].includes(ext)) {
                    mediaPreview.innerHTML = `<img src="${content}" style="max-width:100%; max-height:80%; object-fit:contain; border:1px solid #3c3c3c; border-radius:4px; box-shadow:0 4px 12px rgba(0,0,0,0.5);"/>
                    <div style="margin-top:15px; font-weight:600; color:#858585; font-size:12px;">Image Preview (${ext.toUpperCase()})</div>`;
                } else if (['mp3', 'wav', 'ogg', 'm4a'].includes(ext)) {
                    mediaPreview.innerHTML = `<audio controls src="${content}" style="width:80%; margin-top:20px;"></audio>
                    <div style="margin-top:15px; font-weight:600; color:#858585; font-size:12px;">Audio Preview (${ext.toUpperCase()})</div>`;
                } else if (['mp4', 'webm'].includes(ext)) {
                    mediaPreview.innerHTML = `<video controls src="${content}" style="max-width:90%; max-height:75%; border:1px solid #3c3c3c; border-radius:4px; box-shadow:0 4px 12px rgba(0,0,0,0.5);"></video>
                    <div style="margin-top:15px; font-weight:600; color:#858585; font-size:12px;">Video Preview (${ext.toUpperCase()})</div>`;
                }
            } else {
                mediaPreview.style.display = 'none';
                editorDiv.style.display = 'block';
                
                const content = virtualFiles[path] || '';
                editor.setValue(content, -1);
                
                // Configure Mode
                let mode = "ace/mode/text";
                if (ext === 'html') mode = "ace/mode/html";
                else if (ext === 'css') mode = "ace/mode/css";
                else if (ext === 'js') mode = "ace/mode/javascript";
                else if (ext === 'php') mode = "ace/mode/php";
                else if (ext === 'py') mode = "ace/mode/python";
                else if (ext === 'cpp' || ext === 'c') mode = "ace/mode/c_cpp";
                else if (ext === 'java') mode = "ace/mode/java";
                else if (ext === 'sql') mode = "ace/mode/sql";
                
                editor.session.setMode(mode);
            }
            
            document.getElementById('vscode-window-title').textContent = `Trường Cao Đẳng Cà Mau Grading Mode - ${path}`;
        }

        // Render tabs
        function renderTabs() {
            const tabsBar = document.getElementById('vscode-tabs-bar');
            tabsBar.innerHTML = '';
            
            openTabs.forEach(path => {
                const tab = document.createElement('div');
                tab.className = 'vscode-tab' + (activeFile === path ? ' active' : '');
                
                const ext = path.split('.').pop().toLowerCase();
                let fileIcon = '<i class="fa-regular fa-file-code"></i>';
                if (ext === 'html') fileIcon = '<i class="fa-brands fa-html5" style="color:#e34f26; font-size:12px;"></i>';
                else if (ext === 'css') fileIcon = '<i class="fa-brands fa-css3-alt" style="color:#1572b6; font-size:12px;"></i>';
                else if (ext === 'js') fileIcon = '<i class="fa-brands fa-js" style="color:#f7df1e; font-size:12px;"></i>';
                else if (ext === 'php') fileIcon = '<i class="fa-brands fa-php" style="color:#777bb4; font-size:12px;"></i>';
                
                tab.innerHTML = `
                    ${fileIcon}
                    <span>${path.split('/').pop()}</span>
                    <span class="close-tab-btn" onclick="closeTab(event, '${path}')"><i class="fa-solid fa-xmark"></i></span>
                `;
                
                tab.onclick = () => openFile(path);
                tabsBar.appendChild(tab);
            });
        }

        // Close file tab
        function closeTab(event, path) {
            event.stopPropagation();
            openTabs = openTabs.filter(t => t !== path);
            
            if (activeFile === path) {
                if (openTabs.length > 0) {
                    openFile(openTabs[openTabs.length - 1]);
                } else {
                    activeFile = null;
                    clearEditor();
                    document.getElementById('vscode-window-title').textContent = `Trường Cao Đẳng Cà Mau Grading Mode - No File Opened`;
                }
            } else {
                renderTabs();
            }
        }

        // Clear editor
        function clearEditor() {
            document.getElementById('media-preview').style.display = 'none';
            document.getElementById('editor').style.display = 'block';
            editor.setValue('', -1);
            editor.session.setMode("ace/mode/text");
            activeFile = null;
        }

        // Run program
        async function runCode() {
            // Save active editor file back to memory first
            if (activeFile && editor) {
                virtualFiles[activeFile] = editor.getValue();
            }
            
            const btnRun = document.querySelector('.btn-run');
            const runIcon = document.getElementById('run-icon');
            const runText = document.getElementById('run-text');
            const terminal = document.getElementById('terminal-content');
            const statusBadge = document.getElementById('run-status-badge');
            
            // Set loading
            btnRun.disabled = true;
            runIcon.className = 'fa-solid fa-circle-notch fa-spin';
            runText.textContent = 'Đang chạy...';
            statusBadge.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Loading...';
            
            // Detect if it is a web project (HTML + CSS + JS or PHP)
            let isWeb = false;
            for (const path in virtualFiles) {
                if (path.endsWith('.html') || path.endsWith('.htm') || path.endsWith('.php')) {
                    isWeb = true;
                    break;
                }
            }
            
            if (isWeb) {
                // Web project deployment mode
                try {
                    const formData = new FormData();
                    formData.append('active_file', activeFile || 'index.php');
                    formData.append('virtual_files', JSON.stringify(virtualFiles));
                    formData.append('project_name', <?= json_encode($sub['lop']) ?>);
                    formData.append('submission_id', <?= $sub_id ?>);
                    
                    const response = await fetch('/tkb/api/deploy_web.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    btnRun.disabled = false;
                    runIcon.className = 'fa-solid fa-play';
                    runText.textContent = 'Chạy Code (Ctrl+Enter)';
                    
                    if (result.success && result.url) {
                        statusBadge.innerHTML = '<i class="fa-solid fa-check text-success"></i> Deployed';
                        
                        // Set URL and switch tab to Live preview
                        const iframe = document.getElementById('preview-frame');
                        iframe.src = result.url;
                        lastDeployUrl = result.url;
                        
                        // Show web preview tab & switch to it
                        document.getElementById('term-tab-preview').style.display = 'inline-flex';
                        document.getElementById('btn-open-tab').style.display = 'inline-flex';
                        switchTerminalTab('preview');
                    } else {
                        statusBadge.innerHTML = '<i class="fa-solid fa-xmark text-danger"></i> Failed';
                        terminal.value = result.error || 'Lỗi không xác định khi triển khai Web Sandbox.';
                        switchTerminalTab('terminal');
                    }
                    
                } catch(err) {
                    btnRun.disabled = false;
                    runIcon.className = 'fa-solid fa-play';
                    runText.textContent = 'Chạy Code (Ctrl+Enter)';
                    statusBadge.innerHTML = '<i class="fa-solid fa-xmark text-danger"></i> Error';
                    terminal.value = 'Lỗi kết nối API: ' + err.message;
                    switchTerminalTab('terminal');
                }
            } else {
                // Console standalone programming execution mode
                try {
                    const ext = activeFile ? activeFile.split('.').pop().toLowerCase() : '';
                    let language = 'python';
                    if (ext === 'py') language = 'python';
                    else if (ext === 'cpp' || ext === 'c') language = 'cpp';
                    else if (ext === 'java') language = 'java';
                    else if (ext === 'js') language = 'javascript';
                    else if (ext === 'sql') language = 'sql';
                    
                    const stdin = document.getElementById('stdin').value;
                    
                    const formData = new FormData();
                    formData.append('language', language);
                    formData.append('active_file', activeFile || '');
                    formData.append('stdin', stdin);
                    formData.append('virtual_files', JSON.stringify(virtualFiles));
                    formData.append('project_name', <?= json_encode($sub['lop']) ?>);
                    formData.append('submission_id', <?= $sub_id ?>);
                    
                    const response = await fetch('/tkb/api/run_code.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    btnRun.disabled = false;
                    runIcon.className = 'fa-solid fa-play';
                    runText.textContent = 'Chạy Code (Ctrl+Enter)';
                    
                    if (result.success) {
                        statusBadge.innerHTML = `<i class="fa-solid fa-check text-success"></i> Finished (${result.duration}s)`;
                        
                        let logText = result.output;
                        if (result.exit_code !== 0) {
                            logText += `\n--- Program exited with code ${result.exit_code} ---`;
                        }
                        terminal.value = logText;
                    } else {
                        statusBadge.innerHTML = '<i class="fa-solid fa-xmark text-danger"></i> Failed';
                        terminal.value = result.error || 'Lỗi không xác định khi thực thi chương trình.';
                    }
                    
                    switchTerminalTab('terminal');
                    
                } catch(err) {
                    btnRun.disabled = false;
                    runIcon.className = 'fa-solid fa-play';
                    runText.textContent = 'Chạy Code (Ctrl+Enter)';
                    statusBadge.innerHTML = '<i class="fa-solid fa-xmark text-danger"></i> Error';
                    terminal.value = 'Lỗi kết nối API: ' + err.message;
                    switchTerminalTab('terminal');
                }
            }
        }

        // Open live preview in a new browser tab
        function openPreviewInNewTab() {
            if (lastDeployUrl) {
                window.open(lastDeployUrl, '_blank');
            }
        }

        // Switch Terminal Tabs (Output Console / Input stdin / Web Preview)
        function switchTerminalTab(tabName) {
            document.querySelectorAll('.terminal-tab').forEach(tab => tab.classList.remove('active'));
            
            const txtConsole = document.getElementById('terminal-content');
            const txtStdin = document.getElementById('terminal-input-content');
            const iframe = document.getElementById('preview-frame');
            
            txtConsole.style.display = 'none';
            txtStdin.style.display = 'none';
            iframe.style.display = 'none';
            
            // Expand terminal if minimized
            const pane = document.querySelector('.vscode-terminal-pane');
            if (pane.style.height === '35px') {
                pane.style.height = '250px';
                document.getElementById('toggle-terminal-text').textContent = 'Thu nhỏ';
                document.querySelector('#btn-toggle-terminal i').className = 'fa-solid fa-chevron-down';
            }
            
            if (tabName === 'terminal') {
                document.getElementById('term-tab-terminal').classList.add('active');
                txtConsole.style.display = 'block';
            } else if (tabName === 'input') {
                document.getElementById('term-tab-input').classList.add('active');
                txtStdin.style.display = 'block';
            } else if (tabName === 'preview') {
                document.getElementById('term-tab-preview').classList.add('active');
                iframe.style.display = 'block';
            }
        }

        // Toggle Expand/Minimize Terminal Pane
        function toggleTerminalPane() {
            const pane = document.querySelector('.vscode-terminal-pane');
            const btn = document.getElementById('btn-toggle-terminal');
            const text = document.getElementById('toggle-terminal-text');
            const icon = btn.querySelector('i');
            
            if (pane.style.height === '35px') {
                pane.style.height = '250px';
                text.textContent = 'Thu nhỏ';
                icon.className = 'fa-solid fa-chevron-down';
            } else {
                pane.style.height = '35px';
                text.textContent = 'Mở rộng';
                icon.className = 'fa-solid fa-chevron-up';
            }
        }

        // Clear Output console log
        function clearConsole() {
            document.getElementById('terminal-content').value = '';
        }

        // Copy Output console log content
        function copyConsoleOutput() {
            const consoleEl = document.getElementById('terminal-content');
            consoleEl.select();
            document.execCommand('copy');
            alert('Đã sao chép Output vào clipboard!');
        }

        // Change Font Size
        function changeFontSize() {
            const select = document.getElementById('setting-font-size');
            editor.setFontSize(select.value);
        }

        // Change Theme
        function changeTheme() {
            const select = document.getElementById('setting-theme');
            editor.setTheme(select.value);
        }

        // Toggle File dropdown menu
        function toggleMenuDropdown(e, menuId) {
            e.stopPropagation();
            const dropdown = document.getElementById(menuId);
            const isVisible = dropdown.style.display === 'block';
            
            closeAllMenuDropdowns();
            
            if (!isVisible) {
                dropdown.style.display = 'block';
            }
        }

        function closeAllMenuDropdowns() {
            document.querySelectorAll('.dropdown-menu-content').forEach(d => d.style.display = 'none');
        }

        window.onclick = function() {
            closeAllMenuDropdowns();
        };

        // Create virtual new file
        function createNewFile() {
            const overlay = document.getElementById('vscode-dialog-overlay');
            overlay.style.display = 'flex';
            overlay.innerHTML = `
                <div class="vscode-dialog-box">
                    <h4>TẠO FILE MỚI</h4>
                    <input type="text" class="vscode-dialog-input" id="dialog-new-file-name" placeholder="Tên tệp tin (ví dụ: product.php, stylesheet.css)..."/>
                    <div class="vscode-dialog-actions">
                        <button class="dialog-btn" onclick="closeDialog()">Hủy bỏ</button>
                        <button class="dialog-btn dialog-btn-primary" onclick="confirmCreateFile()">Tạo File</button>
                    </div>
                </div>
            `;
            setTimeout(() => document.getElementById('dialog-new-file-name').focus(), 100);
        }

        function confirmCreateFile() {
            const filename = document.getElementById('dialog-new-file-name').value.trim();
            if (!filename) return;
            
            if (virtualFiles[filename] !== undefined) {
                alert('Tệp tin này đã tồn tại trong workspace!');
                return;
            }
            
            virtualFiles[filename] = '';
            closeDialog();
            renderExplorer();
            openFile(filename);
        }

        // Create virtual new folder
        function createNewFolder() {
            const overlay = document.getElementById('vscode-dialog-overlay');
            overlay.style.display = 'flex';
            overlay.innerHTML = `
                <div class="vscode-dialog-box">
                    <h4>TẠO THƯ MỤC MỚI</h4>
                    <input type="text" class="vscode-dialog-input" id="dialog-new-folder-name" placeholder="Tên thư mục (ví dụ: assets, css, js)..."/>
                    <div class="vscode-dialog-actions">
                        <button class="dialog-btn" onclick="closeDialog()">Hủy bỏ</button>
                        <button class="dialog-btn dialog-btn-primary" onclick="confirmCreateFolder()">Tạo Thư Mục</button>
                    </div>
                </div>
            `;
            setTimeout(() => document.getElementById('dialog-new-folder-name').focus(), 100);
        }

        function confirmCreateFolder() {
            const foldername = document.getElementById('dialog-new-folder-name').value.trim();
            if (!foldername) return;
            
            const placeholderFile = foldername + '/.keep';
            virtualFiles[placeholderFile] = '';
            closeDialog();
            renderExplorer();
        }

        function deleteExplorerFile(e, path) {
            e.stopPropagation();
            if (confirm(`Bạn có chắc muốn xóa tệp tin "${path}" khỏi workspace tạm?`)) {
                delete virtualFiles[path];
                closeTab(e, path);
                renderExplorer();
            }
        }

        // Download project as ZIP
        function downloadProjectZip() {
            if (typeof JSZip === 'undefined') {
                alert('Thư viện nén ZIP chưa sẵn sàng. Vui lòng tải lại trang.');
                return;
            }
            
            // Save active editor file back to memory first
            if (activeFile && editor) {
                virtualFiles[activeFile] = editor.getValue();
            }
            
            const zip = new JSZip();
            const fileKeys = Object.keys(virtualFiles);
            fileKeys.forEach(path => {
                zip.file(path, virtualFiles[path]);
            });
            
            zip.generateAsync({ type: 'blob' }).then(function(content) {
                const url = window.URL.createObjectURL(content);
                const a = document.createElement('a');
                a.href = url;
                a.download = '<?= htmlspecialchars($sub['ma_sv']) ?>_project.zip';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }).catch(err => {
                alert('Lỗi tạo tệp nén ZIP: ' + err.message);
            });
        }

        function closeDialog() {
            document.getElementById('vscode-dialog-overlay').style.display = 'none';
        }

        // Shortcuts
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                runCode();
            }
        });

        // Initialize lists on load
        document.addEventListener('DOMContentLoaded', () => {
            renderExplorer();
            
            // Auto open the first runnable file (index.php, index.html, or any file)
            let entryFile = '';
            const paths = Object.keys(virtualFiles);
            if (paths.includes('index.php')) entryFile = 'index.php';
            else if (paths.includes('index.html')) entryFile = 'index.html';
            else if (paths.length > 0) {
                // Find any PHP or HTML file
                entryFile = paths.find(p => p.endsWith('.php') || p.endsWith('.html')) || paths[0];
            }
            
            if (entryFile) {
                openFile(entryFile);
            } else {
                clearEditor();
            }
            
            // Automatically open Grade sidebar tab on load
            toggleSidebarSection('grade');
        });
    </script>
</body>
</html>
