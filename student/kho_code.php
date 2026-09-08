<?php
require_once '../config.php';
requireStudent();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$db = getDB();
$student_id = (int)($_SESSION['student_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($student_id <= 0 && $user_id > 0) {
    $res_st = $db->query("SELECT id FROM students WHERE user_id = $user_id LIMIT 1");
    if ($res_st && ($r = $res_st->fetch_assoc())) {
        $student_id = (int)$r['id'];
        $_SESSION['student_id'] = $student_id;
    } else {
        $student_id = $user_id;
    }
}
if ($student_id <= 0) {
    $student_id = (int)($_SESSION['user_id'] ?? 1);
}

// Khai báo sẵn CSDL và lấy danh sách ban đầu để hiển thị ngay lập tức (0ms loading)
$initial_projects = [];
$initial_stats = ['total' => 0, 'count_python' => 0, 'count_cpp' => 0, 'count_html' => 0];

try {
    @$db->query("CREATE TABLE IF NOT EXISTS `student_code_storage` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT NOT NULL,
        `ten_du_an` VARCHAR(255) NOT NULL,
        `ngon_ngu` VARCHAR(50) NOT NULL DEFAULT 'python',
        `mo_ta` TEXT DEFAULT NULL,
        `ma_nguon` LONGTEXT NOT NULL,
        `la_cong_khai` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY (`student_id`),
        KEY (`ngon_ngu`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $res_init = $db->query("SELECT id, student_id, ten_du_an, ngon_ngu, mo_ta, LEFT(ma_nguon, 300) as preview_code, CHAR_LENGTH(ma_nguon) as code_length, la_cong_khai, created_at, updated_at FROM student_code_storage WHERE student_id = $student_id ORDER BY updated_at DESC");
    if ($res_init) {
        while ($r = $res_init->fetch_assoc()) {
            $initial_projects[] = $r;
        }
    }

    $stat_res = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ngon_ngu='python' THEN 1 ELSE 0 END) as count_python,
        SUM(CASE WHEN ngon_ngu IN ('c','cpp') THEN 1 ELSE 0 END) as count_cpp,
        SUM(CASE WHEN ngon_ngu='html' THEN 1 ELSE 0 END) as count_html
        FROM student_code_storage WHERE student_id = $student_id");
    if ($stat_res && ($s_row = $stat_res->fetch_assoc())) {
        $initial_stats = $s_row;
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kho Lưu Trữ Code Sinh Viên - VKC TKB</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <!-- Load JSZip for extracting zip project folders -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script>
        window.initialProjectsData = <?= json_encode(['data' => $initial_projects, 'stats' => $initial_stats]) ?>;
    </script>
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-dark: #1e293b;
            --accent-purple: #a855f7;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-orange: #f97316;
            --border-dark: rgba(255, 255, 255, 0.1);
        }

        .code-storage-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 24px;
        }

        .page-header-box {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.15) 0%, rgba(59, 130, 246, 0.15) 100%);
            border: 1px solid rgba(168, 85, 247, 0.25);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            backdrop-filter: blur(12px);
        }

        .header-title-group h2 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-title-group p {
            color: #94a3b8;
            font-size: 14.5px;
            margin: 0;
        }

        .btn-create-code {
            background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 13px 22px;
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(168, 85, 247, 0.3);
            transition: all 0.25s ease;
        }

        .btn-create-code:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(168, 85, 247, 0.45);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid var(--border-dark);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            backdrop-filter: blur(8px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-val {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.2;
        }

        .stat-lbl {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Search & Filter Bar */
        .filter-bar {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid var(--border-dark);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input-wrap {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-input-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .search-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 11px 16px 11px 44px;
            color: #f8fafc;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.15);
        }

        .lang-select {
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 11px 16px;
            color: #f8fafc;
            font-size: 14px;
            outline: none;
            cursor: pointer;
        }

        /* Code Cards Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 20px;
        }

        .project-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .project-card:hover {
            transform: translateY(-4px);
            border-color: rgba(168, 85, 247, 0.4);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            gap: 12px;
        }

        .project-title {
            font-size: 17px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .lang-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .lang-python { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .lang-cpp { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .lang-c { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.3); }
        .lang-java { background: rgba(249, 115, 22, 0.15); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.3); }
        .lang-php { background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3); }
        .lang-html { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }

        .project-desc {
            color: #94a3b8;
            font-size: 13.5px;
            margin-bottom: 14px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .code-snippet-box {
            background: #090d16;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 12px 14px;
            font-family: 'Fira Code', monospace;
            font-size: 12.5px;
            color: #cbd5e1;
            margin-bottom: 16px;
            max-height: 110px;
            overflow: hidden;
            position: relative;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .code-snippet-box::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(180deg, transparent 0%, #090d16 100%);
        }

        .card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .btn-act {
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-run-ide {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            flex: 1;
            justify-content: center;
        }

        .btn-run-ide:hover {
            background: #10b981;
            color: #ffffff;
        }

        .btn-copy-code {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .btn-copy-code:hover {
            background: #3b82f6;
            color: #ffffff;
        }

        .btn-edit-code {
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .btn-edit-code:hover {
            background: rgba(255, 255, 255, 0.18);
        }

        .btn-del-code {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-del-code:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-backdrop.show {
            display: flex;
        }

        .modal-content-box {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: modalFadeIn 0.25s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            color: #f8fafc;
        }

        .btn-close-modal {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 20px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
        }

        .btn-close-modal:hover { color: #ffffff; background: rgba(255,255,255,0.1); }

        .modal-body { padding: 24px; }

        .form-label-code {
            font-size: 13px;
            font-weight: 700;
            color: #cbd5e1;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-input, .modal-select, .modal-textarea {
            width: 100%;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 12px 16px;
            color: #f8fafc;
            font-size: 14px;
            outline: none;
            margin-bottom: 18px;
            transition: all 0.2s;
        }

        .modal-input:focus, .modal-select:focus, .modal-textarea:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.15);
        }

        .code-textarea {
            font-family: 'Fira Code', monospace;
            font-size: 13.5px;
            line-height: 1.6;
            min-height: 250px;
            resize: vertical;
            white-space: pre;
            tab-size: 4;
            background: #090d16;
            color: #e2e8f0;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            border: none;
            padding: 11px 20px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-save-project {
            background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
            color: #ffffff;
            border: none;
            padding: 11px 24px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(168, 85, 247, 0.35);
        }

        /* Toast notification */
        .toast-msg {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #10b981;
            color: #ffffff;
            padding: 14px 22px;
            border-radius: 14px;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            z-index: 10000;
            display: none;
            align-items: center;
            gap: 10px;
            animation: toastSlideUp 0.3s ease-out;
        }

        @keyframes toastSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body data-mc-mode="dark">

<?php include_once '../includes/student_nav.php'; ?>

<div class="main-content">
    <div class="code-storage-container">
        
        <!-- Header Box -->
        <div class="page-header-box">
            <div class="header-title-group">
                <h2>
                    <i class="fa-solid fa-folder-code" style="color: #a855f7;"></i>
                    Kho Lưu Trữ Code Sinh Viên
                </h2>
                <p>Lưu trữ bài tập lập trình, tải lên tệp code từ máy tính và mở chạy trực tiếp trên IDE 🚀</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button class="btn-create-code" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); box-shadow: 0 8px 20px rgba(2, 132, 199, 0.35);" onclick="document.getElementById('folderInput').click()">
                    <i class="fa-solid fa-folder-plus"></i> Tải Lên Cả Thư Mục Dự Án
                </button>
                <input type="file" id="folderInput" webkitdirectory directory multiple style="display:none;" onclick="this.value=null" onchange="uploadFolderProject(event)">

                <button class="btn-create-code" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);" onclick="document.getElementById('codeFileInput').click()">
                    <i class="fa-solid fa-file-arrow-up"></i> Tải Lên Tệp .ZIP / File Code
                </button>
                <input type="file" id="codeFileInput" style="display:none;" onclick="this.value=null" onchange="uploadAndRunCode(event)">

                <button class="btn-create-code" onclick="openCreateModal()">
                    <i class="fa-solid fa-plus"></i> Tạo Bài Làm Mới
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <div>
                    <div class="stat-val" id="statTotal">0</div>
                    <div class="stat-lbl">Tổng Bài Làm Code</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                    <i class="fa-brands fa-python"></i>
                </div>
                <div>
                    <div class="stat-val" id="statPython">0</div>
                    <div class="stat-lbl">Bài Tập Python</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(14, 165, 233, 0.15); color: #38bdf8;">
                    <i class="fa-solid fa-code"></i>
                </div>
                <div>
                    <div class="stat-val" id="statCpp">0</div>
                    <div class="stat-lbl">C / C++ Projects</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                    <i class="fa-brands fa-html5"></i>
                </div>
                <div>
                    <div class="stat-val" id="statHtml">0</div>
                    <div class="stat-lbl">Dự Án Web HTML</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="filter-bar">
            <div class="search-input-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Tìm kiếm tên bài làm, mô tả hoặc mã nguồn..." oninput="debounceFetch()">
            </div>
            
            <select id="langFilter" class="lang-select" onchange="fetchProjects()">
                <option value="all">⚡ Tất cả ngôn ngữ</option>
                <option value="python">🐍 Python</option>
                <option value="cpp">⚡ C / C++</option>
                <option value="java">☕ Java</option>
                <option value="php">🐘 PHP</option>
                <option value="html">🌐 Web HTML / CSS / JS</option>
            </select>
        </div>

        <!-- Projects Grid -->
        <div class="projects-grid" id="projectsGrid">
            <?php if (empty($initial_projects)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 30px 20px;">
                    <p style="color: #64748b; font-size: 14.5px; margin: 0 0 16px 0;">Kho lưu trữ chưa có bài làm code nào.</p>
                    <button type="button" onclick="openCreateModal()" style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #ffffff; border: none; padding: 11px 24px; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-plus"></i> Tạo Bài Làm Mới
                    </button>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Create / Edit Project -->
<div class="modal-backdrop" id="projectModal">
    <div class="modal-content-box">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fa-solid fa-code"></i> Tạo Bài Làm Code Mới</h3>
            <button class="btn-close-modal" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="projectForm" onsubmit="saveProject(event)">
                <input type="hidden" id="projectId" value="0">

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                    <div>
                        <label class="form-label-code">TÊN BÀI LÀM / DỰ ÁN CODE *</label>
                        <input type="text" id="projectTitle" class="modal-input" placeholder="Ví dụ: Bài Tập Tính Tổng Mảng Python" required>
                    </div>

                    <div>
                        <label class="form-label-code">NGÔN NGỮ LẬP TRÌNH *</label>
                        <select id="projectLang" class="modal-select" onchange="onLanguageChange()">
                            <option value="python">🐍 Python</option>
                            <option value="cpp">⚡ C++</option>
                            <option value="c">⚡ C</option>
                            <option value="java">☕ Java</option>
                            <option value="php">🐘 PHP</option>
                            <option value="html">🌐 Web HTML/JS</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label-code">MÔ TẢ BÀI TẬP / DỰ ÁN (KHÔNG BẮT BUỘC)</label>
                    <input type="text" id="projectDesc" class="modal-input" placeholder="Ghi chú yêu cầu bài tập hoặc mô tả dự án...">
                </div>

                <div>
                    <label class="form-label-code">MÃ NGUỒN CODE (SOURCE CODE) *</label>
                    <textarea id="projectCode" class="modal-textarea code-textarea" placeholder="# Nhập mã nguồn của bạn vào đây..." required></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Hủy Bỏ</button>
            <button class="btn-save-project" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);" onclick="saveAndRunProject(event)">
                <i class="fa-solid fa-play"></i> Lưu & Chạy Ngay Trên IDE
            </button>
            <button class="btn-save-project" onclick="document.getElementById('projectForm').requestSubmit()">
                <i class="fa-solid fa-floppy-disk"></i> Lưu Vào Kho Code
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-msg" id="toastMsg">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastText">Đã sao chép code thành công!</span>
</div>

<script>
let debounceTimer;

const templates = {
    python: `# Bài Tập Lập Trình Python - VKC TKB\ndef main():\n    print("Chào mừng bạn đến với Kho Lưu Trữ Code VKC!")\n    numbers = [10, 20, 30, 40, 50]\n    print("Tổng mảng:", sum(numbers))\n\nif __name__ == "__main__":\n    main()`,
    cpp: `// Bài Tập Lập Trình C++ - VKC TKB\n#include <iostream>\nusing namespace std;\n\nint main() {\n    cout << "Chào mừng bạn đến với Kho Lưu Trữ Code C++!" << endl;\n    return 0;\n}`,
    c: `// Bài Tập Lập Trình C - VKC TKB\n#include <stdio.h>\n\nint main() {\n    printf("Chào mừng bạn đến với Kho Lưu Trữ Code C!\\n");\n    return 0;\n}`,
    java: `// Bài Tập Lập Trình Java - VKC TKB\npublic class Main {\n    public static void main(String[] args) {\n        System.out.println("Chào mừng bạn đến với Kho Lưu Trữ Code Java!");\n    }\n}`,
    php: `<?php\n// Bài Tập Lập Trình PHP - VKC TKB\necho "Chào mừng bạn đến với Kho Lưu Trữ Code PHP!\\n";\n$numbers = [1, 2, 3, 4, 5];\necho "Tổng: " . array_sum($numbers);\n?>`,
    html: `<!DOCTYPE html>\n<html lang="vi">\n<head>\n    <meta charset="UTF-8">\n    <title>Dự Án Web Demo</title>\n    <style>\n        body { font-family: sans-serif; text-align: center; padding: 50px; background: #0f172a; color: #fff; }\n        h1 { color: #a855f7; }\n    </style>\n</head>\n<body>\n    <h1>🚀 Dự Án Web HTML/CSS/JS</h1>\n    <p>Viết code và deploy trực tiếp trên hệ thống VKC TKB</p>\n</body>\n</html>`
};

document.addEventListener("DOMContentLoaded", () => {
    if (window.initialProjectsData) {
        renderStats(window.initialProjectsData.stats);
        renderProjects(window.initialProjectsData.data);
    } else {
        fetchProjects();
    }

    // Support Tab key in code textarea
    const textarea = document.getElementById('projectCode');
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
            this.selectionStart = this.selectionEnd = start + 4;
        }
    });
});

function debounceFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchProjects, 300);
}

function fetchProjects() {
    const search = document.getElementById('searchInput').value;
    const lang = document.getElementById('langFilter').value;

    fetch(`/tkb/api/code_storage_api.php?action=list&ngon_ngu=${encodeURIComponent(lang)}&search=${encodeURIComponent(search)}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                renderStats(res.stats);
                renderProjects(res.data);
            } else {
                renderProjects([]);
            }
        })
        .catch(err => {
            console.error(err);
            renderProjects([]);
        });
}

function readFileAsText(file) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = e => resolve(e.target.result);
        reader.onerror = () => resolve('');
        reader.readAsText(file);
    });
}

async function uploadFolderProject(e) {
    try {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        showToast('Đang đọc các tệp trong thư mục dự án...', true);

        let folderName = 'Dự án Web';
        if (files[0] && files[0].webkitRelativePath) {
            folderName = files[0].webkitRelativePath.split('/')[0] || 'Dự án Web';
        }

        const bundle = {};
        let hasHtml = false;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            let relPath = file.name;
            if (file.webkitRelativePath && file.webkitRelativePath.includes('/')) {
                const parts = file.webkitRelativePath.split('/');
                relPath = parts.slice(1).join('/') || parts[0];
            }
            if (!relPath) relPath = file.name;

            const ext = relPath.split('.').pop().toLowerCase();
            
            const textExts = ['html', 'htm', 'css', 'js', 'json', 'txt', 'md', 'py', 'php', 'c', 'cpp', 'h', 'hpp', 'java', 'xml', 'csv', 'sql', 'sh', 'bat', 'env', 'gitignore', 'yaml', 'yml', 'rb', 'go', 'rs'];
            if (!textExts.includes(ext)) {
                continue;
            }

            if (['html', 'htm'].includes(ext)) hasHtml = true;

            const text = await readFileAsText(file);
            if (text !== '') {
                bundle[relPath] = text;
            }
        }

function utf8_to_b64(str) {
    try {
        return window.btoa(unescape(encodeURIComponent(str || '')));
    } catch(e) {
        return str || '';
    }
}

async function autoSaveProject(title, lang, desc, code) {
    alert('[DEBUG] autoSaveProject bắt đầu! Title=' + title + ', Lang=' + lang);
    showToast('Đang nạp bài làm vào kho code...', true);
    try {
        const payload = {
            id: 0,
            ten_du_an: utf8_to_b64(title),
            ngon_ngu: lang,
            mo_ta: utf8_to_b64(desc),
            ma_nguon: utf8_to_b64(code),
            is_base64: true
        };
        alert('[DEBUG] Đang gửi fetch POST đến API... payload size=' + JSON.stringify(payload).length);
        const rawRes = await fetch('/tkb/api/code_storage_api.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        alert('[DEBUG] Fetch trả về HTTP status=' + rawRes.status);
        const resText = await rawRes.text();
        alert('[DEBUG] Nội dung phản hồi API: ' + resText.substring(0, 300));
        let res;
        try { res = JSON.parse(resText); } catch(pe) { alert('[DEBUG] Lỗi parse JSON: ' + pe.message); return; }

        if (res.success) {
            const langSel = document.getElementById('langFilter');
            if (langSel) langSel.value = 'all';
            const searchIn = document.getElementById('searchInput');
            if (searchIn) searchIn.value = '';
            
            fetchProjects();

            const newId = res.id || 0;
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Đã Tải Lên & Lưu Vào Kho';
            document.getElementById('projectId').value = newId;
            document.getElementById('projectTitle').value = title;
            document.getElementById('projectDesc').value = desc;
            document.getElementById('projectLang').value = lang;
            document.getElementById('projectCode').value = code;

            document.getElementById('projectModal').classList.add('show');
            showToast(`🎉 Đã nạp thành công "${title}" vào Kho Code!`, true);
        } else {
            alert('Lỗi lưu dự án: ' + (res.message || 'Không thể lưu vào CSDL'));
        }
    } catch(err) {
        alert('Lỗi kết nối máy chủ: ' + err.message);
    }
}

async function uploadFolderProject(e) {
    alert('[DEBUG] uploadFolderProject được gọi! Số file: ' + (e.target.files ? e.target.files.length : 0));
    try {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        showToast('Đang đọc các tệp trong thư mục dự án...', true);

        let folderName = 'Dự án Web';
        if (files[0] && files[0].webkitRelativePath) {
            folderName = files[0].webkitRelativePath.split('/')[0] || 'Dự án Web';
        }

        const bundle = {};
        let hasHtml = false;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            let relPath = file.name;
            if (file.webkitRelativePath && file.webkitRelativePath.includes('/')) {
                const parts = file.webkitRelativePath.split('/');
                relPath = parts.slice(1).join('/') || parts[0];
            }
            if (!relPath) relPath = file.name;

            const ext = relPath.split('.').pop().toLowerCase();
            
            const textExts = ['html', 'htm', 'css', 'js', 'json', 'txt', 'md', 'py', 'php', 'c', 'cpp', 'h', 'hpp', 'java', 'xml', 'csv', 'sql', 'sh', 'bat', 'env', 'gitignore', 'yaml', 'yml', 'rb', 'go', 'rs'];
            if (!textExts.includes(ext)) {
                continue;
            }

            if (['html', 'htm'].includes(ext)) hasHtml = true;

            const text = await readFileAsText(file);
            if (text !== '') {
                bundle[relPath] = text;
            }
        }

        if (Object.keys(bundle).length === 0) {
            alert('Không tìm thấy tệp code văn bản nào (như .html, .py, .cpp, .js...) trong thư mục này. Vui lòng chọn thư mục chứa mã nguồn!');
            showToast('Không tìm thấy tệp code hợp lệ!', false);
            return;
        }

        const title = 'Dự án Thư Mục: ' + folderName;
        const desc = `Bao gồm ${Object.keys(bundle).length} tệp code trong thư mục ${folderName}`;
        const lang = hasHtml ? 'html' : 'python';
        const code = JSON.stringify(bundle, null, 4);

        await autoSaveProject(title, lang, desc, code);

    } catch (err) {
        alert('Đã xảy ra lỗi khi đọc tệp: ' + err.message);
        console.error(err);
    } finally {
        if (e.target) e.target.value = '';
    }
}

function uploadAndRunCode(e) {
    alert('[DEBUG] uploadAndRunCode được gọi! File: ' + (e.target.files[0] ? e.target.files[0].name : 'KHÔNG CÓ'));
    try {
        const file = e.target.files[0];
        if (!file) return;

        const fileName = file.name;
        const ext = fileName.split('.').pop().toLowerCase();

        if (ext === 'zip') {
            showToast('Đang giải nén tệp .ZIP dự án...', true);
            if (typeof JSZip === 'undefined') {
                showToast('Thư viện JSZip chưa sẵn sàng, vui lòng thử lại', false);
                return;
            }

            JSZip.loadAsync(file).then(async (zip) => {
                const bundle = {};
                let hasHtml = false;

                for (const relPath in zip.files) {
                    const zipObj = zip.files[relPath];
                    if (zipObj.dir) continue;

                    const fileExt = relPath.split('.').pop().toLowerCase();
                    if (['html', 'htm'].includes(fileExt)) hasHtml = true;
                    
                    const textExts = ['html', 'htm', 'css', 'js', 'json', 'txt', 'md', 'py', 'php', 'c', 'cpp', 'h', 'hpp', 'java', 'xml', 'csv', 'sql', 'sh', 'bat', 'env', 'gitignore', 'yaml', 'yml', 'rb', 'go', 'rs'];
                    if (!textExts.includes(fileExt)) continue;

                    try {
                        const text = await zipObj.async('string');
                        const parts = relPath.split('/');
                        const cleanPath = parts.length > 1 ? parts.slice(1).join('/') : relPath;
                        if (cleanPath) {
                            bundle[cleanPath] = text;
                        }
                    } catch(err) {}
                }

                if (Object.keys(bundle).length === 0) {
                    alert('Không tìm thấy tệp code văn bản nào trong .ZIP!');
                    showToast('Không tìm thấy tệp code nào trong .ZIP!', false);
                    return;
                }

                const title = 'Dự án .ZIP: ' + fileName.replace('.zip', '');
                const desc = `Giải nén từ tệp ${fileName} (${Object.keys(bundle).length} tệp code)`;
                const lang = hasHtml ? 'html' : 'python';
                const code = JSON.stringify(bundle, null, 4);

                await autoSaveProject(title, lang, desc, code);
            }).catch(err => {
                alert('Lỗi khi đọc tệp .ZIP: ' + err.message);
                showToast('Lỗi khi đọc tệp .ZIP', false);
            }).finally(() => {
                if (e.target) e.target.value = '';
            });
            return;
        }

        let lang = 'python';
        if (ext === 'py') lang = 'python';
        else if (ext === 'cpp' || ext === 'c' || ext === 'h' || ext === 'hpp') lang = 'cpp';
        else if (ext === 'java') lang = 'java';
        else if (ext === 'php') lang = 'php';
        else if (['html', 'htm', 'js', 'css'].includes(ext)) lang = 'html';

        const reader = new FileReader();
        reader.onload = async function(evt) {
            const textContent = evt.target.result;
            const title = 'Tệp: ' + fileName;
            const desc = 'Tải lên từ máy tính (' + new Date().toLocaleString('vi-VN') + ')';
            
            await autoSaveProject(title, lang, desc, textContent);
            
            if (e.target) e.target.value = '';
        };
        
        reader.onerror = function() {
            alert('Không thể đọc tệp văn bản từ máy tính');
            showToast('Không thể đọc tệp văn bản từ máy tính', false);
            if (e.target) e.target.value = '';
        };

        reader.readAsText(file);
    } catch (err) {
        alert('Đã xảy ra lỗi: ' + err.message);
        if (e.target) e.target.value = '';
    }
}

function renderStats(stats) {
    if (!stats) return;
    document.getElementById('statTotal').innerText = stats.total || 0;
    document.getElementById('statPython').innerText = stats.count_python || 0;
    document.getElementById('statCpp').innerText = (parseInt(stats.count_cpp) || 0) + (parseInt(stats.count_java) || 0);
    document.getElementById('statHtml').innerText = stats.count_html || 0;
}

function getLangBadgeClass(lang) {
    switch (lang.toLowerCase()) {
        case 'python': return 'lang-python';
        case 'cpp': return 'lang-cpp';
        case 'c': return 'lang-c';
        case 'java': return 'lang-java';
        case 'php': return 'lang-php';
        case 'html': return 'lang-html';
        default: return 'lang-python';
    }
}

function getLangName(lang) {
    switch (lang.toLowerCase()) {
        case 'python': return '🐍 Python';
        case 'cpp': return '⚡ C++';
        case 'c': return '⚡ C';
        case 'java': return '☕ Java';
        case 'php': return '🐘 PHP';
        case 'html': return '🌐 Web HTML';
        default: return lang.toUpperCase();
    }
}

function renderProjects(projects) {
    const grid = document.getElementById('projectsGrid');
    if (!projects || projects.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 30px 20px;">
                <p style="color: #64748b; font-size: 14.5px; margin: 0 0 16px 0;">Kho lưu trữ chưa có bài làm code nào.</p>
                <button type="button" onclick="openCreateModal()" style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #ffffff; border: none; padding: 11px 24px; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-plus"></i> Tạo Bài Làm Mới
                </button>
            </div>
        `;
        return;
    }

    grid.innerHTML = projects.map(item => {
        const badgeClass = getLangBadgeClass(item.ngon_ngu);
        const langLabel = getLangName(item.ngon_ngu);
        const preview = escapeHtml(item.preview_code || '');

        return `
            <div class="project-card">
                <div>
                    <div class="card-top">
                        <div class="project-title">${escapeHtml(item.ten_du_an)}</div>
                        <span class="lang-badge ${badgeClass}">${langLabel}</span>
                    </div>

                    ${item.mo_ta ? `<div class="project-desc">${escapeHtml(item.mo_ta)}</div>` : ''}

                    <div class="code-snippet-box">${preview}</div>
                </div>

                <div>
                    <div style="font-size: 11.5px; color: #64748b; margin-bottom: 10px;">
                        <i class="fa-regular fa-clock"></i> Cập nhật: ${item.updated_at} (${item.code_length} ký tự)
                    </div>

                    <div class="card-actions">
                        <a href="/tkb/student/code_ide.php?storage_id=${item.id}" class="btn-act btn-run-ide">
                            <i class="fa-solid fa-play"></i> Mở IDE
                        </a>
                        <button class="btn-act btn-copy-code" onclick="copyCode(${item.id})">
                            <i class="fa-regular fa-copy"></i> Copy
                        </button>
                        <button class="btn-act btn-edit-code" onclick="openEditModal(${item.id})">
                            <i class="fa-solid fa-pen"></i> Sửa
                        </button>
                        <button class="btn-act btn-del-code" onclick="deleteProject(${item.id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
}

function onLanguageChange() {
    const lang = document.getElementById('projectLang').value;
    const codeArea = document.getElementById('projectCode');
    if (!codeArea.value || Object.values(templates).includes(codeArea.value.trim())) {
        codeArea.value = templates[lang] || '';
    }
}

function openCreateModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus-circle" style="color:#a855f7;"></i> Tạo Bài Làm Code Mới';
    document.getElementById('projectId').value = 0;
    document.getElementById('projectTitle').value = '';
    document.getElementById('projectDesc').value = '';
    document.getElementById('projectLang').value = 'python';
    document.getElementById('projectCode').value = templates.python;
    document.getElementById('projectModal').classList.add('show');
}

function openEditModal(id) {
    fetch(`/tkb/api/code_storage_api.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const item = res.data;
                document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color:#38bdf8;"></i> Chỉnh Sửa Kho Code';
                document.getElementById('projectId').value = item.id;
                document.getElementById('projectTitle').value = item.ten_du_an;
                document.getElementById('projectDesc').value = item.mo_ta || '';
                document.getElementById('projectLang').value = item.ngon_ngu;
                document.getElementById('projectCode').value = item.ma_nguon;
                document.getElementById('projectModal').classList.add('show');
            } else {
                showToast(res.message || 'Không lấy được dữ liệu', false);
            }
        });
}

