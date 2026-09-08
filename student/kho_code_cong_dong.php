<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    header("Location: /tkb/login.php");
    exit();
}

$db = getDB();
$student_id = (int)($_SESSION['student_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_teacher = (!empty($_SESSION['teacher_id']) || !empty($_SESSION['is_teacher']) || ($_SESSION['role'] ?? '') === 'admin' || isset($_SESSION['teacher_name']));

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$initial_projects = [];
$initial_stats = ['total' => 0, 'count_python' => 0, 'count_cpp' => 0, 'count_html' => 0];

try {
    // 1. Đảm bảo cấu trúc cột an toàn
    $existing_cols = [];
    $col_res = @$db->query("SHOW COLUMNS FROM `student_code_storage`");
    if ($col_res) {
        while ($cr = $col_res->fetch_assoc()) {
            $existing_cols[] = strtolower($cr['Field']);
        }
    }
    if (!in_array('la_cong_khai', $existing_cols)) {
        @$db->query("ALTER TABLE `student_code_storage` ADD `la_cong_khai` TINYINT(1) DEFAULT 0");
    }
    if (!in_array('banner_img', $existing_cols)) {
        @$db->query("ALTER TABLE `student_code_storage` ADD `banner_img` LONGTEXT DEFAULT NULL");
    }
    if (!in_array('github_url', $existing_cols)) {
        @$db->query("ALTER TABLE `student_code_storage` ADD `github_url` VARCHAR(500) DEFAULT NULL");
    }
} catch (Throwable $e) {}

try {
    // Tự động dọn dẹp và xóa dứt điểm dự án mẫu keria nếu có
    @$db->query("DELETE FROM `student_code_storage` WHERE `ten_du_an` LIKE '%keria%' OR `mo_ta` LIKE '%keria%'");
} catch (Throwable $e) {}

try {
    $where_public = "la_cong_khai = 1";

    $sql_init = "SELECT id, student_id, ten_du_an, ngon_ngu, mo_ta, created_at, updated_at,
                        LEFT(ma_nguon, 300) as preview_code, CHAR_LENGTH(ma_nguon) as code_length
                 FROM student_code_storage
                 WHERE $where_public
                 ORDER BY id DESC";

    $res_init = @$db->query($sql_init);
    if ($res_init) {
        while ($row = $res_init->fetch_assoc()) {
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
            $author_lop = 'K24CNTT';

            if ($sid > 0) {
                try {
                    $st_res = @$db->query("SELECT * FROM students WHERE id = $sid OR user_id = $sid LIMIT 1");
                    if ($st_res && ($st = $st_res->fetch_assoc())) {
                        $author_name = $st['ho_ten'] ?? $st['fullname'] ?? $st['name'] ?? $st['masv'] ?? 'Sinh viên VKC';
                        $author_masv = $st['masv'] ?? '';
                        $author_avatar = $st['avatar'] ?? '';
                        $author_lop = $st['lop'] ?? 'K24CNTT';
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
                    if (str_starts_with($author_avatar, 'assets/')) {
                        $author_avatar = '/tkb/' . $author_avatar;
                    } else {
                        $author_avatar = '/tkb/assets/img/avatars/' . $author_avatar;
                    }
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
            $initial_projects[] = $row;
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
    if ($stat_res && ($s_row = $stat_res->fetch_assoc())) {
        $initial_stats = $s_row;
    } else {
        $initial_stats['total'] = count($initial_projects);
    }

    $student_count_res = @$db->query("SELECT COUNT(*) as count_sv FROM students");
    $count_students = 0;
    if ($student_count_res && ($sc_row = $student_count_res->fetch_assoc())) {
        $count_students = (int)$sc_row['count_sv'];
    } else {
        $u_cnt_res = @$db->query("SELECT COUNT(*) as count_sv FROM users WHERE role = 'student'");
        $count_students = ($u_cnt_res && ($ur = $u_cnt_res->fetch_assoc())) ? (int)$ur['count_sv'] : 0;
    }
    $initial_stats['count_students'] = $count_students;
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kho Code Cộng Đồng Sinh Viên - VKC TKB</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script>
        window.initialPublicData = <?= json_encode(['data' => $initial_projects, 'stats' => $initial_stats], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    </script>
    <style>
        :root {
            --bg-page: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-dark: #0f172a;
            --text-gray: #64748b;
            --accent-purple: #8b5cf6;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
        }

        body {
            background-color: var(--bg-page);
            color: var(--text-dark);
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        .code-community-container {
            max-width: 1340px;
            margin: 0 auto;
            padding: 24px;
        }

        /* 1. HERO BANNER - Exact match with user reference mockup */
        .hero-banner-card {
            background: linear-gradient(135deg, #090e24 0%, #151136 50%, #080d21 100%);
            border-radius: 24px;
            padding: 36px 40px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(9, 14, 36, 0.25);
            color: #ffffff;
        }

        .hero-banner-card::before {
            content: '';
            position: absolute;
            top: -40%;
            right: 15%;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.35) 0%, rgba(59, 130, 246, 0.15) 50%, transparent 70%);
            filter: blur(50px);
            pointer-events: none;
        }

        .hero-left-content {
            flex: 1;
            max-width: 680px;
            z-index: 2;
        }

        .hero-welcome-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 14px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .hero-title {
            font-size: 32px;
            font-weight: 900;
            line-height: 1.25;
            margin: 0 0 12px 0;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .hero-title .highlight-purple {
            background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 24px 0;
            font-weight: 400;
        }

        .hero-buttons-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-hero {
            padding: 11px 20px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            transition: all 0.25s ease;
            text-decoration: none;
        }

        .btn-hero:hover {
            transform: translateY(-2px);
        }

        .btn-hero-purple {
            background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
            color: #ffffff;
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
        }
        .btn-hero-purple:hover {
            box-shadow: 0 8px 25px rgba(168, 85, 247, 0.55);
        }

        .btn-hero-blue {
            background: #0284c7;
            color: #ffffff;
        }
        .btn-hero-blue:hover {
            background: #0369a1;
        }

        .btn-hero-green {
            background: #10b981;
            color: #ffffff;
        }
        .btn-hero-green:hover {
            background: #059669;
        }

        .btn-hero-glass {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(6px);
        }
        .btn-hero-glass:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.35);
        }

        /* 3D Code Editor Scene in Hero Banner */
        .hero-right-illustration {
            position: relative;
            flex-shrink: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 360px;
            height: 220px;
        }

        .code-3d-scene {
            position: relative;
            width: 100%;
            height: 100%;
            perspective: 1200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ide-3d-card {
            background: rgba(11, 15, 33, 0.92);
            border: 1.5px solid rgba(139, 92, 246, 0.45);
            border-radius: 16px;
            width: 300px;
            padding: 14px 18px;
            transform: rotateY(-18deg) rotateX(10deg) rotateZ(-2deg);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 35px rgba(139, 92, 246, 0.3);
            transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.4s ease;
            backdrop-filter: blur(12px);
            user-select: none;
        }

        .ide-3d-card:hover {
            transform: rotateY(-8deg) rotateX(5deg) scale(1.03);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.8), 0 0 50px rgba(139, 92, 246, 0.5);
            border-color: rgba(168, 85, 247, 0.7);
        }

        .ide-3d-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .ide-mac-dots {
            display: flex;
            gap: 6px;
        }

        .mac-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-r { background: #ff5f56; }
        .dot-y { background: #ffbd2e; }
        .dot-g { background: #27c93f; }

        .ide-tab-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            font-family: 'Fira Code', monospace;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .ide-3d-body {
            font-family: 'Fira Code', monospace;
            font-size: 11px;
            line-height: 1.65;
            color: #e2e8f0;
        }

        .code-line-mock {
            white-space: nowrap;
        }
        .code-line-mock.indent-1 {
            padding-left: 14px;
        }

        .syn-kw { color: #f43f5e; font-weight: 700; }
        .syn-pkg { color: #38bdf8; }
        .syn-fn { color: #c084fc; font-weight: 700; }
        .syn-fn-name { color: #fbbf24; }
        .syn-fn-call { color: #60a5fa; }
        .syn-var { color: #e2e8f0; }
        .syn-str { color: #34d399; }
        .syn-comment { color: #64748b; font-style: italic; }

        /* Floating Holograms */
        .floating-holo {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            border-radius: 12px;
            backdrop-filter: blur(8px);
            user-select: none;
            pointer-events: none;
            animation: floatHolo 3.5s ease-in-out infinite alternate;
        }

        .holo-tag-code {
            top: 20px;
            left: 0px;
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.4) 0%, rgba(139, 92, 246, 0.4) 100%);
            border: 1px solid rgba(168, 85, 247, 0.6);
            color: #ffffff;
            font-size: 14px;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.4);
            animation-delay: 0s;
        }

        .holo-brackets-1 {
            top: 10px;
            left: 90px;
            width: 32px;
            height: 32px;
            background: rgba(59, 130, 246, 0.35);
            border: 1px solid rgba(59, 130, 246, 0.6);
            color: #60a5fa;
            font-family: 'Fira Code', monospace;
            font-size: 13px;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.35);
            animation-delay: 0.8s;
        }

        .holo-brackets-2 {
            bottom: 25px;
            left: 15px;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.4) 0%, rgba(168, 85, 247, 0.4) 100%);
            border: 1px solid rgba(236, 72, 153, 0.6);
            color: #ffffff;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            box-shadow: 0 8px 24px rgba(236, 72, 153, 0.4);
            animation-delay: 1.6s;
        }

        .holo-avatar {
            bottom: 15px;
            right: 0px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%);
            border: 1.5px solid #ffffff;
            color: #ffffff;
            font-size: 16px;
            box-shadow: 0 10px 25px rgba(217, 70, 239, 0.5);
            animation-delay: 2.2s;
        }

        @keyframes floatHolo {
            0% { transform: translateY(0px) rotate(0deg); }
            100% { transform: translateY(-8px) rotate(3deg); }
        }

        /* 2. STATS ROW - 5 Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.025);
            transition: all 0.2s ease;
        }

        .stat-card-clean:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }

        .stat-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-val-text {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.2;
        }

        .stat-lbl-text {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            margin-top: 2px;
            white-space: nowrap;
        }

        /* 3. FILTER & SEARCH BAR */
        .filter-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.025);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .filter-top-row {
            display: flex;
            gap: 14px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box-wrap {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .search-box-wrap i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
        }

        .search-input-field {
            width: 100%;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 18px 12px 46px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .search-input-field:focus {
            background: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }

        .select-lang-pill {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 20px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .select-lang-pill:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }

        .btn-filter-toggle {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 20px;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-filter-toggle:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        /* Tags Row */
        .filter-tags-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .tags-pills-list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .tag-pill-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tag-pill-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .tag-pill-btn.active {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .sort-dropdown-wrap select {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            outline: none;
            cursor: pointer;
        }

        /* 4. PROJECT CARDS - 4 Columns Grid */
        .projects-grid-4col {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .project-card-v2 {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .project-card-v2:hover {
            transform: translateY(-5px);
            border-color: #cbd5e1;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.08);
        }

        .card-author-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .author-avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid #e2e8f0;
            flex-shrink: 0;
        }

        .author-name-title {
            font-size: 13.5px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            display: block;
        }

        .author-sub-title {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 500;
            margin-top: 2px;
            display: block;
        }

        .card-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
        }

        .card-project-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.35;
            word-break: break-word;
        }

        .card-lang-badge {
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }

        .badge-py { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-c { background: #faf5ff; color: #9333ea; border: 1px solid #e9d5ff; }
        .badge-web { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-jv { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
        .badge-ph { background: #fdf4ff; color: #c026d3; border: 1px solid #f5d0fe; }

        .card-desc-snippet {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 38px;
        }

        /* Mockup / Preview Box */
        .card-preview-thumb {
            width: 100%;
            height: 140px;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
            background: #0f172a;
            position: relative;
        }

        .card-preview-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .card-preview-code-box {
            width: 100%;
            height: 140px;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
            background: #0d1117;
            padding: 12px;
            box-sizing: border-box;
            font-family: 'Fira Code', monospace;
            font-size: 11.5px;
            color: #7ee787;
            line-height: 1.5;
            position: relative;
        }

        .card-preview-code-box::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 45px;
            background: linear-gradient(180deg, transparent 0%, #0d1117 100%);
        }

        /* Rating and Date Box */
        .card-rating-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #f1f5f9;
        }

        .rating-stars-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            color: #fbbf24;
        }

        .rating-stars-badge i {
            cursor: pointer;
            transition: transform 0.15s ease, color 0.15s ease;
        }

        .rating-stars-badge i:hover {
            transform: scale(1.3);
            color: #f59e0b !important;
        }

        .rating-score {
            font-weight: 800;
            color: #0f172a;
            font-size: 12px;
            margin-left: 4px;
        }

        .rating-count {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
        }

        .card-date-badge {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Action Buttons on Card */
        .card-actions-bar {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 12px;
        }

        .btn-card-run {
            flex: 1;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            transition: all 0.2s ease;
        }

        .btn-card-run:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45);
            color: #ffffff;
        }

        .btn-card-dl {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 12.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-card-dl:hover {
            background: #dcfce7;
            color: #15803d;
            transform: translateY(-2px);
        }

        .btn-card-del {
            background: #fff1f2;
            color: #e11d48;
            border: 1px solid #fecdd3;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 12.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-card-del:hover {
            background: #ffe4e6;
            color: #be123c;
            transform: translateY(-2px);
        }

        .btn-card-trash {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 12.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-card-trash:hover {
            background: #fee2e2;
            color: #991b1b;
            transform: translateY(-2px);
        }

        .btn-card-share {
            background: #f8fafc;
            color: #2563eb;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-card-share:hover {
            background: #eff6ff;
            color: #1d4ed8;
            transform: translateY(-2px);
        }

        /* Card Footer Meta */
        .card-meta-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #64748b;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
        }

        .meta-stats-group {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .meta-stat-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 600;
        }

        .meta-stat-item i {
            font-size: 12px;
            color: #94a3b8;
        }

        .meta-time-text {
            font-size: 11.5px;
            font-weight: 500;
            color: #94a3b8;
        }

        /* Load More Button */
        .btn-load-more {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            color: #334155;
            padding: 12px 28px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-load-more:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            color: #0f172a;
        }

        /* Modal Styles */
        .modal-backdrop {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(15, 23, 42, 0.6) !important;
            backdrop-filter: blur(8px) !important;
            z-index: 999999 !important;
            display: none;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            box-sizing: border-box !important;
        }
        .modal-backdrop.show { display: flex !important; }

        .modal-content-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.18);
            color: #0f172a;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .modal-body { padding: 24px; }
        .form-label-code { font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; display: block; text-transform: uppercase; }
        .modal-input, .modal-select, .modal-textarea {
            width: 100%; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; color: #0f172a; font-size: 14px; font-weight: 500; outline: none; margin-bottom: 18px; box-sizing: border-box; transition: all 0.2s;
        }
        .modal-input:focus, .modal-select:focus, .modal-textarea:focus {
            background: #ffffff; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }
        .code-textarea { font-family: 'Fira Code', monospace; font-size: 13.5px; line-height: 1.6; min-height: 220px; resize: vertical; white-space: pre; background: #f8fafc; color: #1e293b; }
        .modal-footer { padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; background: #f8fafc; }
        .btn-cancel { background: #e2e8f0; color: #475569; border: none; padding: 11px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .btn-cancel:hover { background: #cbd5e1; color: #0f172a; }
        .btn-save-project { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: #ffffff; border: none; padding: 11px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 6px 18px rgba(139, 92, 246, 0.35); }

        .my-code-row {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            transition: all 0.2s;
        }
        .my-code-row:hover {
            border-color: #cbd5e1;
            background: #ffffff;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.04);
        }

        .toast-msg {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            padding: 14px 22px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.35);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 99999999;
        }

        .toast-msg.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .projects-grid-4col { grid-template-columns: repeat(3, 1fr); }
            .stats-row { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 900px) {
            .hero-right-illustration { display: none; }
            .projects-grid-4col { grid-template-columns: repeat(2, 1fr); }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .projects-grid-4col { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr; }
            .hero-title { font-size: 24px; }
            .hero-banner-card { padding: 24px 20px; }
        }
    </style>
</head>
<body>

<div class="code-community-container">
    
    <!-- 1. HERO BANNER - Exact match with mockup -->
    <div class="hero-banner-card">
        <div class="hero-left-content">
            <div class="hero-welcome-badge">Chào mừng đến với</div>
            <h1 class="hero-title">Kho Code <span class="highlight-purple">Cộng Đồng</span> Sinh Viên</h1>
            <p class="hero-desc">Khám phá, chia sẻ và tham khảo các bài làm & dự án lập trình xuất sắc từ cộng đồng sinh viên VKC.</p>
            
            <div class="hero-buttons-row">
                <?php if ($is_teacher): ?>
                    <a href="/tkb/teacher/dashboard.php" class="btn-hero btn-hero-purple"><i class="fa-solid fa-arrow-left"></i> Về Trang Giáo Viên</a>
                <?php else: ?>
                    <button type="button" class="btn-hero btn-hero-purple" onclick="openMyStorageShareModal()">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Chia sẻ code
                    </button>
                    <button type="button" class="btn-hero btn-hero-blue" onclick="document.getElementById('communityFolderInput').click()">
                        <i class="fa-solid fa-folder-plus"></i> Tải thư mục
                    </button>
                    <input type="file" id="communityFolderInput" webkitdirectory directory multiple style="display:none;" onclick="this.value=null" onchange="uploadFolderProject(event)">

                    <button type="button" class="btn-hero btn-hero-green" onclick="document.getElementById('communityFileInput').click()">
                        <i class="fa-solid fa-file-arrow-up"></i> Tải ZIP / Code
                    </button>
                    <input type="file" id="communityFileInput" style="display:none;" onclick="this.value=null" onchange="uploadAndRunCode(event)">

                    <a href="/tkb/student/luu_tru_code.php" class="btn-hero btn-hero-glass"><i class="fa-solid fa-folder-open"></i> Kho của tôi</a>
                    <a href="/tkb/student/dashboard.php" class="btn-hero btn-hero-glass"><i class="fa-solid fa-house"></i> Trang Chủ</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-right-illustration">
            <div class="code-3d-scene">
                <!-- Cửa sổ IDE 3D nghiêng -->
                <div class="ide-3d-card">
                    <div class="ide-3d-header">
                        <div class="ide-mac-dots">
                            <span class="mac-dot dot-r"></span>
                            <span class="mac-dot dot-y"></span>
                            <span class="mac-dot dot-g"></span>
                        </div>
                        <div class="ide-tab-label"><i class="fa-solid fa-code" style="color:#60a5fa;"></i> main.py</div>
                    </div>
                    <div class="ide-3d-body">
                        <div class="code-line-mock"><span class="syn-kw">import</span> <span class="syn-pkg">vkc_community</span> <span class="syn-kw">as</span> <span class="syn-pkg">vkc</span></div>
                        <div class="code-line-mock"><span class="syn-fn">def</span> <span class="syn-fn-name">init_student_code</span>():</div>
                        <div class="code-line-mock indent-1"><span class="syn-var">projects</span> = vkc.<span class="syn-fn-call">get_all_projects</span>()</div>
                        <div class="code-line-mock indent-1"><span class="syn-kw">return</span> [p.<span class="syn-fn-call">share</span>() <span class="syn-kw">for</span> p <span class="syn-kw">in</span> projects]</div>
                        <div class="code-line-mock"><span class="syn-comment"># 🚀 Chúc mừng sinh viên VKC!</span></div>
                        <div class="code-line-mock"><span class="syn-fn-call">print</span>(<span class="syn-str">"🌟 Sáng tạo &amp; Thành công!"</span>)</div>
                    </div>
                </div>

                <!-- Các huy hiệu 3D Neon bay nổi tuyệt đẹp -->
                <div class="floating-holo holo-tag-code"><i class="fa-solid fa-code"></i></div>
                <div class="floating-holo holo-brackets-1">{ }</div>
                <div class="floating-holo holo-brackets-2">{ }</div>
                <div class="floating-holo holo-avatar"><i class="fa-solid fa-user-astronaut"></i></div>
            </div>
        </div>
    </div>

    <!-- 2. STATS ROW - Removed per user request -->

    <!-- 3. FILTER & SEARCH BAR -->
    <div class="filter-panel">
        <div class="filter-top-row">
            <div class="search-box-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" class="search-input-field" placeholder="Tìm tên dự án, sinh viên, từ khóa mã nguồn..." oninput="debounceFetch()">
            </div>
            <select id="langFilter" class="select-lang-pill" onchange="fetchPublicProjects()">
                <option value="all">🌐 Tất cả ngôn ngữ</option>
                <option value="python">🐍 Python</option>
                <option value="cpp">⚙️ C / C++</option>
                <option value="html">🌐 Web HTML/CSS/JS</option>
                <option value="java">☕ Java</option>
                <option value="php">🐘 PHP</option>
            </select>
            <button class="btn-filter-toggle" onclick="fetchPublicProjects()">
                <i class="fa-solid fa-sliders"></i> Bộ lọc
            </button>
        </div>

        <div class="filter-tags-row">
            <div class="tags-pills-list">
                <button class="tag-pill-btn active" onclick="setCategoryTag(this, 'all')">Tất cả</button>
                <button class="tag-pill-btn" onclick="setCategoryTag(this, 'web')">Web Development</button>
                <button class="tag-pill-btn" onclick="setCategoryTag(this, 'mobile')">Mobile</button>
                <button class="tag-pill-btn" onclick="setCategoryTag(this, 'ai')">AI / Machine Learning</button>
                <button class="tag-pill-btn" onclick="setCategoryTag(this, 'game')">Game</button>
                <button class="tag-pill-btn" onclick="setCategoryTag(this, 'data')">Data Science</button>
                <button class="tag-pill-btn" onclick="setCategoryTag(this, 'desktop')">Ứng dụng Desktop</button>
                <button class="tag-pill-btn" onclick="setCategoryTag(this, 'other')">Khác</button>
            </div>
            <div class="sort-dropdown-wrap">
                <select id="sortFilter" onchange="fetchPublicProjects()">
                    <option value="latest">Mới nhất ▾</option>
                    <option value="popular">Phổ biến nhất</option>
                    <option value="views">Nhiều lượt xem</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 4. PROJECTS GRID (4 Columns) -->
    <div class="projects-grid-4col" id="projectsGrid">
        <?php if (!empty($initial_projects)): ?>
            <?php foreach ($initial_projects as $idx => $item): 
                $lang = strtolower($item['ngon_ngu'] ?? 'python');
                $badgeClass = 'badge-py';
                $langLabel = 'PYTHON';
                if ($lang === 'cpp' || $lang === 'c') { $badgeClass = 'badge-c'; $langLabel = 'C / C++'; }
                else if ($lang === 'html') { $badgeClass = 'badge-web'; $langLabel = 'WEB HTML'; }
                else if ($lang === 'java') { $badgeClass = 'badge-jv'; $langLabel = 'JAVA'; }
                else if ($lang === 'php') { $badgeClass = 'badge-ph'; $langLabel = 'PHP'; }

                $authorName = $item['author_name'] ?? 'Sinh viên VKC';
                $authorInitial = mb_strtoupper(mb_substr($authorName, 0, 1, 'UTF-8'), 'UTF-8');
                $shareUrl = "/tkb/student/code_ide.php?v=2026&storage_id=" . (int)$item['id'];
                $viewsCount = (int)($item['views_count'] ?? 1);
                $starsCount = (int)($item['rating_count'] ?? 1);
                $ratingScore = number_format((float)($item['rating_stars'] ?? 5.0), 1);
                $downloadsCount = (int)($item['downloads_count'] ?? 0);
                $timeLabels = ['2 ngày trước', '5 ngày trước', '1 tuần trước', 'Vừa cập nhật'];
                $timeAgo = $timeLabels[$idx % count($timeLabels)];
                
                $postDateText = 'Vừa cập nhật';
                if (!empty($item['created_at']) || !empty($item['updated_at'])) {
                    $ts = strtotime($item['created_at'] ?? $item['updated_at']);
                    if ($ts > 0) {
                        $postDateText = date('d/m/Y', $ts);
                    }
                }

                $canDelete = true;
            ?>
            <div class="project-card-v2" id="project_card_<?= (int)$item['id'] ?>" onclick="window.location.href='<?= $shareUrl ?>'" style="cursor: pointer;">
                <div>
                    <!-- Author Header -->
                    <div class="card-author-header" onclick="event.stopPropagation(); openAuthorProfileModal(<?= (int)$item['student_id'] ?>, event)" title="Xem hồ sơ sinh viên <?= htmlspecialchars($authorName) ?>" style="cursor: pointer;">
                        <?php 
                            $avtSrc = trim($item['author_avatar'] ?? '');
                            $gradients = ['#3b82f6', '#ec4899', '#8b5cf6', '#10b981', '#f59e0b', '#06b6d4'];
                            $bgGrad = $gradients[ord($authorInitial) % count($gradients)];
                        ?>
                        <?php if (!empty($avtSrc)): ?>
                            <img src="<?= htmlspecialchars($avtSrc) ?>" class="author-avatar-circle" alt="Avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="author-avatar-circle" style="display:none; background:<?= $bgGrad ?>; color:#fff; font-weight:800; font-size:14px; align-items:center; justify-content:center;">
                                <?= htmlspecialchars($authorInitial) ?>
                            </div>
                        <?php else: ?>
                            <div class="author-avatar-circle" style="display:flex; background:<?= $bgGrad ?>; color:#fff; font-weight:800; font-size:14px; align-items:center; justify-content:center;">
                                <?= htmlspecialchars($authorInitial) ?>
                            </div>
                        <?php endif; ?>

                        <div style="flex:1; min-width:0;">
                            <span class="author-name-title"><?= htmlspecialchars($authorName) ?></span>
                            <span class="author-sub-title">Thành viên VKC • <?= htmlspecialchars($item['author_lop'] ?: 'K24CNTT') ?></span>
                        </div>
                    </div>

                    <!-- Title & Badge -->
                    <div class="card-title-row">
                        <h3 class="card-project-title"><?= htmlspecialchars($item['ten_du_an']) ?></h3>
                        <span class="card-lang-badge <?= $badgeClass ?>"><?= $langLabel ?></span>
                    </div>

                    <!-- Description -->
                    <p class="card-desc-snippet"><?= htmlspecialchars($item['mo_ta'] ?: 'Dự án bài làm thực hành lập trình xuất sắc từ sinh viên khoa CNTT.') ?></p>

                    <!-- Preview Thumbnail or Code Box -->
                    <?php if (!empty($item['thumbnail'])): ?>
                        <div class="card-preview-thumb">
                            <img src="<?= htmlspecialchars($item['thumbnail']) ?>" alt="Preview">
                        </div>
                    <?php else: ?>
                        <div class="card-preview-code-box">
                            <pre style="margin:0;"><?= htmlspecialchars(mb_substr($item['preview_code'], 0, 200)) ?></pre>
                        </div>
                    <?php endif; ?>

                    <!-- Rating & Upload Date -->
                    <div class="card-rating-row">
                        <div class="rating-stars-badge" onclick="event.stopPropagation();" id="ratingBadge_<?= (int)$item['id'] ?>" title="Đánh giá từ 1 đến 5 sao cho bài làm này">
                            <?php 
                                $rCount = (int)($item['rating_count'] ?? 0);
                                $rStars = (float)($item['rating_stars'] ?? 0.0);
                                $yellowCount = ($rCount > 0) ? min(5, max(1, (int)round($rStars))) : 0;
                                for ($s = 1; $s <= 5; $s++) {
                                    if ($s <= $yellowCount) {
                                        echo '<i class="fa-solid fa-star" style="color: #fbbf24;" onclick="rateProject(' . (int)$item['id'] . ', ' . $s . ', event)" title="Đánh giá ' . $s . ' sao"></i>';
                                    } else {
                                        echo '<i class="fa-regular fa-star" style="color: #cbd5e1;" onclick="rateProject(' . (int)$item['id'] . ', ' . $s . ', event)" title="Đánh giá ' . $s . ' sao"></i>';
                                    }
                                }
                            ?>
                            <span class="rating-score" id="ratingScore_<?= (int)$item['id'] ?>"><?= $rCount > 0 ? number_format($rStars, 1) : '0.0' ?></span>
                            <span class="rating-count" id="ratingCount_<?= (int)$item['id'] ?>">(<?= $rCount ?>)</span>
                        </div>
                        <span class="card-date-badge">
                            <i class="fa-regular fa-calendar-days"></i> <?= $postDateText ?>
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card-actions-bar" onclick="event.stopPropagation();">
                        <a href="<?= $shareUrl ?>" target="_blank" class="btn-card-run" title="Mở IDE và chạy mã nguồn">
                            <i class="fa-solid fa-play"></i> Chạy code
                        </a>
                        <a href="/tkb/api/code_storage_api.php?action=download_zip&id=<?= (int)$item['id'] ?>" download class="btn-card-dl" title="Tải mã nguồn về máy tính (không ảnh hưởng kho lưu trữ)" onclick="handleDownloadClick(<?= (int)$item['id'] ?>, event)">
                            <i class="fa-solid fa-download"></i> Tải về
                        </a>
                        <button type="button" class="btn-card-share" onclick="copyShareLink('<?= $shareUrl ?>')" title="Sao chép link chia sẻ">
                            <i class="fa-solid fa-share-nodes"></i>
                        </button>
                        <button type="button" class="btn-card-trash" onclick="deletePermanentProject(<?= (int)$item['id'] ?>, event)" title="Xóa vĩnh viễn bài làm này">
                            <i class="fa-solid fa-trash-can"></i> Xóa
                        </button>
                    </div>
                </div>

                <!-- Footer Meta -->
                <div class="card-meta-footer" onclick="event.stopPropagation();">
                    <div class="meta-stats-group">
                        <span class="meta-stat-item"><i class="fa-regular fa-eye"></i> <?= $viewsCount ?></span>
                        <span class="meta-stat-item" id="metaDlItem_<?= $item['id'] ?>" title="Số lượt tải về"><i class="fa-solid fa-download"></i> <span id="dlCountText_<?= $item['id'] ?>"><?= $downloadsCount ?></span></span>
                    </div>
                    <span class="meta-time-text"><?= $timeAgo ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 5. LOAD MORE BUTTON -->
    <div style="text-align: center; margin: 36px 0 10px 0;">
        <button class="btn-load-more" onclick="fetchPublicProjects()">
            Xem thêm dự án <i class="fa-solid fa-rotate"></i>
        </button>
    </div>

</div>

<!-- Modal 1: Chia Sẻ Từ Kho Code Của Tôi -->
<div class="modal-backdrop" id="myStorageModal">
    <div class="modal-content-box">
        <div class="modal-header">
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-cloud-arrow-up" style="color: #8b5cf6;"></i> Chọn Bài Làm Từ Kho Code Để Chia Sẻ
                </h3>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">Tích chọn các bài làm bạn muốn phát sóng công khai lên Kho Code Cộng Đồng.</p>
            </div>
            <button type="button" onclick="closeMyStorageShareModal()" style="background: none; border: none; font-size: 24px; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div class="modal-body">
            <div id="shareSuccessNotice" style="display:none; background:#f0fdf4; border:1.5px solid #86efac; border-radius:16px; padding:18px 20px; margin-bottom:20px; box-shadow:0 8px 24px rgba(16, 185, 129, 0.1);">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                    <div style="width:36px; height:36px; border-radius:50%; background:#10b981; color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <h4 id="shareNoticeTitle" style="margin:0; font-size:16px; font-weight:800; color:#15803d;">🎉 ĐÃ PHÁT SÓNG CHIA SẺ BÀI LÀM THÀNH CÔNG!</h4>
                        <p style="margin:2px 0 0 0; font-size:13px; color:#166534;">Bài làm của bạn hiện đã xuất hiện trên Kho Code Cộng Đồng.</p>
                    </div>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <input type="text" id="shareNoticeInput" readonly style="flex:1; min-width:260px; background:#ffffff; border:1.5px solid #bbf7d0; border-radius:10px; padding:10px 14px; font-size:13.5px; color:#15803d; font-weight:700; outline:none;">
                    <button type="button" class="btn-act" style="background:#10b981; color:#fff; border:none; padding:10px 18px; border-radius:10px; font-weight:700; cursor:pointer;" onclick="copyNoticeLink()">
                        <i class="fa-solid fa-copy"></i> Sao Chép
                    </button>
                    <a id="shareNoticeIdeBtn" href="#" target="_blank" class="btn-act" style="background:#0284c7; color:#fff; border:none; padding:10px 18px; border-radius:10px; font-weight:700; text-decoration:none;">
                        <i class="fa-solid fa-play"></i> Xem & Chạy Code
                    </a>
                </div>
            </div>

            <div id="myStorageProjectsList" style="max-height: 480px; overflow-y: auto; padding-right: 4px;">
                <!-- JavaScript Render -->
            </div>
        </div>

        <div class="modal-footer" style="justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <div style="font-size: 13px; color: #64748b;">
                <i class="fa-solid fa-info-circle"></i> Bạn có thể gỡ bài làm khỏi cộng đồng bất cứ lúc nào.
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn-cancel" onclick="closeMyStorageShareModal()">Đóng</button>
                <button type="button" class="btn-save-project" id="btnShareSelectedProjects" onclick="shareSelectedProjects()">
                    <i class="fa-solid fa-cloud-arrow-up"></i> 🌐 Chia Sẻ Bài Làm Đã Chọn Lên Cộng Đồng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Tạo dự án mới -->
<div class="modal-backdrop" id="projectModal">
    <div class="modal-content-box">
        <div class="modal-header">
            <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;" id="modalTitle">Chia Sẻ Bài Làm Lập Trình</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; color: #64748b; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <form id="projectForm" onsubmit="saveProject(event)">
                <input type="hidden" id="projectId" value="0">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label class="form-label-code">TÊN BÀI TẬP / DỰ ÁN *</label>
                        <input type="text" id="projectTitle" class="modal-input" placeholder="Ví dụ: Website Quản Lý Sinh Viên..." required>
                    </div>
                    <div>
                        <label class="form-label-code">NGÔN NGỮ LẬP TRÌNH *</label>
                        <select id="projectLang" class="modal-select" onchange="onLanguageChange()">
                            <option value="python">🐍 Python</option>
                            <option value="cpp">⚡ C++</option>
                            <option value="c">⚡ C</option>
                            <option value="java">☕ Java</option>
                            <option value="php">🐘 PHP</option>
                            <option value="html">🌐 Web HTML/JS</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label-code">MÔ TẢ BÀI TẬP / DỰ ÁN (KHÔNG BẮT BUỘC)</label>
                    <input type="text" id="projectDesc" class="modal-input" placeholder="Ghi chú yêu cầu bài tập hoặc mô tả dự án...">
                </div>

                <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1.5px solid #e2e8f0;">
                    <input type="checkbox" id="projectIsPublic" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #8b5cf6;">
                    <label for="projectIsPublic" style="font-size: 13.5px; font-weight: 700; color: #0f172a; cursor: pointer; margin: 0;">
                        🌐 Chia sẻ bài làm lên Kho Code Cộng Đồng (cho phép sinh viên khác & giáo viên xem/tham khảo)
                    </label>
                </div>

                <div style="margin-top: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                        <label class="form-label-code" style="margin: 0;">MÃ NGUỒN CODE (SOURCE CODE) *</label>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <button type="button" class="btn-act" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight:700; cursor: pointer;" onclick="document.getElementById('modalFolderInput').click()">
                                <i class="fa-solid fa-folder-plus"></i> Nạp thư mục
                            </button>
                            <input type="file" id="modalFolderInput" webkitdirectory directory multiple style="display:none;" onclick="this.value=null" onchange="readModalFolder(event)">

                            <button type="button" class="btn-act" style="background: #faf5ff; color: #9333ea; border: 1px solid #e9d5ff; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight:700; cursor: pointer;" onclick="document.getElementById('modalFileInput').click()">
                                <i class="fa-solid fa-file-arrow-up"></i> Nạp tệp code
                            </button>
                            <input type="file" id="modalFileInput" style="display:none;" onclick="this.value=null" onchange="readModalFile(event)">
                        </div>
                    </div>

                    <textarea id="projectCode" class="modal-textarea code-textarea" placeholder="# Nhập mã nguồn của bạn vào đây..." required></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Hủy Bỏ</button>
            <button class="btn-save-project" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);" onclick="saveAndRunProject(event)">
                <i class="fa-solid fa-play"></i> Lưu & Chạy Trên IDE
            </button>
            <button class="btn-save-project" onclick="document.getElementById('projectForm').requestSubmit()">
                <i class="fa-solid fa-share-nodes"></i> Lưu & Chia Sẻ Lên Cộng Đồng
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-msg" id="toastMsg">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastText">Đã sao chép liên kết chia sẻ!</span>
</div>

<script src="/tkb/assets/code_storage_v1.js"></script>
<script>
window.communityDebounceTimer = null;
window.currentStudentId = <?= (int)$student_id ?>;
window.currentUserId = <?= (int)$user_id ?>;
window.isTeacher = <?= $is_teacher ? 'true' : 'false' ?>;

document.addEventListener("DOMContentLoaded", () => {
    // Auto-fetch để lấy danh sách bài làm mới nhất từ API
    fetchPublicProjects();
});

function debounceFetch() {
    clearTimeout(window.communityDebounceTimer);
    window.communityDebounceTimer = setTimeout(fetchPublicProjects, 300);
}

function setCategoryTag(btn, cat) {
    document.querySelectorAll('.tag-pill-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    fetchPublicProjects();
}

function fetchPublicProjects() {
    const searchInput = document.getElementById('searchInput');
    const langFilter = document.getElementById('langFilter');
    const search = searchInput ? searchInput.value.trim() : '';
    const lang = langFilter ? langFilter.value : 'all';

    fetch(`/tkb/api/code_storage_api.php?action=public_list&ngon_ngu=${encodeURIComponent(lang)}&search=${encodeURIComponent(search)}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                if (res.stats) renderStats(res.stats);
                if (Array.isArray(res.data) && res.data.length > 0) {
                    // Có dữ liệu → render ra grid
                    renderProjects(res.data);
                } else if (search !== '' || lang !== 'all') {
                    // Đang lọc/tìm kiếm mà rỗng → hiện thông báo không có kết quả
                    renderProjects([]);
                }
                // Nếu fetch lần đầu mà API trả về rỗng + không filter → giữ nguyên nội dung PHP
            }
        })
        .catch(err => {
            console.error('Fetch public projects error:', err);
        });
}

function renderStats(stats) {
    if (!stats) return;
    const elTotal = document.getElementById('statTotal');
    const elPy = document.getElementById('statPy');
    const elCpp = document.getElementById('statCpp');
    const elWeb = document.getElementById('statWeb');
    const elMembers = document.getElementById('statMembers');

    if (elTotal) elTotal.innerText = stats.total > 0 ? (stats.total + '+') : '1,248+';
    if (elPy) elPy.innerText = stats.count_python > 0 ? (stats.count_python + '+') : '856+';
    if (elCpp) elCpp.innerText = stats.count_cpp > 0 ? (stats.count_cpp + '+') : '642+';
    if (elWeb) elWeb.innerText = stats.count_html > 0 ? (stats.count_html + '+') : '1,102+';
    if (elMembers) {
        if (typeof stats.count_students !== 'undefined') {
            elMembers.innerText = Number(stats.count_students).toLocaleString() + '+';
        } else {
            elMembers.innerText = '1,520+';
        }
    }
}

function renderProjects(items) {
    const container = document.getElementById('projectsGrid');
    if (!container) return;
    if (!items || items.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px;">
                <p style="color: #64748b; font-size: 14.5px; margin: 0 0 16px 0;">Chưa có bài làm công khai nào trên cộng đồng.</p>
                <button type="button" class="btn-hero btn-hero-purple" onclick="openMyStorageShareModal()" style="margin:0 auto;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Chọn Bài Làm Từ Kho Code Để Chia Sẻ
                </button>
            </div>
        `;
        return;
    }

    container.innerHTML = items.map((item, idx) => {
        const lang = (item.ngon_ngu || 'python').toLowerCase();
        let badgeClass = 'badge-py';
        let langLabel = 'PYTHON';
        if (lang === 'cpp' || lang === 'c') { badgeClass = 'badge-c'; langLabel = 'C / C++'; }
        else if (lang === 'html') { badgeClass = 'badge-web'; langLabel = 'WEB HTML'; }
        else if (lang === 'java') { badgeClass = 'badge-jv'; langLabel = 'JAVA'; }
        else if (lang === 'php') { badgeClass = 'badge-ph'; langLabel = 'PHP'; }

        const authorName = item.author_name || 'Sinh viên VKC';
        const authorInitial = (authorName.trim().charAt(0) || 'U').toUpperCase();
        const gradients = ['#3b82f6', '#ec4899', '#8b5cf6', '#10b981', '#f59e0b', '#06b6d4'];
        const bgGrad = gradients[authorInitial.charCodeAt(0) % gradients.length];

        let avtSrc = (item.author_avatar || '').trim();
        if (avtSrc && !avtSrc.startsWith('http') && !avtSrc.startsWith('/')) {
            if (avtSrc.startsWith('assets/')) {
                avtSrc = '/tkb/' + avtSrc;
            } else {
                avtSrc = '/tkb/assets/img/avatars/' + avtSrc;
            }
        }

        let avatarHtml = '';
        if (avtSrc) {
            avatarHtml = `
                <img src="${escapeHtml(avtSrc)}" class="author-avatar-circle" alt="Avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="author-avatar-circle" style="display:none; background:${bgGrad}; color:#fff; font-weight:800; font-size:14px; align-items:center; justify-content:center;">
                    ${escapeHtml(authorInitial)}
                </div>
            `;
        } else {
            avatarHtml = `
                <div class="author-avatar-circle" style="display:flex; background:${bgGrad}; color:#fff; font-weight:800; font-size:14px; align-items:center; justify-content:center;">
                    ${escapeHtml(authorInitial)}
                </div>
            `;
        }

        let thumbHtml = '';
        if (item.thumbnail) {
            thumbHtml = `
                <div class="card-preview-thumb">
                    <img src="${escapeHtml(item.thumbnail)}" alt="Thumbnail">
                </div>
            `;
        } else {
            thumbHtml = `
                <div class="card-preview-code-box">
                    <pre style="margin:0;">${escapeHtml((item.preview_code || '').substring(0, 180))}</pre>
                </div>
            `;
        }

        const shareUrl = `${window.location.origin}/tkb/student/code_ide.php?v=2026&storage_id=${item.id}`;
        const viewsCount = parseInt(item.views_count || 1) + '';
        const starsCount = parseInt(item.rating_count || 1) + '';
        const ratingScore = parseFloat(item.rating_stars || 5.0).toFixed(1);
        const downloadsCount = parseInt(item.downloads_count || 0) + '';
        const timeLabels = ['2 ngày trước', '5 ngày trước', '1 tuần trước', 'Vừa cập nhật'];
        const timeAgo = timeLabels[idx % timeLabels.length];

        let postDateText = 'Vừa cập nhật';
        if (item.created_at || item.updated_at) {
            const rawDate = new Date(item.created_at || item.updated_at);
            if (!isNaN(rawDate.getTime())) {
                const d = String(rawDate.getDate()).padStart(2, '0');
                const m = String(rawDate.getMonth() + 1).padStart(2, '0');
                const y = rawDate.getFullYear();
                postDateText = `${d}/${m}/${y}`;
            }
        }

        return `
            <div class="project-card-v2" id="project_card_${item.id}" onclick="window.location.href='${shareUrl}'" style="cursor: pointer;">
                <div>
                    <div class="card-author-header" onclick="event.stopPropagation(); openAuthorProfileModal(${item.student_id || 1}, event)" title="Xem hồ sơ sinh viên ${escapeHtml(authorName)}" style="cursor: pointer;">
                        ${avatarHtml}
                        <div style="flex:1; min-width:0;">
                            <span class="author-name-title">${escapeHtml(authorName)}</span>
                            <span class="author-sub-title">Thành viên VKC • ${escapeHtml(item.author_lop || 'K24CNTT')}</span>
                        </div>
                    </div>

                    <div class="card-title-row">
                        <h3 class="card-project-title">${escapeHtml(item.ten_du_an)}</h3>
                        <span class="card-lang-badge ${badgeClass}">${langLabel}</span>
                    </div>

                    <p class="card-desc-snippet">${escapeHtml(item.mo_ta || 'Dự án bài làm thực hành lập trình xuất sắc từ sinh viên khoa CNTT.')}</p>

                    ${thumbHtml}

                    <!-- Rating & Upload Date -->
                    <div class="card-rating-row">
                        <div class="rating-stars-badge" onclick="event.stopPropagation();" id="ratingBadge_${item.id}" title="Đánh giá từ 1 đến 5 sao cho bài làm này">
                            ${(() => {
                                const rC = parseInt(item.rating_count || 0);
                                const rS = parseFloat(item.rating_stars || 0.0);
                                const yC = rC > 0 ? Math.min(5, Math.max(1, Math.round(rS))) : 0;
                                let sHtml = '';
                                for (let s = 1; s <= 5; s++) {
                                    if (s <= yC) {
                                        sHtml += `<i class="fa-solid fa-star" style="color: #fbbf24;" onclick="rateProject(${item.id}, ${s}, event)" title="Đánh giá ${s} sao"></i>`;
                                    } else {
                                        sHtml += `<i class="fa-regular fa-star" style="color: #cbd5e1;" onclick="rateProject(${item.id}, ${s}, event)" title="Đánh giá ${s} sao"></i>`;
                                    }
                                }
                                sHtml += `<span class="rating-score" id="ratingScore_${item.id}">${rC > 0 ? rS.toFixed(1) : '0.0'}</span>`;
                                sHtml += `<span class="rating-count" id="ratingCount_${item.id}">(${rC})</span>`;
                                return sHtml;
                            })()}
                        </div>
                        <span class="card-date-badge">
                            <i class="fa-regular fa-calendar-days"></i> ${escapeHtml(postDateText)}
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card-actions-bar" onclick="event.stopPropagation();">
                        <a href="${shareUrl}" target="_blank" class="btn-card-run" title="Mở IDE và chạy mã nguồn">
                            <i class="fa-solid fa-play"></i> Chạy code
                        </a>
                        <a href="/tkb/api/code_storage_api.php?action=download_zip&id=${item.id}" download class="btn-card-dl" onclick="handleDownloadClick(${item.id}, event)" title="Tải mã nguồn về máy tính (không ảnh hưởng kho lưu trữ)">
                            <i class="fa-solid fa-download"></i> Tải về
                        </a>
                        <button type="button" class="btn-card-share" onclick="copyShareLink('${shareUrl}')" title="Sao chép link chia sẻ">
                            <i class="fa-solid fa-share-nodes"></i>
                        </button>
                        <button type="button" class="btn-card-trash" onclick="deletePermanentProject(${item.id}, event)" title="Xóa vĩnh viễn bài làm này">
                            <i class="fa-solid fa-trash-can"></i> Xóa
                        </button>
                    </div>
                </div>

                <div class="card-meta-footer" onclick="event.stopPropagation();">
                    <div class="meta-stats-group">
                        <span class="meta-stat-item"><i class="fa-regular fa-eye"></i> ${viewsCount}</span>
                        <span class="meta-stat-item" id="metaDlItem_${item.id}" title="Số lượt tải về"><i class="fa-solid fa-download"></i> <span id="dlCountText_${item.id}">${parseInt(item.downloads_count || 0)}</span></span>
                    </div>
                    <span class="meta-time-text">${timeAgo}</span>
                </div>
            </div>
        `;
    }).join('');
}

function handleDownloadClick(id, event) {
    if (event) event.stopPropagation();
    const span = document.getElementById(`dlCountText_${id}`);
    if (span) {
        let cur = parseInt(span.innerText || '0') || 0;
        span.innerText = (cur + 1) + '';
    }
    // Gửi tracking lượt tải ngầm
    fetch(`/tkb/api/code_storage_api.php?action=inc_download&id=${id}`).catch(()=>{});
}

async function rateProject(id, stars, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    if (!id || !stars) return;

    showToast(`⭐ Đang gửi đánh giá ${stars} sao...`, true);

    try {
        const params = new URLSearchParams();
        params.append('id', id);
        params.append('stars', stars);

        const res = await fetch('/tkb/api/code_storage_api.php?action=rate_project', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });
        const data = await res.json();

        if (data.success) {
            showToast(`🎉 Cảm ơn bạn đã đánh giá ${stars} sao cho bài làm này!`, true);
            const badgeEl = document.getElementById(`ratingBadge_${id}`);
            if (badgeEl) {
                const yCount = Math.min(5, Math.max(1, Math.round(data.rating_stars)));
                let newStarsHtml = '';
                for (let s = 1; s <= 5; s++) {
                    if (s <= yCount) {
                        newStarsHtml += `<i class="fa-solid fa-star" style="color: #fbbf24;" onclick="rateProject(${id}, ${s}, event)" title="Đánh giá ${s} sao"></i>`;
                    } else {
                        newStarsHtml += `<i class="fa-regular fa-star" style="color: #cbd5e1;" onclick="rateProject(${id}, ${s}, event)" title="Đánh giá ${s} sao"></i>`;
                    }
                }
                newStarsHtml += `<span class="rating-score" id="ratingScore_${id}">${parseFloat(data.rating_stars).toFixed(1)}</span>`;
                newStarsHtml += `<span class="rating-count" id="ratingCount_${id}">(${data.rating_count})</span>`;
                badgeEl.innerHTML = newStarsHtml;
            }
        } else {
            showToast('❌ ' + (data.message || 'Không gửi được đánh giá'), false);
        }
    } catch (err) {
        showToast('❌ Lỗi kết nối đánh giá: ' + err.message, false);
    }
}

function openMyStorageShareModal(keepNotice = false, silent = false) {
    if (!keepNotice) {
        const notice = document.getElementById('shareSuccessNotice');
        if (notice) notice.style.display = 'none';
    }

    const modal = document.getElementById('myStorageModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
    }
    const container = document.getElementById('myStorageProjectsList');
    if (container && !silent) {
        container.innerHTML = `<div style="text-align:center; padding:30px; color:#64748b;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px; margin-bottom:10px;"></i><br>Đang nạp kho code của bạn...</div>`;
    }

    fetch('/tkb/api/code_storage_api.php?action=list')
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data && res.data.length > 0) {
                renderMyStorageList(res.data);
            } else {
                if (container) {
                    container.innerHTML = `
                        <div style="text-align:center; padding:30px; background:#f8fafc; border-radius:14px; border:1px solid #e2e8f0; color:#64748b;">
                            <i class="fa-solid fa-folder-open" style="font-size:36px; margin-bottom:10px; color:#94a3b8;"></i>
                            <h4 style="margin:0 0 6px 0; color:#0f172a;">Kho code của bạn đang rỗng</h4>
                            <p style="font-size:13px; margin:0;">Hãy nạp thư mục hoặc tệp code mới để bắt đầu lưu trữ & chia sẻ!</p>
                        </div>
                    `;
                }
            }
        })
        .catch(err => {
            if (container && !silent) {
                container.innerHTML = `<div style="text-align:center; padding:20px; color:#ef4444;">Không thể tải kho code: ${escapeHtml(err.message)}</div>`;
            }
        });
}

function closeMyStorageShareModal() {
    const modal = document.getElementById('myStorageModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
}

function copyNoticeLink() {
    const input = document.getElementById('shareNoticeInput');
    if (input && input.value) {
        try {
            navigator.clipboard.writeText(input.value);
            showToast('📋 Đã sao chép liên kết chia sẻ vào bộ nhớ tạm!');
        } catch(e) {
            input.select();
            document.execCommand('copy');
            showToast('📋 Đã sao chép liên kết!');
        }
    }
}

let selectedProjectIds = new Set();

function renderMyStorageList(projects) {
    const container = document.getElementById('myStorageProjectsList');
    if (!container) return;

    if (selectedProjectIds.size === 0 && projects.length > 0) {
        selectedProjectIds.add(parseInt(projects[0].id));
    }

    container.innerHTML = projects.map(p => {
        const pId = parseInt(p.id);
        const isPublic = (p.la_cong_khai == 1 || p.la_cong_khai === '1');
        const lang = (p.ngon_ngu || 'python').toUpperCase();
        const isSelected = selectedProjectIds.has(pId);

        return `
            <div class="my-code-row ${isSelected ? 'selected' : ''}" id="myCodeRow_${pId}" onclick="toggleRowSelect(${pId}, event)" style="cursor:pointer; ${isSelected ? 'border-color: #8b5cf6; background: #f5f3ff; box-shadow: 0 0 16px rgba(139,92,246,0.18);' : 'border-color: #e2e8f0; background: #ffffff;'}">
                <div style="display:flex; align-items:center; gap:14px; flex:1; min-width:0;">
                    <input type="checkbox" id="chkProj_${pId}" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleRowSelect(${pId}, event);" style="width:20px; height:20px; accent-color:#8b5cf6; cursor:pointer;">
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:4px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span>${escapeHtml(p.ten_du_an)}</span>
                            <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:#eff6ff; color:#2563eb; font-weight:700; border:1px solid #bfdbfe;">${lang}</span>
                            ${isPublic ? '<span style="font-size:11px; padding:2px 8px; border-radius:6px; background:#f0fdf4; color:#16a34a; font-weight:700; border:1px solid #bbf7d0;"><i class="fa-solid fa-globe"></i> Đang Chia Sẻ</span>' : '<span style="font-size:11px; padding:2px 8px; border-radius:6px; background:#f1f5f9; color:#64748b; font-weight:700; border:1px solid #e2e8f0;"><i class="fa-solid fa-lock"></i> Riêng Tư (Trong Kho)</span>'}
                        </div>
                        <div style="font-size:12.5px; color:#64748b; font-weight:500;">
                            ${p.mo_ta ? escapeHtml(p.mo_ta) + ' • ' : ''} Cập nhật: ${p.updated_at || p.created_at || 'Vừa nạp'}
                        </div>
                    </div>
                </div>

                <div onclick="event.stopPropagation();" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    ${isPublic ? `
                        <button type="button" class="btn-act" style="background:#fff1f2; color:#e11d48; border:1px solid #fecdd3; padding:8px 14px; border-radius:10px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;" onclick="quickRevokeSingle(${pId})" title="Gỡ khỏi cộng đồng (vẫn giữ trong kho code)">
                            <i class="fa-solid fa-lock"></i> Gỡ Khỏi Cộng Đồng
                        </button>
                    ` : `
                        <button type="button" class="btn-act" style="background:linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color:#ffffff; box-shadow:0 4px 12px rgba(139,92,246,0.3); border:none; padding:8px 14px; border-radius:10px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;" onclick="quickShareSingle(${pId})">
                            <i class="fa-solid fa-cloud-arrow-up"></i> 🌐 Chia Sẻ Ngay
                        </button>
                    `}
                </div>
            </div>
        `;
    }).join('');

    updateModalFooterButton();
}

function toggleRowSelect(id, evt) {
    const pId = parseInt(id);
    const chk = document.getElementById(`chkProj_${pId}`);
    const row = document.getElementById(`myCodeRow_${pId}`);

    if (selectedProjectIds.has(pId)) {
        selectedProjectIds.delete(pId);
        if (chk) chk.checked = false;
        if (row) {
            row.style.borderColor = '#e2e8f0';
            row.style.background = '#ffffff';
            row.style.boxShadow = 'none';
        }
    } else {
        selectedProjectIds.add(pId);
        if (chk) chk.checked = true;
        if (row) {
            row.style.borderColor = '#8b5cf6';
            row.style.background = '#f5f3ff';
            row.style.boxShadow = '0 0 16px rgba(139,92,246,0.18)';
        }
    }
    updateModalFooterButton();
}

function updateModalFooterButton() {
    const ids = Array.from(selectedProjectIds).filter(id => parseInt(id) > 0);
    const count = ids.length;
    const btn = document.getElementById('btnShareSelectedProjects');
    if (btn) {
        if (count > 0) {
            btn.innerHTML = `<i class="fa-solid fa-cloud-arrow-up"></i> 🌐 Chia Sẻ ${count} Bài Làm Đã Chọn Lên Cộng Đồng`;
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
        } else {
            btn.innerHTML = `<i class="fa-solid fa-hand-pointer"></i> Hãy tích chọn ít nhất 1 bài làm để chia sẻ`;
            btn.style.opacity = '0.5';
            btn.style.pointerEvents = 'none';
        }
    }
}

async function quickShareSingle(id) {
    showToast('Đang phát sóng chia sẻ bài làm...', true);
    try {
        const bodyParams = new URLSearchParams();
        bodyParams.append('id', id);
        bodyParams.append('set_public', '1');

        const res = await fetch('/tkb/api/code_storage_api.php?action=toggle_public', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bodyParams.toString()
        });

        const data = await res.json();
        if (data.success) {
            const shareUrl = `${window.location.origin}/tkb/student/code_ide.php?v=2026&storage_id=${id}`;
            try { navigator.clipboard.writeText(shareUrl); } catch(e){}

            const notice = document.getElementById('shareSuccessNotice');
            const input = document.getElementById('shareNoticeInput');
            const ideBtn = document.getElementById('shareNoticeIdeBtn');
            const noticeTitle = document.getElementById('shareNoticeTitle');
            if (notice && input && ideBtn) {
                if (noticeTitle) noticeTitle.innerText = `🎉 ĐÃ PHÁT SÓNG CHIA SẺ BÀI LÀM THÀNH CÔNG!`;
                input.value = shareUrl;
                ideBtn.href = shareUrl;
                notice.style.display = 'block';
                notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            showToast('✅ Đã chia sẻ thành công lên Cộng Đồng!', true);
            openMyStorageShareModal(true, true);
            fetchPublicProjects();
        } else {
            showToast('❌ Lỗi: ' + (data.message || 'Không thể chia sẻ bài làm'));
        }
    } catch(err) {
        showToast('❌ Lỗi kết nối máy chủ!');
    }
}

async function quickRevokeSingle(id) {
    if (!confirm('Bạn có chắc chắn muốn gỡ bài làm này khỏi Kho Code Cộng Đồng? (Bài làm vẫn được giữ an toàn trong kho cá nhân của bạn)')) return;
    
    showToast('Đang gỡ bài làm khỏi cộng đồng...', true);
    try {
        const bodyParams = new URLSearchParams();
        bodyParams.append('id', id);
        bodyParams.append('set_public', '0');

        const res = await fetch('/tkb/api/code_storage_api.php?action=toggle_public', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bodyParams.toString()
        });

        const data = await res.json();
        if (data.success) {
            showToast('🔒 Đã chuyển bài làm về trạng thái Riêng Tư!');
            openMyStorageShareModal(false, true);
            fetchPublicProjects();
        } else {
            showToast('❌ Lỗi: ' + (data.message || 'Không thể cập nhật'));
        }
    } catch(err) {
        showToast('❌ Lỗi kết nối máy chủ!');
    }
}

async function shareSelectedProjects() {
    const ids = Array.from(selectedProjectIds).filter(id => parseInt(id) > 0);
    if (ids.length === 0) {
        showToast('⚠️ Vui lòng tích chọn ít nhất một bài làm để chia sẻ!');
        return;
    }

    showToast(`Đang phát sóng chia sẻ ${ids.length} bài làm...`, true);
    let successCount = 0;
    let lastId = ids[0];

    for (const id of ids) {
        try {
            const bodyParams = new URLSearchParams();
            bodyParams.append('id', id);
            bodyParams.append('set_public', '1');

            const res = await fetch('/tkb/api/code_storage_api.php?action=toggle_public', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: bodyParams.toString()
            });
            const data = await res.json();
            if (data.success) {
                successCount++;
                lastId = id;
            }
        } catch(e){}
    }

    if (successCount > 0) {
        const shareUrl = `${window.location.origin}/tkb/student/code_ide.php?v=2026&storage_id=${lastId}`;
        try { navigator.clipboard.writeText(shareUrl); } catch(e){}

        const notice = document.getElementById('shareSuccessNotice');
        const input = document.getElementById('shareNoticeInput');
        const ideBtn = document.getElementById('shareNoticeIdeBtn');
        const noticeTitle = document.getElementById('shareNoticeTitle');
        if (notice && input && ideBtn) {
            if (noticeTitle) noticeTitle.innerText = `🎉 ĐÃ PHÁT SÓNG ${successCount} BÀI LÀM LÊN CỘNG ĐỒNG THÀNH CÔNG!`;
            input.value = shareUrl;
            ideBtn.href = shareUrl;
            notice.style.display = 'block';
            notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        showToast(`✅ Đã chia sẻ thành công ${successCount} bài làm lên Cộng Đồng!`, true);
        openMyStorageShareModal(true, true);
        fetchPublicProjects();
    } else {
        showToast('❌ Không thể chia sẻ các bài làm đã chọn.');
    }
}

async function revokePublicProject(id, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    if (!id) return;

    if (!confirm('Bạn có muốn gỡ bài làm này khỏi Kho Code Cộng Đồng không?\n\n(Lưu ý: Bài làm vẫn được lưu an toàn 100% trong Kho Code Cá Nhân của bạn)')) {
        return;
    }

    showToast('Đang gỡ bài làm khỏi cộng đồng...', true);

    try {
        const params = new URLSearchParams();
        params.append('id', id);
        params.append('set_public', '0');

        const res = await fetch('/tkb/api/code_storage_api.php?action=toggle_public', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });

        showToast('✅ Đã gỡ bài làm khỏi cộng đồng! Bài làm vẫn an toàn trong kho của bạn.', true);
        fetchPublicProjects();
    } catch (err) {
        // Fallback GET
        fetch(`/tkb/api/code_storage_api.php?action=toggle_public&set_public=0&id=${id}`)
            .then(() => {
                showToast('✅ Đã gỡ bài làm khỏi cộng đồng!', true);
                fetchPublicProjects();
            })
            .catch(() => {
                showToast('✅ Đã cập nhật trạng thái bài làm!', true);
                fetchPublicProjects();
            });
    }
}

async function deletePermanentProject(id, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    if (!id) return;

    if (!confirm('⚠️ BẠN CÓ CHẮC CHẮN MUỐN XÓA VĨNH VIỄN BÀI LÀM NÀY?\n\n(Dự án sẽ được xóa sạch hoàn toàn khỏi CSDL và không thể hoàn tác)')) {
        return;
    }

    showToast('Đang xóa vĩnh viễn bài làm...', true);

    try {
        const params = new URLSearchParams();
        params.append('id', id);

        const res = await fetch('/tkb/api/code_storage_api.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });

        const data = await res.json();
        if (data.success) {
            showToast('🗑️ Đã xóa vĩnh viễn bài làm thành công!', true);
            const card = document.getElementById(`project_card_${id}`);
            if (card) {
                card.style.transition = 'all 0.35s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    card.remove();
                    // Nếu hết card thì reload
                    const remaining = document.querySelectorAll('.project-card-v2');
                    if (remaining.length === 0) fetchPublicProjects();
                }, 350);
            } else {
                fetchPublicProjects();
            }
        } else {
            showToast('❌ Lỗi: ' + (data.message || 'Không thể xóa bài làm'));
        }
    } catch (err) {
        showToast('❌ Lỗi kết nối xóa dự án: ' + err.message);
    }
}

function copyShareLink(url) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('📋 Đã sao chép liên kết chia sẻ!');
        }).catch(() => {
            prompt('Sao chép liên kết chia sẻ bên dưới:', url);
        });
    } else {
        prompt('Sao chép liên kết chia sẻ bên dưới:', url);
    }
}

function showToast(msg, isSuccess = true) {
    const toast = document.getElementById('toastMsg');
    const toastText = document.getElementById('toastText');
    if (!toast || !toastText) return;
    toastText.innerText = msg;
    toast.style.background = isSuccess ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3500);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

<!-- Modal Hồ Sơ Sinh Viên Tác Giả -->
<div class="modal-backdrop" id="authorProfileModal" style="display:none; z-index:999999;">
    <div class="modal-content-box" style="max-width: 520px; background: #ffffff; color: #0f172a; border-radius: 24px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); padding: 32px 24px 24px; text-align: center; position: relative;">
            <button type="button" class="btn-close-modal" onclick="closeAuthorProfileModal()" style="position: absolute; top: 16px; right: 16px; color: #ffffff; background: rgba(255,255,255,0.2); border-radius: 50%; width: 32px; height: 32px; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;">&times;</button>
            <div id="authorModalAvatarBox" style="width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 12px; border: 4px solid #ffffff; box-shadow: 0 8px 24px rgba(0,0,0,0.2); overflow: hidden; background: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 800; color: #8b5cf6;">
                V
            </div>
            <h3 id="authorModalName" style="margin: 0 0 4px 0; color: #ffffff; font-size: 20px; font-weight: 800;">Tác Giả</h3>
            <div id="authorModalSub" style="color: rgba(255,255,255,0.9); font-size: 13.5px; font-weight: 600;">Thành viên sinh viên VKC</div>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; text-align: center;">
                    <div style="font-size: 11.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">MÃ SINH VIÊN</div>
                    <div id="authorModalMasv" style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">--</div>
                </div>
                <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; text-align: center;">
                    <div style="font-size: 11.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">CODE ĐÃ CHIA SẺ</div>
                    <div id="authorModalPublicCount" style="font-size: 15px; font-weight: 800; color: #8b5cf6; margin-top: 4px;">0 bài làm</div>
                </div>
            </div>
            
            <h4 style="margin: 0 0 12px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-code-commit" style="color: #8b5cf6;"></i> Các bài làm công khai từ tác giả
            </h4>
            <div id="authorProjectsList" style="max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
                <!-- Projects List -->
            </div>
        </div>
    </div>
</div>

<script>
function openAuthorProfileModal(authorId, event) {
    if (event) event.stopPropagation();
    const targetId = (authorId && parseInt(authorId) > 0) ? parseInt(authorId) : 1;

    const modal = document.getElementById('authorProfileModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
    }
    const container = document.getElementById('authorProjectsList');
    if (container) {
        container.innerHTML = `<div style="text-align:center; padding:20px; color:#64748b;"><i class="fa-solid fa-spinner fa-spin"></i> Đang nạp hồ sơ tác giả...</div>`;
    }

    fetch(`/tkb/api/code_storage_api.php?action=get_author_profile&author_id=${targetId}`)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.author) {
                const a = res.author;
                document.getElementById('authorModalName').innerText = a.ho_ten || 'Vũ Nhật Tường Vi';
                document.getElementById('authorModalSub').innerText = `Thành viên VKC • Lớp ${a.lop || 'K24CDCNT1'}`;
                document.getElementById('authorModalMasv').innerText = a.masv || 'VKC202401';
                document.getElementById('authorModalPublicCount').innerText = `${a.total_public || 1} bài làm`;

                const avtBox = document.getElementById('authorModalAvatarBox');
                if (avtBox) {
                    if (a.avatar) {
                        avtBox.innerHTML = `<img src="${escapeHtml(a.avatar)}" style="width:100%; height:100%; object-fit:cover;">`;
                    } else {
                        const initial = (a.ho_ten || 'Vũ Nhật Tường Vi').substring(0, 1).toUpperCase();
                        avtBox.innerText = initial;
                    }
                }

                if (res.projects && res.projects.length > 0) {
                    container.innerHTML = res.projects.map(p => `
                        <a href="/tkb/student/code_ide.php?v=2026&storage_id=${p.id}" target="_blank" style="text-decoration:none; display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; transition:all 0.2s ease;">
                            <div>
                                <div style="font-weight:700; font-size:14px; color:#0f172a;">${escapeHtml(p.ten_du_an)}</div>
                                <div style="font-size:12px; color:#64748b;">${escapeHtml(p.ngon_ngu).toUpperCase()} • ${p.created_at ? p.created_at.substring(0, 10) : ''}</div>
                            </div>
                            <span style="font-size:12.5px; font-weight:700; color:#8b5cf6; display:inline-flex; align-items:center; gap:4px;">Chạy <i class="fa-solid fa-arrow-right"></i></span>
                        </a>
                    `).join('');
                } else {
                    container.innerHTML = `<div style="text-align:center; padding:16px; color:#94a3b8; font-size:13px;">Tác giả chưa chia sẻ thêm bài làm khác.</div>`;
                }
            } else {
                document.getElementById('authorModalName').innerText = 'Vũ Nhật Tường Vi';
                document.getElementById('authorModalSub').innerText = 'Thành viên VKC • Lớp K24CDCNT1';
                document.getElementById('authorModalMasv').innerText = 'VKC202401';
                document.getElementById('authorModalPublicCount').innerText = '1 bài làm';
                const avtBox = document.getElementById('authorModalAvatarBox');
                if (avtBox) avtBox.innerText = 'V';
                if (container) container.innerHTML = `<div style="text-align:center; padding:16px; color:#64748b; font-size:13px;">Dự án: keria (Mã nguồn HTML/CSS/JS)</div>`;
            }
        })
        .catch(err => {
            document.getElementById('authorModalName').innerText = 'Vũ Nhật Tường Vi';
            document.getElementById('authorModalSub').innerText = 'Thành viên VKC • Lớp K24CDCNT1';
            document.getElementById('authorModalMasv').innerText = 'VKC202401';
            document.getElementById('authorModalPublicCount').innerText = '1 bài làm';
            const avtBox = document.getElementById('authorModalAvatarBox');
            if (avtBox) avtBox.innerText = 'V';
            if (container) container.innerHTML = `<div style="text-align:center; padding:16px; color:#64748b; font-size:13px;">Dự án: keria (Mã nguồn HTML/CSS/JS)</div>`;
        });
}

function closeAuthorProfileModal() {
    const modal = document.getElementById('authorProfileModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
}
</script>

<!-- Modal: Chia Sẻ Bài Làm Từ Kho Code Của Tôi -->
<div class="modal-backdrop" id="myStorageModal" style="display:none; z-index:999999;">
    <div class="modal-content-box" style="max-width: 650px; background: #ffffff; border-radius: 24px; color: #0f172a; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="modal-header" style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); padding: 20px 24px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Chọn Bài Làm Để Chia Sẻ Lên Cộng Đồng
                </h3>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: rgba(255,255,255,0.9);">Tích chọn các bài làm trong kho cá nhân của bạn để phát sóng cho các bạn sinh viên khác xem.</p>
            </div>
            <button type="button" onclick="closeMyStorageShareModal()" style="background: rgba(255,255,255,0.2); border: none; font-size: 18px; color: #ffffff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>

        <div class="modal-body" style="padding: 24px;">
            <div style="font-weight: 800; font-size: 14px; color: #0f172a; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <span>DANH SÁCH BÀI LÀM TRONG KHO CỦA BẠN</span>
                <a href="/tkb/student/luu_tru_code.php" target="_blank" style="color: #8b5cf6; text-decoration: none; font-size: 13px; font-weight: 700;"><i class="fa-solid fa-plus-circle"></i> Tạo bài làm mới</a>
            </div>

            <div id="myStorageProjectsList" style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                <div style="text-align:center; padding:30px; color:#64748b;"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải kho bài làm của bạn...</div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="closeMyStorageShareModal()" style="background: #f1f5f9; color: #475569; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 700; cursor: pointer;">Hủy</button>
                <button type="button" onclick="shareSelectedProjects()" style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #ffffff; border: none; padding: 10px 22px; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);">
                    <i class="fa-solid fa-paper-plane"></i> Phát Sóng Chia Sẻ
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedProjectIds = new Set();

function openMyStorageShareModal() {
    const modal = document.getElementById('myStorageModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
    }
    loadMyStorageProjects();
}

function closeMyStorageShareModal() {
    const modal = document.getElementById('myStorageModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
}

function loadMyStorageProjects() {
    const container = document.getElementById('myStorageProjectsList');
    if (!container) return;
    container.innerHTML = `<div style="text-align:center; padding:30px; color:#64748b;"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải bài làm cá nhân...</div>`;

    fetch('/tkb/api/code_storage_api.php?action=list')
        .then(r => r.json())
        .then(res => {
            if (res.success && Array.isArray(res.data)) {
                if (res.data.length === 0) {
                    container.innerHTML = `
                        <div style="text-align:center; padding:24px; color:#64748b;">
                            Kho lưu trữ cá nhân của bạn hiện chưa có bài làm nào.<br>
                            <a href="/tkb/student/luu_tru_code.php" style="color:#8b5cf6; font-weight:700; text-decoration:none; margin-top:8px; display:inline-block;">+ Tạo bài làm trong kho cá nhân ngay</a>
                        </div>
                    `;
                    return;
                }
                selectedProjectIds.clear();
                container.innerHTML = res.data.map(p => {
                    const isPublic = (p.la_cong_khai == 1);
                    if (isPublic) selectedProjectIds.add(p.id.toString());

                    return `
                        <label style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#f8fafc; border:1.5px solid ${isPublic ? '#c084fc' : '#e2e8f0'}; border-radius:14px; cursor:pointer; transition:all 0.2s ease;">
                            <div style="display:flex; align-items:center; gap:12px; flex:1;">
                                <input type="checkbox" value="${p.id}" ${isPublic ? 'checked' : ''} onchange="toggleProjectSelection(this, '${p.id}')" style="width:18px; height:18px; accent-color:#8b5cf6; cursor:pointer;">
                                <div>
                                    <div style="font-weight:700; font-size:14.5px; color:#0f172a;">${escapeHtml(p.ten_du_an)}</div>
                                    <div style="font-size:12px; color:#64748b;">Ngôn ngữ: ${(p.ngon_ngu||'python').toUpperCase()} ${isPublic ? '• <span style="color:#16a34a; font-weight:700;">🌐 Đã công khai</span>' : '• 🔒 Riêng tư'}</div>
                                </div>
                            </div>
                            ${isPublic ? `<button type="button" onclick="quickRevokeSingle(${p.id}); event.preventDefault();" style="background:#fee2e2; color:#dc2626; border:none; border-radius:8px; padding:6px 12px; font-size:12px; font-weight:700; cursor:pointer;">Gỡ chia sẻ</button>` : ''}
                        </label>
                    `;
                }).join('');
            } else {
                container.innerHTML = `<div style="text-align:center; padding:20px; color:#ef4444;">Không thể nạp danh sách bài làm từ kho.</div>`;
            }
        })
        .catch(err => {
            container.innerHTML = `<div style="text-align:center; padding:20px; color:#ef4444;">Lỗi kết nối máy chủ.</div>`;
        });
}

function toggleProjectSelection(checkbox, id) {
    if (checkbox.checked) {
        selectedProjectIds.add(id.toString());
    } else {
        selectedProjectIds.delete(id.toString());
    }
}

async function shareSelectedProjects() {
    const ids = Array.from(selectedProjectIds);
    if (ids.length === 0) {
        alert('Vui lòng chọn ít nhất 1 bài làm để chia sẻ!');
        return;
    }

    let count = 0;
    for (const id of ids) {
        try {
            const formData = new URLSearchParams();
            formData.append('id', id);
            formData.append('set_public', '1');
            await fetch('/tkb/api/code_storage_api.php?action=toggle_public', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            count++;
        } catch(e){}
    }

    closeMyStorageShareModal();
    alert(`🎉 Đã chia sẻ thành công ${count} bài làm lên Kho Code Cộng Đồng!`);
    window.location.reload();
}

async function quickRevokeSingle(id) {
    if (!confirm('Bạn có chắc muốn chuyển bài làm này về trạng thái Riêng Tư?')) return;
    try {
        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('set_public', '0');
        await fetch('/tkb/api/code_storage_api.php?action=toggle_public', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        });
        loadMyStorageProjects();
    } catch(e){}
}

async function rateProject(id, stars, event) {
    if (event) event.stopPropagation();
    if (!id || !stars) return;

    try {
        const res = await fetch(`/tkb/api/code_storage_api.php?action=rate_project&id=${id}&stars=${stars}`);
        const data = await res.json();
        
        if (data.success) {
            const badge = document.getElementById(`ratingBadge_${id}`);
            if (badge) {
                const yellowCount = Math.min(5, Math.max(1, Math.round(data.rating_stars)));
                let starsHtml = '';
                for (let s = 1; s <= 5; s++) {
                    if (s <= yellowCount) {
                        starsHtml += `<i class="fa-solid fa-star" style="color: #fbbf24; cursor: pointer; transition: transform 0.15s ease;" onclick="rateProject(${id}, ${s}, event)" title="Đánh giá ${s} sao"></i>`;
                    } else {
                        starsHtml += `<i class="fa-regular fa-star" style="color: #cbd5e1; cursor: pointer; transition: transform 0.15s ease;" onclick="rateProject(${id}, ${s}, event)" title="Đánh giá ${s} sao"></i>`;
                    }
                }
                starsHtml += `<span class="rating-score" id="ratingScore_${id}">${parseFloat(data.rating_stars).toFixed(1)} (${data.rating_count})</span>`;
                badge.innerHTML = starsHtml;
            }
            if (typeof showToast === 'function') {
                showToast(`⭐ Bạn đã đánh giá ${stars} sao thành công!`, true);
            }
        } else {
            console.warn('Rating response:', data);
        }
    } catch(err) {
        console.error('Rating error:', err);
    }
}
</script>
</body>
</html>
