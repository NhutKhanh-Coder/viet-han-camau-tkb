<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

$tab = $_GET['tab'] ?? 'assignments';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Xử lý Xóa Bài Tập Giáo Viên
if ($action === 'delete_assignment') {
    $aid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($aid > 0) {
        $stmt = $db->prepare("SELECT tieu_de, file_path FROM assignments WHERE id = ?");
        $stmt->bind_param("i", $aid);
        $stmt->execute();
        $assign = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($assign) {
            // Xóa file đính kèm nếu có
            if (!empty($assign['file_path'])) {
                $fpath = '..' . str_replace('/tkb', '', $assign['file_path']);
                if (file_exists($fpath) && is_file($fpath)) {
                    @unlink($fpath);
                }
            }
            // Xóa bài nộp liên quan
            @$db->query("DELETE FROM submissions WHERE assignment_id = $aid");
            // Xóa bài tập
            $del = $db->prepare("DELETE FROM assignments WHERE id = ?");
            $del->bind_param("i", $aid);
            $del->execute();
            $del->close();

            writeSystemLog("Quản trị viên xóa bài tập: " . $assign['tieu_de'] . " (ID: $aid)");
            $msg = "success:Đã xóa bài tập \"" . htmlspecialchars($assign['tieu_de']) . "\" và toàn bộ bài nộp liên quan thành công!";
        } else {
            $msg = "error:Không tìm thấy bài tập cần xóa!";
        }
    }
}

// Xử lý Xóa Tài Liệu Giáo Viên
if ($action === 'delete_tailieu') {
    $tid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($tid > 0) {
        $stmt = $db->prepare("SELECT ten_tai_lieu, file_path, link_download FROM tai_lieu WHERE id = ?");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($doc) {
            $del_path = $doc['file_path'] ?: $doc['link_download'];
            if (!empty($del_path) && strpos($del_path, 'http') === false) {
                $fpath = '..' . str_replace('/tkb', '', $del_path);
                if (file_exists($fpath) && is_file($fpath)) {
                    @unlink($fpath);
                }
            }
            $del = $db->prepare("DELETE FROM tai_lieu WHERE id = ?");
            $del->bind_param("i", $tid);
            $del->execute();
            $del->close();

            writeSystemLog("Quản trị viên xóa tài liệu học tập: " . $doc['ten_tai_lieu'] . " (ID: $tid)");
            $msg = "success:Đã xóa tài liệu \"" . htmlspecialchars($doc['ten_tai_lieu']) . "\" thành công!";
        } else {
            $msg = "error:Không tìm thấy tài liệu cần xóa!";
        }
    }
}

// Xử lý Xóa Bài Đăng Dự Án / Code Sinh Viên
if ($action === 'delete_project') {
    $pid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($pid > 0) {
        $stmt = $db->prepare("SELECT ten_du_an, student_id FROM student_code_storage WHERE id = ?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $proj = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($proj) {
            $del = $db->prepare("DELETE FROM student_code_storage WHERE id = ?");
            $del->bind_param("i", $pid);
            $del->execute();
            $del->close();

            writeSystemLog("Quản trị viên xóa bài đăng dự án sinh viên: " . $proj['ten_du_an'] . " (ID: $pid)");
            $msg = "success:Đã xóa bài đăng dự án \"" . htmlspecialchars($proj['ten_du_an']) . "\" của sinh viên thành công!";
        } else {
            $msg = "error:Không tìm thấy bài đăng dự án cần xóa!";
        }
    }
}

// Tham số tìm kiếm
$search = trim($_GET['search'] ?? '');

