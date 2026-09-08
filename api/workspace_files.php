<?php
/**
 * API WORKSPACE FILES ENGINE (ANTIGRAVITY / CODEX FOLDER PROCESSOR)
 * Scans, reads, searches, creates, modifies, deletes, and batch-saves workspace files & folders
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Parse input from JSON body or POST
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
if (!is_array($jsonInput)) {
    $jsonInput = [];
}

$action = $jsonInput['action'] ?? $_POST['action'] ?? $_GET['action'] ?? 'scan';
$rawProject = trim($jsonInput['project'] ?? $_POST['project'] ?? $_GET['project'] ?? 'tkb');
$projectName = str_replace(['..', '/', '\\', ':', '*', '?', '"', '<', '>', '|', "\0"], '', $rawProject);
$projectName = trim($projectName);
if (empty($projectName)) $projectName = 'tkb';

$htdocsDir = realpath(__DIR__ . '/../..');

// 0. LIST ALL AVAILABLE PROJECTS IN HTDOCS
if ($action === 'list_projects') {
    $projects = [];
    $blacklisted = [
        'webalizer', 'xampp', 'phpmyadmin', 'dashboard',
        'student', 'teacher', 'includes', 'assets', 'api', 'vendor',
        'temp_runs', 'uploads', 'scratch', 'admin', 'node_modules', 'css', 'js', 'fonts', 'images'
    ];
    if ($htdocsDir && is_dir($htdocsDir)) {
        $entries = scandir($htdocsDir);
        if ($entries) {
            foreach ($entries as $e) {
                if ($e === '.' || $e === '..') continue;
                if (is_dir($htdocsDir . DIRECTORY_SEPARATOR . $e)) {
                    if (in_array(strtolower($e), $blacklisted)) continue;
                    $projects[] = $e;
                }
            }
        }
    }
    echo json_encode([
        'status'   => 'success',
        'projects' => $projects
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$targetDir = false;
if ($htdocsDir && is_dir($htdocsDir)) {
    // Direct match
    if (is_dir($htdocsDir . DIRECTORY_SEPARATOR . $projectName)) {
        $targetDir = realpath($htdocsDir . DIRECTORY_SEPARATOR . $projectName);
    }
    
    // Case-insensitive & Unicode normalized matching
    if (!$targetDir) {
        $entries = scandir($htdocsDir);
        if ($entries) {
            foreach ($entries as $e) {
                if ($e === '.' || $e === '..') continue;
                if (!is_dir($htdocsDir . DIRECTORY_SEPARATOR . $e)) continue;

                if (strcasecmp($e, $projectName) === 0 ||
                    (function_exists('normalizer_normalize') && (
                        normalizer_normalize($e, Normalizer::FORM_C) === normalizer_normalize($projectName, Normalizer::FORM_C) ||
                        normalizer_normalize($e, Normalizer::FORM_D) === normalizer_normalize($projectName, Normalizer::FORM_D) ||
                        normalizer_normalize($e, Normalizer::FORM_C) === normalizer_normalize($projectName, Normalizer::FORM_D)
                    ))) {
                    $targetDir = realpath($htdocsDir . DIRECTORY_SEPARATOR . $e);
                    break;
                }
            }
        }
    }
}

if (!$targetDir || !is_dir($targetDir)) {
    if (is_dir(__DIR__ . '/../temp_runs/' . $projectName)) {
        $targetDir = realpath(__DIR__ . '/../temp_runs/' . $projectName);
    } elseif (is_dir(__DIR__ . '/../uploads/' . $projectName)) {
        $targetDir = realpath(__DIR__ . '/../uploads/' . $projectName);
    } else {
        $targetDir = realpath(__DIR__ . '/..');
    }
}
$baseDir = $targetDir;

$ignored = [
    'node_modules', '.git', '.vs', '.idea', 'vendor', '__pycache__',
    'uploads/cache', 'scratch', 'assets/images', 'assets/img', 'images', 'audio', 'videos'
];

$allowedExts = [
    'php', 'html', 'htm', 'js', 'css', 'json', 'sql', 'txt', 'md', 'xml',
    'py', 'c', 'cpp', 'java', 'htaccess', 'ini', 'env', 'svg', 'csv', 'ts', 'jsx', 'tsx'
];

function sanitizeRelativePath($path) {
    if (!is_string($path)) return '';
    $path = str_replace('\\', '/', trim($path));
    $path = preg_replace('/^\/+/', '', $path);
    // Disallow directory traversal
    $parts = explode('/', $path);
    $safeParts = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '' || $p === '.') continue;
        if ($p === '..') return false;
        $safeParts[] = $p;
    }
    return implode('/', $safeParts);
}

// 1. SCAN & RETURN WORKSPACE FILES
if ($action === 'scan' || $action === 'list') {
    $filesList = [];
    $maxFiles = 400;
    $maxFileSize = 800 * 1024; // 800KB per file max
    $totalSize = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if (count($filesList) >= $maxFiles) break;

        $path = $item->getPathname();
        $relPath = str_replace('\\', '/', substr($path, strlen($baseDir) + 1));

        // Skip ignored directories
        $skip = false;
        foreach ($ignored as $ig) {
            if (strpos($relPath, $ig) === 0 || strpos($relPath, '/' . $ig) !== false) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;

        if ($item->isFile()) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts)) {
                $size = $item->getSize();
                if ($size <= $maxFileSize) {
                    $content = @file_get_contents($path);
                    if ($content !== false) {
                        $filesList[] = [
                            'name'   => $item->getFilename(),
                            'path'   => $relPath,
                            'folder' => $projectName,
                            'ext'    => '.' . $ext,
                            'type'   => 'text',
                            'data'   => $content,
                            'size'   => number_format($size / 1024, 1) . ' KB'
                        ];
                        $totalSize += $size;
                    }
                }
            }
        }
    }

    echo json_encode([
        'status'     => 'success',
        'project'    => $projectName,
        'count'      => count($filesList),
        'total_size' => number_format($totalSize / 1024, 1) . ' KB',
        'files'      => $filesList
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 2. SEARCH WITHIN FILES
if ($action === 'search') {
    $query = trim($jsonInput['q'] ?? $_GET['q'] ?? $_POST['q'] ?? '');
    if (!$query) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập từ khóa tìm kiếm']);
        exit();
    }

    $matches = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $path = $item->getPathname();
            $relPath = str_replace('\\', '/', substr($path, strlen($baseDir) + 1));
            
            $skip = false;
            foreach ($ignored as $ig) {
                if (strpos($relPath, $ig) === 0 || strpos($relPath, '/' . $ig) !== false) { $skip = true; break; }
            }
            if ($skip) continue;

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts) && $item->getSize() < 400 * 1024) {
                $lines = @file($path);
                if ($lines) {
                    foreach ($lines as $lineNum => $lineContent) {
                        if (stripos($lineContent, $query) !== false) {
                            $matches[] = [
                                'file'    => $relPath,
                                'line'    => $lineNum + 1,
                                'content' => trim($lineContent)
                            ];
                            if (count($matches) >= 50) break 2;
                        }
                    }
                }
            }
        }
    }

    echo json_encode([
        'status'  => 'success',
        'query'   => $query,
        'count'   => count($matches),
        'matches' => $matches
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 3. SAVE SINGLE FILE
if ($action === 'save_file' || $action === 'create_file') {
    $filePath = trim($jsonInput['path'] ?? $_POST['path'] ?? '');
    $content = $jsonInput['content'] ?? $_POST['content'] ?? '';

    $safeRelPath = sanitizeRelativePath($filePath);
    if ($safeRelPath === false || $safeRelPath === '') {
        echo json_encode(['status' => 'error', 'message' => 'Đường dẫn tệp tin không hợp lệ']);
        exit();
    }

    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelPath);
    $dir = dirname($fullPath);

    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $res = @file_put_contents($fullPath, $content);
    if ($res !== false) {
        echo json_encode([
            'status'  => 'success',
            'message' => "Đã lưu thành công vào tệp {$safeRelPath}",
            'path'    => $safeRelPath,
            'bytes'   => $res
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => "Không thể ghi vào tệp {$safeRelPath}. Vui lòng kiểm tra quyền thư mục."
        ], JSON_UNESCAPED_UNICODE);
    }
    exit();
}

// 4. BATCH SAVE (MULTIPLE FILES)
if ($action === 'batch_save' || $action === 'save_multiple') {
    $files = $jsonInput['files'] ?? $_POST['files'] ?? [];
    if (is_string($files)) {
        $files = json_decode($files, true) ?: [];
    }

    if (!is_array($files) || count($files) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Danh sách tệp tin trống']);
        exit();
    }

    $saved = [];
    $failed = [];

    foreach ($files as $f) {
        $filePath = trim($f['path'] ?? '');
        $content = $f['content'] ?? '';

        $safeRelPath = sanitizeRelativePath($filePath);
        if ($safeRelPath === false || $safeRelPath === '') {
            $failed[] = ['path' => $filePath, 'error' => 'Đường dẫn không hợp lệ'];
            continue;
        }

        $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelPath);
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $res = @file_put_contents($fullPath, $content);
        if ($res !== false) {
            $saved[] = ['path' => $safeRelPath, 'bytes' => $res];
        } else {
            $failed[] = ['path' => $safeRelPath, 'error' => 'Không thể ghi tệp'];
        }
    }

    echo json_encode([
        'status'  => count($failed) === 0 ? 'success' : 'partial',
        'message' => 'Đã xử lý ' . count($saved) . '/' . count($files) . ' tệp tin.',
        'saved'   => $saved,
        'failed'  => $failed
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 5. CREATE FOLDER
if ($action === 'create_folder' || $action === 'mkdir') {
    $folderPath = trim($jsonInput['path'] ?? $_POST['path'] ?? '');
    $safeRelPath = sanitizeRelativePath($folderPath);
    if ($safeRelPath === false || $safeRelPath === '') {
        echo json_encode(['status' => 'error', 'message' => 'Đường dẫn thư mục không hợp lệ']);
        exit();
    }

    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelPath);
    if (is_dir($fullPath)) {
        echo json_encode(['status' => 'success', 'message' => "Thư mục {$safeRelPath} đã tồn tại", 'path' => $safeRelPath]);
        exit();
    }

    if (@mkdir($fullPath, 0777, true)) {
        echo json_encode(['status' => 'success', 'message' => "Đã tạo thư mục {$safeRelPath}", 'path' => $safeRelPath]);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Không thể tạo thư mục {$safeRelPath}"]);
    }
    exit();
}

// 6. DELETE FILE
if ($action === 'delete_file') {
    $filePath = trim($jsonInput['path'] ?? $_POST['path'] ?? '');
    $safeRelPath = sanitizeRelativePath($filePath);
    if ($safeRelPath === false || $safeRelPath === '') {
        echo json_encode(['status' => 'error', 'message' => 'Đường dẫn tệp tin không hợp lệ']);
        exit();
    }

    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelPath);
    if (file_exists($fullPath) && is_file($fullPath)) {
        if (@unlink($fullPath)) {
            echo json_encode(['status' => 'success', 'message' => "Đã xóa tệp {$safeRelPath}", 'path' => $safeRelPath]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Không thể xóa tệp {$safeRelPath}"]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => "Tệp {$safeRelPath} không tồn tại"]);
    }
    exit();
}

// 7. RENAME / MOVE FILE
if ($action === 'rename_file' || $action === 'move_file') {
    $oldPath = trim($jsonInput['old_path'] ?? $_POST['old_path'] ?? '');
    $newPath = trim($jsonInput['new_path'] ?? $_POST['new_path'] ?? '');

    $safeOld = sanitizeRelativePath($oldPath);
    $safeNew = sanitizeRelativePath($newPath);

    if (!$safeOld || !$safeNew) {
        echo json_encode(['status' => 'error', 'message' => 'Đường dẫn không hợp lệ']);
        exit();
    }

    $fullOld = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeOld);
    $fullNew = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeNew);

    if (!file_exists($fullOld)) {
        echo json_encode(['status' => 'error', 'message' => "Tệp nguồn {$safeOld} không tồn tại"]);
        exit();
    }

    $newDir = dirname($fullNew);
    if (!is_dir($newDir)) {
        @mkdir($newDir, 0777, true);
    }

    if (@rename($fullOld, $fullNew)) {
        echo json_encode(['status' => 'success', 'message' => "Đã đổi tên {$safeOld} thành {$safeNew}", 'old' => $safeOld, 'new' => $safeNew]);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Không thể đổi tên tệp {$safeOld}"]);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ']);
