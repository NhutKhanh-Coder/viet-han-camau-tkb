<?php
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Lỗi PHP Server: ' . $err['message'] . ' (tại dòng ' . $err['line'] . ')']);
    }
});

ob_start();
error_reporting(0);
ini_set('display_errors', 0);
@mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config.php';
require_once '../includes/code_access.php';

// Clear any accidental output before headers
if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại!']);
    exit;
}
if (!isStudent() && !isTeacher() && !isAdmin()) {
    echo json_encode(['error' => 'Bạn không có quyền thực hiện hành động này.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Phương thức yêu cầu không hợp lệ.']);
    exit;
}

if (isTeacher() || isAdmin()) {
    // Teachers and admins are allowed to run and preview student code at any time
} else {
    $db = getDB();
    $sv_id = $_SESSION['student_id'] ?? 0;
    $code_access = getStudentCodeAccess($db, $sv_id);
    $db->close();

    if (!$code_access['allowed']) {
        $error = 'Hiện tại IDE chưa được mở cho lớp của bạn. Vui lòng liên hệ giáo viên!';
        if ($code_access['practice_available_at']) {
            $error = 'Bài kiểm tra/thi vừa kết thúc. IDE sẽ mở lại để luyện Code lúc ' . date('H:i', $code_access['practice_available_at']) . '.';
        }
        echo json_encode(['error' => $error]);
        exit;
    }
}

$virtual_files_json = $_POST['virtual_files'] ?? '';
$active_file = trim($_POST['active_file'] ?? '');
$storage_id = (int)($_POST['storage_id'] ?? 0);
$virtual_files = json_decode($virtual_files_json, true) ?: [];

// If storage_id is provided, load files from database as baseline
if ($storage_id > 0) {
    $db_dep = getDB();
    $st_res = $db_dep->query("SELECT ma_nguon, ngon_ngu FROM student_code_storage WHERE id = $storage_id LIMIT 1");
    if ($st_res && ($st_row = $st_res->fetch_assoc())) {
        $db_bundle = @json_decode($st_row['ma_nguon'], true);
        if (!is_array($db_bundle)) {
            $b64 = @base64_decode($st_row['ma_nguon']);
            if ($b64 && (strpos(trim($b64), '{') === 0 || strpos(trim($b64), '[')) === 0) {
                $db_bundle = @json_decode($b64, true);
            }
        }
        if (!is_array($db_bundle) || empty($db_bundle)) {
            $db_bundle = [];
            if (preg_match_all('/"([^"\r\n]+\.[a-zA-Z0-9]+)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $st_row['ma_nguon'], $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $db_bundle[$m[1]] = stripcslashes($m[2]);
                }
            }
        }
        $fres = $db_dep->query("SELECT file_path FROM student_code_files WHERE storage_id = $storage_id");
        if ($fres && $fres->num_rows > 0) {
            while ($fr = $fres->fetch_assoc()) {
                $db_bundle[$fr['file_path']] = '__LAZY_FETCH__'; // Just keep path for active_file detection
            }
            $fres->free(); // Free result to prevent commands out of sync
        }
        // Remove __LAZY_FETCH__ from client payload so it doesn't overwrite DB baseline
        foreach ($virtual_files as $k => $v) {
            if ($v === '__LAZY_FETCH__') {
                unset($virtual_files[$k]);
            }
        }
        // Client edits override DB baseline files
        $virtual_files = array_merge($db_bundle, $virtual_files);
    }
}

// Auto-determine active_file if missing
if (empty($active_file) && !empty($virtual_files)) {
    $fileKeys = array_keys($virtual_files);
    foreach ($fileKeys as $k) {
        $lk = strtolower($k);
        if ($lk === 'about.php' || str_ends_with($lk, '/about.php') || $lk === 'index.php' || str_ends_with($lk, '/index.php') || $lk === 'index.html' || str_ends_with($lk, '/index.html')) {
            $active_file = $k;
            break;
        }
    }
    if (empty($active_file)) {
        foreach ($fileKeys as $k) {
            $lk = strtolower($k);
            if (str_ends_with($lk, '.php') && !str_contains($lk, 'config') && !str_contains($lk, 'include') && !str_contains($lk, 'db.')) {
                $active_file = $k;
                break;
            }
        }
    }
    if (empty($active_file)) {
        $active_file = $fileKeys[0];
    }
}

if (empty($virtual_files) || empty($active_file)) {
    echo json_encode(['error' => 'Vui lòng cung cấp danh sách file và tệp tin chính.']);
    exit;
}

// --- Fast non-blocking cleanup (max 3 old dirs, 10% chance) ---
$web_root = __DIR__ . '/../temp_runs';
if (!is_dir($web_root)) {
    @mkdir($web_root, 0777, true);
}

if (rand(1, 10) === 1) {
    $cutoff = time() - 1800; // 30 minutes
    $cleaned = 0;
    $dirs = @glob($web_root . '/web_*', GLOB_ONLYDIR);
    if ($dirs) {
        foreach ($dirs as $old_dir) {
            if ($cleaned >= 3) break;
            if (@filemtime($old_dir) < $cutoff) {
                $cleaned++;
                $files = @scandir($old_dir);
                if ($files) {
                    foreach ($files as $f) {
                        if ($f !== '.' && $f !== '..') {
                            $p = $old_dir . '/' . $f;
                            if (is_file($p)) @unlink($p);
                        }
                    }
                }
                @rmdir($old_dir);
            }
        }
    }
}

// --- Create new deployment directory ---
$deploy_id = 'web_' . uniqid('', true);
$deploy_dir = $web_root . '/' . $deploy_id;

if (!mkdir($deploy_dir, 0777, true)) {
    echo json_encode(['error' => 'Lỗi hệ thống: Không thể tạo thư mục triển khai.']);
    exit;
}