// Đếm số lượng tổng quan
$cnt_assign = $db->query("SELECT COUNT(*) as c FROM assignments")->fetch_assoc()['c'] ?? 0;
$cnt_tailieu = 0;
$chk_tl = $db->query("SHOW TABLES LIKE 'tai_lieu'");
if ($chk_tl && $chk_tl->num_rows > 0) {
    $cnt_tailieu = $db->query("SELECT COUNT(*) as c FROM tai_lieu")->fetch_assoc()['c'] ?? 0;
}
$cnt_proj = 0;
$chk_pr = $db->query("SHOW TABLES LIKE 'student_code_storage'");
if ($chk_pr && $chk_pr->num_rows > 0) {
    $cnt_proj = $db->query("SELECT COUNT(*) as c FROM student_code_storage")->fetch_assoc()['c'] ?? 0;
}

// Truy vấn dữ liệu theo từng Tab
$assignments_list = [];
$tailieu_list = [];
$projects_list = [];

if ($tab === 'assignments') {
    $q_assign = "
        SELECT a.*, 
               COALESCE(m.ten_mon, 'Môn học') as ten_mon, 
               COALESCE(g.ho_ten, u.ho_ten, 'Ban Giảng Viên') as ten_gv,
               (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) as sub_count
        FROM assignments a
        LEFT JOIN mon_hoc m ON a.mon_hoc_id = m.id
        LEFT JOIN giang_vien g ON a.giang_vien_id = g.id
        LEFT JOIN users u ON a.giang_vien_id = u.id
        WHERE 1=1
    ";
    if (!empty($search)) {
        $sq = $db->real_escape_string($search);
        $q_assign .= " AND (a.tieu_de LIKE '%$sq%' OR a.lop LIKE '%$sq%' OR m.ten_mon LIKE '%$sq%' OR g.ho_ten LIKE '%$sq%')";
    }
    $q_assign .= " ORDER BY a.id DESC";
    $res = $db->query($q_assign);
    if ($res) $assignments_list = $res->fetch_all(MYSQLI_ASSOC);
} elseif ($tab === 'tailieu') {
    if ($chk_tl && $chk_tl->num_rows > 0) {
        $q_tl = "
            SELECT t.*, 
                   COALESCE(m.ten_mon, 'Tài liệu chung') as ten_mon, 
                   COALESCE(g.ho_ten, u.ho_ten, 'Ban Giảng Viên') as ten_gv
            FROM tai_lieu t
            LEFT JOIN mon_hoc m ON t.mon_hoc_id = m.id
            LEFT JOIN giang_vien g ON t.giang_vien_id = g.id
            LEFT JOIN users u ON t.giang_vien_id = u.id
            WHERE 1=1
        ";
        if (!empty($search)) {
            $sq = $db->real_escape_string($search);
            $q_tl .= " AND (t.ten_tai_lieu LIKE '%$sq%' OR m.ten_mon LIKE '%$sq%' OR g.ho_ten LIKE '%$sq%')";
        }
        $q_tl .= " ORDER BY t.id DESC";
        $res = $db->query($q_tl);
        if ($res) $tailieu_list = $res->fetch_all(MYSQLI_ASSOC);
    }
} elseif ($tab === 'projects') {
    if ($chk_pr && $chk_pr->num_rows > 0) {
        $q_pr = "
            SELECT p.id, p.ten_du_an, p.ngon_ngu, p.mo_ta, p.created_at, p.updated_at,
                   COALESCE(s.ho_ten, u.ho_ten, 'Sinh viên') as ten_sinh_vien,
                   s.ma_sv, s.lop
            FROM student_code_storage p
            LEFT JOIN students s ON p.student_id = s.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE 1=1
        ";
        if (!empty($search)) {
            $sq = $db->real_escape_string($search);
            $q_pr .= " AND (p.ten_du_an LIKE '%$sq%' OR p.ngon_ngu LIKE '%$sq%' OR s.ho_ten LIKE '%$sq%' OR s.ma_sv LIKE '%$sq%')";
        }
        $q_pr .= " ORDER BY p.id DESC";
        $res = $db->query($q_pr);
        if ($res) $projects_list = $res->fetch_all(MYSQLI_ASSOC);
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý &amp; Xóa Bài Đăng - Hệ Thống Quản Trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .post-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(168, 85, 247, 0.2);
            padding-bottom: 12px;
            flex-wrap: wrap;
        }
        body.tuyen-theme .post-tabs {
            border-bottom-color: #e2e8f0;
        }
        .post-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            color: #a79bb7;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(168, 85, 247, 0.15);
            transition: all 0.2s ease;
        }
        body.tuyen-theme .post-tab-btn {
            color: #64748b;
            background: #ffffff;
            border-color: #e2e8f0;
        }
        .post-tab-btn:hover {
            color: #f3e8ff;
            background: rgba(168, 85, 247, 0.15);
            border-color: rgba(168, 85, 247, 0.35);
        }
        body.tuyen-theme .post-tab-btn:hover {
            color: #7c3aed;
            background: #f5f3ff;
            border-color: #c4b5fd;
        }
        .post-tab-btn.active {
            color: #ffffff !important;
            background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%) !important;
            border-color: transparent !important;
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.35);
        }
        body.tuyen-theme .post-tab-btn.active {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.25);
        }
        .tab-badge {
            font-size: 11px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
    </style>
</head>
<body class="admin-portal">
    <?php include '../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-file-pen" style="color:#ec4899;"></i> Quản lý &amp; Xóa Bài Đăng</h1>
                <p class="page-sub">Xem xét, kiểm duyệt và xóa các bài tập, tài liệu học tập của giảng viên và bài đăng dự án sinh viên</p>
            </div>
        </div>

        <!-- Alert messages -->
        <?php if ($msg): 
            $parts = explode(':', $msg, 2);
            $type = $parts[0];
            $text = $parts[1] ?? '';
        ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>">
                <i class="fa-solid <?= $type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <?= htmlspecialchars($text) ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="post-tabs">
            <a href="?tab=assignments&search=<?= urlencode($search) ?>" class="post-tab-btn <?= $tab === 'assignments' ? 'active' : '' ?>">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Bài Tập Giáo Viên</span>
                <span class="tab-badge"><?= $cnt_assign ?></span>
            </a>
            <a href="?tab=tailieu&search=<?= urlencode($search) ?>" class="post-tab-btn <?= $tab === 'tailieu' ? 'active' : '' ?>">
                <i class="fa-solid fa-book-open"></i>
                <span>Tài Liệu &amp; Bài Giảng</span>
                <span class="tab-badge"><?= $cnt_tailieu ?></span>
            </a>
            <a href="?tab=projects&search=<?= urlencode($search) ?>" class="post-tab-btn <?= $tab === 'projects' ? 'active' : '' ?>">
                <i class="fa-solid fa-code"></i>
                <span>Dự Án / Code Sinh Viên</span>
                <span class="tab-badge"><?= $cnt_proj ?></span>
            </a>
        </div>

        <!-- Search & Filter Form -->
        <div class="filter-bar">
            <form method="GET" style="display:flex; gap:10px; flex:1; align-items:center;">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <div class="search-wrap" style="flex:1;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Tìm theo tiêu đề, tác giả, môn học, lớp..." class="search-input" value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn btn-ghost"><i class="fa-solid fa-filter"></i> Lọc</button>
                <?php if (!empty($search)): ?>
                    <a href="?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-ghost" title="Hủy tìm kiếm"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <!-- TAB 1: BÀI TẬP GIÁO VIÊN GIAO -->
        <?php if ($tab === 'assignments'): ?>
            <div class="card">
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:70px; text-align:center;">ID</th>
                                <th>Tiêu đề bài tập</th>
                                <th style="width:200px;">Môn học &amp; Lớp</th>
                                <th style="width:190px;">Giảng viên đăng</th>
                                <th style="width:140px;">Hạn nộp</th>
                                <th style="width:100px; text-align:center;">Bài nộp</th>
                                <th style="width:120px; text-align:center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignments_list)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">Không tìm thấy bài tập nào.</td>
                                </tr>
                            <?php else: foreach ($assignments_list as $as): ?>
                                <tr>
                                    <td style="text-align:center; font-weight:700; color:#94a3b8; font-family:monospace;">#<?= $as['id'] ?></td>
                                    <td>
                                        <div style="font-weight:700; color:#0f172a; margin-bottom:3px; font-size:13.5px;">
                                            <?= htmlspecialchars($as['tieu_de']) ?>
                                        </div>
                                        <?php if (!empty($as['mo_ta'])): ?>
                                            <div style="font-size:11.5px; color:#64748b; line-height:1.4; max-width:400px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                <?= htmlspecialchars(strip_tags($as['mo_ta'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($as['file_path'])): ?>
                                            <a href="<?= htmlspecialchars($as['file_path']) ?>" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#0284c7; text-decoration:none; margin-top:3px; background:#f0f9ff; padding:2px 6px; border-radius:4px; border:1px solid #bae6fd;">
                                                <i class="fa-solid fa-paperclip"></i> Đính kèm
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="color:#0f172a; display:block; font-size:12.5px;"><?= htmlspecialchars($as['ten_mon']) ?></strong>
                                        <span style="font-size:11px; color:#64748b;">Lớp: <code style="color:#e11d48; background:#f8fafc; padding:1px 5px; border-radius:4px; border:1px solid #e2e8f0;"><?= htmlspecialchars($as['lop']) ?></code></span>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <i class="fa-solid fa-chalkboard-user" style="color:#3b82f6;"></i>
                                            <span style="font-weight:600; color:#0f172a; font-size:12.5px;"><?= htmlspecialchars($as['ten_gv']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($as['han_nop'])): ?>
                                            <span style="font-size:11.5px; color:#475569; font-weight:600;">
                                                <?= date('d/m/Y H:i', strtotime($as['han_nop'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:11px;">Không giới hạn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="display:inline-block; font-size:12px; font-weight:800; background:#f0fdf4; color:#16a34a; padding:3px 9px; border-radius:20px; border:1px solid #bbf7d0;">
                                            <?= $as['sub_count'] ?> bài
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="?tab=assignments&action=delete_assignment&id=<?= $as['id'] ?>&search=<?= urlencode($search) ?>" onclick="return confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN bài tập này?\nMọi bài nộp của học viên liên quan cũng sẽ bị xóa!')" class="btn btn-danger btn-sm" title="Xóa bài tập" style="background:#fee2e2 !important; color:#dc2626 !important; border:1px solid #fca5a5 !important; padding:6px 12px !important; width:auto !important; font-weight:700; gap:4px;">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 2: TÀI LIỆU & BÀI GIẢNG -->
        <?php if ($tab === 'tailieu'): ?>
            <div class="card">
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:70px; text-align:center;">ID</th>
                                <th>Tên tài liệu / Bài giảng</th>
                                <th style="width:220px;">Môn học</th>
                                <th style="width:200px;">Giảng viên đăng</th>
                                <th style="width:140px;">Ngày đăng</th>
                                <th style="width:120px; text-align:center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tailieu_list)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">Không tìm thấy tài liệu nào.</td>
                                </tr>
                            <?php else: foreach ($tailieu_list as $tl): ?>
                                <tr>
                                    <td style="text-align:center; font-weight:700; color:#94a3b8; font-family:monospace;">#<?= $tl['id'] ?></td>
                                    <td>
                                        <div style="font-weight:700; color:#0f172a; margin-bottom:3px; font-size:13.5px;">
                                            <?= htmlspecialchars($tl['ten_tai_lieu']) ?>
                                        </div>
                                        <?php 
                                        $file_link = $tl['file_path'] ?? $tl['link_download'] ?? '';
                                        if (!empty($file_link)): ?>
                                            <a href="<?= htmlspecialchars($file_link) ?>" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#7c3aed; text-decoration:none; background:#f5f3ff; padding:2px 8px; border-radius:4px; border:1px solid #ddd6fe;">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Mở xem file / liên kết
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="color:#0f172a; font-size:12.5px;"><?= htmlspecialchars($tl['ten_mon']) ?></strong>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <i class="fa-solid fa-chalkboard-user" style="color:#8b5cf6;"></i>
                                            <span style="font-weight:600; color:#0f172a; font-size:12.5px;"><?= htmlspecialchars($tl['ten_gv']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size:11.5px; color:#64748b;">
                                            <?= !empty($tl['created_at']) ? date('d/m/Y', strtotime($tl['created_at'])) : '-' ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="?tab=tailieu&action=delete_tailieu&id=<?= $tl['id'] ?>&search=<?= urlencode($search) ?>" onclick="return confirm('Bạn có chắc chắn muốn XÓA tài liệu này?')" class="btn btn-danger btn-sm" title="Xóa tài liệu" style="background:#fee2e2 !important; color:#dc2626 !important; border:1px solid #fca5a5 !important; padding:6px 12px !important; width:auto !important; font-weight:700; gap:4px;">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 3: DỰ ÁN & CODE SINH VIÊN -->
        <?php if ($tab === 'projects'): ?>
            <div class="card">
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:70px; text-align:center;">ID</th>
                                <th>Tên dự án / Bài đăng code</th>
                                <th style="width:120px;">Ngôn ngữ</th>
                                <th style="width:220px;">Học viên đăng</th>
                                <th style="width:140px;">Ngày đăng</th>
                                <th style="width:120px; text-align:center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($projects_list)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">Không tìm thấy bài đăng dự án sinh viên nào.</td>
                                </tr>
                            <?php else: foreach ($projects_list as $pr): ?>
                                <tr>
                                    <td style="text-align:center; font-weight:700; color:#94a3b8; font-family:monospace;">#<?= $pr['id'] ?></td>
                                    <td>
                                        <div style="font-weight:700; color:#0f172a; margin-bottom:3px; font-size:13.5px;">
                                            <?= htmlspecialchars($pr['ten_du_an']) ?>
                                        </div>
                                        <?php if (!empty($pr['mo_ta'])): ?>
                                            <div style="font-size:11.5px; color:#64748b; line-height:1.4; max-width:400px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                <?= htmlspecialchars(strip_tags($pr['mo_ta'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <a href="/tkb/student/code_ide.php?storage_id=<?= $pr['id'] ?>" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#0284c7; text-decoration:none; margin-top:3px; background:#f0f9ff; padding:2px 7px; border-radius:4px; border:1px solid #bae6fd;">
                                            <i class="fa-solid fa-code"></i> Xem mã nguồn IDE
                                        </a>
                                    </td>
                                    <td>
                                        <span style="font-family:monospace; font-size:12px; font-weight:700; background:#f1f5f9; color:#0f172a; padding:3px 8px; border-radius:6px; border:1px solid #e2e8f0;">
                                            <?= htmlspecialchars(strtoupper($pr['ngon_ngu'] ?: 'TXT')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color:#0f172a; font-size:12.5px; display:block;"><?= htmlspecialchars($pr['ten_sinh_vien']) ?></strong>
                                        <span style="font-size:11px; color:#64748b;">Mã SV: <code style="color:#7c3aed; background:#faf5ff; padding:1px 5px; border-radius:4px; border:1px solid #f3e8ff;"><?= htmlspecialchars($pr['ma_sv'] ?: 'N/A') ?></code> • Lớp: <?= htmlspecialchars($pr['lop'] ?: '-') ?></span>
                                    </td>
                                    <td>
                                        <span style="font-size:11.5px; color:#64748b;">
                                            <?= !empty($pr['created_at']) ? date('d/m/Y', strtotime($pr['created_at'])) : '-' ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="?tab=projects&action=delete_project&id=<?= $pr['id'] ?>&search=<?= urlencode($search) ?>" onclick="return confirm('Bạn có chắc chắn muốn XÓA bài đăng dự án này của sinh viên?')" class="btn btn-danger btn-sm" title="Xóa bài đăng dự án" style="background:#fee2e2 !important; color:#dc2626 !important; border:1px solid #fca5a5 !important; padding:6px 12px !important; width:auto !important; font-weight:700; gap:4px;">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
