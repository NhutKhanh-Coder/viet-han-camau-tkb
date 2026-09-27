<?php
require_once '../config.php';
requireTeacher();

$db = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
$gv_name = $_SESSION['ho_ten'] ?? 'Giảng viên';

$msg = '';
$msg_type = 'success';

// Handle Action: Send Reminder Notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_reminder') {
    $target_lop = trim($_POST['target_lop'] ?? '');
    $reminder_title = trim($_POST['reminder_title'] ?? '');
    $reminder_content = trim($_POST['reminder_content'] ?? '');
    $reminder_type = trim($_POST['reminder_type'] ?? 'Thông báo');

    if (!empty($reminder_title) && !empty($reminder_content)) {
        $stmt_rem = $db->prepare("INSERT INTO thong_bao (tieu_de, noi_dung, ngay_dang, trang_thai, khoa, loai, giang_vien_id, tac_gia) VALUES (?, ?, CURDATE(), 'Đã xuất bản', ?, ?, ?, ?)");
        $khoa_tag = !empty($target_lop) ? "Lớp " . $target_lop : "Công Nghệ Thông Tin";
        $stmt_rem->bind_param("sssis", $reminder_title, $reminder_content, $khoa_tag, $reminder_type, $gv_id, $gv_name);
        if ($stmt_rem->execute()) {
            $msg = "Đã gửi thông báo nhắc nhở thành công cho sinh viên!";
            $msg_type = 'success';
        } else {
            $msg = "Lỗi khi gửi thông báo: " . $db->error;
            $msg_type = 'danger';
        }
    } else {
        $msg = "Vui lòng nhập đầy đủ tiêu đề và nội dung nhắc nhở.";
        $msg_type = 'danger';
    }
}

// Filters
$filter_lop = trim($_GET['lop'] ?? '');
$filter_mon = (int)($_GET['mon_hoc_id'] ?? 0);
$filter_tab = trim($_GET['tab'] ?? 'baitap'); // baitap or diem

// Fetch list of classes for filter dropdown
$classes = [];
$res_c = $db->query("SELECT DISTINCT lop FROM students WHERE lop IS NOT NULL AND lop != '' ORDER BY lop");
if ($res_c) {
    while ($row = $res_c->fetch_assoc()) {
        $classes[] = $row['lop'];
    }
}

