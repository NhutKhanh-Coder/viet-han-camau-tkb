<?php
// Tu dong phat hien moi truong localhost hoac production
$isLocal = false;
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $isLocal = true;
} elseif (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' || $_SERVER['HTTP_HOST'] === '[::1]')) {
    $isLocal = true;
} elseif (php_sapi_name() === 'cli') {
    $isLocal = true;
}

if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'truong_caodang');
} else {
    define('DB_HOST', 'sql308.infinityfree.com');
    define('DB_USER', 'if0_41796593');
    define('DB_PASS', 'T5v3vJeuvOxCI');
    define('DB_NAME', 'if0_41796593_truong_caodang');
}

global $NGANH_LIST;
$NGANH_LIST = [
    'Công Nghệ Thông Tin',
    'Cơ Khí Ô Tô',
    'Điện - Điện Tử',
    'Quản Trị Doanh Nghiệp'
];

if (session_status() === PHP_SESSION_NONE) session_start();

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) die("Lỗi kết nối: " . $conn->connect_error);
    $conn->set_charset("utf8mb4");
    return $conn;
}

function isLoggedIn()  { return isset($_SESSION['user_id']); }
function isAdmin()     { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function isStudent()   { return isset($_SESSION['role']) && $_SESSION['role'] === 'student'; }
function isTeacher()   { return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'; }
function isPrincipal() { return isset($_SESSION['role']) && $_SESSION['role'] === 'principal'; }

function getDashboardUrlByRole($role) {
    switch ($role) {
        case 'admin': return '/tkb/admin/dashboard.php';
        case 'student': return '/tkb/student/dashboard.php';
        case 'teacher': return '/tkb/teacher/dashboard.php';
        case 'principal': return '/tkb/principal/dashboard.php';
        default: return '/tkb/login.php';
    }
}

function requireLogin() {
    if (!isLoggedIn()) { header('Location: /tkb/login.php'); exit(); }
}
function requireAdmin() {
    requireLogin();
    if (!isAdmin() && !isPrincipal()) { header('Location: ' . getDashboardUrlByRole($_SESSION['role'] ?? '')); exit(); }
}
function requireStudent() {
    requireLogin();
    if (!isStudent()) { header('Location: ' . getDashboardUrlByRole($_SESSION['role'] ?? '')); exit(); }
}
function requireTeacher() {
    requireLogin();
    if (!isTeacher()) { header('Location: ' . getDashboardUrlByRole($_SESSION['role'] ?? '')); exit(); }
}
function requirePrincipal() {
    requireLogin();
    if (!isPrincipal()) { header('Location: ' . getDashboardUrlByRole($_SESSION['role'] ?? '')); exit(); }
}
?>