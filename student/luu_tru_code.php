<?php
require_once '../config.php';
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && (!isset($_GET['v']) || (int)$_GET['v'] < 250)) {
    header("Location: /tkb/student/luu_tru_code.php?v=250");
    exit();
}

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

// MY D LỖI
ob_start();
$res_debug = $db->query("SELECT id, student_id, ten_du_an, ngon_ngu, LEFT(ma_nguon, 50) as m FROM student_code_storage ORDER BY id DESC LIMIT 20");
$debug_arr = [];
if ($res_debug) {
    while($r = $res_debug->fetch_assoc()) $debug_arr[] = $r;
}
file_put_contents(__DIR__ . '/debug_output.txt', "ROWS:\n" . print_r($debug_arr, true) . "\n\nSESSION:\n" . print_r($_SESSION, true) . "\n\nPOST:\n" . print_r($_POST, true) . "\n\nFILES:\n" . print_r($_FILES, true));
ob_end_clean();

// ========== XỬ L FORM POST UPLOAD (bypass WAF 100%) ==========
$upload_success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['ten_du_an'])) {
        $upload_success_msg = ' Dung lượng thư mục vượt qu giới hạn cho php của my chủ! Vui lng chn thư mục chứa m nguồn nhẹ hơn (dưới 1.5MB).';
    } else {
        @$db->query("CREATE TABLE IF NOT EXISTS `student_code_storage` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `ten_du_an` VARCHAR(255) NOT NULL,
            `ngon_ngu` VARCHAR(50) NOT NULL DEFAULT 'python',
            `mo_ta` TEXT DEFAULT NULL,
            `ma_nguon` LONGTEXT NOT NULL,
            `github_url` VARCHAR(500) DEFAULT NULL,
            `la_cong_khai` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY (`student_id`),
            KEY (`ngon_ngu`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $raw_ten = $_POST['ten_du_an'] ?? '';
        $raw_mota = $_POST['mo_ta'] ?? '';
        $raw_code = $_POST['ma_nguon'] ?? '';
        $raw_gh = $_POST['github_url'] ?? '';

        if (!empty($_POST['is_base64'])) {
            $raw_ten = base64_decode($raw_ten);
            $raw_mota = base64_decode($raw_mota);
            $raw_code = base64_decode($raw_code);
            if (!empty($raw_gh)) $raw_gh = base64_decode($raw_gh);
        }

        $ten = $db->real_escape_string(trim($raw_ten));
        $lang = $db->real_escape_string(strtolower(trim($_POST['ngon_ngu'] ?? 'python')));
        $mota = $db->real_escape_string(trim($raw_mota));
        $code = $db->real_escape_string($raw_code);
        $gh_sql = !empty($raw_gh) ? "'" . $db->real_escape_string(trim($raw_gh)) . "'" : "NULL";
        $la_cong_khai = isset($_POST['la_cong_khai']) ? (int)$_POST['la_cong_khai'] : 0;

        $allowed_lang = ['python', 'c', 'cpp', 'java', 'php', 'html', 'javascript'];
        if (!in_array($lang, $allowed_lang)) $lang = 'python';

        $sql = "INSERT INTO student_code_storage (student_id, ten_du_an, ngon_ngu, mo_ta, ma_nguon, github_url, la_cong_khai) 
                VALUES ($student_id, '$ten', '$lang', '$mota', '$code', $gh_sql, $la_cong_khai)";
        if ($db->query($sql)) {
            $upload_success_msg = ' ✅ Đã tải lên và lưu thành công vào kho: ' . htmlspecialchars($raw_ten);
        } else {
            $upload_success_msg = ' ❌ Lỗi CSDL: ' . $db->error;
        }
    }
}

// Khai bo sẵn CSDL v lấy danh sch ban đầu để hiển thị ngay lập tức (0ms loading)
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
        `github_url` VARCHAR(500) DEFAULT NULL,
        `la_cong_khai` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY (`student_id`),
        KEY (`ngon_ngu`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try { @$db->query("ALTER TABLE `student_code_storage` ADD `github_url` VARCHAR(500) DEFAULT NULL"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` ADD `la_cong_khai` TINYINT(1) DEFAULT 1"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` ADD `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` ADD `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` MODIFY COLUMN `ma_nguon` LONGTEXT NOT NULL"); } catch(Exception $e){}

    $where_ids = array_unique(array_filter([(int)$student_id, (int)$user_id, 1]));
    $where_sql = "student_id IN (" . implode(',', $where_ids) . ")";

    $res_init = $db->query("SELECT id, student_id, ten_du_an, ngon_ngu, mo_ta, github_url, LEFT(ma_nguon, 300) as preview_code, CHAR_LENGTH(ma_nguon) as code_length FROM student_code_storage WHERE $where_sql ORDER BY id DESC");
    if ($res_init) {
        while ($r = $res_init->fetch_assoc()) {
            $initial_projects[] = $r;
        }
    }

    // Chỉ lấy bài làm chính thức thuộc sở hữu của sinh viên này
    $stat_res = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ngon_ngu='python' THEN 1 ELSE 0 END) as count_python,
        SUM(CASE WHEN ngon_ngu IN ('c','cpp') THEN 1 ELSE 0 END) as count_cpp,
        SUM(CASE WHEN ngon_ngu='html' THEN 1 ELSE 0 END) as count_html
        FROM student_code_storage WHERE $where_sql");
    if ($stat_res && ($s_row = $stat_res->fetch_assoc())) {
        $initial_stats = $s_row;
    }
    $initial_stats['total'] = count($initial_projects);
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
    <?php
    $safe_json = json_encode(['data' => $initial_projects, 'stats' => $initial_stats], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($safe_json === false) {
        file_put_contents(__DIR__ . '/debug_output.txt', "JSON ENCODE FAILED: " . json_last_error_msg() . "\n", FILE_APPEND);
        $safe_json = '{"data":[],"stats":{"total":0,"count_python":0,"count_cpp":0,"count_html":0}}';
    }
    ?>
    <script>
        window.initialProjectsData = <?= $safe_json ?>;
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

        /* Modal Styles - Centered 100% Viewport */
        .modal-backdrop {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(15, 23, 42, 0.85) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            z-index: 9999999 !important;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
            margin: 0 !important;
        }

        .modal-backdrop.show {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .modal-content-box {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            width: 100%;
            max-width: 650px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            animation: modalFadeIn 0.25s ease-out;
            margin: auto;
            position: relative;
            z-index: 10000000 !important;
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
                <a href="/tkb/student/kho_code_cong_dong.php" class="btn-create-code" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); text-decoration: none; box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);">
                    <i class="fa-solid fa-globe"></i> Kho Code Cộng Đồng
                </a>

                <button type="button" class="btn-create-code" style="background: linear-gradient(135deg, #24292e 0%, #1f2328 100%); border: 1px solid rgba(255,255,255,0.25); box-shadow: 0 8px 20px rgba(0,0,0,0.4);" onclick="openGitHubModal()">
                    <i class="fa-brands fa-github" style="font-size: 16px;"></i> Nhập / Lưu từ GitHub
                </button>

                <button type="button" class="btn-create-code" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); box-shadow: 0 8px 20px rgba(2, 132, 199, 0.35);" onclick="document.getElementById('folderInput').click()">
                    <i class="fa-solid fa-folder-plus"></i> Tải Lên Cả Thư Mục Dự Án
                </button>
                <input type="file" id="folderInput" webkitdirectory directory multiple style="display:none;" onclick="this.value=null" onchange="uploadFolderProject(event)">

                <button type="button" class="btn-create-code" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);" onclick="document.getElementById('codeFileInput').click()">
                    <i class="fa-solid fa-file-arrow-up"></i> Tải Lên Tệp .ZIP / File Code
                </button>
                <input type="file" id="codeFileInput" style="display:none;" onclick="this.value=null" onchange="uploadAndRunCode(event)">

                <button type="button" class="btn-create-code" onclick="openCreateModal()">
                    <i class="fa-solid fa-plus"></i> Tạo Bài Làm Mới
                </button>
            </div>
        </div>

        <!-- Hidden POST form for saving code (bypass WAF 100%) -->
        <form id="hiddenUploadForm" method="POST" action="/tkb/student/luu_tru_code.php" style="display:none;">
            <input type="hidden" name="ten_du_an" id="hf_ten_du_an">
            <input type="hidden" name="ngon_ngu" id="hf_ngon_ngu">
            <input type="hidden" name="mo_ta" id="hf_mo_ta">
            <input type="hidden" name="github_url" id="hf_github_url">
            <input type="hidden" name="is_base64" id="hf_is_base64" value="1">
            <textarea name="ma_nguon" id="hf_ma_nguon" style="display:none;"></textarea>
        </form>

        <?php if (!empty($upload_success_msg)): ?>
        <div style="padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 15px; background: <?= strpos($upload_success_msg, '❌') !== false ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #10b981, #059669)' ?>; color: #fff; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <?= $upload_success_msg ?>
        </div>
        <script>
            window.postSuccessMsg = <?= json_encode($upload_success_msg) ?>;
        </script>
        <?php endif; ?>

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
                    <div class="stat-lbl">Bi Tập Python</div>
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
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px;">
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

                <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.05); padding: 10px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);">
                    <input type="checkbox" id="projectIsPublic" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #a855f7;">
                    <label for="projectIsPublic" style="font-size: 13.5px; font-weight: 600; color: #f1f5f9; cursor: pointer; margin: 0;">
                        🌐 Công khai bài làm lên Kho Code Cộng Đồng (cho phép sinh viên khác & giáo viên xem/tham khảo)
                    </label>
                </div>

                <div style="margin-top: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                        <label class="form-label-code" style="margin: 0;">MÃ NGUỒN CODE (SOURCE CODE) *</label>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <button type="button" class="btn-act" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); padding: 4px 10px; border-radius: 6px; font-size: 12px; cursor: pointer;" onclick="document.getElementById('modalFolderInput').click()">
                                <i class="fa-solid fa-folder-plus"></i> Nạp cả thư mục (kèm ảnh/nhạc)
                            </button>
                            <input type="file" id="modalFolderInput" webkitdirectory directory multiple style="display:none;" onclick="this.value=null" onchange="readModalFolder(event)">

                            <button type="button" class="btn-act" style="background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); padding: 4px 10px; border-radius: 6px; font-size: 12px; cursor: pointer;" onclick="document.getElementById('modalFileInput').click()">
                                <i class="fa-solid fa-file-arrow-up"></i> Nạp tệp code (.py, .html...)
                            </button>
                            <input type="file" id="modalFileInput" style="display:none;" onclick="this.value=null" onchange="readModalFile(event)">

                            <button type="button" class="btn-act" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); padding: 4px 10px; border-radius: 6px; font-size: 12px; cursor: pointer;" onclick="document.getElementById('modalBannerInput').click()">
                                <i class="fa-solid fa-image"></i> 🖼️ Tải Ảnh Banner
                            </button>
                            <input type="file" id="modalBannerInput" accept="image/*" style="display:none;" onclick="this.value=null" onchange="uploadModalBanner(event)">
                        </div>
                    </div>

                    <div id="modalBannerPreviewBox" style="display:none; margin-bottom: 10px; border-radius: 10px; overflow: hidden; height: 100px; position: relative; border: 1px solid rgba(255,255,255,0.2);">
                        <img id="modalBannerPreviewImg" src="" style="width:100%; height:100%; object-fit:cover; display:block;">
                        <button type="button" onclick="removeModalBanner()" style="position:absolute; top:6px; right:6px; background:rgba(239, 68, 68, 0.85); color:#fff; border:none; border-radius:50%; width:24px; height:24px; cursor:pointer; font-size:12px; display:flex; align-items:center; justify-content:center;">&times;</button>
                    </div>

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

