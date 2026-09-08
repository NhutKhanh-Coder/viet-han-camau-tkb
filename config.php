<?php
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
date_default_timezone_set('Asia/Ho_Chi_Minh');
@mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/includes/anti_ddos.php';
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

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '813774355776-3t4nij3dpskdnmi4ucrlouhiv41uqftb.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', ($isLocal ? 'http://localhost/tkb/google_auth.php' : 'https://viethan.free.nf/tkb/google_auth.php'));

// GitHub OAuth Configuration
define('GITHUB_CLIENT_ID', 'Ov23lirrDthodSFrWyJz');
define('GITHUB_CLIENT_SECRET', '213dc308a98f7aa7a248c17da9b77fac8f8ae9fa');
define('GITHUB_REDIRECT_URI', ($isLocal ? 'http://localhost/tkb/github_auth.php' : 'https://viethan.free.nf/tkb/github_auth.php'));


global $NGANH_LIST;
$NGANH_LIST = [
    'Công Nghệ Thông Tin',
    'Cơ Khí Ô Tô',
    'Điện - Điện Tử',
    'Quản Trị Doanh Nghiệp'
];

if (session_status() === PHP_SESSION_NONE) {
    session_name('TKB_SESSID');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'path' => '/tkb',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(0, '/tkb', '', false, true);
    }
    session_start();
}

