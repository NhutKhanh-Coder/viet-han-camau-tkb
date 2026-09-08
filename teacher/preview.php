<?php
require_once '../config.php';
requireTeacher();

$submission_id = (int)($_GET['submission_id'] ?? 0);
$file_path = trim($_GET['file_path'] ?? '');

if (!$submission_id || empty($file_path)) {
    die("Thiếu tham số hợp lệ.");
}

// Target directory for previews
$preview_dir = '../assets/uploads/previews/' . $submission_id;

// Resolve absolute path to zip file
if (strpos($file_path, '/tkb/') === 0) {
    $zip_real_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
} else if (strpos($file_path, 'assets/') === 0) {
    $zip_real_path = $_SERVER['DOCUMENT_ROOT'] . '/tkb/' . $file_path;
} else if (strpos($file_path, 'submissions/') === 0) {
    $zip_real_path = $_SERVER['DOCUMENT_ROOT'] . '/tkb/assets/uploads/' . $file_path;
} else {
    $zip_real_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($file_path, '/');
}
// Normalize slashes
$zip_real_path = str_replace(array('\\', '//'), '/', $zip_real_path);

if (!file_exists($zip_real_path)) {
    die("Không tìm thấy tệp tin bài nộp: " . htmlspecialchars($zip_real_path));
}

// Extract zip if target folder doesn't exist
if (!is_dir($preview_dir)) {
    if (!mkdir($preview_dir, 0777, true)) {
        die("Không thể tạo thư mục xem trước.");
    }
    
    // Check if zip file
    $ext = strtolower(pathinfo($zip_real_path, PATHINFO_EXTENSION));
    if ($ext === 'zip') {
        $zip = new ZipArchive;
        if ($zip->open($zip_real_path) === TRUE) {
            $zip->extractTo($preview_dir);
            $zip->close();
        } else {
            die("Lỗi: Không thể giải nén tệp tin bài nộp.");
        }
    } else {
        // If it's a single non-zip file (like pdf, image, HTML), copy it
        $dest_file = $preview_dir . '/' . basename($zip_real_path);
        copy($zip_real_path, $dest_file);
    }
}

// Auto-patch database connection strings in preview PHP files
if (is_dir($preview_dir)) {
    $db_host = defined('DB_HOST') ? DB_HOST : 'sql308.infinityfree.com';
    $db_user = defined('DB_USER') ? DB_USER : 'if0_41796593';
    $db_pass = defined('DB_PASS') ? DB_PASS : 'T5v3vJeuvOxCI';
    $db_name = defined('DB_NAME') ? DB_NAME : 'if0_41796593_truong_caodang';

    $rit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($preview_dir));
    foreach ($rit as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $path = $file->getPathname();
            $content = file_get_contents($path);
            $patched = preg_replace('/(mysql:\s*host\s*=\s*)(localhost|127\.0\.0\.1)/i', '${1}' . $db_host, $content);
            $patched = preg_replace('/(dbname\s*=\s*)[a-zA-Z0-9_-]+/i', '${1}' . $db_name, $patched);
            $patched = preg_replace('/(\$(?:host|db_host|hostname|server|servername|dbhost|db_server|my_host|host_name)\s*=\s*[\'\"]).*?([\'\"]\s*;)/i', '${1}' . $db_host . '${2}', $patched);
            $patched = preg_replace('/(\$(?:db_user|dbuser|db_username|username|user|user_name)\s*=\s*[\'\"]).*?([\'\"]\s*;)/i', '${1}' . $db_user . '${2}', $patched);
            $patched = preg_replace('/(\$(?:db_pass|dbpass|db_password|password|pass|pass_word)\s*=\s*[\'\"]).*?([\'\"]\s*;)/i', '${1}' . $db_pass . '${2}', $patched);
            $patched = preg_replace('/(\$(?:dbname|db_name|db_database|database)\s*=\s*[\'\"]).*?([\'\"]\s*;)/i', '${1}' . $db_name . '${2}', $patched);
            $patched = preg_replace(
                '/(catch\s*\(\s*(?:PDOException|Exception|Throwable)\s*\$\w+\s*\)\s*\{\s*)(?:die|exit)\s*\(\s*[\'\"]?[^\'\"]*(?:Database|Connection|connect|kết nối|thất bại|Lỗi)[^\'\"]*[\'\"]?\s*(?:\.\s*\$\w+->getMessage\(\)|\.\s*\$\w+->connect_error)?\s*\)\s*;/i',
                '$1try { $pdo = new PDO("mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8mb4", "' . $db_user . '", "' . $db_pass . '"); $conn = @new mysqli("' . $db_host . '", "' . $db_user . '", "' . $db_pass . '", "' . $db_name . '"); } catch(Throwable $ex){}',
                $patched
            );
            $patched = preg_replace(
                '/(?:die|exit)\s*\(\s*[\'\"](?:Database connection failed|Connection failed|Lỗi kết nối)[^\'\"]*[\'\"]\s*(?:\.\s*\$e->getMessage\(\))?\s*\)\s*;/i',
                'try { $pdo = new PDO("mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8mb4", "' . $db_user . '", "' . $db_pass . '"); $conn = @new mysqli("' . $db_host . '", "' . $db_user . '", "' . $db_pass . '", "' . $db_name . '"); } catch(Throwable $ex){}',
                $patched
            );
            if ($patched !== $content) {
                file_put_contents($path, $patched);
            }
        }
    }
}

