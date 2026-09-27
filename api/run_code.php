<?php
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length()) ob_clean();
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Lỗi PHP Server: ' . $err['message'] . ' (tại dòng ' . $err['line'] . ')']);
    }
});

ob_start();
error_reporting(0);
ini_set('display_errors', 0);
http_response_code(200);
@mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config.php';
require_once '../includes/code_access.php';

// Clear any accidental output before headers
if (ob_get_length()) ob_clean();
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');

$is_guest = !empty($_SESSION['is_guest']);
if (!isLoggedIn() && !$is_guest) {
    echo json_encode(['error' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại!']);
    exit;
}
if (!$is_guest && !isStudent() && !isTeacher() && !isAdmin()) {
    echo json_encode(['error' => 'Bạn không có quyền thực hiện hành động này.']);
    exit;
}

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Phương thức yêu cầu không hợp lệ.']);
    exit;
}

if (isTeacher() || isAdmin() || $is_guest) {
    // Teachers, admins and trial guests are allowed to run code in sandbox mode at any time
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

// Read inputs
$language = trim($_POST['language'] ?? '');
$active_file = trim($_POST['active_file'] ?? '');
$stdin = $_POST['stdin'] ?? '';
$virtual_files_json = $_POST['virtual_files'] ?? '';

$virtual_files = json_decode($virtual_files_json, true) ?: [];

if (empty($language) || empty($virtual_files) || empty($active_file)) {
    echo json_encode(['error' => 'Vui lòng cung cấp đầy đủ ngôn ngữ, tệp tin hoạt động và danh sách mã nguồn.']);
    exit;
}

// HTML/CSS/JS is executed client-side
if ($language === 'html') {
    echo json_encode([
        'stdout' => '',
        'stderr' => '',
        'time' => 0,
        'info' => 'Chạy client-side'
    ]);
    exit;
}

// Create temp directory
$temp_dir = __DIR__ . '/../temp_runs';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

$run_id = uniqid('run_', true);
$files_to_cleanup = [];
$dirs_to_cleanup = [];

// Create isolated subfolder for this run workspace
$run_subdir = $temp_dir . '/' . $run_id;
if (!mkdir($run_subdir, 0777, true)) {
    echo json_encode(['error' => 'Lỗi hệ thống: Không thể khởi tạo thư mục chạy ảo.']);
    exit;
}
$dirs_to_cleanup[] = $run_subdir;

// Write all virtual files to the workspace folder (supports subfolder paths)
foreach ($virtual_files as $filename => $content) {
    // Validate: no path traversal, allow common filename characters including spaces and parentheses
    if (preg_match('/\.\./', $filename) || preg_match('/[^a-zA-Z0-9_\.\-\/\s\(\)\[\]\+]/', $filename)) {
        continue; // skip invalid filenames
    }
    $target_path = $run_subdir . '/' . $filename;
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
            continue;
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
    } elseif (in_array($ext, ['env', 'ini', 'json', 'yaml', 'yml'], true)) {
        $content = patchStudentDatabaseCode($content);
    }
    
    file_put_contents($target_path, $content);
}

// Automatically import any SQL schema/data files included in the student project
autoImportStudentSqlFiles($virtual_files);

// Ensure all common student configuration & helper files exist (fallbacks for missing db.php, functions.php, etc.)
ensureCommonStudentFilesExist($run_subdir);

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
                        $zip->extractTo($run_subdir, $filename);
                        $target_path = $run_subdir . '/' . $filename;
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
    $source_dir = 'C:/xampp/htdocs/' . $project_name;
    $source_dir = str_replace(['..', '\\'], ['', '/'], $source_dir);
    if (is_dir($source_dir)) {
        copyBinaryFiles($source_dir, $run_subdir);
    }
}

// Ensure the active file exists in workspace
if (!file_exists($run_subdir . '/' . $active_file)) {
    echo json_encode(['error' => "Không tìm thấy tệp chạy chính '$active_file' trong không gian làm việc."]);
    exit;
}

