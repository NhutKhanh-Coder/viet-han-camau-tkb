<?php
require_once __DIR__ . '/../config.php';
requireStudent();

$db = getDB();
$sv_id = $_SESSION['student_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch student info
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $sv_id);
$stmt->execute();
$sv = $stmt->get_result()->fetch_assoc();
$lop = $sv['lop'] ?? '';

$msg = '';
$msg_type = 'info';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle submission
if ($action === 'submit_homework' || $action === 'save_submission') {
    $aid = (int)($_POST['assignment_id'] ?? 0);
    $text_content = trim($_POST['submission_content'] ?? '');
    $code_content = trim($_POST['code_content'] ?? '');
    $github_link = trim($_POST['github_link'] ?? '');
    
    // Combine content if code or link provided
    $full_text = $text_content;
    if (!empty($github_link)) {
        $full_text .= "\n\n🔗 **Link sản phẩm / Repository:** " . $github_link;
    }
    if (!empty($code_content)) {
        $lang = htmlspecialchars($_POST['code_language'] ?? 'code');
        $full_text .= "\n\n💻 **Mã nguồn thực hành (" . $lang . "):**\n```" . $lang . "\n" . $code_content . "\n```";
    }
    $full_text = trim($full_text);

    // File Upload handling
    $file_url = '';
    if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/submissions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $safe_name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['attachment_file']['name']));
        $filename = time() . '_' . $safe_name;
        $target_file = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['attachment_file']['tmp_name'], $target_file)) {
            $file_url = '/tkb/assets/uploads/submissions/' . $filename;
        } else {
            $msg = "Lỗi: Không thể tải lên tệp đính kèm. Vui lòng thử lại!";
            $msg_type = 'error';
        }
    }

    if (!$msg) {
        // Check if already submitted
        $stmt_check = $db->prepare("SELECT id, file_path FROM submissions WHERE student_id = ? AND assignment_id = ?");
        $stmt_check->bind_param("ii", $sv_id, $aid);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        
        if ($res_check->num_rows > 0) {
            $existing_row = $res_check->fetch_assoc();
            $final_file = ($file_url !== '') ? $file_url : $existing_row['file_path'];
            
            $stmt_up = $db->prepare("UPDATE submissions SET submission_text = ?, file_path = ?, submitted_at = NOW() WHERE student_id = ? AND assignment_id = ?");
            $stmt_up->bind_param("ssii", $full_text, $final_file, $sv_id, $aid);
            if ($stmt_up->execute()) {
                $msg = "Tuyệt vời! Bạn đã cập nhật bài làm thành công!";
                $msg_type = 'success';
            } else {
                $msg = "Lỗi khi cập nhật bài làm: " . $db->error;
                $msg_type = 'error';
            }
        } else {
            $stmt_ins = $db->prepare("INSERT INTO submissions (assignment_id, student_id, submission_text, file_path, submitted_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt_ins->bind_param("iiss", $aid, $sv_id, $full_text, $file_url);
            if ($stmt_ins->execute()) {
                $msg = "Chúc mừng! Bạn đã nộp bài tập thành công lên hệ thống!";
                $msg_type = 'success';
            } else {
                $msg = "Lỗi khi nộp bài tập: " . $db->error;
                $msg_type = 'error';
            }
        }
    }
}

