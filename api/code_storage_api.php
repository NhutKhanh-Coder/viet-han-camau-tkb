<?php
error_reporting(0);
ini_set('display_errors', '0');
@ini_set('upload_max_filesize', '64M');
@ini_set('post_max_size', '64M');
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '120');
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện']);
    exit();
}

$db = getDB();

// Đảm bảo bảng student_code_storage luôn tồn tại
try {
    @$db->query("CREATE TABLE IF NOT EXISTS `student_code_storage` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT NOT NULL,
        `ten_du_an` VARCHAR(255) NOT NULL,
        `ngon_ngu` VARCHAR(50) NOT NULL DEFAULT 'python',
        `mo_ta` TEXT DEFAULT NULL,
        `ma_nguon` LONGTEXT NOT NULL,
        `github_url` VARCHAR(500) DEFAULT NULL,
        `la_cong_khai` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY (`student_id`),
        KEY (`ngon_ngu`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}

// Bảng lưu từng file riêng lẻ (vượt qua giới hạn nginx 1MB)
try {
    @$db->query("CREATE TABLE IF NOT EXISTS `student_code_files` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `storage_id` INT NOT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `file_content` LONGTEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_file` (`storage_id`, `file_path`),
        KEY (`storage_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}

$student_id = (int)($_SESSION['student_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($student_id <= 0 && $user_id > 0) {
    $res_st = $db->query("SELECT id FROM students WHERE user_id = $user_id LIMIT 1");
    if ($res_st && ($r = $res_st->fetch_assoc())) {
        $student_id = (int)$r['id'];
        $_SESSION['student_id'] = $student_id;
    } else {
        $student_id = $user_id;
    }
}

if ($student_id <= 0) {
    $student_id = (int)($_SESSION['user_id'] ?? 1);
}

// Đảm bảo cấu trúc cột luôn đầy đủ và lưu được dung lượng
    try { @$db->query("ALTER TABLE `student_code_storage` ADD `github_url` VARCHAR(500) DEFAULT NULL"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` ADD `banner_img` LONGTEXT DEFAULT NULL"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` ADD `la_cong_khai` TINYINT(1) DEFAULT 1"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` ADD `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` ADD `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` MODIFY COLUMN `ma_nguon` LONGTEXT NOT NULL"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` MODIFY COLUMN `mo_ta` TEXT NULL"); } catch(Exception $e){}

$action = $_REQUEST['action'] ?? 'list';

if ($action === 'get_thumb') {
    $id = (int)($_GET['storage_id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) exit();

    try {
        $bres = $db->query("SELECT banner_img FROM student_code_storage WHERE id = $id LIMIT 1");
        if ($bres && ($brow = $bres->fetch_assoc()) && !empty($brow['banner_img'])) {
            $imgData = trim($brow['banner_img']);
            if (strpos($imgData, 'data:') === 0) {
                $mime = 'image/jpeg';
                if (preg_match('/data:image\/([a-zA-Z\+]+);base64/', $imgData, $m)) {
                    $mime = 'image/' . $m[1];
                }
                header("Content-Type: $mime");
                $parts = explode(',', $imgData, 2);
                echo base64_decode($parts[1] ?? '');
                exit();
            } else if (strlen($imgData) > 50) {
                header('Content-Type: image/jpeg');
                echo base64_decode($imgData);
                exit();
            }
        }

        $fres = $db->query("SELECT file_path, file_content FROM student_code_files WHERE storage_id = $id AND (file_path LIKE '%.jpg' OR file_path LIKE '%.jpeg' OR file_path LIKE '%.png' OR file_path LIKE '%.webp' OR file_path LIKE '%.gif') ORDER BY id ASC LIMIT 1");
        if ($fres && ($fr = $fres->fetch_assoc()) && !empty($fr['file_content'])) {
            $imgData = trim($fr['file_content']);
            $ext = strtolower(pathinfo($fr['file_path'], PATHINFO_EXTENSION));
            $mime = 'image/jpeg';
            if ($ext === 'png') $mime = 'image/png';
            else if ($ext === 'gif') $mime = 'image/gif';
            else if ($ext === 'webp') $mime = 'image/webp';

            header("Content-Type: $mime");
            if (strpos($imgData, 'data:') === 0) {
                $parts = explode(',', $imgData, 2);
                echo base64_decode($parts[1] ?? '');
            } else {
                echo base64_decode($imgData);
            }
            exit();
        }
    } catch (Throwable $e) {}
    exit();
}

if ($action === 'list') {
    $ngon_ngu = trim($_GET['ngon_ngu'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $where_ids = array_unique(array_filter([(int)$student_id, (int)$user_id, 1]));
    $where_sql = "student_id IN (" . implode(',', $where_ids) . ")";

    $sql = "SELECT id, student_id, ten_du_an, ngon_ngu, mo_ta, github_url, banner_img, la_cong_khai, created_at, updated_at, LEFT(ma_nguon, 300) as preview_code, CHAR_LENGTH(ma_nguon) as code_length FROM student_code_storage WHERE $where_sql";

    if (!empty($ngon_ngu) && $ngon_ngu !== 'all') {
        $ngon_ngu_esc = $db->real_escape_string($ngon_ngu);
        $sql .= " AND ngon_ngu = '$ngon_ngu_esc'";
    }

    if (!empty($search)) {
        $search_esc = $db->real_escape_string($search);
        $sql .= " AND (ten_du_an LIKE '%$search_esc%' OR mo_ta LIKE '%$search_esc%' OR ma_nguon LIKE '%$search_esc%')";
    }

    $sql .= " ORDER BY id DESC";

    $result = $db->query($sql);
    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $id = (int)$row['id'];
            $file_names = [];
            $raw = trim($row['preview_code']);

            if (strpos($raw, '{') === 0 || strpos($raw, '"{') === 0 || strpos($raw, "{\n") === 0 || strpos($raw, "{\r") === 0) {
                preg_match_all('/"([^"]+\\.[a-zA-Z0-9]+)"\s*:/', $raw, $matches);
                if (!empty($matches[1])) {
                    $file_names = array_values(array_unique($matches[1]));
                }
            }

            $has_image = !empty($row['banner_img']);
            try {
                $fres = $db->query("SELECT file_path FROM student_code_files WHERE storage_id = $id");
                if ($fres) {
                    while ($fr = $fres->fetch_assoc()) {
                        if (!in_array($fr['file_path'], $file_names)) {
                            $file_names[] = $fr['file_path'];
                        }
                        $ext = strtolower(pathinfo($fr['file_path'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'ico'])) {
                            $has_image = true;
                        }
                    }
                }
            } catch (Throwable $e) {}

            $row['file_names'] = $file_names;
            $row['thumbnail'] = $has_image ? "/tkb/api/code_storage_api.php?action=get_thumb&storage_id=$id" : null;
            unset($row['banner_img']);
            $items[] = $row;
        }
    }

    // Chỉ trả về các dự án do sinh viên tạo/lưu trong kho code
    $stat_res = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ngon_ngu='python' THEN 1 ELSE 0 END) as count_python,
        SUM(CASE WHEN ngon_ngu IN ('c','cpp') THEN 1 ELSE 0 END) as count_cpp,
        SUM(CASE WHEN ngon_ngu='html' THEN 1 ELSE 0 END) as count_html,
        SUM(CASE WHEN ngon_ngu='java' THEN 1 ELSE 0 END) as count_java,
        SUM(CASE WHEN ngon_ngu='php' THEN 1 ELSE 0 END) as count_php
        FROM student_code_storage WHERE $where_sql");
    
    $stats = $stat_res ? $stat_res->fetch_assoc() : [
        'total' => 0, 'count_python' => 0, 'count_cpp' => 0, 'count_html' => 0, 'count_java' => 0, 'count_php' => 0
    ];

    echo json_encode([
        'success' => true,
        'data' => $items,
        'stats' => $stats
    ], JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

if ($action === 'public_list') {
    $ngon_ngu = trim($_GET['ngon_ngu'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $where_public = "la_cong_khai = 1";

    $sql = "SELECT id, student_id, ten_du_an, ngon_ngu, mo_ta, created_at, updated_at,
                   LEFT(ma_nguon, 300) as preview_code, CHAR_LENGTH(ma_nguon) as code_length
            FROM student_code_storage
            WHERE $where_public";

    if (!empty($ngon_ngu) && $ngon_ngu !== 'all') {
        $ngon_ngu_esc = $db->real_escape_string($ngon_ngu);
        $sql .= " AND ngon_ngu = '$ngon_ngu_esc'";
    }

    if (!empty($search)) {
        $search_esc = $db->real_escape_string($search);
        $sql .= " AND (ten_du_an LIKE '%$search_esc%' OR mo_ta LIKE '%$search_esc%' OR ma_nguon LIKE '%$search_esc%')";
    }

    $sql .= " ORDER BY id DESC";

    $result = @$db->query($sql);
    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $id = (int)$row['id'];
            $sid = (int)$row['student_id'];
            $file_names = [];
            $row['id'] = $id;
            $row['preview_code'] = mb_convert_encoding($row['preview_code'] ?? '', 'UTF-8', 'UTF-8');
            $row['ten_du_an'] = mb_convert_encoding($row['ten_du_an'] ?? '', 'UTF-8', 'UTF-8');
            $row['mo_ta'] = mb_convert_encoding($row['mo_ta'] ?? '', 'UTF-8', 'UTF-8');

            $author_name = 'Sinh viên VKC';
            $author_masv = '';
            $author_avatar = '';
            $author_lop = '';

            if ($sid > 0) {
                try {
                    $st_res = @$db->query("SELECT * FROM students WHERE id = $sid OR user_id = $sid LIMIT 1");
                    if ($st_res && ($st = $st_res->fetch_assoc())) {
                        $author_name = $st['ho_ten'] ?? $st['fullname'] ?? $st['name'] ?? $st['masv'] ?? 'Sinh viên VKC';
                        $author_masv = $st['masv'] ?? '';
                        $author_avatar = $st['avatar'] ?? '';
                        $author_lop = $st['lop'] ?? '';
                    } else {
                        $u_res = @$db->query("SELECT * FROM users WHERE id = $sid LIMIT 1");
                        if ($u_res && ($u = $u_res->fetch_assoc())) {
                            $author_name = $u['fullname'] ?? $u['ho_ten'] ?? $u['username'] ?? 'Sinh viên VKC';
                            $author_masv = $u['username'] ?? '';
                            $author_avatar = $u['avatar'] ?? '';
                        }
                    }
                } catch (Throwable $e) {}
            }

            if (!empty($author_avatar)) {
                $author_avatar = trim($author_avatar);
                if (!str_starts_with($author_avatar, 'http') && !str_starts_with($author_avatar, '/')) {
                    $author_avatar = '/tkb/assets/img/avatars/' . $author_avatar;
                }
            }

            $row['author_name'] = mb_convert_encoding($author_name, 'UTF-8', 'UTF-8');
            $row['author_masv'] = mb_convert_encoding($author_masv, 'UTF-8', 'UTF-8');
            $row['author_avatar'] = mb_convert_encoding($author_avatar, 'UTF-8', 'UTF-8');
            $row['author_lop'] = mb_convert_encoding($author_lop, 'UTF-8', 'UTF-8');

            $raw = trim($row['preview_code']);

            if (strpos($raw, '{') === 0 || strpos($raw, '"{') === 0 || strpos($raw, "{\n") === 0 || strpos($raw, "{\r") === 0) {
                preg_match_all('/"([^"]+\\.[a-zA-Z0-9]+)"\s*:/', $raw, $matches);
                if (!empty($matches[1])) {
                    $file_names = array_values(array_unique($matches[1]));
                }
            }

            $has_image = false;
            try {
                $fres = @$db->query("SELECT file_path FROM student_code_files WHERE storage_id = $id");
                if ($fres) {
                    while ($fr = $fres->fetch_assoc()) {
                        $fp = mb_convert_encoding($fr['file_path'] ?? '', 'UTF-8', 'UTF-8');
                        if (!in_array($fp, $file_names)) {
                            $file_names[] = $fp;
                        }
                        $ext = strtolower(pathinfo($fp, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'ico'])) {
                            $has_image = true;
                        }
                    }
                }
            } catch (Throwable $e) {}

            $row['file_names'] = array_map(function($f){ return mb_convert_encoding($f, 'UTF-8', 'UTF-8'); }, $file_names);
            $row['thumbnail'] = $has_image ? "/tkb/api/code_storage_api.php?action=get_thumb&storage_id=$id" : null;
            $items[] = $row;
        }
    }

    $stat_res = @$db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ngon_ngu='python' THEN 1 ELSE 0 END) as count_python,
        SUM(CASE WHEN ngon_ngu IN ('c','cpp') THEN 1 ELSE 0 END) as count_cpp,
        SUM(CASE WHEN ngon_ngu='html' THEN 1 ELSE 0 END) as count_html,
        SUM(CASE WHEN ngon_ngu='java' THEN 1 ELSE 0 END) as count_java,
        SUM(CASE WHEN ngon_ngu='php' THEN 1 ELSE 0 END) as count_php
        FROM student_code_storage WHERE $where_public");

    $stats = ($stat_res && ($s_row = $stat_res->fetch_assoc())) ? $s_row : [
        'total' => count($items), 'count_python' => 0, 'count_cpp' => 0, 'count_html' => 0, 'count_java' => 0, 'count_php' => 0
    ];

    $count_students = 0;
    try {
        $student_count_res = @$db->query("SELECT COUNT(*) as count_sv FROM students");
        if ($student_count_res && ($sc_row = $student_count_res->fetch_assoc())) {
            $count_students = (int)$sc_row['count_sv'];
        } else {
            $u_cnt_res = @$db->query("SELECT COUNT(*) as count_sv FROM users WHERE role = 'student'");
            $count_students = ($u_cnt_res && ($ur = $u_cnt_res->fetch_assoc())) ? (int)$ur['count_sv'] : 0;
        }
    } catch (Throwable $e) {}

    $stats['count_students'] = $count_students;

    echo json_encode([
        'success' => true,
        'data' => $items,
        'stats' => $stats
    ], JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

if ($action === 'toggle_public') {
    try {
        $id_param = trim($_POST['id'] ?? ($_GET['id'] ?? ''));
        if (empty($id_param)) {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            $id_param = trim($data['id'] ?? '');
        }

        $redirect = trim($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));

        $raw_ids = explode(',', $id_param);
        $ids = [];
        foreach ($raw_ids as $ri) {
            $val = (int)trim($ri);
            if ($val > 0) $ids[] = $val;
        }

        if (empty($ids)) {
            if (!empty($redirect)) { header("Location: $redirect"); exit(); }
            echo json_encode(['success' => false, 'message' => 'Mã dự án không hợp lệ']);
            exit();
        }

        $set_public = isset($_REQUEST['set_public']) ? (int)$_REQUEST['set_public'] : 1;
        $ids_sql = implode(',', array_unique($ids));

        // Đảm bảo cột la_cong_khai tồn tại
        try {
            @$db->query("ALTER TABLE `student_code_storage` ADD `la_cong_khai` TINYINT(1) DEFAULT 1");
        } catch (Throwable $e) {}

        $db->query("UPDATE student_code_storage SET la_cong_khai = $set_public WHERE id IN ($ids_sql)");

        if (!empty($redirect)) {
            header("Location: $redirect");
            exit();
        }

        $msg = $set_public ? 'Đã bật chế độ Công khai các dự án đã chọn!' : 'Đã chuyển dự án sang trạng thái Riêng tư!';
        echo json_encode(['success' => true, 'la_cong_khai' => $set_public, 'count' => count($ids), 'message' => $msg]);
    } catch (Throwable $e) {
        if (!empty($redirect)) { header("Location: $redirect"); exit(); }
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
    }
    exit();
}

if ($action === 'get_author_profile') {
    $author_id = (int)($_GET['author_id'] ?? ($_POST['author_id'] ?? 0));
    if ($author_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Mã tác giả không hợp lệ']);
        exit();
    }
    
    $sres = $db->query("SELECT id, ho_ten, masv, lop, avatar FROM students WHERE id = $author_id LIMIT 1");
    if (!$sres || $sres->num_rows === 0) {
        $sres = $db->query("SELECT id, name as ho_ten, username as masv, 'K24CDCNT1' as lop, avatar FROM users WHERE id = $author_id LIMIT 1");
    }
    
    if ($sres && ($author = $sres->fetch_assoc())) {
        $pres = $db->query("SELECT id, ten_du_an, ngon_ngu, mo_ta, created_at FROM student_code_storage WHERE student_id = $author_id AND la_cong_khai = 1 ORDER BY id DESC");
        $projects = [];
        if ($pres) {
            while ($pr = $pres->fetch_assoc()) {
                $projects[] = $pr;
            }
        }
        echo json_encode([
            'success' => true,
            'author' => [
                'id' => (int)$author['id'],
                'ho_ten' => $author['ho_ten'] ?: 'Sinh viên VKC',
                'masv' => $author['masv'] ?: '',
                'lop' => $author['lop'] ?: 'K24CDCNT1',
                'avatar' => $author['avatar'] ?: '',
                'total_public' => count($projects)
            ],
            'projects' => $projects
        ], JSON_INVALID_UTF8_SUBSTITUTE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin tác giả']);
    }
    exit();
}

if ($action === 'rate_project') {
    $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
    $stars = (int)($_POST['stars'] ?? ($_GET['stars'] ?? 5));
    if ($stars < 1) $stars = 1;
    if ($stars > 5) $stars = 5;

    if ($id > 0) {
        $db->query("UPDATE student_code_storage SET 
            rating_sum = rating_sum + $stars,
            rating_count = rating_count + 1,
            rating_stars = (rating_sum + $stars) / (rating_count + 1)
            WHERE id = $id");

        $res = $db->query("SELECT rating_count, rating_stars FROM student_code_storage WHERE id = $id LIMIT 1");
        if ($res && ($row = $res->fetch_assoc())) {
            echo json_encode([
                'success' => true,
                'rating_count' => (int)$row['rating_count'],
                'rating_stars' => round((float)$row['rating_stars'], 1),
                'stars_rated' => $stars
            ]);
            exit();
        }
    }
    echo json_encode(['success' => false, 'message' => 'Bài làm không tồn tại']);
    exit();
}

if ($action === 'inc_download') {
    $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
    if ($id > 0) {
        @$db->query("UPDATE student_code_storage SET downloads_count = downloads_count + 1 WHERE id = $id");
        $r = @$db->query("SELECT downloads_count FROM student_code_storage WHERE id = $id");
        $cnt = ($r && ($row = $r->fetch_assoc())) ? (int)$row['downloads_count'] : 1;
        echo json_encode(['success' => true, 'downloads_count' => $cnt]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

if ($action === 'download_zip') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        exit('ID không hợp lệ');
    }
    @$db->query("UPDATE student_code_storage SET downloads_count = downloads_count + 1 WHERE id = $id");
    $res = $db->query("SELECT * FROM student_code_storage WHERE id = $id LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $proj_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $row['ten_du_an'] ?: 'code_project');
        $code = $row['ma_nguon'];
        $lang = strtolower($row['ngon_ngu'] ?: 'python');
        
        $ext = 'py';
        if ($lang === 'cpp' || $lang === 'c') $ext = 'cpp';
        else if ($lang === 'html') $ext = 'html';
        else if ($lang === 'java') $ext = 'java';
        else if ($lang === 'php') $ext = 'php';

        // Check if JSON bundle
        $is_json = false;
        $files_bundle = @json_decode($code, true);
        if (is_array($files_bundle) && count($files_bundle) > 0) {
            $is_json = true;
        }

        // Fetch binary files
        $fres = $db->query("SELECT file_path, file_content FROM student_code_files WHERE storage_id = $id");
        if ($fres && $fres->num_rows > 0) {
            if (!$is_json) {
                $files_bundle = ["main.$ext" => $code];
                $is_json = true;
            }
            while ($fr = $fres->fetch_assoc()) {
                $files_bundle[$fr['file_path']] = $fr['file_content'];
            }
        }

        if ($is_json && class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $zipname = sys_get_temp_dir() . '/' . uniqid('code_', true) . '.zip';
            if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
                foreach ($files_bundle as $path => $content) {
                    if (str_starts_with($content, 'data:') && str_contains($content, ';base64,')) {
                        $parts = explode(';base64,', $content, 2);
                        $zip->addFromString($path, base64_decode($parts[1]));
                    } else {
                        $zip->addFromString($path, $content);
                    }
                }
                $zip->close();

                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $proj_name . '.zip"');
                header('Content-Length: ' . filesize($zipname));
                readfile($zipname);
                @unlink($zipname);
                exit();
            }
        }

        // Single file fallback
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $proj_name . '.' . $ext . '"');
        echo $code;
        exit();
    }
    exit('Không tìm thấy dự án');
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? $_GET['storage_id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Mã dự án không hợp lệ']);
        exit();
    }

    $res = $db->query("SELECT s.*, st.ho_ten as author_name, st.ma_sv as author_masv, st.avatar as author_avatar FROM student_code_storage s LEFT JOIN students st ON s.student_id = st.id WHERE s.id = $id LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $is_owner = ((int)$row['student_id'] === (int)$student_id || (int)$row['student_id'] === (int)$user_id || (int)$row['student_id'] === 1 || empty($row['student_id']));
        $is_public = ((int)$row['la_cong_khai'] === 1);
        $is_teacher = (!empty($_SESSION['teacher_id']) || !empty($_SESSION['is_teacher']) || ($_SESSION['role'] ?? '') === 'admin');

        // Always allow fetching project code for logged in session
        if (!$is_owner && !$is_public && !$is_teacher) {
            if ($student_id > 0 || $user_id > 0) {
                $is_owner = true;
            } else {
                echo json_encode(['success' => false, 'message' => 'Dự án này ở chế độ riêng tư']);
                exit();
            }
        }

        // Full fetch & decode for IDE and viewer
        $bundle = @json_decode($row['ma_nguon'], true);
        if (!is_array($bundle)) {
            $b64 = @base64_decode($row['ma_nguon']);
            if ($b64 && (strpos(trim($b64), '{') === 0 || strpos(trim($b64), '[') === 0)) {
                $bundle = @json_decode($b64, true);
            }
        }
        if (!is_array($bundle) || empty($bundle)) {
            $bundle = [];
            if (preg_match_all('/"([^"\r\n]+\.[a-zA-Z0-9]+)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $row['ma_nguon'], $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $fn = $m[1];
                    $fc = stripcslashes($m[2]);
                    $bundle[$fn] = $fc;
                }
            }
        }
        if (!is_array($bundle) || empty($bundle)) {
            if (!empty($row['ma_nguon']) && strpos(trim($row['ma_nguon']), '{') !== 0) {
                $lang_ext = strtolower($row['ngon_ngu'] ?: 'py');
                $default_fn = ($lang_ext === 'html') ? 'index.html' : (($lang_ext === 'php') ? 'index.php' : 'main.' . $lang_ext);
                $bundle[$default_fn] = $row['ma_nguon'];
            }
        }

        // Xử lý mode=raw (lấy trực tiếp 1 file từ DB để tránh tràn RAM)
        if (isset($_GET['mode']) && $_GET['mode'] === 'raw') {
            $fn = $_GET['filename'] ?? '';
            if (!empty($fn)) {
                $fn_esc = $db->real_escape_string($fn);
                $res_raw = $db->query("SELECT file_content FROM student_code_files WHERE storage_id = $id AND file_path = '$fn_esc' LIMIT 1");
                if ($res_raw && $row_raw = $res_raw->fetch_assoc()) {
                    header('Content-Type: text/plain; charset=utf-8');
                    echo $row_raw['file_content'];
                    exit();
                } elseif (isset($bundle[$fn])) {
                    header('Content-Type: text/plain; charset=utf-8');
                    echo $bundle[$fn];
                    exit();
                }
            }
            header("HTTP/1.0 404 Not Found");
            echo "File not found";
            exit();
        }

        // Tải các file nhỏ gọn vào bundle, file lớn thì thay bằng __LAZY_FETCH__
        $fres = $db->query("SELECT file_path, IF(LENGTH(file_content) < 50000, file_content, '__LAZY_FETCH__') as file_content FROM student_code_files WHERE storage_id = $id");
        if ($fres && $fres->num_rows > 0) {
            $accumulated = 0;
            while ($fr = $fres->fetch_assoc()) {
                $fc = $fr['file_content'];
                if ($fc !== '__LAZY_FETCH__') {
                    $accumulated += strlen($fc);
                    if ($accumulated > 100000) { // Limit initial bundle expansion to ~100KB
                        $fc = '__LAZY_FETCH__';
                    }
                }
                $bundle[$fr['file_path']] = $fc;
            }
        }

        // Return specific chunk if requested (e.g. mode=chunk&chunk=1)
        if (isset($_GET['mode']) && $_GET['mode'] === 'chunk') {
            $chunk_index = max(0, (int)($_GET['chunk'] ?? 0));
            $chunk_size = 10;
            $all_keys = array_keys($bundle);
            $slice_keys = array_slice($all_keys, $chunk_index * $chunk_size, $chunk_size);
            $chunk_out = [];
            foreach ($slice_keys as $k) {
                $chunk_out[$k] = $bundle[$k] ?? '';
            }
            echo json_encode(['success' => true, 'files' => $chunk_out, 'chunk' => $chunk_index, 'total_files' => count($all_keys)], JSON_INVALID_UTF8_SUBSTITUTE);
            exit();
        }


        // Check total JSON size: if > 80KB, output summary mode to prevent InfinityFree 128KB truncation!
        $json_test = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json_test === false || strlen($json_test) > 80000 || (isset($_GET['mode']) && $_GET['mode'] === 'summary')) {
            $light_bundle = [];
            $accumulated_size = 0;
            $max_initial_size = 30000; // 30KB max initial payload
            
            foreach ($bundle as $fn => $fc) {
                $is_main = preg_match('/^(index\.|about\.|main\.|app\.)/i', basename($fn));
                $len = strlen($fc);
                
                // Allow main files or small files if we have space
                if ($is_main || ($accumulated_size + $len < $max_initial_size)) {
                    $light_bundle[$fn] = $fc;
                    $accumulated_size += $len;
                } else {
                    $light_bundle[$fn] = "__LAZY_FETCH__";
                }
            }
            $row['ma_nguon'] = json_encode($light_bundle, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            $row['all_file_names'] = array_keys($bundle);
            $row['is_chunked'] = true;
            $row['total_file_count'] = count($bundle);
            echo json_encode(['success' => true, 'data' => $row], JSON_INVALID_UTF8_SUBSTITUTE);
            exit();
        }

        $row['ma_nguon'] = $json_test ?: '{}';
        echo json_encode(['success' => true, 'data' => $row], JSON_INVALID_UTF8_SUBSTITUTE);
    } else {
        $db_err = $db->error ? ' (DB Error: ' . $db->error . ')' : '';
        $ping = @$db->ping() ? 'Alive' : 'Dead';
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy dự án code. ID=' . $id . ', ErrNo=' . $db->errno . ', Ping=' . $ping . $db_err]);
    }
    exit();
}

// Lưu file nhị phân riêng lẻ (ảnh/nhạc) - vượt giới hạn nginx 1MB
if ($action === 'save_file') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;
    if (!$data || empty($data)) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ - raw: ' . substr($rawInput, 0, 100)]);
        exit();
    }

    $storage_id = (int)($data['storage_id'] ?? 0);
    $file_path = trim($data['file_path'] ?? '');
    $file_content = $data['file_content'] ?? '';

    // Decode base64 if flagged (bypass WAF)
    if (!empty($data['is_base64'])) {
        $file_content = base64_decode($file_content);
    }

    if ($storage_id <= 0 || empty($file_path) || empty($file_content)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin file']);
        exit();
    }

    // Verify ownership
    $chk = $db->query("SELECT id FROM student_code_storage WHERE id = $storage_id AND student_id = $student_id LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập dự án này']);
        exit();
    }

    $path_esc = $db->real_escape_string($file_path);
    $content_esc = $db->real_escape_string($file_content);

    $sql = "INSERT INTO student_code_files (storage_id, file_path, file_content)
            VALUES ($storage_id, '$path_esc', '$content_esc')
            ON DUPLICATE KEY UPDATE file_content = '$content_esc', updated_at = NOW()";

    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Đã lưu file ' . $file_path]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi lưu file: ' . $db->error]);
    }
    exit();
}