// Auto-create _db_config.php for PHP runs (MySQL sandbox connection)
if ($language === 'php' && !file_exists($run_subdir . '/_db_config.php')) {
    $real_db_host = defined('DB_HOST') ? DB_HOST : 'sql308.infinityfree.com';
    $real_db_user = defined('DB_USER') ? DB_USER : 'if0_41796593';
    $real_db_pass = defined('DB_PASS') ? DB_PASS : 'T5v3vJeuvOxCI';
    $real_db_name = defined('DB_NAME') ? DB_NAME : 'if0_41796593_truong_caodang';

    $db_config = '<?php
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

function db_query($sql) {
    global $conn;
    if (!isset($conn) || !$conn || $conn->connect_error) return [];
    $result = @$conn->query($sql);
    if ($result === false || $result === true) return [];
    return $result->fetch_all(MYSQLI_ASSOC);
}
';
    file_put_contents($run_subdir . '/_db_config.php', $db_config);
}

// Create redirection files for input/output in the parent temp folder
$stdin_file = $temp_dir . '/' . $run_id . '.stdin';
$stdout_file = $temp_dir . '/' . $run_id . '.stdout';
$stderr_file = $temp_dir . '/' . $run_id . '.stderr';

file_put_contents($stdin_file, $stdin);
file_put_contents($stdout_file, '');
file_put_contents($stderr_file, '');

$files_to_cleanup[] = $stdin_file;
$files_to_cleanup[] = $stdout_file;
$files_to_cleanup[] = $stderr_file;

$cmd = '';
switch ($language) {
    case 'python':
        $cmd = 'python -u "' . $active_file . '"';
        break;

    case 'javascript':
        $cmd = 'node "' . $active_file . '"';
        break;

    case 'php':
        $php_bin = 'php';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && file_exists('c:\xampp\php\php.exe')) {
            $php_bin = 'c:\xampp\php\php.exe';
        }
        $cmd = '"' . $php_bin . '" "' . $active_file . '"';
        break;

    case 'c':
        $exe_name = basename($active_file, '.c') . '_bin.exe';
        $cmd = 'gcc "' . $active_file . '" -o "' . $exe_name . '" && "' . $exe_name . '"';
        break;

    case 'cpp':
        $exe_name = basename($active_file, '.cpp') . '_bin.exe';
        $cmd = 'g++ "' . $active_file . '" -o "' . $exe_name . '" && "' . $exe_name . '"';
        break;

    case 'java':
        $classname = basename($active_file, '.java');
        $cmd = 'javac -encoding utf8 "' . $active_file . '" && java ' . $classname;
        break;

    default:
        echo json_encode(['error' => 'Ngôn ngữ lập trình không được hỗ trợ trên máy chủ này.']);
        exit;
}

$descriptorspec = [
    0 => ["file", $stdin_file, "r"],
    1 => ["file", $stdout_file, "w"],
    2 => ["file", $stderr_file, "w"]
];

$start_time = microtime(true);
$timeout = 5.0; // 5 seconds execution limit

$disabled_funcs = array_map('trim', explode(',', @ini_get('disable_functions') ?: ''));
$is_proc_disabled = !function_exists('proc_open') 
    || !function_exists('proc_get_status') 
    || in_array('proc_open', $disabled_funcs) 
    || in_array('proc_get_status', $disabled_funcs);

$start_time = microtime(true);
$timeout = 5.0; // 5 seconds execution limit

if ($is_proc_disabled) {
    $cloud_result = runViaJudge0Cloud($language, $active_file, $virtual_files, $stdin);
    if ($cloud_result) {
        foreach ($files_to_cleanup as $file) { if (file_exists($file)) @unlink($file); }
        foreach ($dirs_to_cleanup as $dir) {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) { if ($object !== "." && $object !== "..") @unlink($dir . "/" . $object); }
                @rmdir($dir);
            }
        }
        echo json_encode($cloud_result);
        exit;
    }

    foreach ($files_to_cleanup as $file) { if (file_exists($file)) @unlink($file); }
    foreach ($dirs_to_cleanup as $dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) { if ($object !== "." && $object !== "..") @unlink($dir . "/" . $object); }
            @rmdir($dir);
        }
    }
    echo json_encode(['error' => 'Máy chủ hosting hiện không hỗ trợ thực thi tiến trình proc_open và không thể kết nối Cloud Runner. Vui lòng thử lại hoặc dùng XAMPP local.']);
    exit;
}