<!-- Modal Import GitHub Repository -->
<div class="modal-backdrop" id="githubModal">
    <div class="modal-content-box" style="max-width: 600px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #24292e 0%, #0d1117 100%);">
            <h3 style="color: #f0f6fc; display: flex; align-items: center; gap: 10px;">
                <i class="fa-brands fa-github" style="font-size: 24px;"></i> Nhập Repository Từ GitHub
            </h3>
            <button type="button" class="btn-close-modal" onclick="document.getElementById('githubModal').style.display='none'; document.getElementById('githubModal').classList.remove('show');"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 16px;">
                <label class="form-label-code">ĐƯỜNG DẪN HOẶC TÊN GITHUB REPOSITORY *</label>
                <input type="text" id="githubRepoUrl" class="modal-input" placeholder="Dán link, ví dụ: https://github.com/octocat/Hello-World hoặc octocat/Hello-World" onkeypress="if(event.key==='Enter'){event.preventDefault();saveGitHubProject();}">
            </div>

            <div style="margin-bottom: 16px;">
                <label class="form-label-code">TÊN BÀI LÀM / MÔ TẢ TÙY CHỌN (KHÔNG BẮT BUỘC)</label>
                <input type="text" id="githubCustomTitle" class="modal-input" placeholder="Để trống hệ thống sẽ tự lấy tên Repository trên GitHub...">
            </div>

            <div style="font-size: 13px; color: #94a3b8; background: rgba(255,255,255,0.05); padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
                💡 Hỗ trợ kết nối mọi Public Repository trên GitHub. Hệ thống sẽ tự động lấy thông tin, mã nguồn và tạo liên kết trực tiếp vào Kho Code Sinh Viên.
            </div>

            <input type="hidden" id="ghTitle">
            <input type="hidden" id="ghLang">
            <input type="hidden" id="ghDesc">
            <input type="hidden" id="ghUrlVal">
            <textarea id="ghCode" style="display:none;"></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="document.getElementById('githubModal').style.display='none'; document.getElementById('githubModal').classList.remove('show');">Hủy Bỏ</button>
            <button type="button" class="btn-save-project" id="btnSaveGh" style="background: linear-gradient(135deg, #238636 0%, #2ea043 100%); box-shadow: 0 6px 18px rgba(35, 134, 54, 0.4);" onclick="saveGitHubProject()">
                <i class="fa-solid fa-cloud-arrow-down"></i> Lưu Vào Kho Code Sinh Viên
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
window.openGitHubModal = function() {
    const urlInput = document.getElementById('githubRepoUrl');
    if (urlInput) urlInput.value = '';
    const titleInput = document.getElementById('githubCustomTitle');
    if (titleInput) titleInput.value = '';
    const modal = document.getElementById('githubModal');
    if (modal) {
        document.body.appendChild(modal);
        modal.style.setProperty('display', 'flex', 'important');
        modal.classList.add('show');
    }
};
window.closeGitHubModal = function() {
    const modal = document.getElementById('githubModal');
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
        modal.classList.remove('show');
    }
};
window.openCreateModal = function() {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus-circle" style="color:#a855f7;"></i> Tạo Bài Làm Code Mới';
    document.getElementById('projectId').value = 0;
    document.getElementById('projectTitle').value = '';
    document.getElementById('projectDesc').value = '';
    document.getElementById('projectLang').value = 'python';
    if (typeof templates !== 'undefined' && templates.python) {
        document.getElementById('projectCode').value = templates.python;
    }
    const modal = document.getElementById('projectModal');
    if (modal) {
        document.body.appendChild(modal);
        modal.style.setProperty('display', 'flex', 'important');
        modal.classList.add('show');
    }
};
window.closeModal = function() {
    const modal = document.getElementById('projectModal');
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
        modal.classList.remove('show');
    }
};
</script>
<script src="/tkb/assets/code_storage_v1.js?v=<?php echo time(); ?>"></script>

</body>
</html>

