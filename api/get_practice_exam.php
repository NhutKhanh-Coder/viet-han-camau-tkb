<?php
require_once '../config.php';
require_once '../includes/code_access.php';
requireStudent();

$session_id = intval($_GET['session_id'] ?? 0);
$sv_id = $_SESSION['student_id'] ?? 0;

if ($session_id <= 0 || $sv_id <= 0) {
    http_response_code(400);
    exit('Yêu cầu không hợp lệ.');
}

$db = getDB();
$stmt = $db->prepare("SELECT lop FROM students WHERE id = ?");
$stmt->bind_param("i", $sv_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();
$lop = $student['lop'] ?? '';

$session = getValidatedActivePracticeSession($db, $sv_id, $session_id, $lop);
$db->close();

if (!$session) {
    http_response_code(403);
    exit('Bạn chỉ xem được đề khi phiên thi/kiểm tra đang diễn ra.');
}

if (empty($session['de_file_path'])) {
    http_response_code(404);
    exit('Phiên này chưa có tệp đề bài.');
}

$disk_path = '..' . str_replace('/tkb', '', $session['de_file_path']);
if (!is_file($disk_path)) {
    http_response_code(404);
    exit('Không tìm thấy tệp đề trên máy chủ.');
}

$filename = $session['de_file_name'] ?: basename($disk_path);
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$mime_map = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt' => 'text/plain; charset=utf-8',
    'zip' => 'application/zip',
    'rar' => 'application/vnd.rar',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];

$mime = $mime_map[$extension] ?? 'application/octet-stream';
if ($mime === 'application/octet-stream' && function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = finfo_file($finfo, $disk_path);
        finfo_close($finfo);
        if ($detected) {
            $mime = $detected;
        }
    }
}

$inline_types = ['application/pdf', 'text/plain; charset=utf-8', 'image/jpeg', 'image/png'];
$disposition = in_array($mime, $inline_types, true) ? 'inline' : 'attachment';
$safe_name = preg_replace('/[^\w\.\-\s]/u', '_', $filename);

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . $safe_name . '"');
header('Content-Length: ' . filesize($disk_path));
header('Cache-Control: private, no-store');
readfile($disk_path);
exit;
