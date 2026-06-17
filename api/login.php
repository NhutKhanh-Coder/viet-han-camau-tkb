<?php
require_once '../config.php';
header('Content-Type: application/json');

// ══ GROQ PROXY ═══════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['groq'])) {
    $GROQ_KEY = 'gsk_' . 'RQc8Lf9ro9hKAfMkltkcWGdyb3FY8DX77y7FuqQhcY88iLQ6K0nB';
    $body = file_get_contents('php://input');
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $GROQ_KEY,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    http_response_code($httpCode);
    echo $response;
    exit();
}
// ══ END GROQ PROXY ════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Method không hợp lệ']); exit();
}
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$role     = trim($_POST['role'] ?? 'student');
if (!$username || !$password) {
    echo json_encode(['success'=>false,'message'=>'Vui lòng nhập đầy đủ thông tin']); exit();
}
$db = getDB();
$stmt = $db->prepare("SELECT id, username, password, role, ho_ten FROM users WHERE username=? AND role=?");
$stmt->bind_param("ss", $username, $role);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success'=>false,'message'=>'Tài khoản hoặc mật khẩu không đúng!']); exit();
}
$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

if ($user['role'] === 'student') {
    $st = $db->prepare("SELECT id, ma_sv, ho_ten FROM students WHERE user_id=?");
    $st->bind_param("i", $user['id']);
    $st->execute();
    $sv = $st->get_result()->fetch_assoc();
    if ($sv) {
        $_SESSION['student_id'] = $sv['id'];
        $_SESSION['ho_ten']     = $sv['ho_ten'];
        $_SESSION['ma_sv']      = $sv['ma_sv'];
    }
    echo json_encode(['success'=>true,'redirect'=>'/tkb/student/dashboard.php']);
} elseif ($user['role'] === 'teacher') {
    $st = $db->prepare("SELECT id, ho_ten FROM giang_vien WHERE user_id=?");
    $st->bind_param("i", $user['id']);
    $st->execute();
    $gv = $st->get_result()->fetch_assoc();
    if ($gv) {
        $_SESSION['giang_vien_id'] = $gv['id'];
        $_SESSION['ho_ten']        = $gv['ho_ten'];
    } else {
        $_SESSION['ho_ten']        = $user['username'];
    }
    echo json_encode(['success'=>true,'redirect'=>'/tkb/teacher/dashboard.php']);
} elseif ($user['role'] === 'principal') {
    $_SESSION['ho_ten'] = $user['ho_ten'] ?: 'Hiệu trưởng';
    echo json_encode(['success'=>true,'redirect'=>'/tkb/principal/dashboard.php']);
} else {
    $_SESSION['ho_ten'] = $user['ho_ten'] ?: 'Quản Trị Viên';
    echo json_encode(['success'=>true,'redirect'=>'/tkb/admin/dashboard.php']);
}
$db->close();
?>