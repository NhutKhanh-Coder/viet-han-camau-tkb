<?php
// Remote Database Auto-Importer / Sync for InfinityFree
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config.php';

$sql_file = __DIR__ . '/db_sync_data.sql';
if (!file_exists($sql_file)) {
    die(json_encode(['error' => 'Khong tim thay file db_sync_data.sql']));
}

$db_host = DB_HOST;
$db_user = DB_USER;
$db_pass = DB_PASS;
$db_name = DB_NAME;

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Lỗi kết nối CSDL: ' . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");

$sql_content = file_get_contents($sql_file);

// Clean up database statements incompatible with InfinityFree
$sql_content = preg_replace('/CREATE\s+DATABASE\s+[^;]+;/i', '', $sql_content);
$sql_content = preg_replace('/USE\s+[^;]+;/i', '', $sql_content);
$sql_content = preg_replace('/LOCK\s+TABLES\s+[^;]+;/i', '', $sql_content);
$sql_content = preg_replace('/UNLOCK\s+TABLES\s*;/i', '', $sql_content);

$statements = explode(';', $sql_content);
$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    
    if ($conn->query($stmt)) {
        $success_count++;
    } else {
        $error_count++;
        $errors[] = substr($stmt, 0, 60) . " -> " . $conn->error;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Cơ sở dữ liệu đã được nạp & đồng bộ hoàn toàn lên InfinityFree!',
    'executed_statements' => $success_count,
    'failed_statements' => $error_count,
    'sample_errors' => array_slice($errors, 0, 5)
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