// Find starter file recursively (html, htm, php, login, home)
function findStartFile($dir) {
    $starters = ['index.html', 'index.htm', 'index.php', 'login.php', 'home.php'];
    foreach ($starters as $st) {
        if (file_exists($dir . '/' . $st)) {
            return $dir . '/' . $st;
        }
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $found = findStartFile($path);
                if ($found) {
                    return $found;
                }
            }
        }
    }
    return null;
}

$found_index = findStartFile($preview_dir);

if ($found_index) {
    // Calculate URL path relative to web root
    // $preview_dir starts with ../assets/...
    // Let's convert $found_index to relative URL
    // $found_index is like ../assets/uploads/previews/1/index.html or ../assets/uploads/previews/1/banghang/index.html
    $relative_url = str_replace('../', '/tkb/', $found_index);
    header("Location: " . $relative_url);
    exit();
}

// If no index.html is found, render a premium file explorer
function getFileExplorer($dir, $relative_base = '') {
    global $submission_id;
    $files = scandir($dir);
    $html = '<ul>';
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        $rel_path = $relative_base ? $relative_base . '/' . $file : $file;
        if (is_dir($path)) {
            $html .= '<li class="folder"><i class="fa-solid fa-folder" style="color:#eab308; margin-right:8px;"></i>' . htmlspecialchars($file);
            $html .= getFileExplorer($path, $rel_path);
            $html .= '</li>';
        } else {
            $file_url = '/tkb/assets/uploads/previews/' . $submission_id . '/' . $rel_path;
            $html .= '<li class="file"><i class="fa-regular fa-file-code" style="color:#ef4444; margin-right:8px;"></i><a href="' . htmlspecialchars($file_url) . '" target="_blank">' . htmlspecialchars($file) . '</a></li>';
        }
    }
    $html .= '</ul>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem Trước Bài Làm</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            padding: 30px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        h2 {
            margin-top: 0;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .desc {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 25px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
        }
        ul {
            list-style: none;
            padding-left: 20px;
            margin: 5px 0;
        }
        li {
            padding: 6px 0;
            font-size: 14.5px;
        }
        li.folder {
            font-weight: 700;
            color: #334155;
        }
        li.file a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        li.file a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa-solid fa-folder-open" style="color:#ef4444"></i> Explorer - Chi Tiết Thư Mục Bài Làm</h2>
        <div class="desc">Không tìm thấy file <code>index.html</code> ở cấp cao nhất. Dưới đây là cấu trúc tệp tin của sinh viên. Vui lòng bấm vào liên kết để xem/chạy thử các file:</div>
        
        <div style="background:#f1f5f9; border-radius:10px; padding:20px; border:1px solid #cbd5e1;">
            <?php echo getFileExplorer($preview_dir); ?>
        </div>
    </div>
</body>
</html>
