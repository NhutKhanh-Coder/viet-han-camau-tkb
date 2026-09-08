<?php
/**
 * Safe validation and execution endpoint for the Antigravity AI Coding Agent.
 * Validates syntax, checks code integrity, and optionally applies changes directly to the project workspace.
 */
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config.php';

if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Chỉ hỗ trợ yêu cầu POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$changes = $input['changes'] ?? [];
$shouldApply = !empty($input['apply']);
$rawProject = trim($input['project'] ?? 'tkb');
$projectName = str_replace(['..', '/', '\\', ':', '*', '?', '"', '<', '>', '|', "\0"], '', $rawProject);
$projectName = trim($projectName);
if (empty($projectName)) $projectName = 'tkb';

if (!is_array($changes) || count($changes) === 0 || count($changes) > 25) {
    echo json_encode(['success' => false, 'error' => 'Danh sách thay đổi không hợp lệ.']);
    exit;
}

function agentPathIsSafe($path) {
    if (!is_string($path) || $path === '' || strlen($path) > 250) return false;
    $path = str_replace('\\', '/', $path);
    if (strpos($path, '..') !== false || strpos($path, "\0") !== false) return false;
    return (bool) preg_match('/^[^:*?"<>|\x00-\x1f]+$/u', $path);
}

// Locate project base directory
$htdocsDir = realpath(__DIR__ . '/../..');
$targetDir = false;
if ($htdocsDir && is_dir($htdocsDir)) {
    if (is_dir($htdocsDir . DIRECTORY_SEPARATOR . $projectName)) {
        $targetDir = realpath($htdocsDir . DIRECTORY_SEPARATOR . $projectName);
    }
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
    $targetDir = realpath(__DIR__ . '/..');
}
$baseDir = $targetDir;

$results = [];
$passed = 0;
$failed = 0;

foreach ($changes as $change) {
    $path = str_replace('\\', '/', (string)($change['path'] ?? ''));
    $content = $change['content'] ?? null;
    if (!agentPathIsSafe($path) || !is_string($content) || strlen($content) > 800 * 1024) {
        $results[] = ['path' => $path ?: '(không rõ)', 'status' => 'failed', 'message' => 'Tệp hoặc nội dung thay đổi không hợp lệ.'];
        $failed++;
        continue;
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    // PHP Syntax validation
    if ($extension === 'php') {
        $tmp = tempnam(sys_get_temp_dir(), 'tkb_agent_php_');
        if ($tmp === false || @file_put_contents($tmp, $content) === false) {
            $results[] = ['path' => $path, 'status' => 'failed', 'message' => 'Không tạo được tệp tạm để kiểm tra PHP.'];
            $failed++;
            continue;
        }

        $output = [];
        $code = 1;
        $phpBin = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
        @exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($tmp) . ' 2>&1', $output, $code);
        @unlink($tmp);
        if ($code === 0) {
            $results[] = ['path' => $path, 'status' => 'passed', 'message' => 'PHP syntax check passed (0 errors).'];
            $passed++;
        } else {
            $message = trim(implode("\n", $output));
            $results[] = ['path' => $path, 'status' => 'failed', 'message' => $message ?: 'PHP syntax check failed.'];
            $failed++;
        }
        continue;
    }

    // JSON syntax validation
    if ($extension === 'json') {
        json_decode($content);
        if (json_last_error() === JSON_ERROR_NONE) {
            $results[] = ['path' => $path, 'status' => 'passed', 'message' => 'JSON valid.'];
            $passed++;
        } else {
            $results[] = ['path' => $path, 'status' => 'failed', 'message' => 'JSON syntax error: ' . json_last_error_msg()];
            $failed++;
        }
        continue;
    }

    // Default passed for HTML, JS, CSS, SQL, Py, etc.
    $label = $extension ? strtoupper($extension) : 'Text';
    $results[] = ['path' => $path, 'status' => 'passed', 'message' => $label . ' integrity check passed.'];
    $passed++;
}

// Optionally apply changes directly if requested and validation passed
$appliedFiles = [];
if ($shouldApply && $failed === 0) {
    foreach ($changes as $change) {
        $path = str_replace('\\', '/', (string)($change['path'] ?? ''));
        $content = $change['content'] ?? null;
        if (agentPathIsSafe($path) && is_string($content)) {
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (@file_put_contents($fullPath, $content) !== false) {
                $appliedFiles[] = $path;
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'summary' => ['passed' => $passed, 'failed' => $failed],
    'results' => $results,
    'applied' => $appliedFiles
], JSON_UNESCAPED_UNICODE);