function closeModal() {
    document.getElementById('projectModal').classList.remove('show');
}

function saveProject(e) {
    e.preventDefault();
    const id = document.getElementById('projectId').value;
    const data = {
        id: parseInt(id),
        ten_du_an: utf8_to_b64(document.getElementById('projectTitle').value),
        ngon_ngu: document.getElementById('projectLang').value,
        mo_ta: utf8_to_b64(document.getElementById('projectDesc').value),
        ma_nguon: utf8_to_b64(document.getElementById('projectCode').value),
        is_base64: true
    };

    fetch('/tkb/api/code_storage_api.php?action=save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            showToast(res.message || 'Đã lưu kho code thành công!');
            closeModal();
            const langSel = document.getElementById('langFilter');
            if (langSel) langSel.value = 'all';
            const searchIn = document.getElementById('searchInput');
            if (searchIn) searchIn.value = '';
            fetchProjects();
        } else {
            showToast(res.message || 'Lỗi lưu dữ liệu', false);
        }
    });
}

function saveAndRunProject(e) {
    if (e) e.preventDefault();
    const id = document.getElementById('projectId').value;
    const title = document.getElementById('projectTitle').value;
    const code = document.getElementById('projectCode').value;

    if (!title || !code) {
        showToast('Vui lòng nhập tên bài làm và nội dung code!', false);
        return;
    }

    const data = {
        id: parseInt(id),
        ten_du_an: utf8_to_b64(title),
        ngon_ngu: document.getElementById('projectLang').value,
        mo_ta: utf8_to_b64(document.getElementById('projectDesc').value),
        ma_nguon: utf8_to_b64(code),
        is_base64: true
    };

    showToast('Đang lưu bài làm và chuyển sang VS Code IDE...', true);

    fetch('/tkb/api/code_storage_api.php?action=save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success && res.id) {
            closeModal();
            window.location.href = `/tkb/student/code_ide.php?storage_id=${res.id}&autorun=1`;
        } else {
            showToast(res.message || 'Lỗi lưu dữ liệu', false);
        }
    })
    .catch(err => showToast('Lỗi gửi kết quả lên máy chủ', false));
}

function copyCode(id) {
    fetch(`/tkb/api/code_storage_api.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                navigator.clipboard.writeText(res.data.ma_nguon)
                    .then(() => showToast('Đã sao chép mã nguồn vào bộ nhớ tạm!'))
                    .catch(() => showToast('Không thể tự động sao chép', false));
            }
        });
}

function deleteProject(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa bài làm code này khỏi kho lưu trữ?')) return;

    fetch('/tkb/api/code_storage_api.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}`
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            showToast(res.message || 'Đã xóa dự án thành công!');
            fetchProjects();
        } else {
            showToast(res.message || 'Lỗi khi xóa', false);
        }
    });
}

function showToast(msg, isSuccess = true) {
    const toast = document.getElementById('toastMsg');
    const toastText = document.getElementById('toastText');
    toastText.innerText = msg;
    toast.style.background = isSuccess ? '#10b981' : '#ef4444';
    toast.style.display = 'flex';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}
</script>
</body>
</html>