// Fetch all assignments: smart query matches student's class, or demo classes, or all
$assignments = [];
$empty_str = '';
$stmt_assign = $db->prepare("
    SELECT a.id, a.mon_hoc_id, a.giang_vien_id, a.lop, a.tieu_de, a.mo_ta, a.han_nop, a.created_at, a.file_path as assign_file_path,
           m.ten_mon, g.ho_ten as gv_name,
           sub.id as submission_id, sub.file_path as sub_file_path, sub.submission_text, sub.grade, sub.feedback, sub.submitted_at
    FROM assignments a
    JOIN mon_hoc m ON a.mon_hoc_id = m.id
    LEFT JOIN giang_vien g ON a.giang_vien_id = g.id
    LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
    WHERE a.lop = ? OR a.lop IN ('K24CDCNT1', 'CNTT24A') OR ? = ''
    ORDER BY a.id DESC
");
$stmt_assign->bind_param("iss", $sv_id, $lop, $empty_str);
$stmt_assign->execute();
$assignments = $stmt_assign->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate KPI Statistics
$total_count = count($assignments);
$todo_count = 0;
$submitted_count = 0;
$graded_count = 0;
$total_score = 0;

foreach ($assignments as $as) {
    if ($as['submission_id'] !== null) {
        $submitted_count++;
        if ($as['grade'] !== null) {
            $graded_count++;
            $total_score += (float)$as['grade'];
        }
    } else {
        $todo_count++;
    }
}
$avg_score = $graded_count > 0 ? round($total_score / $graded_count, 1) : 0;

// Get unique subjects for filter
$subjects = [];
foreach ($assignments as $as) {
    if (!empty($as['ten_mon']) && !in_array($as['ten_mon'], $subjects)) {
        $subjects[] = $as['ten_mon'];
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Làm Bài Tập - Cổng Sinh Viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <link rel="stylesheet" href="/tkb/assets/soft_female.css">
    <style>
        /* ═══════════════════════════════════════════════════════════════════
           CUSTOM STYLES FOR LÀM BÀI TẬP — HARMONIOUS WITH SOFT FEMALE THEME
           ═══════════════════════════════════════════════════════════════════ */
        
        .hw-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px 0 50px 0;
        }

        /* Hero Banner matching Dashboard Soft Female Style */
        .hw-hero-banner {
            background: linear-gradient(135deg, #ffffff 0%, #fdf2f8 50%, #faf5ff 100%);
            border: 1.5px solid #f3e8ff;
            border-radius: 22px;
            padding: 24px 28px;
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.05);
        }

        .hw-hero-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .hw-hero-icon {
            width: 58px;
            height: 58px;
            background: linear-gradient(135deg, #ec4899, #8b5cf6);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 24px;
            box-shadow: 0 6px 18px rgba(236, 72, 153, 0.28);
            flex-shrink: 0;
        }

        .hw-hero-title {
            font-size: 22px;
            font-weight: 900;
            color: #1e1b4b;
            margin: 0 0 4px 0;
            letter-spacing: -0.3px;
        }

        .hw-hero-sub {
            font-size: 13.5px;
            color: #64748b;
            margin: 0;
            font-weight: 500;
        }

        .hw-hero-badges {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .hw-pill-badge {
            background: #ffffff;
            border: 1.5px solid #f3e8ff;
            border-radius: 30px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.04);
        }

        /* 4 KPI Statistics Cards Grid (Strict 4-Columns) */
        .hw-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 22px;
        }

        @media (max-width: 992px) {
            .hw-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 540px) {
            .hw-kpi-grid { grid-template-columns: 1fr; }
        }

        .hw-kpi-card {
            background: #ffffff;
            border: 1.5px solid #f3e8ff;
            border-radius: 20px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.05);
            transition: all 0.25s ease;
        }

        .hw-kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(139, 92, 246, 0.12);
            border-color: #e9d5ff;
        }

        .hw-kpi-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .hw-kpi-num {
            font-size: 26px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.1;
        }

        .hw-kpi-label {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }

        /* Filter & Search Toolbar */
        .hw-toolbar {
            background: #ffffff;
            border: 1.5px solid #f3e8ff;
            border-radius: 20px;
            padding: 14px 18px;
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.04);
        }

        .hw-tab-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .hw-tab-btn {
            background: #ffffff;
            border: 1.5px solid #f3e8ff;
            border-radius: 12px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .hw-tab-btn:hover {
            color: #ec4899;
            border-color: #fbcfe8;
            background: #fdf2f8;
        }

        .hw-tab-btn.active {
            background: linear-gradient(135deg, #ec4899, #8b5cf6);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 4px 14px rgba(236, 72, 153, 0.3);
        }

        .hw-tab-pill {
            background: rgba(255, 255, 255, 0.3);
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 20px;
        }

        .hw-tab-btn:not(.active) .hw-tab-pill {
            background: #f1f5f9;
            color: #64748b;
        }

        .hw-search-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .hw-input-control, .hw-select-control {
            background: #faf5ff;
            border: 1.5px solid #f3e8ff;
            border-radius: 12px;
            padding: 8px 14px;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            transition: all 0.2s;
        }

        .hw-input-control:focus, .hw-select-control:focus {
            background: #ffffff;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.12);
        }

        /* Assignment Cards Grid */
        .hw-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 20px;
        }

        .hw-card-item {
            background: #ffffff;
            border: 1.5px solid #f3e8ff;
            border-radius: 22px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.05);
            transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hw-card-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 32px rgba(139, 92, 246, 0.12);
            border-color: #e9d5ff;
        }

        .hw-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            gap: 10px;
        }

        .hw-card-subject-badge {
            background: rgba(14, 165, 233, 0.1);
            color: #0284c7;
            font-size: 11.5px;
            font-weight: 800;
            padding: 5px 11px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .hw-card-status-badge {
            font-size: 11.5px;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .badge-status-todo { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-status-sub { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-status-graded { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }

        .hw-card-title {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 6px 0 6px 0;
            line-height: 1.35;
        }

        .hw-card-meta {
            font-size: 12.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 14px;
        }

        .hw-card-desc-box {
            background: #faf5ff;
            border: 1px solid #f3e8ff;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 13.5px;
            color: #334155;
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .hw-card-bottom {
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .hw-countdown-badge {
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #059669;
        }
        .hw-countdown-badge.expired { color: #dc2626; }
        .hw-countdown-badge.urgent { color: #d97706; }

        .hw-btn-do {
            background: linear-gradient(135deg, #ec4899, #8b5cf6);
            color: #ffffff;
            border: none;
            padding: 9px 20px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 14px rgba(236, 72, 153, 0.28);
            transition: all 0.2s ease;
        }

        .hw-btn-do:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(236, 72, 153, 0.4);
        }

        /* Workspace Modal */
        .hw-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(10px);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .hw-modal-backdrop.active {
            display: flex;
            opacity: 1;
        }

        .hw-modal-window {
            background: #ffffff;
            border-radius: 26px;
            width: 100%;
            max-width: 1080px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
            border: 1.5px solid #f3e8ff;
            animation: popIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes popIn {
            from { transform: scale(0.95) translateY(12px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        .hw-modal-top {
            padding: 18px 24px;
            background: #faf5ff;
            border-bottom: 1.5px solid #f3e8ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .hw-modal-top h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #1e1b4b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hw-modal-btn-close {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 15px;
        }

        .hw-modal-btn-close:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
        }

        .hw-modal-content-grid {
            display: grid;
            grid-template-columns: 4fr 6fr;
            flex: 1;
            overflow: hidden;
            min-height: 500px;
        }

        @media (max-width: 850px) {
            .hw-modal-content-grid {
                grid-template-columns: 1fr;
                overflow-y: auto;
            }
        }

        .hw-modal-left {
            padding: 22px;
            border-right: 1.5px solid #f3e8ff;
            background: #faf5ff;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .hw-box-card {
            background: #ffffff;
            border: 1.5px solid #f3e8ff;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.03);
        }

        .hw-box-card h4 {
            margin: 0 0 8px 0;
            font-size: 13.5px;
            font-weight: 800;
            color: #1e1b4b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hw-modal-right {
            padding: 22px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            background: #ffffff;
        }

        .hw-work-tabs {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #f3e8ff;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .hw-work-tabs button {
            background: #ffffff;
            border: 1px solid #f3e8ff;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .hw-work-tabs button.active {
            color: #ec4899;
            border-color: #fbcfe8;
            background: #fdf2f8;
        }

        .hw-editor {
            width: 100%;
            height: 220px;
            background: #faf5ff;
            border: 1.5px solid #f3e8ff;
            border-radius: 14px;
            padding: 14px;
            font-size: 14px;
            color: #1e293b;
            font-family: inherit;
            outline: none;
            resize: vertical;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .hw-editor:focus {
            background: #ffffff;
            border-color: #ec4899;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.12);
        }

        .hw-code-container {
            background: #0f172a;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 12px;
            border: 1px solid #334155;
        }

        .hw-code-bar {
            background: #1e293b;
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .hw-code-editor {
            width: 100%;
            height: 160px;
            background: #0f172a;
            color: #38bdf8;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 13px;
            padding: 12px 14px;
            border: none;
            outline: none;
            resize: vertical;
            box-sizing: border-box;
        }

        .hw-code-output {
            background: #020617;
            border-top: 1px solid #1e293b;
            padding: 10px 14px;
            font-family: monospace;
            font-size: 12.5px;
            color: #4ade80;
            max-height: 120px;
            overflow-y: auto;
            white-space: pre-wrap;
            display: none;
        }

        .hw-drop-zone {
            border: 2px dashed #cbd5e1;
            background: #faf5ff;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .hw-drop-zone:hover {
            border-color: #ec4899;
            background: #fdf2f8;
        }

        .hw-modal-bottom {
            padding: 16px 24px;
            background: #faf5ff;
            border-top: 1.5px solid #f3e8ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .hw-btn-cancel {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            color: #475569;
            padding: 9px 18px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
        }

        .hw-btn-cancel:hover {
            background: #f1f5f9;
        }

        .hw-btn-submit-main {
            background: linear-gradient(135deg, #ec4899, #8b5cf6);
            color: #ffffff;
            border: none;
            padding: 10px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(236, 72, 153, 0.3);
            transition: all 0.2s;
        }

        .hw-btn-submit-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(236, 72, 153, 0.4);
        }

        /* Toast Component */
        .hw-toast-msg {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 22px;
            background: #0f172a;
            color: #fff;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            z-index: 100000;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 600;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .hw-toast-msg.show { transform: translateY(0); opacity: 1; }
        .hw-toast-msg.success { background: #059669; }
        .hw-toast-msg.error { background: #dc2626; }
    </style>
</head>
<body data-mc-mode="<?= ($sv['gioi_tinh'] ?? 'Nữ') === 'Nữ' ? 'female' : 'male' ?>">

    <!-- Include student navigation sidebar (which opens .main-content and .top-header) -->
    <?php include __DIR__ . '/../includes/student_nav.php'; ?>

    <div class="hw-container">

        <!-- Alert Notification -->
        <?php if ($msg): ?>
            <div style="background: <?= $msg_type==='success'?'#d1fae5':'#fee2e2' ?>; border: 1.5px solid <?= $msg_type==='success'?'#a7f3d0':'#fecaca' ?>; color: <?= $msg_type==='success'?'#065f46':'#991b1b' ?>; padding: 14px 18px; border-radius: 16px; margin-bottom: 20px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
                <i class="fa-solid <?= $msg_type==='success'?'fa-circle-check':'fa-triangle-exclamation' ?>" style="font-size: 18px;"></i>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <!-- Hero Section -->
        <div class="hw-hero-banner">
            <div class="hw-hero-left">
                <div class="hw-hero-icon">
                    <i class="fa-solid fa-pen-ruler"></i>
                </div>
                <div>
                    <h1 class="hw-hero-title">Không Gian Làm Bài Tập Trực Tuyến</h1>
                    <p class="hw-hero-sub">Xem bài tập được giao, làm bài trực tiếp trên hệ thống, thực hành code và nhận điểm từ Giảng viên.</p>
                </div>
            </div>
            <div class="hw-hero-badges">
                <div class="hw-pill-badge">
                    <i class="fa-solid fa-graduation-cap" style="color: #ec4899;"></i> Lớp: <strong><?= htmlspecialchars($lop ?: 'K24CDCNT1') ?></strong>
                </div>
                <div class="hw-pill-badge">
                    <i class="fa-solid fa-user" style="color: #0284c7;"></i> <strong><?= htmlspecialchars($sv['ho_ten'] ?? 'Sinh viên') ?></strong>
                </div>
            </div>
        </div>

        <!-- 4 KPI Cards Grid (Strict 4 Columns) -->
        <div class="hw-kpi-grid">
            <div class="hw-kpi-card">
                <div class="hw-kpi-icon-box" style="background: #e0f2fe; color: #0284c7;">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <div class="hw-kpi-num"><?= $total_count ?></div>
                    <div class="hw-kpi-label">Tổng bài tập</div>
                </div>
            </div>

            <div class="hw-kpi-card">
                <div class="hw-kpi-icon-box" style="background: #fef3c7; color: #d97706;">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <div class="hw-kpi-num"><?= $todo_count ?></div>
                    <div class="hw-kpi-label">Cần làm ngay</div>
                </div>
            </div>

            <div class="hw-kpi-card">
                <div class="hw-kpi-icon-box" style="background: #d1fae5; color: #059669;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <div class="hw-kpi-num"><?= $submitted_count ?></div>
                    <div class="hw-kpi-label">Đã nộp bài</div>
                </div>
            </div>

            <div class="hw-kpi-card">
                <div class="hw-kpi-icon-box" style="background: #ede9fe; color: #7c3aed;">
                    <i class="fa-solid fa-award"></i>
                </div>
                <div>
                    <div class="hw-kpi-num"><?= $graded_count > 0 ? $avg_score . '<span style="font-size:14px; font-weight:600; color:#64748b">/10</span>' : '--' ?></div>
                    <div class="hw-kpi-label">Điểm TB (<?= $graded_count ?> bài)</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="hw-toolbar">
            <div class="hw-tab-group">
                <button class="hw-tab-btn active" data-filter="all" onclick="filterTab('all', this)">
                    Tất cả <span class="hw-tab-pill"><?= $total_count ?></span>
                </button>
                <button class="hw-tab-btn" data-filter="todo" onclick="filterTab('todo', this)">
                    <i class="fa-solid fa-clock" style="color: #d97706;"></i> Chưa làm <span class="hw-tab-pill"><?= $todo_count ?></span>
                </button>
                <button class="hw-tab-btn" data-filter="submitted" onclick="filterTab('submitted', this)">
                    <i class="fa-solid fa-circle-check" style="color: #059669;"></i> Đã nộp <span class="hw-tab-pill"><?= $submitted_count ?></span>
                </button>
                <button class="hw-tab-btn" data-filter="graded" onclick="filterTab('graded', this)">
                    <i class="fa-solid fa-star" style="color: #7c3aed;"></i> Đã chấm điểm <span class="hw-tab-pill"><?= $graded_count ?></span>
                </button>
            </div>

            <div class="hw-search-controls">
                <input type="text" id="hwSearchInput" class="hw-input-control" placeholder="🔍 Tìm tên bài tập, môn..." oninput="filterAssignments()">
                <?php if (!empty($subjects)): ?>
                    <select id="hwSubjectFilter" class="hw-select-control" onchange="filterAssignments()">
                        <option value="">Tất cả môn học</option>
                        <?php foreach ($subjects as $sub_name): ?>
                            <option value="<?= htmlspecialchars($sub_name) ?>"><?= htmlspecialchars($sub_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignment Cards Grid -->
        <?php if (empty($assignments)): ?>
            <div style="background: #ffffff; border: 1.5px dashed #f3e8ff; border-radius: 24px; padding: 60px 20px; text-align: center; color: #64748b;">
                <i class="fa-solid fa-folder-open" style="font-size: 48px; color: #ec4899; margin-bottom: 16px; display: block; opacity: 0.8;"></i>
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">Hiện tại chưa có bài tập nào được giao!</h3>
                <p style="font-size: 13.5px; max-width: 450px; margin: 0 auto;">Khi Giảng viên giao bài tập mới cho lớp của bạn, bài tập sẽ xuất hiện tại đây.</p>
            </div>
        <?php else: ?>
            <div class="hw-cards-grid" id="hwGridContainer">
                <?php foreach ($assignments as $as): 
                    $has_sub = ($as['submission_id'] !== null);
                    $is_graded = ($has_sub && $as['grade'] !== null);
                    $is_expired = ($as['han_nop'] && strtotime($as['han_nop']) < time());
                    
                    $status_filter = 'todo';
                    if ($is_graded) {
                        $status_filter = 'graded';
                    } elseif ($has_sub) {
                        $status_filter = 'submitted';
                    }
                ?>
                    <div class="hw-card-item" 
                         data-status="<?= $status_filter ?>" 
                         data-subject="<?= htmlspecialchars($as['ten_mon']) ?>"
                         data-title="<?= htmlspecialchars(mb_strtolower($as['tieu_de'])) ?>">
                        
                        <div>
                            <div class="hw-card-top">
                                <span class="hw-card-subject-badge">
                                    <i class="fa-solid fa-book"></i> <?= htmlspecialchars($as['ten_mon']) ?>
                                </span>
                                <?php if ($is_graded): ?>
                                    <span class="hw-card-status-badge badge-status-graded">
                                        <i class="fa-solid fa-star"></i> <?= htmlspecialchars($as['grade']) ?>/10 Điểm
                                    </span>
                                <?php elseif ($has_sub): ?>
                                    <span class="hw-card-status-badge badge-status-sub">
                                        <i class="fa-solid fa-circle-check"></i> Đã nộp
                                    </span>
                                <?php else: ?>
                                    <span class="hw-card-status-badge badge-status-todo">
                                        <i class="fa-solid fa-clock"></i> Chưa làm
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="hw-card-title"><?= htmlspecialchars($as['tieu_de']) ?></h3>
                            
                            <div class="hw-card-meta">
                                <i class="fa-solid fa-chalkboard-user" style="color: #8b5cf6;"></i>
                                <span>GV: <strong><?= htmlspecialchars($as['gv_name'] ?? 'Giảng viên phụ trách') ?></strong></span>
                                <span style="opacity: 0.4">•</span>
                                <span>Lớp: <?= htmlspecialchars($as['lop']) ?></span>
                            </div>

                            <div class="hw-card-desc-box">
                                <?= nl2br(htmlspecialchars($as['mo_ta'] ?: 'Không có mô tả chi tiết.')) ?>
                            </div>
                        </div>

                        <div>
                            <?php if (!empty($as['assign_file_path'])): ?>
                                <div style="margin-bottom: 12px;">
                                    <a href="<?= htmlspecialchars($as['assign_file_path']) ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #0284c7; background: #e0f2fe; padding: 5px 12px; border-radius: 10px; text-decoration: none; font-weight: 700;">
                                        <i class="fa-solid fa-paperclip"></i> Tải tài liệu đề bài
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="hw-card-bottom">
                                <div class="hw-countdown-badge <?= $is_expired ? 'expired' : ($has_sub ? '' : 'urgent') ?>">
                                    <i class="fa-solid fa-calendar-day"></i>
                                    <?php if ($as['han_nop']): ?>
                                        <?= date('d/m/Y H:i', strtotime($as['han_nop'])) ?>
                                    <?php else: ?>
                                        Không giới hạn
                                    <?php endif; ?>
                                </div>

                                <button class="hw-btn-do" onclick="openWorkspaceModal(<?= htmlspecialchars(json_encode($as, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)) ?>)">
                                    <i class="fa-solid <?= $has_sub ? 'fa-arrows-rotate' : 'fa-pen-to-square' ?>"></i>
                                    <?= $has_sub ? 'Xem / Nộp lại' : 'Vào làm bài' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
    </div> <!-- Closes .main-content opened by includes/student_nav.php -->

    <!-- Interactive Homework Workspace Modal -->
    <div id="hwWorkspaceModal" class="hw-modal-backdrop" onclick="handleBackdropClick(event)">
        <div class="hw-modal-window">
            
            <!-- Modal Header -->
            <div class="hw-modal-top">
                <div>
                    <h3 id="modalTitle">
                        <i class="fa-solid fa-laptop-code" style="color: #ec4899;"></i> Tiêu đề bài tập
                    </h3>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px; display: flex; gap: 10px; align-items: center;">
                        <span id="modalSubject" style="font-weight: 800; color: #0284c7;">Môn học</span>
                        <span>•</span>
                        <span id="modalTeacher">GV phụ trách</span>
                        <span>•</span>
                        <span id="modalDeadline" style="font-weight: 700; color: #ec4899;">Hạn nộp</span>
                    </div>
                </div>
                <button class="hw-modal-btn-close" onclick="closeWorkspaceModal()" title="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="hwSubmissionForm" method="POST" action="lam_bai_tap.php" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex: 1; overflow: hidden; margin: 0;">
                <input type="hidden" name="action" value="submit_homework">
                <input type="hidden" name="assignment_id" id="modalAssignmentId" value="0">
                <input type="hidden" name="code_language" id="modalCodeLangInput" value="html">

                <!-- Modal Body: 2 Columns -->
                <div class="hw-modal-content-grid">
                    
                    <!-- Left Column: Details & Teacher Feedback -->
                    <div class="hw-modal-left">
                        <!-- Grade & Feedback Box (if graded) -->
                        <div id="modalGradeCard" style="background: #ecfdf5; border: 1.5px solid #a7f3d0; border-radius: 16px; padding: 16px; display: none; align-items: center; gap: 16px;">
                            <div style="font-size: 32px; font-weight: 900; color: #059669; background: #ffffff; width: 65px; height: 65px; border-radius: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(5,150,105,0.15);" id="modalScoreVal">10</div>
                            <div style="flex: 1;">
                                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #059669; margin-bottom: 2px;">
                                    <i class="fa-solid fa-award"></i> Giảng viên đã chấm điểm
                                </div>
                                <div style="font-size: 13.5px; color: #1e293b; font-weight: 600;" id="modalFeedbackVal">
                                    Bài làm tốt!
                                </div>
                            </div>
                        </div>

                        <!-- Assignment Description -->
                        <div class="hw-box-card">
                            <h4><i class="fa-solid fa-file-lines" style="color: #ec4899;"></i> Yêu cầu & Đề bài:</h4>
                            <div style="font-size: 13.5px; color: #334155; line-height: 1.65; white-space: pre-wrap; word-break: break-word;" id="modalDescription">Đề bài chi tiết...</div>
                        </div>

                        <!-- Attachment Link -->
                        <div id="modalAttachmentCard" class="hw-box-card" style="display: none;">
                            <h4><i class="fa-solid fa-paperclip" style="color: #0284c7;"></i> Tài liệu đính kèm:</h4>
                            <a href="#" id="modalAttachmentLink" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #0284c7; font-weight: 700; text-decoration: none;">
                                <i class="fa-solid fa-download"></i> Tải về tài liệu đề bài
                            </a>
                        </div>

                        <!-- Instructions -->
                        <div class="hw-box-card" style="background: #fffbeb; border-color: #fde68a;">
                            <h4 style="color: #b45309;"><i class="fa-solid fa-lightbulb"></i> Hướng dẫn làm bài:</h4>
                            <ul style="font-size: 12.5px; color: #78350f; margin: 0; padding-left: 18px; line-height: 1.6;">
                                <li>Bạn có thể nhập câu trả lời trực tiếp tại tab <strong>Soạn bài</strong>.</li>
                                <li>Với bài tập lập trình, bạn có thể thực hành tại tab <strong>Chạy code</strong> rồi chèn mã vào bài làm.</li>
                                <li>Hệ thống tự động lưu bản nháp vào trình duyệt tránh mất bài.</li>
                                <li>Có thể tải lên file nén ZIP, RAR, PDF, DOCX... (tối đa 15MB).</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Right Column: Interactive Workspace -->
                    <div class="hw-modal-right">
                        <!-- Navigation Tabs inside Workspace -->
                        <div class="hw-work-tabs">
                            <button type="button" class="active" id="workTabBtn1" onclick="switchWorkTab(1)">
                                <i class="fa-solid fa-file-pen"></i> 1. Soạn bài làm
                            </button>
                            <button type="button" id="workTabBtn2" onclick="switchWorkTab(2)">
                                <i class="fa-solid fa-terminal"></i> 2. Viết & Chạy Code
                            </button>
                            <button type="button" id="workTabBtn3" onclick="switchWorkTab(3)">
                                <i class="fa-solid fa-cloud-arrow-up"></i> 3. Đính kèm File & Link
                            </button>
                        </div>

                        <!-- Tab 1: Textarea Editor -->
                        <div id="workTabContent1">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label style="font-size: 13px; font-weight: 700; color: #1e1b4b;">
                                    Nội dung lời giải / Câu trả lời bài tập:
                                </label>
                                <span id="charCounter" style="font-size: 11px; color: #64748b;">0 ký tự</span>
                            </div>
                            <textarea name="submission_content" id="modalTextContent" class="hw-editor" placeholder="Nhập câu trả lời, giải thuật, hướng làm bài hoặc giải trình chi tiết tại đây..." oninput="updateCharCount()"></textarea>
                            
                            <div style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                                <button type="button" class="hw-btn-cancel" style="padding: 6px 12px; font-size: 12px;" onclick="insertTemplate('step')">
                                    <i class="fa-solid fa-list-ol"></i> Mẫu các bước giải
                                </button>
                                <button type="button" class="hw-btn-cancel" style="padding: 6px 12px; font-size: 12px;" onclick="insertTemplate('result')">
                                    <i class="fa-solid fa-check-double"></i> Mẫu kết luận
                                </button>
                            </div>
                        </div>

                        <!-- Tab 2: Code Editor & Runner -->
                        <div id="workTabContent2" style="display: none;">
                            <div class="hw-code-container">
                                <div class="hw-code-bar">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <select id="codeLanguageSelect" class="hw-select-control" style="background:#0f172a; color:#f8fafc; border-color:#334155; padding:4px 8px; font-size:12px;" onchange="updateCodeLanguage(this.value)">
                                            <option value="html">HTML / CSS / JS</option>
                                            <option value="python">Python 3</option>
                                            <option value="php">PHP</option>
                                            <option value="cpp">C++ (GCC)</option>
                                            <option value="c">C</option>
                                            <option value="java">Java</option>
                                            <option value="csharp">C# (.NET)</option>
                                        </select>
                                        <span style="font-size: 11px; color: #94a3b8;"><i class="fa-solid fa-code"></i> Trình thực hành Code</span>
                                    </div>
                                    <button type="button" class="hw-btn-do" style="padding: 5px 12px; font-size: 12px; background: #0284c7;" onclick="runInteractiveCode()">
                                        <i class="fa-solid fa-play"></i> Chạy thử code
                                    </button>
                                </div>
                                <textarea name="code_content" id="modalCodeArea" class="hw-code-editor" placeholder="// Viết mã nguồn thực hành của bạn tại đây..."></textarea>
                                <div id="codeConsoleOutput" class="hw-code-output"></div>
                            </div>
                            <button type="button" class="hw-btn-cancel" style="width: 100%; font-size: 12.5px; padding: 9px;" onclick="copyCodeToSubmission()">
                                <i class="fa-solid fa-arrow-down-long"></i> Chèn mã nguồn này vào nội dung bài nộp (Tab 1)
                            </button>
                        </div>

                        <!-- Tab 3: Attachment & Link -->
                        <div id="workTabContent3" style="display: none;">
                            <div class="hw-drop-zone" id="modalDropzone" onclick="document.getElementById('modalFileInput').click()">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 36px; color: #ec4899; margin-bottom: 8px; display: block;"></i>
                                <div style="font-size: 14px; font-weight: 700; color: #1e1b4b;">Kéo thả file bài làm vào đây hoặc <span style="color: #ec4899; text-decoration: underline;">chọn tệp</span></div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Hỗ trợ file ZIP, RAR, PDF, DOCX, PNG... (Tối đa 15MB)</div>
                                <div id="selectedFileName" style="font-size: 13px; font-weight: 700; color: #059669; margin-top: 8px;"></div>
                                <input type="file" name="attachment_file" id="modalFileInput" style="display: none;" onchange="handleFileChange(this)">
                            </div>

                            <div id="modalExistingFileRow" style="margin-top: 12px; font-size: 12.5px; display: none;">
                                <span>File đã nộp trước đó:</span>
                                <a href="#" id="modalExistingFileLink" target="_blank" style="color: #ec4899; font-weight: 700; text-decoration: none;">
                                    <i class="fa-solid fa-file-arrow-down"></i> Tải file đã nộp
                                </a>
                            </div>

                            <div style="margin-top: 18px;">
                                <label style="font-size: 13px; font-weight: 700; color: #1e1b4b; display: block; margin-bottom: 6px;">
                                    <i class="fa-brands fa-github"></i> Hoặc dán Link sản phẩm (GitHub / Google Drive / Figma / YouTube demo):
                                </label>
                                <input type="url" name="github_link" id="modalGithubLink" class="hw-input-control" style="width: 100%; box-sizing: border-box;" placeholder="https://github.com/username/project hoặc https://drive.google.com/...">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="hw-modal-bottom">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button type="button" class="hw-btn-cancel" onclick="saveDraftLocal()">
                            <i class="fa-regular fa-floppy-disk"></i> Lưu bản nháp
                        </button>
                        <span id="draftSavedNotice" style="font-size: 12px; color: #059669; font-weight: 700; display: none;">
                            <i class="fa-solid fa-check"></i> Đã lưu nháp vào máy!
                        </span>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="hw-btn-cancel" onclick="closeWorkspaceModal()">
                            Hủy bỏ
                        </button>
                        <button type="submit" class="hw-btn-submit-main" id="btnSubmitHomework">
                            <i class="fa-solid fa-paper-plane"></i> Nộp bài chính thức
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Component -->
    <div id="hwToast" class="hw-toast-msg">
        <i id="hwToastIcon" class="fa-solid fa-circle-check"></i>
        <span id="hwToastMsg">Thông báo</span>
    </div>

    <script>
        let currentAssignment = null;

        function filterTab(type, btn) {
            document.querySelectorAll('.hw-tab-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            
            const cards = document.querySelectorAll('.hw-card-item');
            cards.forEach(card => {
                const status = card.dataset.status;
                if (type === 'all') {
                    card.style.display = 'flex';
                } else if (type === 'todo') {
                    card.style.display = (status === 'todo') ? 'flex' : 'none';
                } else if (type === 'submitted') {
                    card.style.display = (status === 'submitted' || status === 'graded') ? 'flex' : 'none';
                } else if (type === 'graded') {
                    card.style.display = (status === 'graded') ? 'flex' : 'none';
                }
            });
        }

        function filterAssignments() {
            const searchVal = document.getElementById('hwSearchInput').value.toLowerCase().trim();
            const subjectVal = document.getElementById('hwSubjectFilter') ? document.getElementById('hwSubjectFilter').value : '';
            
            const cards = document.querySelectorAll('.hw-card-item');
            cards.forEach(card => {
                const title = card.dataset.title || '';
                const subject = card.dataset.subject || '';
                
                const matchSearch = !searchVal || title.includes(searchVal) || subject.toLowerCase().includes(searchVal);
                const matchSubject = !subjectVal || subject === subjectVal;
                
                if (matchSearch && matchSubject) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function openWorkspaceModal(data) {
            currentAssignment = data;
            
            document.getElementById('modalAssignmentId').value = data.id;
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-laptop-code" style="color: #ec4899;"></i> ' + escapeHtml(data.tieu_de);
            document.getElementById('modalSubject').innerText = data.ten_mon;
            document.getElementById('modalTeacher').innerText = 'GV: ' + (data.gv_name || 'TBA');
            
            if (data.han_nop) {
                document.getElementById('modalDeadline').innerText = 'Hạn: ' + data.han_nop;
            } else {
                document.getElementById('modalDeadline').innerText = 'Hạn: Không giới hạn';
            }

            document.getElementById('modalDescription').innerText = data.mo_ta || 'Không có mô tả chi tiết.';

            // Attachment
            const attCard = document.getElementById('modalAttachmentCard');
            if (data.assign_file_path) {
                attCard.style.display = 'block';
                document.getElementById('modalAttachmentLink').href = data.assign_file_path;
            } else {
                attCard.style.display = 'none';
            }

            // Grade feedback
            const gradeCard = document.getElementById('modalGradeCard');
            if (data.grade !== null && data.grade !== undefined && data.grade !== '') {
                gradeCard.style.display = 'flex';
                document.getElementById('modalScoreVal').innerText = data.grade;
                document.getElementById('modalFeedbackVal').innerText = data.feedback || 'Bài làm tốt!';
            } else {
                gradeCard.style.display = 'none';
            }

            // Populate existing submission text if any
            const draft = localStorage.getItem('hw_draft_' + data.id);
            if (draft && (!data.submission_text || draft.length > (data.submission_text||'').length)) {
                document.getElementById('modalTextContent').value = draft;
            } else {
                document.getElementById('modalTextContent').value = data.submission_text || '';
            }
            updateCharCount();

            // Existing file
            const fileRow = document.getElementById('modalExistingFileRow');
            if (data.sub_file_path) {
                fileRow.style.display = 'block';
                document.getElementById('modalExistingFileLink').href = data.sub_file_path;
            } else {
                fileRow.style.display = 'none';
            }

            // Switch to Tab 1
            switchWorkTab(1);

            // Open modal
            document.getElementById('hwWorkspaceModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeWorkspaceModal() {
            document.getElementById('hwWorkspaceModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function handleBackdropClick(e) {
            if (e.target.id === 'hwWorkspaceModal') {
                closeWorkspaceModal();
            }
        }

        function switchWorkTab(idx) {
            for (let i = 1; i <= 3; i++) {
                const btn = document.getElementById('workTabBtn' + i);
                const content = document.getElementById('workTabContent' + i);
                if (btn) btn.classList.toggle('active', i === idx);
                if (content) content.style.display = (i === idx) ? 'block' : 'none';
            }
        }

        function updateCharCount() {
            const val = document.getElementById('modalTextContent').value;
            document.getElementById('charCounter').innerText = val.length + ' ký tự';
        }

        function insertTemplate(type) {
            const textarea = document.getElementById('modalTextContent');
            if (type === 'step') {
                textarea.value += "\n\n### 📝 Các bước giải quyết:\n1. Phân tích yêu cầu đề bài...\n2. Thiết kế giải thuật / giải pháp...\n3. Triển khai thực hiện...";
            } else if (type === 'result') {
                textarea.value += "\n\n### 🎯 Kết luận & Kết quả đạt được:\n- Hoàn thành đầy đủ các chức năng theo yêu cầu đề bài.\n- Đã kiểm tra hoạt động ổn định và không phát sinh lỗi.";
            }
            updateCharCount();
        }

        function handleFileChange(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                document.getElementById('selectedFileName').innerText = '✅ Đã chọn tệp: ' + file.name + ' (' + sizeMb + ' MB)';
            }
        }

        function saveDraftLocal() {
            if (!currentAssignment) return;
            const text = document.getElementById('modalTextContent').value;
            localStorage.setItem('hw_draft_' + currentAssignment.id, text);
            
            const notice = document.getElementById('draftSavedNotice');
            notice.style.display = 'inline';
            setTimeout(() => { notice.style.display = 'none'; }, 3000);
            showToast('Đã lưu bản nháp vào trình duyệt!', 'success');
        }

        function updateCodeLanguage(lang) {
            document.getElementById('modalCodeLangInput').value = lang;
        }

        async function runInteractiveCode() {
            const lang = document.getElementById('codeLanguageSelect').value;
            const code = document.getElementById('modalCodeArea').value;
            const consoleBox = document.getElementById('codeConsoleOutput');

            if (!code.trim()) {
                showToast('Vui lòng nhập mã nguồn trước khi chạy!', 'error');
                return;
            }

            consoleBox.style.display = 'block';
            consoleBox.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang biên dịch và thực thi code...';

            try {
                const form = new FormData();
                form.append('language', lang);
                form.append('code', code);

                const res = await fetch('../api/run_code.php', {
                    method: 'POST',
                    body: form
                });
                const data = await res.json();
                
                if (data.error) {
                    consoleBox.style.color = '#ef4444';
                    consoleBox.innerText = 'LỖI BIÊN DỊCH / RUNTIME:\n' + data.error;
                } else {
                    consoleBox.style.color = '#4ade80';
                    consoleBox.innerText = '=== OUTPUT (' + lang + ') ===\n' + (data.output || 'Chương trình thực thi thành công (không có output trả về).');
                }
            } catch (err) {
                consoleBox.style.color = '#ef4444';
                consoleBox.innerText = 'Lỗi kết nối bộ chạy code: ' + err.message;
            }
        }

        function copyCodeToSubmission() {
            const code = document.getElementById('modalCodeArea').value;
            const lang = document.getElementById('codeLanguageSelect').value;
            if (!code.trim()) {
                showToast('Chưa có code để chèn!', 'error');
                return;
            }
            const snippet = "\n\n```" + lang + "\n" + code + "\n```";
            document.getElementById('modalTextContent').value += snippet;
            updateCharCount();
            switchWorkTab(1);
            showToast('Đã chèn mã nguồn vào Tab Soạn bài!', 'success');
        }

        function showToast(msg, type = 'success') {
            const toast = document.getElementById('hwToast');
            const icon = document.getElementById('hwToastIcon');
            const txt = document.getElementById('hwToastMsg');
            
            toast.className = 'hw-toast-msg ' + type + ' show';
            txt.innerText = msg;
            icon.className = 'fa-solid ' + (type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3500);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }
    </script>
</body>
</html>