// --- Write all virtual files (supports subfolder paths like css/style.css) ---
function deploy_write_file($filename, $content, $deploy_dir) {
    if ($content === '__LAZY_FETCH__') return false; // Handled by DB stream later

    // Validate: no path traversal, allow common filename characters including spaces and parentheses
    if (preg_match('/\.\./', $filename) || preg_match('/[^a-zA-Z0-9_\.\-\/\s\(\)\[\]\+]/', $filename)) {
        return false;
    }
    $target_path = $deploy_dir . '/' . $filename;
    $target_dir = dirname($target_path);
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $is_binary = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv'], true);

    if ($content === '__SERVER_LOCAL_FILE__' || ($is_binary && (empty($content) || strlen($content) < 50))) {
        // Copy real binary image file from server disk if available
        $copied = false;
        $possible_sources = [
            'C:/xampp/htdocs/mohinh/' . $filename,
            'C:/xampp/htdocs/tkb/' . $filename,
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/mohinh/' . $filename,
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/' . $filename
        ];
        foreach ($possible_sources as $c_src) {
            if (file_exists($c_src)) {
                @copy($c_src, $target_path);
                $copied = true;
                break;
            }
        }
        if ($copied) {
            return true;
        }
    }

    // Decode data URLs for binary files (images/audio/video) or encoded text
    if (strpos($content, 'data:') === 0) {
        if (strpos($content, ';base64,') !== false) {
            $parts = explode(';base64,', $content);
            $content = base64_decode($parts[1]);
        } else {
            $parts = explode(',', $content, 2);
            $content = isset($parts[1]) ? urldecode($parts[1]) : $content;
        }
    }
    
    // Master Auto-patcher for ALL student PHP files when running on production (InfinityFree)
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        $content = patchStudentDatabaseCode($content);
        // Inject robust error handler forcing HTTP 200 so InfinityFree never hijacks error page with 500 screen
        $error_handler = '
http_response_code(200);
@header("HTTP/1.1 200 OK");
error_reporting(E_ALL);
ini_set("display_errors", "1");
set_exception_handler(function($e) {
    http_response_code(200);
    $msg = $e->getMessage();
    if (preg_match("/1146 Table \'[^\']+\.([a-zA-Z0-9_]+)\' doesn\'t exist/i", $msg, $m)) {
        $tbl = $m[1];
        $h = defined("DB_HOST") ? DB_HOST : "sql308.infinityfree.com";
        $u = defined("DB_USER") ? DB_USER : "if0_41796593";
        $p = defined("DB_PASS") ? DB_PASS : "T5v3vJeuvOxCI";
        $d = defined("DB_NAME") ? DB_NAME : "if0_41796593_truong_caodang";
        try {
            $c = @new mysqli($h, $u, $p, $d);
            if ($c && !$c->connect_error) {
                $c->set_charset("utf8mb4");
                $c->query("CREATE TABLE IF NOT EXISTS `$tbl` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) DEFAULT \'Default\',
                    `title` VARCHAR(255) DEFAULT \'Default\',
                    `setting_key` VARCHAR(255) DEFAULT \'site_name\',
                    `setting_value` TEXT,
                    `site_name` VARCHAR(255) DEFAULT \'Jollibee\',
                    `site_email` VARCHAR(255) DEFAULT \'admin@gmail.com\',
                    `phone` VARCHAR(50) DEFAULT \'0901234567\',
                    `address` VARCHAR(255) DEFAULT \'Cà Mau\',
                    `status` VARCHAR(50) DEFAULT \'active\',
                    `description` TEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $c->query("INSERT IGNORE INTO `$tbl` (`id`, `name`, `title`, `setting_key`, `setting_value`, `site_name`, `site_email`, `phone`, `address`) VALUES (1, \'Jollibee\', \'Jollibee\', \'site_name\', \'Jollibee\', \'Jollibee\', \'admin@gmail.com\', \'0901234567\', \'Cà Mau\');");
                echo "<script>window.location.reload();</script>";
                exit;
            }
        } catch (Throwable $ex) {}
    }
    echo "<div style=\"background:#fef2f2;color:#991b1b;padding:15px;border:2px solid #f87171;border-radius:8px;font-family:sans-serif;margin:10px;\">
    <strong>⚠️ Lỗi Ngoại Lệ (Exception):</strong> " . htmlspecialchars($msg) . "<br>
    <small>Tệp: " . htmlspecialchars($e->getFile()) . " (Dòng " . $e->getLine() . ")</small>
    </div>";
});
set_error_handler(function($no, $str, $file, $line) {
    if (!(error_reporting() & $no)) return false;
    // Suppress non-fatal notices/warnings like Undefined array key to keep UI layout clean
    if (in_array($no, [E_WARNING, E_NOTICE, E_DEPRECATED, E_USER_WARNING, E_USER_NOTICE])) {
        return true;
    }
    http_response_code(200);
    echo "<div style=\"background:#fffbeb;color:#92400e;padding:10px;border:1px solid #fcd34d;border-radius:6px;font-family:sans-serif;margin:8px;\">
    <strong>⚠️ Cảnh báo PHP [$no]:</strong> " . htmlspecialchars($str) . "<br>
    <small>Tệp: " . htmlspecialchars($file) . " (Dòng $line)</small>
    </div>";
    return true;
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e["type"], [1, 4, 16, 64])) {
        http_response_code(200);
        @header("HTTP/1.1 200 OK");
        echo "<div style=\"background:#fef2f2;color:#991b1b;padding:15px;border:2px solid #ef4444;border-radius:8px;font-family:sans-serif;margin:10px;\">
        <strong>❌ Lỗi Thực Thi PHP (Fatal Error):</strong> " . htmlspecialchars($e["message"]) . "<br>
        <small>Tệp: " . htmlspecialchars($e["file"]) . " (Dòng " . $e["line"] . ")</small>
        </div>";
    }
});
';
        $content = preg_replace('/(<\?php\b)/i', '$1' . $error_handler, $content, 1);
    } elseif (in_array($ext, ['env', 'ini', 'json', 'yaml', 'yml'], true)) {
        // A number of student projects keep DB_HOST outside PHP (for example
        // in .env). Patch those settings before the web preview starts too.
        $content = patchStudentDatabaseCode($content);
    }
    
    file_put_contents($target_path, $content);
    return true;
}

$written_files = [];
foreach ($virtual_files as $filename => $content) {
    if (deploy_write_file($filename, $content, $deploy_dir)) {
        $written_files[] = $filename;
    }
}

// Memory-safe stream of remaining large DB files
if ($storage_id > 0) {
    $db_dep = getDB();
    $db_dep->real_query("SELECT file_path, file_content FROM student_code_files WHERE storage_id = $storage_id");
    if ($fres = $db_dep->use_result()) {
        while ($fr = $fres->fetch_assoc()) {
            if (!in_array($fr['file_path'], $written_files)) {
                deploy_write_file($fr['file_path'], $fr['file_content'], $deploy_dir);
                $written_files[] = $fr['file_path'];
            }
        }
        $fres->free(); // Free result
    }
}

// --- Construct Base64 DataURL Media Map for HTML preview fallback ---
$media_map = [];
foreach ($virtual_files as $fname => $fcontent) {
    if (strpos($fcontent, 'data:') === 0) {
        $media_map[$fname] = $fcontent;
        $bname = basename($fname);
        $media_map[$bname] = $fcontent;
    }
}

if (!empty($media_map)) {
    $media_json = json_encode($media_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $media_patcher = '
<script>
(function() {
    var _PM = ' . $media_json . ';
    function _patchMedia() {
        if (!_PM) return;
        document.querySelectorAll("img").forEach(function(img) {
            var s = img.getAttribute("src");
            if (s && !s.startsWith("data:") && !s.startsWith("http")) {
                var clean = s.replace(/^\.\//, "").replace(/^\//, "");
                if (_PM[clean]) img.src = _PM[clean];
                else {
                    var base = clean.split("/").pop();
                    if (_PM[base]) img.src = _PM[base];
                }
            }
        });
        document.querySelectorAll("audio, video, source").forEach(function(m) {
            var s = m.getAttribute("src");
            if (s && !s.startsWith("data:") && !s.startsWith("http")) {
                var clean = s.replace(/^\.\//, "").replace(/^\//, "");
                if (_PM[clean]) m.src = _PM[clean];
                else {
                    var base = clean.split("/").pop();
                    if (_PM[base]) m.src = _PM[base];
                }
            }
        });
        document.querySelectorAll("*").forEach(function(el) {
            var bg = el.style.backgroundImage || (window.getComputedStyle ? window.getComputedStyle(el).backgroundImage : "");
            if (bg && bg.indexOf("url(") !== -1) {
                for (var fn in _PM) {
                    if (bg.indexOf(fn) !== -1) {
                        el.style.backgroundImage = "url(\"" + _PM[fn] + "\")";
                    }
                }
            }
        });
    }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", _patchMedia);
    } else {
        _patchMedia();
    }
    setTimeout(_patchMedia, 300);
    setTimeout(_patchMedia, 1000);
})();
</script>
';
    $html_files = @glob($deploy_dir . '/*.{html,htm}', GLOB_BRACE) ?: [];
    foreach ($html_files as $hf) {
        $hc = file_get_contents($hf);
        if ($hc !== false) {
            if (strpos($hc, '</head>') !== false) {
                $hc = str_replace('</head>', $media_patcher . '</head>', $hc);
            } else {
                $hc = $media_patcher . $hc;
            }
            file_put_contents($hf, $hc);
        }
    }
}

// Automatically import any SQL schema/data files included in the student project
autoImportStudentSqlFiles($virtual_files);
autoRunStudentDatabaseInitializers($deploy_dir, $virtual_files);

// Ensure all common student configuration & helper files exist (fallbacks for missing db.php, functions.php, etc.)
ensureCommonStudentFilesExist($deploy_dir);

// --- Auto-create _db_config.php with sandbox connection ---
$real_db_host = defined('DB_HOST') ? DB_HOST : 'sql308.infinityfree.com';
$real_db_user = defined('DB_USER') ? DB_USER : 'if0_41796593';
$real_db_pass = defined('DB_PASS') ? DB_PASS : 'T5v3vJeuvOxCI';
$real_db_name = defined('DB_NAME') ? DB_NAME : 'if0_41796593_truong_caodang';

$db_config_content = '<?php
// =================================================================
// Kết nối Database Sandbox (tự động tạo bởi hệ thống IDE)
// =================================================================
$DB_HOST = ' . var_export($real_db_host, true) . ';
$DB_USER = ' . var_export($real_db_user, true) . ';
$DB_PASS = ' . var_export($real_db_pass, true) . ';
$DB_NAME = ' . var_export($real_db_name, true) . ';

// Kết nối MySQLi & PDO
try {
    $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn && !$conn->connect_error) {
        $conn->set_charset("utf8mb4");
    }
} catch (Throwable $e) {}

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db = $pdo;
} catch (Throwable $e) {}