// Fetch list of subjects for filter dropdown
$subjects = [];
$res_m = $db->query("SELECT id, ma_mon, ten_mon FROM mon_hoc ORDER BY ten_mon");
if ($res_m) {
    while ($row = $res_m->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// -------------------------------------------------------------
// DATA ANALYTICS QUERIES
// -------------------------------------------------------------

// 1. Missing Assignments & Practice Sessions Query
// (Liên kết trực tiếp bảng assignments và practice_sessions. Khi xóa bài bên Quản lý thực hành, bài đó sẽ tự động mất khỏi báo cáo)
$sql_unsubmitted = "
    SELECT 
        sv.id AS student_id,
        sv.ma_sv,
        sv.ho_ten,
        sv.lop,
        sv.email,
        sv.sdt,
        a.id AS assignment_id,
        a.tieu_de AS assignment_title,
        a.han_nop,
        mh.ten_mon AS ten_mon_hoc,
        'Bài Tập' AS loai_bai,
        CASE 
            WHEN a.han_nop IS NOT NULL AND a.han_nop < NOW() THEN 'quahan'
            ELSE 'conhan'
        END AS trang_thai_nop
    FROM assignments a
    JOIN students sv ON a.lop = sv.lop
    JOIN mon_hoc mh ON a.mon_hoc_id = mh.id
    LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sv.id = sub.student_id
    WHERE sub.id IS NULL
";

if (!empty($filter_lop)) {
    $sql_unsubmitted .= " AND sv.lop = '" . $db->real_escape_string($filter_lop) . "'";
}
if ($filter_mon > 0) {
    $sql_unsubmitted .= " AND a.mon_hoc_id = " . $filter_mon;
}

$sql_unsubmitted .= " UNION ALL ";

// Thêm các phiên Bài thi / Thực hành từ bảng practice_sessions
$sql_unsubmitted .= "
    SELECT 
        sv.id AS student_id,
        sv.ma_sv,
        sv.ho_ten,
        sv.lop,
        sv.email,
        sv.sdt,
        ps.id AS assignment_id,
        CONCAT('[Thực Hành] ', IFNULL(ps.mo_ta, 'Phiên thi thực hành')) AS assignment_title,
        ps.end_time AS han_nop,
        'Thực Hành / Lập Trình' AS ten_mon_hoc,
        'Bài Thi Thực Hành' AS loai_bai,
        CASE 
            WHEN ps.end_time IS NOT NULL AND ps.end_time < NOW() THEN 'quahan'
            ELSE 'conhan'
        END AS trang_thai_nop
    FROM practice_sessions ps
    JOIN students sv ON ps.lop = sv.lop
    LEFT JOIN student_code_storage scs ON ps.id = scs.session_id AND sv.id = scs.student_id
    WHERE scs.id IS NULL AND ps.is_enabled = 1
";

if (!empty($filter_lop)) {
    $sql_unsubmitted .= " AND sv.lop = '" . $db->real_escape_string($filter_lop) . "'";
}

$sql_unsubmitted .= " ORDER BY han_nop ASC, lop ASC, ho_ten ASC";

$res_unsub = $db->query($sql_unsubmitted);
$unsubmitted_list = [];
$count_quahan = 0;
$count_conhan = 0;

if ($res_unsub) {
    while ($row = $res_unsub->fetch_assoc()) {
        $unsubmitted_list[] = $row;
        if ($row['trang_thai_nop'] === 'quahan') {
            $count_quahan++;
        } else {
            $count_conhan++;
        }
    }
}

// 2. Missing Exam / Grades Query
// Chỉ thống kê các môn học mà sinh viên đã được phân công/nhập bảng điểm nhưng bị khuyết điểm Giữa kỳ / Cuối kỳ
$sql_missing_grades = "
    SELECT 
        sv.id AS student_id,
        sv.ma_sv,
        sv.ho_ten,
        sv.lop,
        sv.email,
        sv.sdt,
        mh.ten_mon AS ten_mon_hoc,
        d.diem_giua_ky,
        d.diem_cuoi_ky,
        d.diem_tong_ket,
        CASE 
            WHEN d.diem_giua_ky IS NULL AND d.diem_cuoi_ky IS NULL THEN 'Thiếu cả 2 kỳ'
            WHEN d.diem_giua_ky IS NULL THEN 'Chưa có điểm Giữa kỳ'
            WHEN d.diem_cuoi_ky IS NULL THEN 'Chưa có điểm Cuối kỳ'
        END AS loai_thieu
    FROM diem d
    JOIN students sv ON d.student_id = sv.id
    JOIN mon_hoc mh ON d.mon_hoc_id = mh.id
    WHERE (d.diem_giua_ky IS NULL OR d.diem_cuoi_ky IS NULL)
";

if (!empty($filter_lop)) {
    $sql_missing_grades .= " AND sv.lop = '" . $db->real_escape_string($filter_lop) . "'";
}
if ($filter_mon > 0) {
    $sql_missing_grades .= " AND d.mon_hoc_id = " . $filter_mon;
}
$sql_missing_grades .= " ORDER BY sv.lop ASC, sv.ho_ten ASC";

$res_mgrade = $db->query($sql_missing_grades);
$missing_grades_list = [];

if ($res_mgrade) {
    while ($row = $res_mgrade->fetch_assoc()) {
        $missing_grades_list[] = $row;
    }
}

// Totals
$total_students_res = $db->query("SELECT COUNT(*) as total FROM students" . (!empty($filter_lop) ? " WHERE lop = '" . $db->real_escape_string($filter_lop) . "'" : ""));
$total_students = $total_students_res ? ($total_students_res->fetch_assoc()['total'] ?? 0) : 0;

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Tiến Độ (DA) - Giảng Viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <link rel="stylesheet" href="/tkb/assets/teacher_portal.css">
    <style>
        :root {
            /* Default: Dark Lofi Theme */
            --page-bg: #090514;
            --page-card: #140d27;
            --page-card-sub: rgba(255, 255, 255, 0.05);
            --page-border: rgba(168, 85, 247, 0.2);
            --page-border-hover: rgba(192, 132, 252, 0.45);
            --page-text: #f3e8ff;
            --page-text-muted: #a79bb7;
            --page-input-bg: #0c0717;
            --page-input-border: rgba(168, 85, 247, 0.3);
            --page-shadow: 0 8px 30px rgba(0,0,0,0.4);
            --page-badge-bg: rgba(255, 255, 255, 0.06);
            --page-table-th: #0f0a1e;
            --page-table-hover: rgba(168, 85, 247, 0.08);

            --kpi-users-bg: rgba(79, 70, 229, 0.2);
            --kpi-users-color: #818cf8;
            --kpi-quahan-bg: rgba(239, 68, 68, 0.2);
            --kpi-quahan-color: #f87171;
            --kpi-conhan-bg: rgba(245, 158, 11, 0.2);
            --kpi-conhan-color: #fbbf24;
            --kpi-thieu-bg: rgba(147, 51, 234, 0.2);
            --kpi-thieu-color: #c084fc;
        }

        body.adm-light-mode {
            /* Light Mode Theme */
            --page-bg: #f8fafc;
            --page-card: #ffffff;
            --page-card-sub: #f1f5f9;
            --page-border: #e2e8f0;
            --page-border-hover: #cbd5e1;
            --page-text: #0f172a;
            --page-text-muted: #64748b;
            --page-input-bg: #ffffff;
            --page-input-border: #cbd5e1;
            --page-shadow: 0 2px 10px rgba(0,0,0,0.03);
            --page-badge-bg: #f1f5f9;
            --page-table-th: #f8fafc;
            --page-table-hover: #f8fafc;

            --kpi-users-bg: #eef2ff;
            --kpi-users-color: #4f46e5;
            --kpi-quahan-bg: #fef2f2;
            --kpi-quahan-color: #ef4444;
            --kpi-conhan-bg: #fffbeb;
            --kpi-conhan-color: #d97706;
            --kpi-thieu-bg: #faf5ff;
            --kpi-thieu-color: #9333ea;
        }

        body {
            background: var(--page-bg) !important;
            color: var(--page-text) !important;
            font-family: 'Outfit', sans-serif;
            transition: background 0.3s ease, color 0.3s ease;
        }
        .main-content {
            background: var(--page-bg) !important;
            color: var(--page-text) !important;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .da-header-badge {
            background: rgba(79, 70, 229, 0.15);
            color: #818cf8;
            border: 1px solid rgba(79, 70, 229, 0.35);
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        body.adm-light-mode .da-header-badge {
            background: #eef2ff;
            color: #4f46e5;
            border-color: #c7d2fe;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .kpi-card {
            background: var(--page-card);
            border: 1px solid var(--page-border);
            border-radius: 16px;
            padding: 22px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--page-shadow);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            border-color: var(--page-border-hover);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }

        .kpi-val {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.1;
        }

        .kpi-label {
            font-size: 13px;
            color: var(--page-text-muted);
            margin-top: 6px;
            font-weight: 600;
        }

        .filter-bar {
            background: var(--page-card);
            border: 1px solid var(--page-border);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--page-shadow);
        }

        .tab-btn-group {
            display: flex;
            gap: 6px;
            background: var(--page-card-sub);
            padding: 5px;
            border-radius: 12px;
            border: 1px solid var(--page-border);
        }

        .tab-btn {
            padding: 8px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            color: var(--page-text-muted);
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-btn:hover {
            color: var(--page-text);
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .da-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            background: var(--page-card);
        }
        .da-table th {
            background: var(--page-table-th);
            color: var(--page-text-muted);
            text-align: left;
            padding: 14px 16px;
            font-weight: 700;
            border-bottom: 2px solid var(--page-border);
            white-space: nowrap;
        }
        .da-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--page-border);
            color: var(--page-text);
            vertical-align: middle;
        }
        .da-table tr:hover td {
            background: var(--page-table-hover);
        }

        .status-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.35);
        }
        body.adm-light-mode .status-danger {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }
        .status-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.35);
        }
        body.adm-light-mode .status-warning {
            background: #fffbeb;
            color: #d97706;
            border-color: #fde68a;
        }
        .status-info {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.35);
        }
        body.adm-light-mode .status-info {
            background: #f0f9ff;
            color: #0284c7;
            border-color: #bae6fd;
        }

        .btn-remind {
            background: linear-gradient(135deg, #7c3aed, #9333ea);
            color: #fff;
            border: none;
            padding: 7px 14px;
            border-radius: 9px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
        }
        .btn-remind:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(124, 58, 237, 0.4);
        }

        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .modal-box {
            background: var(--page-card);
            border: 1px solid var(--page-border);
            border-radius: 18px;
            width: 100%;
            max-width: 520px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            color: var(--page-text);
        }
        .da-select {
            background: var(--page-input-bg) !important;
            color: var(--page-text) !important;
            border: 1px solid var(--page-input-border) !important;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            outline: none;
            transition: background 0.3s ease, border-color 0.2s ease, color 0.3s ease;
        }
        .lop-badge {
            background: var(--page-badge-bg);
            color: var(--page-text);
            border: 1px solid var(--page-border);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .modal-input {
            width: 100%;
            background: var(--page-input-bg) !important;
            border: 1px solid var(--page-input-border) !important;
            color: var(--page-text) !important;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            transition: background 0.3s ease, border-color 0.2s ease, color 0.3s ease;
        }
        .modal-input:focus {
            border-color: #7c3aed !important;
        }
    </style>
</head>
<body class="<?= isAdmin() ? 'admin-portal' : '' ?> <?= (isset($_COOKIE['adm_theme']) && $_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : '' ?>">
    <?php if (isAdmin()): ?>
        <?php include '../includes/admin_nav.php'; ?>
        <div class="main-content">
    <?php else: ?>
        <?php include '../includes/teacher_nav.php'; ?>
    <?php endif; ?>

    <div style="padding: 25px 30px; max-width: 1400px; margin: 0 auto;">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
            <div>
                <div class="da-header-badge">
                    <i class="fa-solid fa-chart-line"></i> DATA ANALYTICS & INSIGHTS
                </div>
                <h1 style="font-size: 26px; font-weight: 800; margin-top: 8px; color: var(--page-text);">
                    Báo Cáo Tiến Độ & Thiếu Hụt Học Tập
                </h1>
                <p style="color: var(--page-text-muted); font-size: 14px; margin-top: 4px;">
                    Thống kê sinh viên chưa nộp bài tập hoặc chưa thi để phát hiện nguy cơ rớt môn kịp thời.
                </p>
            </div>
            
            <button onclick="openReminderModal('', 'Tất cả sinh viên cần nhắc nhở')" class="btn-remind" style="padding: 10px 18px; font-size: 14px; background: #4f46e5; color: #fff; border: none; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);">
                <i class="fa-solid fa-paper-plane"></i> Gửi Thông Báo Nhắc Nhở Hàng Loạt
            </button>
        </div>

        <?php if (!empty($msg)): ?>
            <div style="padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; background: <?= $msg_type === 'success' ? '#f0fdf4' : '#fef2f2' ?>; color: <?= $msg_type === 'success' ? '#166534' : '#991b1b' ?>; border: 1px solid <?= $msg_type === 'success' ? '#bbf7d0' : '#fecaca' ?>; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon" style="background: var(--kpi-users-bg); color: var(--kpi-users-color);">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="kpi-val" style="color: var(--kpi-users-color);"><?= number_format($total_students) ?></div>
                <div class="kpi-label">Tổng sinh viên <?= !empty($filter_lop) ? 'Lớp ' . htmlspecialchars($filter_lop) : 'hệ thống' ?></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon" style="background: var(--kpi-quahan-bg); color: var(--kpi-quahan-color);">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div class="kpi-val" style="color: var(--kpi-quahan-color);"><?= number_format($count_quahan) ?></div>
                <div class="kpi-label">Chưa nộp bài (Quá hạn 🔴)</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon" style="background: var(--kpi-conhan-bg); color: var(--kpi-conhan-color);">
                    <i class="fa-solid fa-file-pen"></i>
                </div>
                <div class="kpi-val" style="color: var(--kpi-conhan-color);"><?= number_format($count_conhan) ?></div>
                <div class="kpi-label">Chưa nộp bài (Còn hạn 🟡)</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon" style="background: var(--kpi-thieu-bg); color: var(--kpi-thieu-color);">
                    <i class="fa-solid fa-user-xmark"></i>
                </div>
                <div class="kpi-val" style="color: var(--kpi-thieu-color);"><?= number_format(count($missing_grades_list)) ?></div>
                <div class="kpi-label">Chưa có điểm thi / Thiếu điểm</div>
            </div>
        </div>

        <!-- Filter & Navigation Bar -->
        <div class="filter-bar">
            <!-- Tabs -->
            <div class="tab-btn-group">
                <a href="?tab=baitap&lop=<?= urlencode($filter_lop) ?>&mon_hoc_id=<?= $filter_mon ?>" class="tab-btn <?= $filter_tab === 'baitap' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i> Chưa Nộp Bài Tập (<?= count($unsubmitted_list) ?>)
                </a>
                <a href="?tab=diem&lop=<?= urlencode($filter_lop) ?>&mon_hoc_id=<?= $filter_mon ?>" class="tab-btn <?= $filter_tab === 'diem' ? 'active' : '' ?>">
                    <i class="fa-solid fa-graduation-cap"></i> Chưa Thi / Thiếu Điểm (<?= count($missing_grades_list) ?>)
                </a>
            </div>

            <!-- Filters Form -->
            <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($filter_tab) ?>">

                <select name="lop" onchange="this.form.submit()" class="da-select">
                    <option value="">-- Tất cả Lớp --</option>
                    <?php foreach ($classes as $cl): ?>
                        <option value="<?= htmlspecialchars($cl) ?>" <?= $filter_lop === $cl ? 'selected' : '' ?>>
                            Lớp: <?= htmlspecialchars($cl) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="mon_hoc_id" onchange="this.form.submit()" class="da-select">
                    <option value="0">-- Tất cả Môn học --</option>
                    <?php foreach ($subjects as $sb): ?>
                        <option value="<?= $sb['id'] ?>" <?= $filter_mon === (int)$sb['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sb['ten_mon']) ?> (<?= htmlspecialchars($sb['ma_mon']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($filter_lop) || $filter_mon > 0): ?>
                    <a href="?tab=<?= htmlspecialchars($filter_tab) ?>" style="color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 600;">
                        <i class="fa-solid fa-xmark"></i> Xóa lọc
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Data Content Card -->
        <div class="card" style="background: var(--page-card); border: 1px solid var(--page-border); border-radius: 16px; padding: 24px; box-shadow: var(--page-shadow); transition: background 0.3s ease, border-color 0.3s ease;">
            
            <?php if ($filter_tab === 'baitap'): ?>
                <!-- TAB 1: UNSUBMITTED ASSIGNMENTS -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--page-text);">
                        <i class="fa-solid fa-file-circle-exclamation" style="color: #d97706;"></i> Danh Sách Sinh Viên Chưa Nộp Bài Tập
                    </h3>
                    <span style="font-size: 13px; color: var(--page-text-muted);">
                        Tìm thấy <strong><?= count($unsubmitted_list) ?></strong> lượt chưa nộp bài
                    </span>
                </div>

                <?php if (empty($unsubmitted_list)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--page-text-muted);">
                        <i class="fa-solid fa-circle-check" style="font-size: 40px; color: #10b981; margin-bottom: 10px;"></i>
                        <p style="font-size: 15px; font-weight: 600; color: var(--page-text);">Tất cả sinh viên đã nộp bài đầy đủ!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="da-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã SV</th>
                                    <th>Họ & Tên</th>
                                    <th>Lớp</th>
                                    <th>Tên Bài Tập</th>
                                    <th>Môn Học</th>
                                    <th>Hạn Nộp</th>
                                    <th>Trạng Thái</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unsubmitted_list as $idx => $row): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td style="font-family: monospace; font-weight: 700; color: var(--kpi-users-color);"><?= htmlspecialchars($row['ma_sv']) ?></td>
                                        <td style="font-weight: 600;"><?= htmlspecialchars($row['ho_ten']) ?></td>
                                        <td><span class="lop-badge"><?= htmlspecialchars($row['lop']) ?></span></td>
                                        <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['assignment_title']) ?>">
                                            <?= htmlspecialchars($row['assignment_title']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['ten_mon_hoc']) ?></td>
                                        <td style="font-size: 13px; color: var(--page-text-muted);">
                                            <?= $row['han_nop'] ? date('d/m/Y H:i', strtotime($row['han_nop'])) : 'Không hạn' ?>
                                        </td>
                                        <td>
                                            <?php if ($row['trang_thai_nop'] === 'quahan'): ?>
                                                <span class="status-pill status-danger">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> Quá hạn chưa nộp
                                                </span>
                                            <?php else: ?>
                                                <span class="status-pill status-warning">
                                                    <i class="fa-solid fa-clock"></i> Chưa nộp (Còn hạn)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="openReminderModal('<?= htmlspecialchars($row['lop']) ?>', 'Nhắc nộp bài: <?= htmlspecialchars($row['assignment_title']) ?>')" class="btn-remind">
                                                <i class="fa-solid fa-bell"></i> Nhắc nhở
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- TAB 2: MISSING GRADES / EXAMS -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--page-text);">
                        <i class="fa-solid fa-user-slash" style="color: #9333ea;"></i> Danh Sách Sinh Viên Chưa Thi / Thiếu Điểm
                    </h3>
                    <span style="font-size: 13px; color: var(--page-text-muted);">
                        Tìm thấy <strong><?= count($missing_grades_list) ?></strong> sinh viên chưa có cột điểm
                    </span>
                </div>

                <?php if (empty($missing_grades_list)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--page-text-muted);">
                        <i class="fa-solid fa-circle-check" style="font-size: 40px; color: #10b981; margin-bottom: 10px;"></i>
                        <p style="font-size: 15px; font-weight: 600; color: var(--page-text);">Tất cả sinh viên đã có đủ điểm thi!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="da-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã SV</th>
                                    <th>Họ & Tên</th>
                                    <th>Lớp</th>
                                    <th>Môn Học</th>
                                    <th>Điểm Giữa Kỳ</th>
                                    <th>Điểm Cuối Kỳ</th>
                                    <th>Tình Trạng Thiếu</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($missing_grades_list as $idx => $row): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td style="font-family: monospace; font-weight: 700; color: var(--kpi-users-color);"><?= htmlspecialchars($row['ma_sv']) ?></td>
                                        <td style="font-weight: 600;"><?= htmlspecialchars($row['ho_ten']) ?></td>
                                        <td><span class="lop-badge"><?= htmlspecialchars($row['lop']) ?></span></td>
                                        <td><?= htmlspecialchars($row['ten_mon_hoc'] ?: 'Chưa phân môn') ?></td>
                                        <td>
                                            <?= $row['diem_giua_ky'] !== null ? htmlspecialchars($row['diem_giua_ky']) : '<span style="color:#ef4444;font-weight:700">Trống</span>' ?>
                                        </td>
                                        <td>
                                            <?= $row['diem_cuoi_ky'] !== null ? htmlspecialchars($row['diem_cuoi_ky']) : '<span style="color:#ef4444;font-weight:700">Trống</span>' ?>
                                        </td>
                                        <td>
                                            <span class="status-pill status-danger">
                                                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($row['loai_thieu']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick="openReminderModal('<?= htmlspecialchars($row['lop']) ?>', 'Lịch thi & Cập nhật điểm môn <?= htmlspecialchars($row['ten_mon_hoc']) ?>')" class="btn-remind">
                                                <i class="fa-solid fa-bell"></i> Nhắc bổ sung
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>

    <!-- Reminder Modal -->
    <div class="modal-overlay" id="reminderModal">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 18px; font-weight: 700; color: var(--page-text);">
                    <i class="fa-solid fa-paper-plane" style="color: #4f46e5;"></i> Gửi Thông Báo Nhắc Nhở
                </h3>
                <button onclick="closeReminderModal()" style="background: none; border: none; color: var(--page-text-muted); font-size: 18px; cursor: pointer;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="send_reminder">
                <input type="hidden" name="target_lop" id="modalTargetLop" value="">

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; color: var(--page-text); margin-bottom: 6px; font-weight: 600;">Tiêu đề thông báo:</label>
                    <input type="text" name="reminder_title" id="modalTitle" required class="modal-input">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; color: var(--page-text); margin-bottom: 6px; font-weight: 600;">Loại thông báo:</label>
                    <select name="reminder_type" class="modal-input">
                        <option value="Thông báo">Thông báo bài tập</option>
                        <option value="Lịch thi">Lịch thi / Cập nhật điểm</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; color: var(--page-text); margin-bottom: 6px; font-weight: 600;">Nội dung lời nhắn:</label>
                    <textarea name="reminder_content" rows="4" required class="modal-input" placeholder="Nhập nội dung nhắc nhở nộp bài tập hoặc lịch thi bổ sung..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeReminderModal()" style="background: var(--page-card-sub); color: var(--page-text-muted); border: 1px solid var(--page-border); padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        Hủy
                    </button>
                    <button type="submit" style="background: #4f46e5; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);">
                        <i class="fa-solid fa-paper-plane"></i> Gửi Ngay
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReminderModal(lop, defaultTitle) {
            document.getElementById('modalTargetLop').value = lop;
            document.getElementById('modalTitle').value = defaultTitle || 'Nhắc nhở nộp bài tập / lịch thi';
            document.getElementById('reminderModal').style.display = 'flex';
        }

        function closeReminderModal() {
            document.getElementById('reminderModal').style.display = 'none';
        }
    </script>
</body>
</html>
