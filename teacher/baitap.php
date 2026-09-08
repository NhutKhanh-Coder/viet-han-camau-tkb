<?php
require_once '../config.php';
requireTeacher();

if (isAdmin()) {
    $qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: /tkb/admin/baitap.php' . $qs);
    exit;
}

$db = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;
$msg = '';

$tab = $_POST['tab'] ?? $_GET['tab'] ?? 'manage'; // manage or grade

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'add') {
    $mid = (int)$_POST['mon_hoc_id'];
    $lop = trim($_POST['lop'] ?? '');
    $title = trim($_POST['tieu_de'] ?? '');
    $desc = trim($_POST['mo_ta'] ?? '');
    $deadline_date = trim($_POST['han_nop_date'] ?? '');
    $deadline_time = trim($_POST['han_nop_time'] ?? '23:59');
    $deadline = !empty($deadline_date) ? $deadline_date . ' ' . $deadline_time : '';
    
    if ($mid && $lop && $title) {
        $file_url = null;
        if (isset($_FILES['assign_file']) && $_FILES['assign_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['assign_file']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['assign_file']['tmp_name'], $target_file)) {
                $file_url = '/tkb/assets/uploads/assignments/' . $filename;
            } else {
                $msg = "error:Lỗi không thể tải lên tài liệu học liệu.";
            }
        }
        
        if (!$msg) {
            $deadline_val = !empty($deadline) ? date('Y-m-d H:i:s', strtotime($deadline)) : null;
            $stmt = $db->prepare("INSERT INTO assignments (giang_vien_id, mon_hoc_id, lop, tieu_de, mo_ta, han_nop, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssss", $gv_id, $mid, $lop, $title, $desc, $deadline_val, $file_url);
            if ($stmt->execute()) {
                $msg = "success:Đã giao bài tập mới thành công!";
            } else {
                $msg = "error:Lỗi giao bài tập: " . $db->error;
            }
        }
    } else {
        $msg = "error:Vui lòng điền đầy đủ tiêu đề, chọn lớp và chọn môn học.";
    }
} elseif ($action === 'delete') {
    $aid = (int)$_GET['assignment_id'];
    if (isAdmin()) {
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->bind_param("i", $aid);
    } else {
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ? AND giang_vien_id = ?");
        $stmt->bind_param("ii", $aid, $gv_id);
    }
    if ($stmt->execute()) {
        $msg = "success:Đã xóa bài tập thành công!";
    } else {
        $msg = "error:Lỗi xóa bài tập: " . $db->error;
    }
} elseif ($action === 'grade') {
    $sid = (int)$_POST['student_id'];
    $aid = (int)$_POST['assignment_id'];
    $grade = $_POST['grade'] !== '' ? (float)$_POST['grade'] : null;
    $feedback = trim($_POST['feedback'] ?? '');
    
    // Check if submission already exists, else insert
    $chk_sub = $db->prepare("SELECT id FROM submissions WHERE student_id = ? AND assignment_id = ?");
    $chk_sub->bind_param("ii", $sid, $aid);
    $chk_sub->execute();
    $existing_sub = $chk_sub->get_result()->fetch_assoc();
    $chk_sub->close();

    if ($existing_sub) {
        $stmt = $db->prepare("UPDATE submissions SET grade = ?, feedback = ? WHERE student_id = ? AND assignment_id = ?");
        $stmt->bind_param("dsii", $grade, $feedback, $sid, $aid);
    } else {
        $stmt = $db->prepare("INSERT INTO submissions (student_id, assignment_id, grade, feedback, submitted_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iids", $sid, $aid, $grade, $feedback);
    }

    if ($stmt->execute()) {
        $msg = "success:Đã lưu điểm và nhận xét bài nộp thành công!";
        $tab = 'grade';
    } else {
        $msg = "error:Lỗi chấm điểm: " . $db->error;
    }
}