// Hàm tiện ích: chạy truy vấn và trả về mảng kết quả
function db_query($sql) {
    global $conn;
    if (!isset($conn) || !$conn || $conn->connect_error) return [];
    $result = @$conn->query($sql);
    if ($result === false || $result === true) return [];
    return $result->fetch_all(MYSQLI_ASSOC);
}
';

file_put_contents($deploy_dir . '/_db_config.php', $db_config_content);

// --- Extract student zip binaries if grading submission ---
$submission_id = intval($_POST['submission_id'] ?? 0);
if ($submission_id > 0) {
    $db = getDB();
    $stmt = $db->prepare("SELECT file_path FROM practice_submissions WHERE id = ?");
    $stmt->bind_param("i", $submission_id);
    $stmt->execute();
    $sub_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();
    
    if ($sub_row) {
        $zip_file_path = '..' . str_replace('/tkb', '', $sub_row['file_path']);
        if (file_exists($zip_file_path)) {
            $zip = new ZipArchive;
            if ($zip->open($zip_file_path) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $is_binary = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv']);
                    if ($is_binary) {
                        $zip->extractTo($deploy_dir, $filename);
                        $target_path = $deploy_dir . '/' . $filename;
                        if (file_exists($target_path)) {
                            $content = file_get_contents($target_path);
                            if (strpos($content, 'data:') === 0 && strpos($content, ';base64,') !== false) {
                                $parts = explode(';base64,', $content);
                                $decoded = base64_decode($parts[1]);
                                if ($decoded !== false) {
                                    file_put_contents($target_path, $decoded);
                                }
                            }
                        }
                    }
                }
                $zip->close();
            }
        }
    }
}

// --- Local binary files copy on server to bypass POST limits ---
$project_name = trim($_POST['project_name'] ?? '');
if (!empty($project_name)) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $source_dir = 'C:/xampp/htdocs/' . $project_name;
    } else {
        $source_dir = ($_SERVER['DOCUMENT_ROOT'] ?? '/home/vol6_7/infinityfree.com/if0_41796593/viethan.free.nf/htdocs') . '/' . $project_name;
    }
    $source_dir = str_replace(['..', '\\'], ['', '/'], $source_dir);
    if (is_dir($source_dir)) {
        copyBinaryFiles($source_dir, $deploy_dir);
    }
}

// --- Verify active file exists ---
if (!file_exists($deploy_dir . '/' . $active_file)) {
    echo json_encode(['error' => "Không tìm thấy tệp chính '$active_file' trong dự án."]);
    exit;
}

// --- Build the preview URL ---
// Determine base path relative to document root
$doc_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? 'c:/xampp/htdocs');
$deploy_real = realpath($deploy_dir);

if ($doc_root && $deploy_real && strpos($deploy_real, $doc_root) === 0) {
    $relative_path = str_replace('\\', '/', substr($deploy_real, strlen($doc_root)));
    $preview_url = $relative_path . '/' . $active_file;
} else {
    // Fallback: use known path structure
    $preview_url = '/tkb/temp_runs/' . $deploy_id . '/' . $active_file;
}

// --- Read rendered HTML for srcdoc fallback ---
$html_content = null;
$active_path = $deploy_dir . '/' . $active_file;
if (file_exists($active_path) && strtolower(pathinfo($active_file, PATHINFO_EXTENSION)) === 'html') {
    $html_content = file_get_contents($active_path);
}

echo json_encode([
    'success' => true,
    'url' => $preview_url,
    'deploy_id' => $deploy_id,
    'html_content' => $html_content,
    'info' => 'Dự án đã được triển khai lên Apache thành công.'
]);

// Helper to copy binary files locally on the server
function copyBinaryFiles($src, $dst) {
    $dir = @opendir($src);
    if (!$dir) return;
    @mkdir($dst, 0777, true);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyBinaryFiles($src . '/' . $file, $dst . '/' . $file);
            } else {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $is_binary = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv']);
                if ($is_binary) {
                    @copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
    }
    closedir($dir);
}

function ensureCommonStudentFilesExist($deploy_dir) {
    $real_db_host = defined('DB_HOST') ? DB_HOST : 'sql308.infinityfree.com';
    $real_db_user = defined('DB_USER') ? DB_USER : 'if0_41796593';
    $real_db_pass = defined('DB_PASS') ? DB_PASS : 'T5v3vJeuvOxCI';
    $real_db_name = defined('DB_NAME') ? DB_NAME : 'if0_41796593_truong_caodang';

    $db_code = '<?php
// Auto-generated DB config fallback
$host = $db_host = $servername = ' . var_export($real_db_host, true) . ';
$user = $db_user = $username = ' . var_export($real_db_user, true) . ';
$pass = $db_pass = $password = ' . var_export($real_db_pass, true) . ';
$dbname = $db_name = $database = ' . var_export($real_db_name, true) . ';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db = $pdo;
} catch(Throwable $e) {}

try {
    $conn = @new mysqli($host, $user, $pass, $dbname);
    if ($conn && !$conn->connect_error) {
        $conn->set_charset("utf8mb4");
    }
} catch(Throwable $e) {}
';

    $common_db_paths = [
        'config/db.php',
        'config/database.php',
        'config/config.php',
        'db.php',
        'db_config.php',
        'database.php',
        'connect.php',
        'includes/db.php',
        'inc/db.php'
    ];

    foreach ($common_db_paths as $rel_path) {
        $full_path = $deploy_dir . '/' . $rel_path;
        if (!file_exists($full_path)) {
            @mkdir(dirname($full_path), 0777, true);
            file_put_contents($full_path, $db_code);
        }
    }

    $func_code = '<?php