$process = null;
try {
    $process = @proc_open($cmd, $descriptorspec, $pipes, $run_subdir);
} catch (Throwable $e) {
    $process = null;
}

$timeout_reached = false;
if (is_resource($process)) {
    $running = true;
    while ($running) {
        $status = @proc_get_status($process);
        $running = $status['running'] ?? false;

        if ($running && (microtime(true) - $start_time) > $timeout) {
            $timeout_reached = true;
            break;
        }

        usleep(20000); // 20ms
    }

    if ($timeout_reached) {
        // Force terminate process tree
        $pid = $status['pid'] ?? 0;
        if ($pid > 0) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                @exec("taskkill /F /T /PID $pid 2>&1");
            } else {
                @exec("kill -9 $pid 2>&1");
            }
        }
        @proc_terminate($process);
    }
    
    @proc_close($process);
} else {
    $cloud_result = runViaJudge0Cloud($language, $active_file, $virtual_files, $stdin);
    if ($cloud_result) {
        foreach ($files_to_cleanup as $file) { if (file_exists($file)) @unlink($file); }
        foreach ($dirs_to_cleanup as $dir) {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) { if ($object !== "." && $object !== "..") @unlink($dir . "/" . $object); }
                @rmdir($dir);
            }
        }
        echo json_encode($cloud_result);
        exit;
    }

    echo json_encode(['error' => 'Lỗi hệ thống: Không thể khởi chạy trình biên dịch hoặc môi trường thực thi. Vui lòng liên hệ Admin.']);
    exit;
}

$execution_time = round((microtime(true) - $start_time) * 1000, 2); // ms

// Read stdout and stderr from files
$stdout = file_get_contents($stdout_file);
$stderr = file_get_contents($stderr_file);

if ($timeout_reached) {
    $stderr .= "\n[Hệ Thống: Quá thời gian chạy tối đa " . $timeout . " giây - Tiến trình đã bị hủy]";
}

// Fallback to Cloud Runner if local CLI command failed (e.g. 'python' not installed on server)
if (!empty($stderr) && (strpos($stderr, 'not recognized as an internal or external command') !== false || strpos($stderr, 'command not found') !== false)) {
    $cloud_result = runViaJudge0Cloud($language, $active_file, $virtual_files, $stdin);
    if ($cloud_result) {
        foreach ($files_to_cleanup as $file) { if (file_exists($file)) @unlink($file); }
        foreach ($dirs_to_cleanup as $dir) {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) { if ($object !== "." && $object !== "..") @unlink($dir . "/" . $object); }
                @rmdir($dir);
            }
        }
        echo json_encode($cloud_result);
        exit;
    }
}

// Cleanup temporary files and directories
foreach ($files_to_cleanup as $file) {
    if (file_exists($file)) {
        @unlink($file);
    }
}
foreach ($dirs_to_cleanup as $dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object !== "." && $object !== "..") {
                @unlink($dir . "/" . $object);
            }
        }
        @rmdir($dir);
    }
}