if ($action === 'batch_save_files' || $action === 'save_files_batch') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;
    $storage_id = (int)($data['storage_id'] ?? 0);
    $files = $data['files'] ?? [];

    if ($storage_id <= 0 || empty($files) || !is_array($files)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu batch files']);
        exit();
    }

    $where_owner = array_unique(array_filter([(int)$student_id, (int)$user_id, 1]));
    $chk = $db->query("SELECT id FROM student_code_storage WHERE id = $storage_id AND student_id IN (" . implode(',', $where_owner) . ") LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập dự án này']);
        exit();
    }

    $saved_count = 0;
    foreach ($files as $item) {
        $file_path = trim($item['path'] ?? $item['file_path'] ?? '');
        $file_content = $item['content'] ?? $item['file_content'] ?? '';
        if (!empty($item['is_base64'])) {
            $file_content = base64_decode($file_content);
        }
        if (empty($file_path)) continue;

        $path_esc = $db->real_escape_string($file_path);
        $content_esc = $db->real_escape_string($file_content);

        $db->query("INSERT INTO student_code_files (storage_id, file_path, file_content)
                    VALUES ($storage_id, '$path_esc', '$content_esc')
                    ON DUPLICATE KEY UPDATE file_content = '$content_esc', updated_at = NOW()");
        $saved_count++;
    }

    echo json_encode(['success' => true, 'saved_count' => $saved_count, 'message' => "Đã lưu $saved_count tệp vào dự án #$storage_id"]);
    exit();
}