// Auto-generated helper functions fallback
if (!function_exists("require_admin")) {
    function require_admin() {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        if (!isset($_SESSION["user"]) && !isset($_SESSION["admin"])) {
            $_SESSION["user"] = ["role" => "admin", "username" => "admin"];
            $_SESSION["admin"] = true;
        }
    }
}
if (!function_exists("require_login")) {
    function require_login() {
        if (session_status() === PHP_SESSION_NONE) @session_start();
    }
}
if (!function_exists("is_logged_in")) {
    function is_logged_in() { return true; }
}
if (!function_exists("redirect")) {
    function redirect($url) { header("Location: $url"); exit; }
}
if (!function_exists("sanitize")) {
    function sanitize($data) { return htmlspecialchars(trim($data)); }
}
';

    $common_func_paths = [
        'includes/functions.php',
        'inc/functions.php',
        'functions.php',
        'includes/func.php'
    ];

    foreach ($common_func_paths as $rel_path) {
        $full_path = $deploy_dir . '/' . $rel_path;
        if (!file_exists($full_path)) {
            @mkdir(dirname($full_path), 0777, true);
            file_put_contents($full_path, $func_code);
        }
    }

    $header_code = '<?php
if (session_status() === PHP_SESSION_NONE) @session_start();
if (file_exists(__DIR__ . "/../config/db.php")) include_once __DIR__ . "/../config/db.php";
elseif (file_exists(__DIR__ . "/db.php")) include_once __DIR__ . "/db.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : "Quản trị hệ thống" ?></title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; padding: 20px; background: #f8fafc; color: #1e293b; margin: 0; }
        .admin-nav { background: #1e293b; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; }
        .admin-nav a { color: #f8fafc; text-decoration: none; font-weight: 500; font-size: 14px; }
        .admin-nav a:hover { color: #38bdf8; }
    </style>
</head>
<body>
';

    $common_header_paths = [
        'includes/admin-header.php',
        'includes/header.php',
        'inc/header.php'
    ];

    foreach ($common_header_paths as $rel_path) {
        $full_path = $deploy_dir . '/' . $rel_path;
        if (!file_exists($full_path)) {
            @mkdir(dirname($full_path), 0777, true);
            file_put_contents($full_path, $header_code);
        }
    }

    $footer_code = '</body></html>';
    $common_footer_paths = [
        'includes/admin-footer.php',
        'includes/footer.php',
        'inc/footer.php'
    ];
    foreach ($common_footer_paths as $rel_path) {
        $full_path = $deploy_dir . '/' . $rel_path;
        if (!file_exists($full_path)) {
            @mkdir(dirname($full_path), 0777, true);
            file_put_contents($full_path, $footer_code);
        }
    }
}

function patchStudentDatabaseCode($content) {
    $http_host = $_SERVER['HTTP_HOST'] ?? '';
    $is_local_request = (strpos($http_host, 'localhost') !== false || strpos($http_host, '127.0.0.1') !== false || strpos($http_host, '::1') !== false);

    if ($is_local_request) {
        return $content;
    }

    // Always use the database configured for this installation.  InfinityFree
    // assigns a different SQL host to each account; localhost is a Unix socket
    // on their PHP servers and causes SQLSTATE[HY000] [2002].
    $db_host = defined('DB_HOST') ? DB_HOST : 'sql308.infinityfree.com';
    $db_user = defined('DB_USER') ? DB_USER : 'if0_41796593';
    $db_pass = defined('DB_PASS') ? DB_PASS : 'T5v3vJeuvOxCI';
    $db_name = defined('DB_NAME') ? DB_NAME : 'if0_41796593_truong_caodang';

    // Student exercises use many connection styles.  Replace literal local
    // hosts as well as the common variable/constant forms before previewing.
    $content = preg_replace('/([\'\"])(?:localhost|127\\.0\\.0\\.1)(\1)/i', '${1}' . $db_host . '${2}', $content);
    $content = preg_replace('/((?:DB_HOST|DB_SERVER|DATABASE_HOST|MYSQL_HOST)\s*=\s*)(?:localhost|127\\.0\\.0\\.1)/im', '${1}' . $db_host, $content);
    $content = preg_replace('/(mysql:\s*)unix_socket\s*=\s*[^;\'\"\s]+/i', '${1}host=' . $db_host, $content);
    $content = preg_replace('/(const\s+(?:DB_HOST|DB_SERVER|HOST)\s*=\s*[\'\"]).*?([\'\"]\s*;)/i', '${1}' . $db_host . '${2}', $content);
    $content = preg_replace('/([\'\"](?:host|hostname|db_host|server|servername)[\'\"]\s*=>\s*[\'\"]).*?([\'\"])/i', '${1}' . $db_host . '${2}', $content);

    // 1. Replace DSN host
    $content = preg_replace('/(mysql:\s*host\s*=\s*)(localhost|127\.0\.0\.1)/i', '${1}' . $db_host, $content);

    // 2. Replace dbname in PDO DSN
    $content = preg_replace('/(dbname\s*=\s*)[a-zA-Z0-9_-]+/i', '${1}' . $db_name, $content);

    // 3. Replace $host, $db_host, $servername, $hostname, etc.
    $content = preg_replace('/(\$(?:host|db_host|hostname|server|servername|dbhost|db_server|my_host|host_name)\s*=\s*[\'\"]).*?([\'\"]\s*;)/i', '${1}' . $db_host . '${2}', $content);

    // 4. Replace $db_user, $dbuser, $db_username, $username, $user when setting DB credentials
    $content = preg_replace('/(\$(?:db_user|dbuser|db_username|username|user)\s*=\s*[\'\"])(?:root|admin|localhost|[\s]*)([\'\"]\s*;)/i', '${1}' . $db_user . '${2}', $content);

    // 5. Replace $db_pass, $dbpass, $db_password, $password, $pass when setting DB credentials
    $content = preg_replace('/(\$(?:db_pass|dbpass|db_password|password|pass)\s*=\s*[\'\"])(?:root|admin|123456|pass|password|[\s]*)([\'\"]\s*;)/i', '${1}' . $db_pass . '${2}', $content);

    // 6. Replace $dbname, $db_name, $db_database (do NOT touch $database)
    $content = preg_replace('/(\$(?:dbname|db_name|db_database)\s*=\s*[\'\"]).*?([\'\"]\s*;)/i', '${1}' . $db_name . '${2}', $content);

    // 7. Replace define('DB_HOST', ...), define('DB_USER', ...), etc.
    $content = preg_replace('/(define\s*\(\s*[\'\"](?:DB_HOST|DB_SERVER|DBHOST|HOST|SERVER)[\'\"]\s*,\s*[\'\"]).*?([\'\"]\s*\);)/i', '${1}' . $db_host . '${2}', $content);
    $content = preg_replace('/(define\s*\(\s*[\'\"](?:DB_USER|DB_USERNAME|USER|USERNAME)[\'\"]\s*,\s*[\'\"]).*?([\'\"]\s*\);)/i', '${1}' . $db_user . '${2}', $content);
    $content = preg_replace('/(define\s*\(\s*[\'\"](?:DB_PASS|DB_PASSWORD|PASS|PASSWORD)[\'\"]\s*,\s*[\'\"]).*?([\'\"]\s*\);)/i', '${1}' . $db_pass . '${2}', $content);
    $content = preg_replace('/(define\s*\(\s*[\'\"](?:DB_NAME|DB_DATABASE|DBNAME|DATABASE)[\'\"]\s*,\s*[\'\"]).*?([\'\"]\s*\);)/i', '${1}' . $db_name . '${2}', $content);

    // 8. Replace ANY new mysqli(...) / mysqli_connect(...) call with hosting connection
    $content = preg_replace(
        '/(?:new\s+mysqli|mysqli_connect)\s*\([^;)]+\)/i',
        'new mysqli(' . var_export($db_host, true) . ', ' . var_export($db_user, true) . ', ' . var_export($db_pass, true) . ', ' . var_export($db_name, true) . ')',
        $content
    );

    // 9. Replace ANY new PDO(...) call with hosting connection
    $content = preg_replace(
        '/new\s+PDO\s*\([^;)]+\)/i',
        'new PDO("mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8mb4", ' . var_export($db_user, true) . ', ' . var_export($db_pass, true) . ')',
        $content
    );

    // 12. Patch CREATE DATABASE & USE statements safely (supporting all variable names, literal strings, concatenated variables, and method calls)
    $content = preg_replace('/(\$\S+?\s*->\s*(?:exec|query)\s*\(\s*[\'\"])\s*CREATE\s+DATABASE\b[^;\n\r]*;/i', '${1}SET @dummy_db = 1");', $content);
    $content = preg_replace('/(\$[a-zA-Z0-9_>-]+\s*=\s*[\'\"])\s*CREATE\s+DATABASE\b[^;\n\r]*;/i', '${1}SET @dummy_db = 1";', $content);
    $content = preg_replace('/([\'\"])\s*CREATE\s+DATABASE\b[^\'\"\n\r]*([\'\"])/i', '${1}SET @dummy_db = 1${2}', $content);

    $content = preg_replace('/(\$\S+?\s*->\s*(?:exec|query)\s*\(\s*[\'\"])\s*USE\s+[^;\n\r]*;/i', '${1}SET @dummy_use = 1");', $content);
    $content = preg_replace('/(\$[a-zA-Z0-9_>-]+\s*=\s*[\'\"])\s*USE\s+[^;\n\r]*;/i', '${1}SET @dummy_use = 1";', $content);
    $content = preg_replace('/([\'\"])\s*USE\s+[`\'\"]?[a-zA-Z0-9_-]+[`\'\"]?\s*([\'\"])/i', '${1}SET @dummy_use = 1${2}', $content);

    // 13. Auto-heal any die() or exit() connection errors cleanly
    $content = preg_replace(
        '/(?:die|exit)\s*\(\s*(?:[\'\"][^\'\"]*(?:Database|Connection|connect|kết nối|thất bại|Lỗi)[^\'\"]*[\'\"]?\s*(?:\.\s*\$\w+->(?:getMessage\(\)|connect_error))?|\$\w+->(?:getMessage\(\)|connect_error))\s*\)\s*;/i',
        'try {
            $f_h = defined("DB_HOST") ? DB_HOST : "sql308.infinityfree.com";
            $f_u = defined("DB_USER") ? DB_USER : "if0_41796593";
            $f_p = defined("DB_PASS") ? DB_PASS : "T5v3vJeuvOxCI";
            $f_d = defined("DB_NAME") ? DB_NAME : "if0_41796593_truong_caodang";
            $pdo = new PDO("mysql:host=$f_h;dbname=$f_d;charset=utf8mb4", $f_u, $f_p);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn = @new mysqli($f_h, $f_u, $f_p, $f_d);
        } catch(Throwable $ex){}',
        $content
    );

    // 14. Patch hardcoded absolute paths (/rapphim/, /mohinh/, /assets/, /css/, /js/) to relative paths
    $content = preg_replace('/(href|src|action)\s*=\s*([\'\"])\/(?:rapphim|mohinh|shop|web|caodang|tkb)\//i', '${1}=${2}', $content);
    $content = preg_replace('/(href|src|action)\s*=\s*([\'\"])\/(assets|css|js|images|img|uploads|style|styles|views|config|includes)\//i', '${1}=${2}${3}/', $content);
    $content = preg_replace('/header\s*\(\s*[\'\"]Location:\s*\/(?:rapphim|mohinh|shop|web|caodang|tkb)\/([^\'\"]+)[\'\"]\s*\)/i', 'header("Location: ${1}")', $content);
    $content = preg_replace('/(\$(?:base_url|baseUrl|URL|BASE_URL)\s*=\s*)[^;]*?\/(?:rapphim|mohinh|shop|web|caodang|tkb)\/[^;]*;/i', '${1}"./";', $content);

    return $content;
}

function autoImportStudentSqlFiles($virtual_files) {
    @mysqli_report(MYSQLI_REPORT_OFF);
    $http_host = $_SERVER['HTTP_HOST'] ?? '';
    $is_local_request = (strpos($http_host, 'localhost') !== false || strpos($http_host, '127.0.0.1') !== false || strpos($http_host, '::1') !== false);

    if ($is_local_request) {
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = '';
        $db_name = 'truong_caodang';
    } else {
        $db_host = 'sql308.infinityfree.com';
        $db_user = 'if0_41796593';
        $db_pass = 'T5v3vJeuvOxCI';
        $db_name = 'if0_41796593_truong_caodang';
    }

    try {
        $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        if (!$conn || $conn->connect_error) {
            return;
        }
        @$conn->set_charset("utf8mb4");
    } catch (Throwable $e) {
        return;
    }

    foreach ($virtual_files as $filename => $sql_content) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            continue;
        }

        if (empty($sql_content) || strlen(trim($sql_content)) < 5) {
            continue;
        }

        // Clean up SQL content:
        // Remove statements that fail on free hosting (CREATE DATABASE, USE, CREATE VIEW, CREATE PROCEDURE, etc.)
        $clean_sql = preg_replace('/CREATE\s+DATABASE\s+[^;]+;/i', '', $sql_content);
        $clean_sql = preg_replace('/USE\s+[^;]+;/i', '', $clean_sql);
        $clean_sql = preg_replace('/CREATE\s+ALGORITHM=[^;]+VIEW\s+[^;]+;/i', '', $clean_sql);
        $clean_sql = preg_replace('/CREATE\s+VIEW\s+[^;]+;/i', '', $clean_sql);
        $clean_sql = preg_replace('/CREATE\s+PROCEDURE\s+[^;]+;/i', '', $clean_sql);
        $clean_sql = preg_replace('/LOCK\s+TABLES\s+[^;]+;/i', '', $clean_sql);
        $clean_sql = preg_replace('/UNLOCK\s+TABLES\s*;/i', '', $clean_sql);

        // Statement-by-statement execution inside try-catch block for max stability
        $statements = explode(';', $clean_sql);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt) || strpos($stmt, '--') === 0 || strpos($stmt, '/*') === 0) {
                continue;
            }
            try {
                @$conn->query($stmt);
            } catch (Throwable $e) {}
        }
    }

    // Auto-create physical tables for all common student schema variations
    $auto_tables = [
        "CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL DEFAULT 'Thời trang',
            `category_name` VARCHAR(255) DEFAULT 'Thời trang',
            `ten_danhmuc` VARCHAR(255) DEFAULT 'Thời trang',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `Categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL DEFAULT 'Thời trang',
            `category_name` VARCHAR(255) DEFAULT 'Thời trang',
            `ten_danhmuc` VARCHAR(255) DEFAULT 'Thời trang',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `category_name` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL DEFAULT 'Thời trang',
            `category_name` VARCHAR(255) DEFAULT 'Thời trang',
            `ten_danhmuc` VARCHAR(255) DEFAULT 'Thời trang',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `Category_name` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL DEFAULT 'Thời trang',
            `category_name` VARCHAR(255) DEFAULT 'Thời trang',
            `ten_danhmuc` VARCHAR(255) DEFAULT 'Thời trang',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT DEFAULT 1,
            `c_id` INT DEFAULT 1,
            `name` VARCHAR(255) NOT NULL DEFAULT 'Áo thun Polo cao cấp',
            `product_name` VARCHAR(255) DEFAULT 'Áo thun Polo cao cấp',
            `ten_sanpham` VARCHAR(255) DEFAULT 'Áo thun Polo cao cấp',
            `price` DECIMAL(10,2) DEFAULT 199000.00,
            `gia` DECIMAL(10,2) DEFAULT 199000.00,
            `status` VARCHAR(50) DEFAULT 'active',
            `trangthai` VARCHAR(50) DEFAULT 'active',
            `quantity` INT DEFAULT 10,
            `soluong` INT DEFAULT 10,
            `rating` DECIMAL(3,1) DEFAULT 5.0,
            `danhgia` INT DEFAULT 5,
            `image` VARCHAR(255) DEFAULT '',
            `hinh_anh` VARCHAR(255) DEFAULT '',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `Products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT DEFAULT 1,
            `c_id` INT DEFAULT 1,
            `name` VARCHAR(255) NOT NULL DEFAULT 'Áo thun Polo cao cấp',
            `product_name` VARCHAR(255) DEFAULT 'Áo thun Polo cao cấp',
            `ten_sanpham` VARCHAR(255) DEFAULT 'Áo thun Polo cao cấp',
            `price` DECIMAL(10,2) DEFAULT 199000.00,
            `gia` DECIMAL(10,2) DEFAULT 199000.00,
            `status` VARCHAR(50) DEFAULT 'active',
            `trangthai` VARCHAR(50) DEFAULT 'active',
            `quantity` INT DEFAULT 10,
            `soluong` INT DEFAULT 10,
            `rating` DECIMAL(3,1) DEFAULT 5.0,
            `danhgia` INT DEFAULT 5,
            `image` VARCHAR(255) DEFAULT '',
            `hinh_anh` VARCHAR(255) DEFAULT '',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `product_name` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT DEFAULT 1,
            `c_id` INT DEFAULT 1,
            `name` VARCHAR(255) NOT NULL DEFAULT 'Áo thun Polo cao cấp',
            `product_name` VARCHAR(255) DEFAULT 'Áo thun Polo cao cấp',
            `ten_sanpham` VARCHAR(255) DEFAULT 'Áo thun Polo cao cấp',
            `price` DECIMAL(10,2) DEFAULT 199000.00,
            `gia` DECIMAL(10,2) DEFAULT 199000.00,
            `status` VARCHAR(50) DEFAULT 'active',
            `trangthai` VARCHAR(50) DEFAULT 'active',
            `quantity` INT DEFAULT 10,
            `soluong` INT DEFAULT 10,
            `rating` DECIMAL(3,1) DEFAULT 5.0,
            `danhgia` INT DEFAULT 5,
            `image` VARCHAR(255) DEFAULT '',
            `hinh_anh` VARCHAR(255) DEFAULT '',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `Product_name` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT DEFAULT 1,
            `c_id` INT DEFAULT 1,
            `name` VARCHAR(255) NOT NULL DEFAULT 'Áo thun Polo cao cấp',
            `product_name` VARCHAR(255) DEFAULT 'Áo thun Polo cao cấp',
            `ten_sanpham` VARCHAR(255) DEFAULT 'Áo thun Polo cao cấp',
            `price` DECIMAL(10,2) DEFAULT 199000.00,
            `gia` DECIMAL(10,2) DEFAULT 199000.00,
            `status` VARCHAR(50) DEFAULT 'active',
            `trangthai` VARCHAR(50) DEFAULT 'active',
            `quantity` INT DEFAULT 10,
            `soluong` INT DEFAULT 10,
            `rating` DECIMAL(3,1) DEFAULT 5.0,
            `danhgia` INT DEFAULT 5,
            `image` VARCHAR(255) DEFAULT '',
            `hinh_anh` VARCHAR(255) DEFAULT '',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL DEFAULT 'admin',
            `password` VARCHAR(255) NOT NULL DEFAULT '123456',
            `fullname` VARCHAR(150) DEFAULT 'Quản trị viên',
            `email` VARCHAR(150) DEFAULT 'admin@example.com',
            `role` VARCHAR(50) DEFAULT 'admin',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `Users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL DEFAULT 'admin',
            `password` VARCHAR(255) NOT NULL DEFAULT '123456',
            `fullname` VARCHAR(150) DEFAULT 'Quản trị viên',
            `email` VARCHAR(150) DEFAULT 'admin@example.com',
            `role` VARCHAR(50) DEFAULT 'admin',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) DEFAULT 'Cửa Hàng Trực Tuyến',
            `site_name` VARCHAR(255) DEFAULT 'Cửa Hàng Trực Tuyến',
            `logo` VARCHAR(255) DEFAULT 'logo.png',
            `banner` VARCHAR(255) DEFAULT 'banner.jpg',
            `phone` VARCHAR(50) DEFAULT '0901234567',
            `hotline` VARCHAR(50) DEFAULT '0901234567',
            `email` VARCHAR(100) DEFAULT 'contact@example.com',
            `address` TEXT,
            `dia_chi` TEXT,
            `facebook` VARCHAR(255) DEFAULT '',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `Settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) DEFAULT 'Cửa Hàng Trực Tuyến',
            `site_name` VARCHAR(255) DEFAULT 'Cửa Hàng Trực Tuyến',
            `logo` VARCHAR(255) DEFAULT 'logo.png',
            `banner` VARCHAR(255) DEFAULT 'banner.jpg',
            `phone` VARCHAR(50) DEFAULT '0901234567',
            `hotline` VARCHAR(50) DEFAULT '0901234567',
            `email` VARCHAR(100) DEFAULT 'contact@example.com',
            `address` TEXT,
            `dia_chi` TEXT,
            `facebook` VARCHAR(255) DEFAULT '',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `cauhinh` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) DEFAULT 'Cửa Hàng Trực Tuyến',
            `site_name` VARCHAR(255) DEFAULT 'Cửa Hàng Trực Tuyến',
            `logo` VARCHAR(255) DEFAULT 'logo.png',
            `banner` VARCHAR(255) DEFAULT 'banner.jpg',
            `phone` VARCHAR(50) DEFAULT '0901234567',
            `hotline` VARCHAR(50) DEFAULT '0901234567',
            `email` VARCHAR(100) DEFAULT 'contact@example.com',
            `address` TEXT,
            `description` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `options` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `option_name` VARCHAR(255) DEFAULT 'site_title',
            `option_value` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT DEFAULT 1,
            `customer_name` VARCHAR(150) DEFAULT 'Khách hàng',
            `phone` VARCHAR(50) DEFAULT '0901234567',
            `email` VARCHAR(100) DEFAULT 'khach@gmail.com',
            `address` TEXT,
            `total_price` DECIMAL(10,2) DEFAULT 0.00,
            `total` DECIMAL(10,2) DEFAULT 0.00,
            `status` VARCHAR(50) DEFAULT 'pending',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `carts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT DEFAULT 1,
            `product_id` INT DEFAULT 1,
            `quantity` INT DEFAULT 1,
            `price` DECIMAL(10,2) DEFAULT 0.00,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `cart` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT DEFAULT 1,
            `product_id` INT DEFAULT 1,
            `quantity` INT DEFAULT 1,
            `price` DECIMAL(10,2) DEFAULT 0.00,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "REPLACE INTO `settings` (`id`, `title`, `site_name`, `phone`, `email`, `address`) VALUES (1, 'Cửa Hàng Trực Tuyến', 'Cửa Hàng Trực Tuyến', '0901234567', 'contact@example.com', '123 Đường Hoa, TP.HCM');",
        "REPLACE INTO `Settings` (`id`, `title`, `site_name`, `phone`, `email`, `address`) VALUES (1, 'Cửa Hàng Trực Tuyến', 'Cửa Hàng Trực Tuyến', '0901234567', 'contact@example.com', '123 Đường Hoa, TP.HCM');",
        "REPLACE INTO `cauhinh` (`id`, `title`, `site_name`, `phone`, `email`, `address`) VALUES (1, 'Cửa Hàng Trực Tuyến', 'Cửa Hàng Trực Tuyến', '0901234567', 'contact@example.com', '123 Đường Hoa, TP.HCM');",

        "REPLACE INTO `categories` (`id`, `name`, `ten_danhmuc`, `category_name`) VALUES 
        (1, 'Nendoroid', 'Nendoroid', 'Nendoroid'), 
        (2, 'Scale Figure', 'Scale Figure', 'Scale Figure'), 
        (3, 'Figma', 'Figma', 'Figma'), 
        (4, 'Action Figure', 'Action Figure', 'Action Figure');",

        "REPLACE INTO `Categories` (`id`, `name`, `ten_danhmuc`, `category_name`) VALUES 
        (1, 'Nendoroid', 'Nendoroid', 'Nendoroid'), 
        (2, 'Scale Figure', 'Scale Figure', 'Scale Figure'), 
        (3, 'Figma', 'Figma', 'Figma'), 
        (4, 'Action Figure', 'Action Figure', 'Action Figure');",

        "REPLACE INTO `category_name` (`id`, `name`, `ten_danhmuc`, `category_name`) VALUES 
        (1, 'Nendoroid', 'Nendoroid', 'Nendoroid'), 
        (2, 'Scale Figure', 'Scale Figure', 'Scale Figure'), 
        (3, 'Figma', 'Figma', 'Figma'), 
        (4, 'Action Figure', 'Action Figure', 'Action Figure');",

        "REPLACE INTO `Category_name` (`id`, `name`, `ten_danhmuc`, `category_name`) VALUES 
        (1, 'Nendoroid', 'Nendoroid', 'Nendoroid'), 
        (2, 'Scale Figure', 'Scale Figure', 'Scale Figure'), 
        (3, 'Figma', 'Figma', 'Figma'), 
        (4, 'Action Figure', 'Action Figure', 'Action Figure');",

        "REPLACE INTO `products` (`id`, `category_id`, `c_id`, `name`, `ten_sanpham`, `product_name`, `price`, `gia`, `image`, `hinh_anh`) VALUES 
        (1, 1, 1, 'Nendoroid Hatsune Miku: V4X', 'Nendoroid Hatsune Miku: V4X', 'Nendoroid Hatsune Miku: V4X', 1200000, 1200000, 'miku.jpg', 'miku.jpg'),
        (2, 2, 2, 'Scale Figure Rimuru Tempest 1/7', 'Scale Figure Rimuru Tempest 1/7', 'Scale Figure Rimuru Tempest 1/7', 3500000, 3500000, 'rimuru.jpg', 'rimuru.jpg'),
        (3, 3, 3, 'Figma Tanjiro Kamado', 'Figma Tanjiro Kamado', 'Figma Tanjiro Kamado', 1800000, 1800000, 'tanjiro.jpg', 'tanjiro.jpg'),
        (4, 4, 4, 'Pop Up Parade Monkey D. Luffy - Gear 5', 'Pop Up Parade Monkey D. Luffy - Gear 5', 'Pop Up Parade Monkey D. Luffy - Gear 5', 950000, 950000, 'luffy.jpg', 'luffy.jpg'),
        (5, 2, 2, 'Scale Figure Ganyu - Genshin Impact 1/7', 'Scale Figure Ganyu - Genshin Impact 1/7', 'Scale Figure Ganyu - Genshin Impact 1/7', 4200000, 4200000, 'ganyu.jpg', 'ganyu.jpg'),
        (6, 1, 1, 'Nendoroid Naruto Uzumaki', 'Nendoroid Naruto Uzumaki', 'Nendoroid Naruto Uzumaki', 1150000, 1150000, 'naruto.jpg', 'naruto.jpg');",

        "REPLACE INTO `Products` (`id`, `category_id`, `c_id`, `name`, `ten_sanpham`, `product_name`, `price`, `gia`, `image`, `hinh_anh`) VALUES 
        (1, 1, 1, 'Nendoroid Hatsune Miku: V4X', 'Nendoroid Hatsune Miku: V4X', 'Nendoroid Hatsune Miku: V4X', 1200000, 1200000, 'miku.jpg', 'miku.jpg'),
        (2, 2, 2, 'Scale Figure Rimuru Tempest 1/7', 'Scale Figure Rimuru Tempest 1/7', 'Scale Figure Rimuru Tempest 1/7', 3500000, 3500000, 'rimuru.jpg', 'rimuru.jpg'),
        (3, 3, 3, 'Figma Tanjiro Kamado', 'Figma Tanjiro Kamado', 'Figma Tanjiro Kamado', 1800000, 1800000, 'tanjiro.jpg', 'tanjiro.jpg'),
        (4, 4, 4, 'Pop Up Parade Monkey D. Luffy - Gear 5', 'Pop Up Parade Monkey D. Luffy - Gear 5', 'Pop Up Parade Monkey D. Luffy - Gear 5', 950000, 950000, 'luffy.jpg', 'luffy.jpg'),
        (5, 2, 2, 'Scale Figure Ganyu - Genshin Impact 1/7', 'Scale Figure Ganyu - Genshin Impact 1/7', 'Scale Figure Ganyu - Genshin Impact 1/7', 4200000, 4200000, 'ganyu.jpg', 'ganyu.jpg'),
        (6, 1, 1, 'Nendoroid Naruto Uzumaki', 'Nendoroid Naruto Uzumaki', 'Nendoroid Naruto Uzumaki', 1150000, 1150000, 'naruto.jpg', 'naruto.jpg');",

        "REPLACE INTO `product_name` (`id`, `category_id`, `c_id`, `name`, `ten_sanpham`, `product_name`, `price`, `gia`, `image`, `hinh_anh`) VALUES 
        (1, 1, 1, 'Nendoroid Hatsune Miku: V4X', 'Nendoroid Hatsune Miku: V4X', 'Nendoroid Hatsune Miku: V4X', 1200000, 1200000, 'miku.jpg', 'miku.jpg'),
        (2, 2, 2, 'Scale Figure Rimuru Tempest 1/7', 'Scale Figure Rimuru Tempest 1/7', 'Scale Figure Rimuru Tempest 1/7', 3500000, 3500000, 'rimuru.jpg', 'rimuru.jpg');",

        "REPLACE INTO `Product_name` (`id`, `category_id`, `c_id`, `name`, `ten_sanpham`, `product_name`, `price`, `gia`, `image`, `hinh_anh`) VALUES 
        (1, 1, 1, 'Nendoroid Hatsune Miku: V4X', 'Nendoroid Hatsune Miku: V4X', 'Nendoroid Hatsune Miku: V4X', 1200000, 1200000, 'miku.jpg', 'miku.jpg'),
        (2, 2, 2, 'Scale Figure Rimuru Tempest 1/7', 'Scale Figure Rimuru Tempest 1/7', 'Scale Figure Rimuru Tempest 1/7', 3500000, 3500000, 'rimuru.jpg', 'rimuru.jpg');"
    ];

    foreach ($auto_tables as $q) {
        try {
            @$conn->query($q);
        } catch (Throwable $e) {}
    }

    // Auto-alter tables to guarantee missing columns are added to pre-existing tables
    $alter_queries = [
        "ALTER TABLE `categories` ADD COLUMN `category_name` VARCHAR(255) DEFAULT 'Danh mục'",
        "ALTER TABLE `categories` ADD COLUMN `ten_danhmuc` VARCHAR(255) DEFAULT 'Danh mục'",
        "ALTER TABLE `categories` ADD COLUMN `tendanhmuc` VARCHAR(255) DEFAULT 'Danh mục'",
        "ALTER TABLE `categories` ADD COLUMN `name` VARCHAR(255) DEFAULT 'Danh mục'",
        "ALTER TABLE `Categories` ADD COLUMN `category_name` VARCHAR(255) DEFAULT 'Danh mục'",
        "ALTER TABLE `Categories` ADD COLUMN `ten_danhmuc` VARCHAR(255) DEFAULT 'Danh mục'",
        "ALTER TABLE `Categories` ADD COLUMN `name` VARCHAR(255) DEFAULT 'Danh mục'",
        "ALTER TABLE `category_name` ADD COLUMN `category_name` VARCHAR(255) DEFAULT 'Danh mục'",
        "ALTER TABLE `Category_name` ADD COLUMN `category_name` VARCHAR(255) DEFAULT 'Danh mục'",

        "ALTER TABLE `products` ADD COLUMN `product_name` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `products` ADD COLUMN `ten_sanpham` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `products` ADD COLUMN `tensanpham` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `products` ADD COLUMN `name` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `products` ADD COLUMN `category_name` VARCHAR(255) DEFAULT 'Mô hình'",
        "ALTER TABLE `products` ADD COLUMN `category_id` INT DEFAULT 1",
        "ALTER TABLE `products` ADD COLUMN `c_id` INT DEFAULT 1",
        "ALTER TABLE `products` ADD COLUMN `danhmuc_id` INT DEFAULT 1",
        "ALTER TABLE `products` ADD COLUMN `price` DECIMAL(10,2) DEFAULT 199000.00",
        "ALTER TABLE `products` ADD COLUMN `gia` DECIMAL(10,2) DEFAULT 199000.00",
        "ALTER TABLE `products` ADD COLUMN `giatien` DECIMAL(10,2) DEFAULT 199000.00",
        "ALTER TABLE `products` ADD COLUMN `status` VARCHAR(50) DEFAULT 'active'",
        "ALTER TABLE `products` ADD COLUMN `trangthai` VARCHAR(50) DEFAULT 'active'",
        "ALTER TABLE `products` ADD COLUMN `quantity` INT DEFAULT 10",
        "ALTER TABLE `products` ADD COLUMN `soluong` INT DEFAULT 10",
        "ALTER TABLE `products` ADD COLUMN `rating` DECIMAL(3,1) DEFAULT 5.0",
        "ALTER TABLE `products` ADD COLUMN `danhgia` INT DEFAULT 5",

        "ALTER TABLE `Products` ADD COLUMN `product_name` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `Products` ADD COLUMN `ten_sanpham` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `Products` ADD COLUMN `name` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `Products` ADD COLUMN `category_name` VARCHAR(255) DEFAULT 'Mô hình'",
        "ALTER TABLE `Products` ADD COLUMN `category_id` INT DEFAULT 1",
        "ALTER TABLE `Products` ADD COLUMN `c_id` INT DEFAULT 1",
        "ALTER TABLE `Products` ADD COLUMN `price` DECIMAL(10,2) DEFAULT 199000.00",
        "ALTER TABLE `Products` ADD COLUMN `gia` DECIMAL(10,2) DEFAULT 199000.00",
        "ALTER TABLE `Products` ADD COLUMN `status` VARCHAR(50) DEFAULT 'active'",
        "ALTER TABLE `Products` ADD COLUMN `trangthai` VARCHAR(50) DEFAULT 'active'",
        "ALTER TABLE `Products` ADD COLUMN `quantity` INT DEFAULT 10",
        "ALTER TABLE `Products` ADD COLUMN `soluong` INT DEFAULT 10",

        "ALTER TABLE `product_name` ADD COLUMN `product_name` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `product_name` ADD COLUMN `ten_sanpham` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `product_name` ADD COLUMN `category_name` VARCHAR(255) DEFAULT 'Mô hình'",
        "ALTER TABLE `product_name` ADD COLUMN `category_id` INT DEFAULT 1",
        "ALTER TABLE `product_name` ADD COLUMN `price` DECIMAL(10,2) DEFAULT 199000.00",
        "ALTER TABLE `product_name` ADD COLUMN `status` VARCHAR(50) DEFAULT 'active'",
        "ALTER TABLE `product_name` ADD COLUMN `quantity` INT DEFAULT 10",

        "ALTER TABLE `Product_name` ADD COLUMN `product_name` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `Product_name` ADD COLUMN `ten_sanpham` VARCHAR(255) DEFAULT 'Mô hình FiguVerse'",
        "ALTER TABLE `Product_name` ADD COLUMN `category_name` VARCHAR(255) DEFAULT 'Mô hình'",
        "ALTER TABLE `Product_name` ADD COLUMN `category_id` INT DEFAULT 1",
        "ALTER TABLE `Product_name` ADD COLUMN `price` DECIMAL(10,2) DEFAULT 199000.00",
        "ALTER TABLE `Product_name` ADD COLUMN `status` VARCHAR(50) DEFAULT 'active'",
        "ALTER TABLE `Product_name` ADD COLUMN `quantity` INT DEFAULT 10"
    ];

    foreach ($alter_queries as $aq) {
        try {
            @$conn->query($aq);
        } catch (Throwable $e) {}
    }

    // Clean up any double-prefixed 'assets/images/' in image columns
    $all_product_tables = ['products', 'Products', 'product_name', 'Product_name'];
    foreach ($all_product_tables as $p_tbl) {
        $clean_img_sql = "UPDATE `$p_tbl` SET 
            `image` = REPLACE(`image`, 'assets/images/', ''),
            `hinh_anh` = REPLACE(`hinh_anh`, 'assets/images/', '')
            WHERE `image` LIKE 'assets/images/%' OR `hinh_anh` LIKE 'assets/images/%'";
        try { @$conn->query($clean_img_sql); } catch (Throwable $e) {}
    }

    // Smart Dynamic Scanner: Detect real image files in student project and auto-update empty rows
    $project_images = [];
    foreach ($virtual_files as $f_path => $f_val) {
        $f_ext = strtolower(pathinfo($f_path, PATHINFO_EXTENSION));
        if (in_array($f_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
            if (!preg_match('/logo|banner|bg|background|slide|hero|login/i', $f_path)) {
                $project_images[] = basename($f_path);
            }
        }
    }
    if (empty($project_images)) {
        foreach ($virtual_files as $f_path => $f_val) {
            $f_ext = strtolower(pathinfo($f_path, PATHINFO_EXTENSION));
            if (in_array($f_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
                $project_images[] = basename($f_path);
            }
        }
    }

    if (!empty($project_images)) {
        foreach ($all_product_tables as $p_tbl) {
            foreach ($project_images as $idx => $img_name) {
                $row_id = $idx + 1;
                $update_sql = "UPDATE `$p_tbl` SET 
                    `image` = " . var_export($img_name, true) . ", 
                    `hinh_anh` = " . var_export($img_name, true) . "
                    WHERE (`image` IS NULL OR `image` = '' OR `image` = '0') AND `id` = $row_id";
                try { @$conn->query($update_sql); } catch (Throwable $e) {}
            }
            
            // Fill any extra/unmatched empty rows with the first image
            $first_img = $project_images[0];
            $fill_sql = "UPDATE `$p_tbl` SET `image` = " . var_export($first_img, true) . ", `hinh_anh` = " . var_export($first_img, true) . " WHERE (`image` IS NULL OR `image` = '' OR `image` = '0')";
            try { @$conn->query($fill_sql); } catch (Throwable $e) {}
        }
    }

    $conn->close();
}

function autoRunStudentDatabaseInitializers($deploy_dir, $virtual_files) {
    $init_files = ['init_db.php', 'setup.php', 'install.php', 'create_db.php', 'db_init.php'];
    foreach ($init_files as $init_file) {
        $init_path = $deploy_dir . '/' . $init_file;
        if (file_exists($init_path)) {
            @include_once($init_path);
        }
    }
}