// Return execution results
echo json_encode([
    'stdout' => $stdout,
    'stderr' => $stderr,
    'time'   => $execution_time,
    'info'   => 'Hoàn thành'
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

function runViaJudge0Cloud($language, $active_file, $virtual_files, $stdin) {
    $judge0_lang_map = [
        'python'     => 71, // Python 3
        'c'          => 50, // C (GCC 9.2.0)
        'cpp'        => 54, // C++ (GCC 9.2.0)
        'c++'        => 54,
        'java'       => 62, // Java (OpenJDK 13.0.1)
        'javascript' => 63, // JavaScript (Node.js 12.14.0)
        'node'       => 63,
        'php'        => 68  // PHP 7.4.1
    ];

    $lang = strtolower($language);
    $lang_id = $judge0_lang_map[$lang] ?? null;
    if (!$lang_id) return null;

    $main_content = $virtual_files[$active_file] ?? '';
    if (empty($main_content)) {
        $active_base = basename($active_file);
        foreach ($virtual_files as $vf_path => $vf_code) {
            if ($vf_path === $active_file || basename($vf_path) === $active_base) {
                $main_content = $vf_code;
                break;
            }
        }
    }
    if (empty($main_content)) return null;

    $source_code = $main_content;
    $active_base = basename($active_file);
    
    // For Python: Inject virtual module registration for other .py files in virtual_files so imports work
    if ($lang === 'python' && count($virtual_files) > 1) {
        $injected_modules = "import sys, types\n";
        foreach ($virtual_files as $fname => $fcontent) {
            if ($fname !== $active_file && basename($fname) !== $active_base && strtolower(pathinfo($fname, PATHINFO_EXTENSION)) === 'py') {
                $mod_name = pathinfo($fname, PATHINFO_FILENAME);
                $escaped_content = str_replace(['\\', "'''"], ['\\\\', "\\'\\'\\'"], $fcontent);
                $injected_modules .= "_m_$mod_name = types.ModuleType('$mod_name')\n"
                    . "exec('''" . $escaped_content . "''', _m_$mod_name.__dict__)\n"
                    . "sys.modules['$mod_name'] = _m_$mod_name\n\n";
            }
        }
        $source_code = $injected_modules . $source_code;
    }

    // For PHP: Combine helper PHP files cleanly
    if ($lang === 'php' && count($virtual_files) > 1) {
        $combined_extra = "";
        foreach ($virtual_files as $fname => $fcontent) {
            if ($fname !== $active_file && basename($fname) !== $active_base && strtolower(pathinfo($fname, PATHINFO_EXTENSION)) === 'php') {
                $clean_content = preg_replace('/^\s*<\?(?:php)?/i', '', $fcontent);
                $clean_content = preg_replace('/\?>\s*$/i', '', $clean_content);
                $combined_extra .= "\n// --- Virtual File: $fname ---\n" . $clean_content . "\n";
            }
        }
        if (!empty($combined_extra)) {
            $source_code = preg_replace('/(?:require|include)(?:_once)?\s*\(\s*[\'\"][^\'\"]+[\'\"]\s*\)\s*;/i', '// ${0}', $source_code);
            $source_code = preg_replace('/(?:require|include)(?:_once)?\s*[\'\"][^\'\"]+[\'\"]\s*;/i', '// ${0}', $source_code);
            $source_code = preg_replace('/^\s*<\?(?:php)?/i', "<?php\n" . $combined_extra . "\n", $source_code);
        }
    }

    $stdin_payload = ($stdin !== '' && $stdin !== null) ? $stdin : "\n";

    $payload = json_encode([
        'language_id' => $lang_id,
        'source_code' => base64_encode($source_code),
        'stdin'       => base64_encode($stdin_payload)
    ]);

    $ch = @curl_init('https://ce.judge0.com/submissions?wait=true&base64_encoded=true');
    if (!$ch) return null;

    @curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 12
    ]);

    $res = @curl_exec($ch);
    $http_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
    @curl_close($ch);

    if (($http_code === 200 || $http_code === 201) && !empty($res)) {
        $data = @json_decode($res, true);
        if ($data) {
            $stdout_raw = $data['stdout'] ?? '';
            $stderr_raw = $data['stderr'] ?? ($data['compile_output'] ?? '');
            
            $stdout = !empty($stdout_raw) ? @base64_decode($stdout_raw) : '';
            if ($stdout === false) $stdout = $stdout_raw;

            $stderr = !empty($stderr_raw) ? @base64_decode($stderr_raw) : '';
            if ($stderr === false) $stderr = $stderr_raw;

            $time = floatval($data['time'] ?? 0) * 1000;
            return [
                'stdout' => $stdout,
                'stderr' => $stderr,
                'time'   => round($time, 2),
                'info'   => 'Hoàn thành (Cloud Runner)'
            ];
        }
    }
    return null;
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
    // hosts as well as the common variable/constant forms before execution.
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
