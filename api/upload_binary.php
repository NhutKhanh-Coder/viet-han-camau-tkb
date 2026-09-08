<?php
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Lỗi upload binary: ' . $err['message']]);
    }
});

ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../config.php';
require_once '../includes/code_access.php';

if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Phiên đăng nhập đã hết hạn.']);
    exit;
}

$deploy_id = trim($_POST['deploy_id'] ?? $_GET['deploy_id'] ?? '');
if (empty($deploy_id) || !preg_match('/^web_[a-zA-Z0-9\._]+$/', $deploy_id)) {
    echo json_encode(['error' => 'Mã triển khai không hợp lệ.']);
    exit;
}

$deploy_dir = __DIR__ . '/../temp_runs/' . $deploy_id;
if (!is_dir($deploy_dir)) {
    echo json_encode(['error' => 'Thư mục triển khai không tồn tại.']);
    exit;
}

$files_json = $_POST['binary_files'] ?? '';
$files = json_decode($files_json, true) ?: [];

$saved = 0;
foreach ($files as $filename => $content) {
    if (preg_match('/\.\./', $filename)) continue;
    $target_path = $deploy_dir . '/' . $filename;
    $target_dir = dirname($target_path);
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (strpos($content, 'data:') === 0) {
        if (strpos($content, ';base64,') !== false) {
            $parts = explode(';base64,', $content);
            $content = base64_decode($parts[1]);
        } else {
            $parts = explode(',', $content, 2);
            $content = isset($parts[1]) ? urldecode($parts[1]) : $content;
        }
    }
    file_put_contents($target_path, $content);
    $saved++;
}

echo json_encode(['success' => true, 'saved' => $saved]);