// Fetch teacher's department
$st_t = $db->prepare("SELECT khoa FROM giang_vien WHERE id = ?");
$st_t->bind_param("i", $gv_id);
$st_t->execute();
$teacher_khoa = $st_t->get_result()->fetch_assoc()['khoa'] ?? '';

// Fetch all classes of this department for select dropdown
$classes = [];
if (!isAdmin() && $teacher_khoa) {
    $stmt = $db->prepare("SELECT DISTINCT lop FROM students WHERE LOWER(khoa) = LOWER(?) ORDER BY lop");
    $stmt->bind_param("s", $teacher_khoa);
    $stmt->execute();
    $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $res = $db->query("SELECT DISTINCT lop FROM students WHERE lop IS NOT NULL AND lop != '' ORDER BY lop");
    $classes = $res->fetch_all(MYSQLI_ASSOC);
}

// Fetch subjects taught by this teacher
$monList = [];
if (isAdmin()) {
    $res_m = $db->query("SELECT id, ten_mon FROM mon_hoc ORDER BY ten_mon");
    $monList = $res_m->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt_mon = $db->prepare("
        SELECT DISTINCT m.id, m.ten_mon 
        FROM thoi_khoa_bieu tkb 
        JOIN mon_hoc m ON tkb.mon_hoc_id = m.id 
        WHERE tkb.giang_vien_id = ? 
        ORDER BY m.ten_mon
    ");
    $stmt_mon->bind_param("i", $gv_id);
    $stmt_mon->execute();
    $monList = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($monList)) {
        if (!empty($teacher_khoa)) {
            $stmt_mon = $db->prepare("SELECT id, ten_mon FROM mon_hoc WHERE LOWER(khoa) = LOWER(?) ORDER BY ten_mon");
            $stmt_mon->bind_param("s", $teacher_khoa);
            $stmt_mon->execute();
            $monList = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        if (empty($monList)) {
            $res = $db->query("SELECT id, ten_mon FROM mon_hoc ORDER BY ten_mon");
            $monList = $res->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Fetch all current assignments
$assignments = [];
if (isAdmin()) {
    $res_assign = $db->query("
        SELECT a.*, m.ten_mon, g.ho_ten as ten_giang_vien 
        FROM assignments a
        JOIN mon_hoc m ON a.mon_hoc_id = m.id
        LEFT JOIN giang_vien g ON a.giang_vien_id = g.id
        ORDER BY a.id DESC
    ");
    $assignments = $res_assign ? $res_assign->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $stmt_assign = $db->prepare("
        SELECT a.*, m.ten_mon 
        FROM assignments a
        JOIN mon_hoc m ON a.mon_hoc_id = m.id
        WHERE a.giang_vien_id = ? 
        ORDER BY a.id DESC
    ");
    $stmt_assign->bind_param("i", $gv_id);
    $stmt_assign->execute();
    $assignments = $stmt_assign->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch submissions for a specific assignment if selected
$selected_assignment_id = (int)($_GET['assignment_id'] ?? ($assignments[0]['id'] ?? 0));
$submissions = [];
$assignment_info = null;
if ($selected_assignment_id) {
    if (isAdmin()) {
        $stmt_chk = $db->prepare("SELECT a.*, m.ten_mon, g.ho_ten as ten_giang_vien FROM assignments a JOIN mon_hoc m ON a.mon_hoc_id = m.id LEFT JOIN giang_vien g ON a.giang_vien_id = g.id WHERE a.id = ?");
        $stmt_chk->bind_param("i", $selected_assignment_id);
    } else {
        $stmt_chk = $db->prepare("SELECT a.*, m.ten_mon FROM assignments a JOIN mon_hoc m ON a.mon_hoc_id = m.id WHERE a.id = ? AND a.giang_vien_id = ?");
        $stmt_chk->bind_param("ii", $selected_assignment_id, $gv_id);
    }
    $stmt_chk->execute();
    $assignment_info = $stmt_chk->get_result()->fetch_assoc();
    
    if ($assignment_info) {
        // Fetch all students in the class, and check if they submitted
        $stmt_sub = $db->prepare("
            SELECT s.id as student_id, s.ma_sv, s.ho_ten, 
                   sub.id as submission_id, sub.submission_text, sub.file_path, sub.grade, sub.feedback, sub.submitted_at
            FROM students s
            LEFT JOIN submissions sub ON s.id = sub.student_id AND sub.assignment_id = ?
            WHERE s.lop = ?
            ORDER BY s.ho_ten
        ");
        $stmt_sub->bind_param("is", $selected_assignment_id, $assignment_info['lop']);
        $stmt_sub->execute();
        $submissions = $stmt_sub->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giao bài tập & Chấm điểm - Giảng viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .form-control {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 15px;
            border-radius: 8px;
            width: 100%;
            font-size: 14px;
            outline: none;
            margin-bottom: 15px;
        }
        .btn-submit {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.15s;
        }
        .btn-submit:hover {
            opacity: 0.9;
        }
        .assignment-card {
            background: rgba(255,255,255,0.01);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 10px 20px;
            border-bottom: 3px solid transparent;
            font-weight: 700;
            color: var(--text2);
            text-decoration: none;
            font-size: 14.5px;
            transition: 0.15s;
        }
        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }
        .dropzone {
            border: 2px dashed var(--border);
            background: var(--bg3);
            border-radius: 12px;
            padding: 25px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            margin-top: 8px;
            user-select: none;
        }
        .dropzone:hover, .dropzone.dragover {
            border-color: var(--accent);
            background: rgba(217, 27, 67, 0.02);
        }
        .dropzone-icon {
            font-size: 28px;
            color: var(--text2);
            margin-bottom: 10px;
            transition: transform 0.2s ease;
        }
        .dropzone:hover .dropzone-icon, .dropzone.dragover .dropzone-icon {
            color: var(--accent);
            transform: translateY(-2px);
        }
        .dropzone-text {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 5px 0;
        }
        .dropzone-text span {
            color: var(--accent);
            text-decoration: underline;
        }
        .dropzone-sub {
            font-size: 11px;
            color: var(--text2);
            margin: 0;
        }
        .dropzone-file-name {
            display: none;
            margin-top: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #10b981;
            background: rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 6px 12px;
            border-radius: 6px;
            word-break: break-all;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <?php include '../includes/teacher_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-pen-to-square" style="color:var(--accent)"></i> Quản Lý Bài Tập</h1>
            <p style="color: var(--text2); margin-top: 5px;">Giao bài tập về nhà cho lớp phụ trách, theo dõi và chấm điểm các bài nộp của sinh viên</p>
        </div>
    </div>

    <!-- Feedback messages -->
    <?php if ($msg): 
        $parts = explode(':', $msg);
        $type = $parts[0];
        $text = $parts[1];
    ?>
        <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="display: block; margin-bottom: 20px;">
            <?= htmlspecialchars($text) ?>
        </div>
    <?php endif; ?>

    <!-- Tab Header -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body" style="display: flex; gap: 5px; padding: 15px 20px;">
            <a href="?tab=manage&assignment_id=<?= $selected_assignment_id ?>" class="tab-btn <?= $tab === 'manage' ? 'active' : '' ?>"><i class="fa-solid fa-list-check"></i> Tạo &amp; Giao bài tập</a>
            <a href="?tab=grade&assignment_id=<?= $selected_assignment_id ?>" class="tab-btn <?= $tab === 'grade' ? 'active' : '' ?>"><i class="fa-solid fa-graduation-cap"></i> Chấm điểm bài nộp</a>
        </div>
    </div>

    <?php if ($tab === 'manage'): ?>
        <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 30px; align-items: start;">
            <!-- Left: Add Assignment Form -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-folder-plus"></i> Tạo bài tập mới</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="baitap.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="tab" value="manage">
                        
                        <div class="form-group">
                            <label class="form-label">Chọn học phần</label>
                            <select name="mon_hoc_id" class="form-control" required style="cursor: pointer;">
                                <option value="">-- Chọn môn học --</option>
                                <?php foreach ($monList as $mon): ?>
                                    <option value="<?= $mon['id'] ?>"><?= htmlspecialchars($mon['ten_mon']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lớp nhận bài</label>
                            <select name="lop" class="form-control" required style="cursor: pointer;">
                                <option value="">-- Chọn lớp học --</option>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?= htmlspecialchars($cl['lop']) ?>"><?= htmlspecialchars($cl['lop']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tiêu đề bài tập</label>
                            <input type="text" name="tieu_de" class="form-control" placeholder="Ví dụ: Bài tập thực hành 1: Thiết kế giao diện" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mô tả / Đề bài chi tiết</label>
                            <textarea name="mo_ta" class="form-control" rows="6" placeholder="Nhập yêu cầu đề bài, quy chế nộp file..." style="resize: vertical;"></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label">Hạn nộp bài</label>
                            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 10px;">
                                <input type="date" name="han_nop_date" class="form-control" style="margin-bottom:0;" required>
                                <input type="time" name="han_nop_time" class="form-control" style="margin-bottom:0;" value="23:59" required>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">Tài liệu đính kèm (Zip, PDF, Rar...)</label>
                            <div class="dropzone" id="dropzone-assign" onclick="document.getElementById('file-input-assign').click()">
                                <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
                                <p class="dropzone-text">Kéo thả tệp tin vào đây hoặc <span>chọn tệp</span> để tải lên</p>
                                <p class="dropzone-sub">Hỗ trợ các tệp dạng ZIP, RAR, PDF, DOCX... (Tối đa 10MB)</p>
                                <div class="dropzone-file-name" id="file-name-assign"></div>
                                <input type="file" name="assign_file" id="file-input-assign" class="dropzone-input" style="display:none;" onchange="handleFileSelect(this)">
                            </div>
                        </div>
                        <button type="submit" class="btn-submit"><i class="fa-solid fa-bullhorn"></i> Giao bài ngay</button>
                    </form>
                </div>
            </div>

            <!-- Right: Assignments list -->
            <div>
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-head">
                        <span class="card-title"><i class="fa-solid fa-list-check"></i> Bài tập đã giao (<?= count($assignments) ?>)</span>
                    </div>
                </div>

                <?php if (empty($assignments)): ?>
                    <div class="card" style="padding: 40px; text-align: center; color: var(--text2);">
                        <i class="fa-solid fa-box-open" style="font-size: 32px; color: var(--accent); margin-bottom: 15px; display: block;"></i>
                        Chưa có bài tập nào được giao.
                    </div>
                <?php else: foreach ($assignments as $as): 
                    $is_expired = $as['han_nop'] && (strtotime($as['han_nop']) < time());
                ?>
                    <div class="assignment-card">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <span class="badge" style="background: rgba(56, 189, 248, 0.12); color:#38bdf8; border:1px solid rgba(56,189,248,0.2); font-size: 11px; margin-right: 8px; font-weight: 700;"><?= htmlspecialchars($as['ten_mon']) ?></span>
                                <span class="badge" style="background: rgba(16, 185, 129, 0.12); color:#10b981; border:1px solid rgba(16, 185, 129, 0.2); font-size: 11px; font-weight: 700;">Lớp: <?= htmlspecialchars($as['lop']) ?></span>
                            </div>
                            <span style="font-size: 11.5px; color: <?= $is_expired ? '#ef4444' : '#10b981' ?>; font-weight: 700;">
                                <i class="fa-solid fa-clock-rotate-left"></i> Hạn nộp: <?= $as['han_nop'] ? date('d/m/Y H:i', strtotime($as['han_nop'])) : 'Không giới hạn' ?> <?= $is_expired ? '(Hết hạn)' : '' ?>
                            </span>
                        </div>
                        
                        <h3 style="font-size: 16px; font-weight: 800; color: var(--text); margin-bottom: 10px;"><?= htmlspecialchars($as['tieu_de']) ?></h3>
                        <p style="font-size: 13.5px; color: var(--text2); line-height: 1.6; white-space: pre-wrap; margin-bottom: 15px;"><?= htmlspecialchars($as['mo_ta']) ?></p>
                        
                        <?php if (!empty($as['file_path'])): ?>
                            <div style="font-size:12.5px; margin-bottom: 15px; background: rgba(56, 189, 248, 0.03); border: 1px solid rgba(56, 189, 248, 0.2); padding: 8px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-paperclip" style="color:#38bdf8;"></i>
                                <span style="color:var(--text2);">Tài liệu đính kèm:</span>
                                <a href="<?= htmlspecialchars($as['file_path']) ?>" target="_blank" style="color:#38bdf8; font-weight:700; text-decoration:none;">Xem / Tải tài liệu</a>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 15px; margin-top: 15px;">
                            <span style="font-size: 12px; color: var(--text2);">Giao ngày: <?= date('d/m/Y', strtotime($as['created_at'])) ?></span>
                            <div style="display:flex; gap:10px;">
                                <a href="?tab=grade&assignment_id=<?= $as['id'] ?>" class="btn-ghost" style="padding: 5px 12px; border-radius: 6px; font-size: 11.5px; text-decoration: none; font-weight:700;">
                                    <i class="fa-solid fa-circle-check"></i> Chấm bài nộp
                                </a>
                                <a href="?action=delete&assignment_id=<?= $as['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa bài tập này?')" class="btn-ghost" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2); padding: 5px 10px; border-radius: 6px; font-size: 11px; text-decoration: none;">
                                    <i class="fa-solid fa-trash-can"></i> Xóa
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- Grade Submissions tab -->
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
            <!-- Left: Choose Assignment -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-filter"></i> Lọc theo bài tập</span>
                </div>
                <div class="card-body" style="padding:15px;">
                    <?php if (empty($assignments)): ?>
                        <p style="text-align:center; color:var(--text2);">Chưa có bài tập nào để lọc.</p>
                    <?php else: foreach ($assignments as $as): ?>
                        <a href="?tab=grade&assignment_id=<?= $as['id'] ?>" class="list-item" style="display: block; padding: 12px 15px; border-bottom: 1px solid var(--border); text-decoration: none; color: var(--text); border-left: 3px solid <?= $selected_assignment_id === $as['id'] ? 'var(--accent)' : 'transparent' ?>; background: <?= $selected_assignment_id === $as['id'] ? 'rgba(217,27,67,0.02)' : 'transparent' ?>; transition: 0.15s; border-radius: 6px; margin-bottom: 5px;">
                            <div style="font-weight: 700; font-size:13.5px;"><?= htmlspecialchars($as['tieu_de']) ?></div>
                            <div style="font-size:11px; color:var(--text2); margin-top:4px;">Lớp: <?= htmlspecialchars($as['lop']) ?> &bull; <?= htmlspecialchars($as['ten_mon']) ?></div>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Right: Submissions List -->
            <div>
                <?php if (!$assignment_info): ?>
                    <div class="card" style="padding: 40px; text-align: center; color: var(--text2);">
                        Vui lòng chọn bài tập bên trái để chấm điểm.
                    </div>
                <?php else: ?>
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-head">
                            <span class="card-title"><i class="fa-solid fa-graduation-cap"></i> Bài tập: <?= htmlspecialchars($assignment_info['tieu_de']) ?> (Lớp <?= htmlspecialchars($assignment_info['lop']) ?>)</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($submissions)): ?>
                                <p style="text-align: center; color: var(--text2); padding: 30px;">Không tìm thấy sinh viên nào trong lớp <?= htmlspecialchars($assignment_info['lop']) ?>.</p>
                            <?php else: ?>
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        <tr style="background:rgba(0,0,0,0.02);">
                                            <th style="padding: 15px; text-align: left;">Mã SV</th>
                                            <th style="padding: 15px; text-align: left;">Họ tên</th>
                                            <th style="padding: 15px; text-align: left;">Ngày nộp</th>
                                            <th style="padding: 15px; text-align: left;">Bài nộp</th>
                                            <th style="padding: 15px; text-align: center;">Điểm</th>
                                            <th style="padding: 15px; text-align: center;">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissions as $sub): 
                                            $has_sub = ($sub['submission_id'] !== null);
                                        ?>
                                            <tr style="border-bottom: 1px solid var(--border);">
                                                <td style="padding: 15px; font-weight: 700; color: var(--accent);"><?= htmlspecialchars($sub['ma_sv']) ?></td>
                                                <td style="padding: 15px; font-weight: 700; color: var(--text);"><?= htmlspecialchars($sub['ho_ten']) ?></td>
                                                <td style="padding: 15px; font-size:12px; color: var(--text2);">
                                                    <?= $has_sub ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : '<span style="color:#ef4444;">Chưa nộp</span>' ?>
                                                </td>
                                                <td style="padding: 15px; color: var(--text2); font-size: 13px;">
                                                    <?php if ($has_sub): ?>
                                                        <?php if (!empty($sub['file_path'])): ?>
                                                            <a href="<?= htmlspecialchars($sub['file_path']) ?>" target="_blank" style="color:var(--accent); font-weight:700; margin-right:8px; text-decoration:none;"><i class="fa-solid fa-file-arrow-down"></i> Tải file</a>
                                                            <button class="btn-ghost" style="padding:2px 6px; font-size:10.5px; border-radius:4px; font-weight:700; color:#10b981; border-color:rgba(16,185,129,0.2); background:rgba(16,185,129,0.02); cursor:pointer;" onclick="openPreviewModal(<?= $sub['submission_id'] ?>, '<?= htmlspecialchars($sub['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sub['ho_ten'], ENT_QUOTES) ?>')">
                                                                <i class="fa-solid fa-play"></i> Chạy thử
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if (!empty($sub['submission_text'])): ?>
                                                            <div style="font-style:italic; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; cursor:pointer;" onclick="alert('Nội dung: <?= htmlspecialchars(str_replace(array("\r", "\n"), ' ', $sub['submission_text']), ENT_QUOTES) ?>')"><?= htmlspecialchars($sub['submission_text']) ?></div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 15px; text-align: center; font-weight:800; color:var(--accent);">
                                                    <?= $sub['grade'] !== null ? htmlspecialchars($sub['grade']) : '<span style="color:#aaa; font-weight:400;">-</span>' ?>
                                                </td>
                                                <td style="padding: 15px; text-align: center;">
                                                    <?php if ($has_sub): ?>
                                                        <button class="btn-ghost" style="padding:4px 8px; font-size:11px; border-radius:4px; font-weight:600;" onclick="openGradeModal(<?= $sub['student_id'] ?>, '<?= htmlspecialchars($sub['ho_ten'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sub['grade'] ?? '') ?>', '<?= htmlspecialchars($sub['feedback'] ?? '', ENT_QUOTES) ?>')">
                                                            <i class="fa-solid fa-pen"></i> Chấm
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn-ghost" disabled style="padding:4px 8px; font-size:11px; border-radius:4px; opacity:0.4; cursor:not-allowed;">Chấm</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Grade Modal -->
    <div class="modal-overlay" id="gradeModal" onclick="if(event.target==this) toggleModal('gradeModal')">
        <div class="modal-box" style="width: 500px; max-width: 95vw;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; border-bottom: 1px solid rgba(217, 27, 67, 0.2); padding-bottom: 15px;">
                <h3 class="modal-title" style="margin:0;"><i class="fa-solid fa-graduation-cap" style="color:var(--accent);"></i> Chấm điểm bài tập</h3>
                <button onclick="toggleModal('gradeModal')" style="background:none; border:none; color:var(--text2); font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="?action=grade&tab=grade&assignment_id=<?= $selected_assignment_id ?>">
                <input type="hidden" name="student_id" id="grade_student_id">
                <input type="hidden" name="assignment_id" value="<?= $selected_assignment_id ?>">
                
                <div class="form-group">
                    <label class="form-label">Sinh viên</label>
                    <input type="text" id="grade_student_name" class="form-control" readonly style="background:var(--border);">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Điểm số (0 - 10)</label>
                    <input type="number" step="0.1" min="0" max="10" name="grade" id="grade_score" class="form-control" placeholder="Nhập điểm..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nhận xét bài làm</label>
                    <textarea name="feedback" id="grade_feedback" class="form-control" rows="4" placeholder="Nhập nhận xét của giáo viên..."></textarea>
                </div>
                
                <button type="submit" class="btn-submit"><i class="fa-solid fa-check"></i> Lưu điểm</button>
            </form>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal-overlay" id="previewModal" onclick="if(event.target==this) closePreviewModal()">
        <div class="modal-box" style="width: 1000px; max-width: 95vw; height: 85vh; display: flex; flex-direction: column; padding: 0;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid rgba(217, 27, 67, 0.2); padding: 15px 20px; background:var(--bg2); border-top-left-radius:16px; border-top-right-radius:16px;">
                <h3 class="modal-title" style="margin:0; font-size:16px; font-weight:800; color:var(--text);"><i class="fa-solid fa-laptop-code" style="color:var(--accent); margin-right:8px;"></i> Chạy thử bài nộp: <span id="preview_student_name" style="color:var(--accent)"></span></h3>
                <button onclick="closePreviewModal()" style="background:none; border:none; color:var(--text2); font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <iframe id="previewIframe" style="width: 100%; flex: 1; border: none; background: #fff; border-bottom-left-radius:16px; border-bottom-right-radius:16px;" src="about:blank"></iframe>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            document.getElementById(id).classList.toggle('show');
        }
        function openGradeModal(sid, name, grade, feedback) {
            document.getElementById('grade_student_id').value = sid;
            document.getElementById('grade_student_name').value = name;
            document.getElementById('grade_score').value = grade;
            document.getElementById('grade_feedback').value = feedback;
            document.getElementById('gradeModal').classList.add('show');
        }
        function openPreviewModal(submissionId, filePath, studentName) {
            document.getElementById('preview_student_name').innerText = studentName;
            const iframe = document.getElementById('previewIframe');
            iframe.src = 'preview.php?submission_id=' + submissionId + '&file_path=' + encodeURIComponent(filePath);
            document.getElementById('previewModal').classList.add('show');
        }
        function closePreviewModal() {
            document.getElementById('previewIframe').src = 'about:blank';
            document.getElementById('previewModal').classList.remove('show');
        }

        // Drag and Drop support for teacher file uploads
        function handleFileSelect(input) {
            const fileNameEl = document.getElementById('file-name-assign');
            if (input.files && input.files.length > 0) {
                const file = input.files[0];
                fileNameEl.innerHTML = `<i class="fa-solid fa-paperclip"></i> ${file.name} (${(file.size/1024/1024).toFixed(2)} MB)`;
                fileNameEl.style.display = 'inline-flex';
            } else {
                fileNameEl.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const dropzone = document.getElementById('dropzone-assign');
            if (dropzone) {
                const fileInput = document.getElementById('file-input-assign');
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('dragover');
                    }, false);
                });

                dropzone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        handleFileSelect(fileInput);
                    }
                }, false);
            }
        });
    </script>
</body>
</html>