function checkAndAutoSyncDB($conn) {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    
    try {
        // Check if main table 'students' exists
        $res = @$conn->query("SHOW TABLES LIKE 'students'");
        if (!$res || $res->num_rows === 0) {
            $sql_file = __DIR__ . '/api/db_sync_data.sql';
            if (file_exists($sql_file)) {
                $sql_content = file_get_contents($sql_file);
                $sql_content = preg_replace('/CREATE\s+DATABASE\s+[^;]+;/i', '', $sql_content);
                $sql_content = preg_replace('/USE\s+[^;]+;/i', '', $sql_content);
                $sql_content = preg_replace('/LOCK\s+TABLES\s+[^;]+;/i', '', $sql_content);
                $sql_content = preg_replace('/UNLOCK\s+TABLES\s*;/i', '', $sql_content);
                $statements = explode(';', $sql_content);
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if (!empty($stmt)) {
                        @$conn->query($stmt);
                    }
                }
            }
        } else {
            // Ensure columns exist for user persistence safely
            $colCheck = @$conn->query("SHOW COLUMNS FROM students LIKE 'banner'");
            if (!$colCheck || $colCheck->num_rows === 0) {
                @$conn->query("ALTER TABLE students ADD COLUMN `banner` LONGTEXT DEFAULT NULL");
            } else {
                @$conn->query("ALTER TABLE students MODIFY COLUMN `banner` LONGTEXT DEFAULT NULL");
            }

            $colCheck2 = @$conn->query("SHOW COLUMNS FROM students LIKE 'tiktok_video'");
            if (!$colCheck2 || $colCheck2->num_rows === 0) {
                @$conn->query("ALTER TABLE students ADD COLUMN `tiktok_video` LONGTEXT DEFAULT NULL");
            } else {
                @$conn->query("ALTER TABLE students MODIFY COLUMN `tiktok_video` LONGTEXT DEFAULT NULL");
            }

            $colCheck3 = @$conn->query("SHOW COLUMNS FROM students LIKE 'banner_pos'");
            if ($colCheck3 && $colCheck3->num_rows === 0) {
                @$conn->query("ALTER TABLE students ADD COLUMN `banner_pos` VARCHAR(50) DEFAULT 'center center'");
            }

            $colCheck4 = @$conn->query("SHOW COLUMNS FROM students LIKE 'banner_fit'");
            if ($colCheck4 && $colCheck4->num_rows === 0) {
                @$conn->query("ALTER TABLE students ADD COLUMN `banner_fit` VARCHAR(50) DEFAULT 'cover'");
            }

            // Bổ sung cột google_id và github_id cho bảng students
            $colG = @$conn->query("SHOW COLUMNS FROM students LIKE 'google_id'");
            if ($colG && $colG->num_rows === 0) {
                @$conn->query("ALTER TABLE students ADD COLUMN `google_id` VARCHAR(100) DEFAULT NULL");
            }

            $colGit = @$conn->query("SHOW COLUMNS FROM students LIKE 'github_id'");
            if ($colGit && $colGit->num_rows === 0) {
                @$conn->query("ALTER TABLE students ADD COLUMN `github_id` VARCHAR(100) DEFAULT NULL");
            }

            // Bổ sung cột google_id và github_id cho bảng users
            $colUG = @$conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
            if ($colUG && $colUG->num_rows === 0) {
                @$conn->query("ALTER TABLE users ADD COLUMN `google_id` VARCHAR(100) DEFAULT NULL");
            }

            $colUGit = @$conn->query("SHOW COLUMNS FROM users LIKE 'github_id'");
            if ($colUGit && $colUGit->num_rows === 0) {
                @$conn->query("ALTER TABLE users ADD COLUMN `github_id` VARCHAR(100) DEFAULT NULL");
            }

            $colUF = @$conn->query("SHOW COLUMNS FROM users LIKE 'facebook_id'");
            if ($colUF && $colUF->num_rows === 0) {
                @$conn->query("ALTER TABLE users ADD COLUMN `facebook_id` VARCHAR(100) DEFAULT NULL");
            }

            // Bổ sung cột status và sub_role cho bảng users
            $colStat = @$conn->query("SHOW COLUMNS FROM users LIKE 'status'");
            if ($colStat && $colStat->num_rows === 0) {
                @$conn->query("ALTER TABLE users ADD COLUMN `status` ENUM('active', 'locked') DEFAULT 'active'");
            }
            $colSubRole = @$conn->query("SHOW COLUMNS FROM users LIKE 'sub_role'");
            if ($colSubRole && $colSubRole->num_rows === 0) {
                @$conn->query("ALTER TABLE users ADD COLUMN `sub_role` VARCHAR(100) DEFAULT NULL");
            }

            // Bổ sung cột vai_tro cho bảng students
            $colStRole = @$conn->query("SHOW COLUMNS FROM students LIKE 'vai_tro'");
            if ($colStRole && $colStRole->num_rows === 0) {
                @$conn->query("ALTER TABLE students ADD COLUMN `vai_tro` VARCHAR(100) DEFAULT 'Sinh viên'");
            }

            // Bổ sung cột chuc_vu cho bảng giang_vien
            $colGvRole = @$conn->query("SHOW COLUMNS FROM giang_vien LIKE 'chuc_vu'");
            if ($colGvRole && $colGvRole->num_rows === 0) {
                @$conn->query("ALTER TABLE giang_vien ADD COLUMN `chuc_vu` VARCHAR(100) DEFAULT 'Giảng viên bộ môn'");
            }

            // Khởi tạo bảng student_code_storage cho tính năng Kho Lưu Trữ Code Sinh Viên
            @$conn->query("CREATE TABLE IF NOT EXISTS `student_code_storage` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `student_id` INT NOT NULL,
                `ten_du_an` VARCHAR(255) NOT NULL,
                `ngon_ngu` VARCHAR(50) NOT NULL DEFAULT 'python',
                `mo_ta` TEXT DEFAULT NULL,
                `ma_nguon` LONGTEXT NOT NULL,
                `la_cong_khai` TINYINT(1) DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY (`student_id`),
                KEY (`ngon_ngu`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $svTableCheck = @$conn->query("SHOW TABLES LIKE 'sinh_vien'");
            if ($svTableCheck && $svTableCheck->num_rows > 0) {
                $c1 = @$conn->query("SHOW COLUMNS FROM sinh_vien LIKE 'banner'");
                if (!$c1 || $c1->num_rows === 0) {
                    @$conn->query("ALTER TABLE sinh_vien ADD COLUMN `banner` LONGTEXT DEFAULT NULL");
                } else {
                    @$conn->query("ALTER TABLE sinh_vien MODIFY COLUMN `banner` LONGTEXT DEFAULT NULL");
                }
                $c2 = @$conn->query("SHOW COLUMNS FROM sinh_vien LIKE 'tiktok_video'");
                if (!$c2 || $c2->num_rows === 0) {
                    @$conn->query("ALTER TABLE sinh_vien ADD COLUMN `tiktok_video` LONGTEXT DEFAULT NULL");
                } else {
                    @$conn->query("ALTER TABLE sinh_vien MODIFY COLUMN `tiktok_video` LONGTEXT DEFAULT NULL");
                }
            }

            // Bổ sung bảng & cột cho Thông báo & Lịch thi
            try {
                @$conn->query("CREATE TABLE IF NOT EXISTS `system_settings` (
                    `setting_key` VARCHAR(100) PRIMARY KEY,
                    `setting_value` TEXT DEFAULT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $chkSetting = @$conn->query("SELECT setting_value FROM system_settings WHERE setting_key='schedule_mode' LIMIT 1");
                if (!$chkSetting || $chkSetting->num_rows === 0) {
                    @$conn->query("INSERT INTO system_settings (setting_key, setting_value) VALUES ('schedule_mode', 'lich_hoc')");
                }

                $tbCheck = @$conn->query("SHOW TABLES LIKE 'thong_bao'");
                if ($tbCheck && $tbCheck->num_rows > 0) {
                    $cLoai = @$conn->query("SHOW COLUMNS FROM thong_bao LIKE 'loai'");
                    if (!$cLoai || $cLoai->num_rows === 0) {
                        @$conn->query("ALTER TABLE thong_bao ADD COLUMN `loai` VARCHAR(50) DEFAULT 'Thông báo'");
                    }
                    $cGv = @$conn->query("SHOW COLUMNS FROM thong_bao LIKE 'giang_vien_id'");
                    if (!$cGv || $cGv->num_rows === 0) {
                        @$conn->query("ALTER TABLE thong_bao ADD COLUMN `giang_vien_id` INT DEFAULT 0");
                    }
                    $cTacGia = @$conn->query("SHOW COLUMNS FROM thong_bao LIKE 'tac_gia'");
                    if (!$cTacGia || $cTacGia->num_rows === 0) {
                        @$conn->query("ALTER TABLE thong_bao ADD COLUMN `tac_gia` VARCHAR(100) DEFAULT 'Giảng viên'");
                    }
                }

                // Khởi tạo bảng quiz_exam_codes cho tính năng Tạo mã đề & Trộn câu hỏi
                @$conn->query("CREATE TABLE IF NOT EXISTS `quiz_exam_codes` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `quiz_id` INT NOT NULL,
                    `ma_de` VARCHAR(50) NOT NULL,
                    `title` VARCHAR(255) DEFAULT NULL,
                    `question_count` INT NOT NULL DEFAULT 0,
                    `matrix_data` LONGTEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY (`quiz_id`),
                    KEY (`ma_de`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                // Thêm cột ma_de cho bảng quiz_attempts để lưu mã đề thi sinh viên đã làm
                $qaTableCheck = @$conn->query("SHOW TABLES LIKE 'quiz_attempts'");
                if ($qaTableCheck && $qaTableCheck->num_rows > 0) {
                    $cMaDe = @$conn->query("SHOW COLUMNS FROM quiz_attempts LIKE 'ma_de'");
                    if (!$cMaDe || $cMaDe->num_rows === 0) {
                        @$conn->query("ALTER TABLE quiz_attempts ADD COLUMN `ma_de` VARCHAR(50) DEFAULT NULL");
                    }
                }

                // Đảm bảo Super Admin: Lê Nhựt Khánh
                $pwAdmin = password_hash('admin123', PASSWORD_DEFAULT);
                $chkUserAdmin = @$conn->query("SELECT id, password, role, ho_ten FROM users WHERE username='admin' LIMIT 1");
                if ($chkUserAdmin && $chkUserAdmin->num_rows > 0) {
                    $rowAdm = $chkUserAdmin->fetch_assoc();
                    $updateFields = [];
                    if ($rowAdm['role'] !== 'admin') $updateFields[] = "role='admin'";
                    if (empty($rowAdm['ho_ten']) || $rowAdm['ho_ten'] === 'Quản Trị Viên Hệ Thống') $updateFields[] = "ho_ten='Lê Nhựt Khánh'";
                    if (!empty($updateFields)) {
                        @$conn->query("UPDATE users SET " . implode(', ', $updateFields) . " WHERE username='admin'");
                    }
                } else {
                    @$conn->query("INSERT INTO users (username, password, role, ho_ten) VALUES ('admin', '$pwAdmin', 'admin', 'Lê Nhựt Khánh')");
                }

                // Tự động kiểm tra và khởi tạo tài khoản Sub-Admin: Phan Ngọc Tuyền (dưới quyền Lê Nhựt Khánh)
                $pwTuyen = password_hash('123456', PASSWORD_DEFAULT);
                $chkTuyen = @$conn->query("SELECT id, password, role, avatar FROM users WHERE username='phanngoctuyen' LIMIT 1");
                if ($chkTuyen && $chkTuyen->num_rows > 0) {
                    $rowTuyen = $chkTuyen->fetch_assoc();
                    $upTuyen = [];
                    if ($rowTuyen['role'] !== 'admin') $upTuyen[] = "role='admin'";
                    if (!password_verify('123456', $rowTuyen['password'])) $upTuyen[] = "password='$pwTuyen'";
                    if (empty($rowTuyen['avatar']) || strpos($rowTuyen['avatar'], 'avatar_khanh') !== false) $upTuyen[] = "avatar='/tkb/assets/img/avatar_tuyen.jpg'";
                    $upTuyen[] = "ho_ten='Phan Ngọc Tuyền'";
                    $upTuyen[] = "email='phanngoctuyen@vkc.edu.vn'";
                    @$conn->query("UPDATE users SET " . implode(', ', $upTuyen) . " WHERE username='phanngoctuyen'");
                } else {
                    @$conn->query("INSERT INTO users (username, password, role, ho_ten, email, avatar) VALUES ('phanngoctuyen', '$pwTuyen', 'admin', 'Phan Ngọc Tuyền', 'phanngoctuyen@vkc.edu.vn', '/tkb/assets/img/avatar_tuyen.jpg')");
                }
            } catch (Throwable $e) {}
        }
    } catch (Throwable $e) {}
}

function getSystemSetting($key, $default = '', $db_conn = null) {
    try {
        $created_conn = false;
        if (!$db_conn) {
            $db_conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($db_conn->connect_error) return $default;
            $db_conn->set_charset("utf8mb4");
            $created_conn = true;
        }
        $key_esc = $db_conn->real_escape_string($key);
        $res = @$db_conn->query("SELECT setting_value FROM system_settings WHERE setting_key='$key_esc' LIMIT 1");
        $val = $default;
        if ($res && method_exists($res, 'fetch_assoc') && ($row = $res->fetch_assoc())) {
            $val = $row['setting_value'];
        }
        if ($created_conn) @$db_conn->close();
        return $val;
    } catch (Throwable $e) {
        return $default;
    }
}

function setSystemSetting($key, $value, $db_conn = null) {
    try {
        $created_conn = false;
        if (!$db_conn) {
            $db_conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($db_conn->connect_error) return false;
            $db_conn->set_charset("utf8mb4");
            $created_conn = true;
        }
        $key_esc = $db_conn->real_escape_string($key);
        $val_esc = $db_conn->real_escape_string($value);
        @$db_conn->query("INSERT INTO system_settings (setting_key, setting_value) VALUES ('$key_esc', '$val_esc') ON DUPLICATE KEY UPDATE setting_value='$val_esc'");
        if ($created_conn) @$db_conn->close();
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function getDB() {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) die("Lỗi kết nối CSDL: " . $conn->connect_error);
    $conn->set_charset("utf8mb4");
    checkAndAutoSyncDB($conn);
    return $conn;
}

function isLoggedIn()  { return isset($_SESSION['user_id']) || isset($_SESSION['student_id']); }
function isAdmin()     { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function isSuperAdmin() {
    if (!isAdmin()) return false;
    $un = strtolower($_SESSION['username'] ?? '');
    $name = mb_strtolower($_SESSION['ho_ten'] ?? '', 'UTF-8');
    $uid = (int)($_SESSION['user_id'] ?? 0);
    return ($uid === 1 || $un === 'admin' || strpos($name, 'nhựt khánh') !== false || strpos($name, 'nhut khanh') !== false);
}
function isSubAdmin()   { return isAdmin() && !isSuperAdmin(); }
function isStudent()   { return (isset($_SESSION['role']) && $_SESSION['role'] === 'student') || isset($_SESSION['student_id']); }
function isStudentLoggedIn() { return isStudent() || isLoggedIn(); }
function isTeacher()   { return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'; }


function getDashboardUrlByRole($role) {
    switch ($role) {
        case 'admin':   return '/tkb/admin/dashboard.php';
        case 'teacher': return '/tkb/teacher/dashboard.php';
        case 'student': return '/tkb/student/dashboard.php';
        default: return '/tkb/login.php';
    }
}

function requireLogin() {
    if (!isLoggedIn()) { header('Location: /tkb/login.php'); exit(); }
}
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) { header('Location: ' . getDashboardUrlByRole($_SESSION['role'] ?? '')); exit(); }
}
function requireStudent() {
    requireLogin();
    if (!isStudent()) { header('Location: ' . getDashboardUrlByRole($_SESSION['role'] ?? '')); exit(); }
}
function requireTeacher() {
    requireLogin();
    if (!isTeacher() && !isAdmin()) { header('Location: ' . getDashboardUrlByRole($_SESSION['role'] ?? '')); exit(); }
}

function writeSystemLog($action) {
    try {
        $db = getDB();
        $uid = (int)($_SESSION['user_id'] ?? 0);
        $username = $db->real_escape_string($_SESSION['username'] ?? 'Guest');
        $act_esc  = $db->real_escape_string($action);
        $ip       = $db->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        @$db->query("INSERT INTO system_logs (user_id, username, hanh_dong, ip_address) VALUES ($uid, '$username', '$act_esc', '$ip')");
    } catch (Throwable $e) {}
}
?>