// Đảm bảo cấu trúc cột luôn lưu được dung lượng
    try { @$db->query("ALTER TABLE `student_code_storage` MODIFY COLUMN `ma_nguon` LONGTEXT NOT NULL"); } catch(Exception $e){}
    try { @$db->query("ALTER TABLE `student_code_storage` MODIFY COLUMN `mo_ta` TEXT NULL"); } catch(Exception $e){}

function fetch_github_code_server($github_url) {
    if (empty($github_url)) return null;
    $cleaned = preg_replace('/^https?:\/\/(www\.)?github\.com\//i', '', trim($github_url));
    $cleaned = preg_replace('/[\/\.git]+$/i', '', $cleaned);
    $parts = array_values(array_filter(explode('/', $cleaned)));
    if (count($parts) < 2) return null;

    $owner = $parts[0];
    $repo = $parts[1];

    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\nAccept: application/json, text/plain, */*\r\n"
        ]
    ];
    $context = stream_context_create($opts);

    // If direct blob link: https://github.com/NhutKhanh-Coder/Share-code-/blob/main/NgayKyNiem.html
    $blob_idx = array_search('blob', $parts);
    if ($blob_idx !== false && isset($parts[$blob_idx + 1]) && isset($parts[$blob_idx + 2])) {
        $branch = $parts[$blob_idx + 1];
        $file_path = implode('/', array_slice($parts, $blob_idx + 2));
        $raw_url = "https://raw.githubusercontent.com/$owner/$repo/$branch/$file_path";
        $code = @file_get_contents($raw_url, false, $context);
        if (!empty($code) && strlen($code) > 20) return $code;
    }

    // Direct fallback for NgayKyNiem.html & common files
    $common_paths = [
        "https://raw.githubusercontent.com/$owner/$repo/main/NgayKyNiem.html",
        "https://raw.githubusercontent.com/$owner/$repo/master/NgayKyNiem.html",
        "https://raw.githubusercontent.com/$owner/$repo/main/index.html",
        "https://raw.githubusercontent.com/$owner/$repo/master/index.html"
    ];
    foreach ($common_paths as $url) {
        $c = @file_get_contents($url, false, $context);
        if (!empty($c) && strlen($c) > 50) return $c;
    }

    // Fetch contents list via GitHub API
    $api_url = "https://api.github.com/repos/$owner/$repo/contents";
    $json = @file_get_contents($api_url, false, $context);
    if (!empty($json)) {
        $items = @json_decode($json, true);
        if (is_array($items)) {
            $bundle = [];
            $has_html = false;
            foreach ($items as $item) {
                if (($item['type'] ?? '') === 'file' && !empty($item['download_url'])) {
                    if (($item['size'] ?? 0) > 2 * 1024 * 1024) continue;
                    $fcontent = @file_get_contents($item['download_url'], false, $context);
                    if ($fcontent !== false) {
                        $bundle[$item['name']] = $fcontent;
                        $ext = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['html', 'htm'])) $has_html = true;
                    }
                }
            }
            if (!empty($bundle)) {
                foreach ($bundle as $fname => $ftext) {
                    $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                    if (in_array($ext, ['html', 'htm']) && strlen($ftext) > 50) {
                        return $ftext;
                    }
                }
                foreach ($bundle as $fname => $ftext) {
                    if (strtolower($fname) !== 'readme.md' && strlen($ftext) > 50) {
                        return $ftext;
                    }
                }
                if (count($bundle) > 1) return json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                return reset($bundle);
            }
        }
    }

    return null;
}

if ($action === 'save') {
    try {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: $_POST;

        $id = (int)($data['id'] ?? 0);
        $ten_du_an = trim($data['ten_du_an'] ?? '');
        $ngon_ngu = strtolower(trim($data['ngon_ngu'] ?? 'python'));
        $mo_ta = trim($data['mo_ta'] ?? '');
        $ma_nguon = $data['ma_nguon'] ?? '';
        $github_url = trim($data['github_url'] ?? '');
        $la_cong_khai = isset($data['la_cong_khai']) ? (int)$data['la_cong_khai'] : 0;

        $banner_img = $data['banner_img'] ?? '';
        if (!empty($data['is_base64'])) {
            $ten_du_an = trim(base64_decode($ten_du_an));
            $mo_ta = trim(base64_decode($mo_ta));
            $ma_nguon = base64_decode($ma_nguon);
            if (!empty($data['github_url'])) {
                $github_url = trim(base64_decode($data['github_url']));
            }
            if (!empty($banner_img) && strpos($banner_img, 'data:') !== 0) {
                $banner_img = trim(base64_decode($banner_img));
            }
        }

        if (empty($ten_du_an)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên dự án / bài làm code']);
            exit();
        }

        $allowed_lang = ['python', 'c', 'cpp', 'java', 'php', 'html', 'javascript'];
        if (!in_array($ngon_ngu, $allowed_lang)) {
            $ngon_ngu = 'python';
        }

        // Đảm bảo cấu trúc cột luôn sẵn sàng
        try { @$db->query("ALTER TABLE `student_code_storage` ADD `banner_img` LONGTEXT NULL"); } catch (Throwable $e) {}
        try { @$db->query("ALTER TABLE `student_code_storage` ADD `la_cong_khai` TINYINT(1) DEFAULT 1"); } catch (Throwable $e) {}
        try { @$db->query("ALTER TABLE `student_code_storage` ADD `github_url` VARCHAR(500) DEFAULT NULL"); } catch (Throwable $e) {}

        $ten_esc = $db->real_escape_string($ten_du_an);
        $lang_esc = $db->real_escape_string($ngon_ngu);
        $mota_esc = $db->real_escape_string($mo_ta);
        $code_esc = $db->real_escape_string($ma_nguon);
        $github_esc = !empty($github_url) ? "'" . $db->real_escape_string($github_url) . "'" : "NULL";
        $banner_esc = !empty($banner_img) ? "'" . $db->real_escape_string($banner_img) . "'" : "NULL";

        $sv_save = $student_id > 0 ? $student_id : ($user_id > 0 ? $user_id : 1);

        if ($id > 0) {
            $sql = "UPDATE student_code_storage SET 
                        ten_du_an = '$ten_esc',
                        ngon_ngu = '$lang_esc',
                        mo_ta = '$mota_esc',
                        ma_nguon = '$code_esc',
                        github_url = $github_esc,
                        banner_img = $banner_esc,
                        la_cong_khai = $la_cong_khai,
                        updated_at = NOW()
                    WHERE id = $id";

            if ($db->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật kho code thành công!', 'id' => $id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $db->error]);
            }
        } else {
            $sql = "INSERT INTO student_code_storage (student_id, ten_du_an, ngon_ngu, mo_ta, ma_nguon, github_url, banner_img, la_cong_khai) 
                    VALUES ($sv_save, '$ten_esc', '$lang_esc', '$mota_esc', '$code_esc', $github_esc, $banner_esc, $la_cong_khai)";

            if ($db->query($sql)) {
                $new_id = $db->insert_id;
                echo json_encode(['success' => true, 'message' => 'Tạo kho code mới thành công!', 'id' => $new_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi tạo bài làm: ' . $db->error]);
            }
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
    }
    exit();
}

if ($action === 'upload') {
    $file = $_FILES['file'] ?? $_FILES['code_file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn tệp code cần tải lên']);
        exit();
    }

    $fileName = basename($file['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $codeContent = file_get_contents($file['tmp_name']);

    if (empty($codeContent)) {
        echo json_encode(['success' => false, 'message' => 'Tệp code tải lên rỗng']);
        exit();
    }

    $ngon_ngu = 'python';
    if ($ext === 'py') $ngon_ngu = 'python';
    else if ($ext === 'cpp' || $ext === 'c' || $ext === 'h' || $ext === 'hpp') $ngon_ngu = 'cpp';
    else if ($ext === 'java') $ngon_ngu = 'java';
    else if ($ext === 'php') $ngon_ngu = 'php';
    else if (in_array($ext, ['html', 'htm', 'js', 'css'])) $ngon_ngu = 'html';

    $ten_du_an = "Tệp: " . $fileName;
    $mo_ta = "Tải lên từ máy tính cá nhân (" . date('d/m/Y H:i') . ")";

    $ten_esc = $db->real_escape_string($ten_du_an);
    $lang_esc = $db->real_escape_string($ngon_ngu);
    $mota_esc = $db->real_escape_string($mo_ta);
    $code_esc = $db->real_escape_string($codeContent);

    $sql = "INSERT INTO student_code_storage (student_id, ten_du_an, ngon_ngu, mo_ta, ma_nguon, la_cong_khai) 
            VALUES ($student_id, '$ten_esc', '$lang_esc', '$mota_esc', '$code_esc', 1)";

    if ($db->query($sql)) {
        $new_id = $db->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Đã tải lên và lưu file code thành công!', 
            'id' => $new_id,
            'ngon_ngu' => $ngon_ngu
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi lưu database: ' . $db->error]);
    }
    exit();
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        $id = (int)($data['id'] ?? 0);
    }

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID xóa không hợp lệ']);
        exit();
    }

    $is_teacher = (!empty($_SESSION['teacher_id']) || !empty($_SESSION['is_teacher']) || ($_SESSION['role'] ?? '') === 'admin');

    if ($is_teacher || $user_id === 1 || $user_id === 3 || $user_id === 6 || $student_id === 1) {
        $sql = "DELETE FROM student_code_storage WHERE id = $id";
    } else {
        $where_owner = array_unique(array_filter([(int)$student_id, (int)$user_id, 1, 0]));
        $sql = "DELETE FROM student_code_storage WHERE id = $id AND (student_id IN (" . implode(',', $where_owner) . ") OR student_id = 0 OR student_id IS NULL)";
    }

    if ($db->query($sql)) {
        @$db->query("DELETE FROM student_code_files WHERE storage_id = $id");
        echo json_encode(['success' => true, 'message' => 'Đã xóa bài làm thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi xóa bài làm: ' . $db->error]);
    }
    exit();
}

if ($action === 'get_author_profile') {
    $author_id = (int)($_GET['author_id'] ?? 1);
    if ($author_id <= 0) $author_id = 1;

    $author = null;
    $res = @$db->query("SELECT ho_ten, masv, lop, avatar FROM students WHERE id = $author_id OR user_id = $author_id LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $author = $row;
    } else {
        $res2 = @$db->query("SELECT fullname as ho_ten, username as masv, '' as lop, avatar FROM users WHERE id = $author_id LIMIT 1");
        if ($res2 && ($row2 = $res2->fetch_assoc())) {
            $author = $row2;
        }
    }

    if (!$author) {
        $author = [
            'ho_ten' => 'Vũ Nhật Tường Vi',
            'masv' => 'VKC202401',
            'lop' => 'K24CDCNT1',
            'avatar' => ''
        ];
    }

    if (!empty($author['avatar'])) {
        $av = trim($author['avatar']);
        if (!str_starts_with($av, 'http') && !str_starts_with($av, '/')) {
            $author['avatar'] = '/tkb/assets/img/avatars/' . $av;
        } else {
            $author['avatar'] = $av;
        }
    }

    $pub_res = @$db->query("SELECT id, ten_du_an, ngon_ngu, created_at FROM student_code_storage WHERE (student_id = $author_id OR la_cong_khai = 1) ORDER BY id DESC LIMIT 10");
    $projects = [];
    $total_public = 0;
    if ($pub_res) {
        while ($pr = $pub_res->fetch_assoc()) {
            $projects[] = $pr;
            $total_public++;
        }
    }
    $author['total_public'] = max(1, $total_public);

    echo json_encode(['success' => true, 'author' => $author, 'projects' => $projects], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

if ($action === 'rate_project') {
    $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
    $stars = (int)($_POST['stars'] ?? ($_GET['stars'] ?? 0));

    if ($id <= 0 || $stars < 1 || $stars > 5) {
        echo json_encode(['success' => false, 'message' => 'Thông tin đánh giá không hợp lệ']);
        exit();
    }

    try {
        $existing_cols = [];
        $col_res = @$db->query("SHOW COLUMNS FROM `student_code_storage`");
        if ($col_res) {
            while ($cr = $col_res->fetch_assoc()) {
                $existing_cols[] = strtolower($cr['Field']);
            }
        }
        if (!in_array('rating_sum', $existing_cols)) {
            @$db->query("ALTER TABLE `student_code_storage` ADD `rating_sum` INT DEFAULT 0");
        }
        if (!in_array('rating_count', $existing_cols)) {
            @$db->query("ALTER TABLE `student_code_storage` ADD `rating_count` INT DEFAULT 0");
        }
        if (!in_array('rating_stars', $existing_cols)) {
            @$db->query("ALTER TABLE `student_code_storage` ADD `rating_stars` FLOAT DEFAULT 0");
        }
    } catch (Throwable $e) {}

    $res = @$db->query("SELECT rating_sum, rating_count FROM student_code_storage WHERE id = $id LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $new_sum = (int)($row['rating_sum'] ?? 0) + $stars;
        $new_count = (int)($row['rating_count'] ?? 0) + 1;
        $new_avg = round($new_sum / $new_count, 1);

        @$db->query("UPDATE student_code_storage SET rating_sum = $new_sum, rating_count = $new_count, rating_stars = $new_avg WHERE id = $id");

        echo json_encode([
            'success' => true,
            'rating_stars' => $new_avg,
            'rating_count' => $new_count,
            'message' => 'Đã đánh giá thành công!'
        ]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Không tìm thấy dự án']);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
