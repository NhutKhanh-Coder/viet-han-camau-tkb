<?php
require_once '../config.php';
requireTeacher();

function remove_accents_vietnamese_teacher($str) {
    $unicode = array(
        'a'=>'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd'=>'đ',
        'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i'=>'í|ì|ỉ|ĩ|ị',
        'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y'=>'ý|ỳ|ỷ|ỹ|ị',
        'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'D'=>'Đ',
        'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
        'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ị',
    );
    foreach($unicode as $nonUnicode=>$uni){
        $str = preg_replace("/($uni)/i", $nonUnicode, $str);
    }
    return $str;
}

function uploadPracticeProblemFile($input_name, &$error_message) {
    $error_message = '';
    if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$input_name];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Không thể tải tệp đề lên máy chủ. Vui lòng thử lại.';
        return false;
    }

    if ($file['size'] > 20 * 1024 * 1024) {
        $error_message = 'Tệp đề không được vượt quá 20 MB.';
        return false;
    }

    $original_name = basename(str_replace('\\', '/', $file['name']));
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
    if (!in_array($extension, $allowed_extensions, true)) {
        $error_message = 'Định dạng đề không được hỗ trợ. Hãy dùng PDF, Word, PowerPoint, Excel, TXT, ZIP/RAR hoặc ảnh.';
        return false;
    }

    $upload_dir = '../assets/uploads/practice_problems/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
        $error_message = 'Không thể tạo thư mục lưu tệp đề.';
        return false;
    }

    $stored_name = 'de_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $stored_name)) {
        $error_message = 'Không thể lưu tệp đề lên máy chủ.';
        return false;
    }

    return [
        'path' => '/tkb/assets/uploads/practice_problems/' . $stored_name,
        'name' => $original_name,
    ];
}

function deletePracticeProblemFile($web_path) {
    if (!$web_path) {
        return;
    }

    $disk_path = '..' . str_replace('/tkb', '', $web_path);
    if (is_file($disk_path)) {
        @unlink($disk_path);
    }
}

$db = getDB();

$message = '';
$message_type = '';

// Handle actions
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $lop = trim($_POST['lop'] ?? '');
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        $start_time_raw = $_POST['start_time'] ?? '';
        $end_time_raw = $_POST['end_time'] ?? '';
        $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;

        if (empty($lop) || empty($start_time_raw) || empty($end_time_raw)) {
            $message = "Vui lòng nhập đầy đủ lớp, thời gian bắt đầu và kết thúc!";
            $message_type = "danger";
        } else {
            $upload_error = '';
            $problem_file = uploadPracticeProblemFile('problem_file', $upload_error);
            if ($problem_file === false) {
                $message = $upload_error;
                $message_type = "danger";
            } else {
            $today = date('Y-m-d');
            $start_time = $today . ' ' . $start_time_raw . ':00';
            if (strtotime($end_time_raw) < strtotime($start_time_raw)) {
                $end_time = date('Y-m-d', strtotime('+1 day')) . ' ' . $end_time_raw . ':00';
            } else {
                $end_time = $today . ' ' . $end_time_raw . ':00';
            }

            $de_file_path = $problem_file['path'] ?? null;
            $de_file_name = $problem_file['name'] ?? null;
            $stmt = $db->prepare("INSERT INTO practice_sessions (lop, mo_ta, de_file_path, de_file_name, start_time, end_time, is_enabled) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $lop, $mo_ta, $de_file_path, $de_file_name, $start_time, $end_time, $is_enabled);
            if ($stmt->execute()) {
                $message = "Thêm phiên thực hành/thi thành công!";
                $message_type = "success";
            } else {
                if ($problem_file) {
                    deletePracticeProblemFile($problem_file['path']);
                }
                $message = "Lỗi khi thêm phiên thực hành: " . $db->error;
                $message_type = "danger";
            }
            $stmt->close();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $lop = trim($_POST['lop'] ?? '');
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        $start_time_raw = $_POST['start_time'] ?? '';
        $end_time_raw = $_POST['end_time'] ?? '';
        $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;

        if ($id > 0 && !empty($lop) && !empty($start_time_raw) && !empty($end_time_raw)) {
            $stmt_current = $db->prepare("SELECT de_file_path, de_file_name FROM practice_sessions WHERE id = ?");
            $stmt_current->bind_param("i", $id);
            $stmt_current->execute();
            $current_session = $stmt_current->get_result()->fetch_assoc();
            $stmt_current->close();

            $upload_error = '';
            $problem_file = uploadPracticeProblemFile('problem_file', $upload_error);
            if ($problem_file === false) {
                $message = $upload_error;
                $message_type = "danger";
            } else {
                $today = date('Y-m-d');
                $start_time = $today . ' ' . $start_time_raw . ':00';
                if (strtotime($end_time_raw) < strtotime($start_time_raw)) {
                    $end_time = date('Y-m-d', strtotime('+1 day')) . ' ' . $end_time_raw . ':00';
                } else {
                    $end_time = $today . ' ' . $end_time_raw . ':00';
                }

                $de_file_path = $problem_file['path'] ?? ($current_session['de_file_path'] ?? null);
                $de_file_name = $problem_file['name'] ?? ($current_session['de_file_name'] ?? null);
                $stmt = $db->prepare("UPDATE practice_sessions SET lop = ?, mo_ta = ?, de_file_path = ?, de_file_name = ?, start_time = ?, end_time = ?, is_enabled = ? WHERE id = ?");
                $stmt->bind_param("ssssssii", $lop, $mo_ta, $de_file_path, $de_file_name, $start_time, $end_time, $is_enabled, $id);
                if ($stmt->execute()) {
                    if ($problem_file && !empty($current_session['de_file_path']) && $current_session['de_file_path'] !== $problem_file['path']) {
                        deletePracticeProblemFile($current_session['de_file_path']);
                    }
                    $message = "Cập nhật phiên thực hành/thi thành công!";
                    $message_type = "success";
                } else {
                    if ($problem_file) {
                        deletePracticeProblemFile($problem_file['path']);
                    }
                    $message = "Lỗi khi cập nhật: " . $db->error;
                    $message_type = "danger";
                }
                $stmt->close();
            }
        }
    }
}

if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt_file = $db->prepare("SELECT de_file_path FROM practice_sessions WHERE id = ?");
        $stmt_file->bind_param("i", $id);
        $stmt_file->execute();
        $session_file = $stmt_file->get_result()->fetch_assoc();
        $stmt_file->close();

        // Delete submissions and session
        $db->query("DELETE FROM practice_submissions WHERE session_id = $id");

        $stmt = $db->prepare("DELETE FROM practice_sessions WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            deletePracticeProblemFile($session_file['de_file_path'] ?? null);
            $message = "Đã xóa phiên thực hành/thi và các bài nộp liên quan!";
            $message_type = "success";
        }
        $stmt->close();
    }
}

if ($action === 'toggle') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE practice_sessions SET is_enabled = 1 - is_enabled WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header('Location: quanly_thuchanh.php');
        exit();
    }
}

// Fetch all classes
$class_query = $db->query("SELECT DISTINCT lop FROM students ORDER BY lop");
$classes = [];
while ($row = $class_query->fetch_assoc()) {
    $classes[] = $row['lop'];
}

// Fetch practice sessions
$sessions_query = $db->query("SELECT * FROM practice_sessions ORDER BY start_time DESC");
$sessions = $sessions_query->fetch_all(MYSQLI_ASSOC);

// If editing, fetch the specific record
$edit_session = null;
if (($action === 'edit_form' || $action === 'edit') && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $stmt = $db->prepare("SELECT * FROM practice_sessions WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_session = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// If viewing submissions
$session_submissions = null;
$selected_session_details = null;
if ($action === 'view_submissions' && isset($_GET['session_id'])) {
    $ss_id = intval($_GET['session_id']);
    
    // Fetch session details
    $stmt = $db->prepare("SELECT * FROM practice_sessions WHERE id = ?");
    $stmt->bind_param("i", $ss_id);
    $stmt->execute();
    $selected_session_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($selected_session_details) {
        $stmt_subs = $db->prepare("
            SELECT ps.*, s.ho_ten, s.ma_sv, s.lop, s.avatar 
            FROM practice_submissions ps
            JOIN students s ON ps.student_id = s.id
            WHERE ps.session_id = ?
            ORDER BY ps.submitted_at DESC
        ");
        $stmt_subs->bind_param("i", $ss_id);
        $stmt_subs->execute();
        $session_submissions = $stmt_subs->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_subs->close();
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Giờ Thực hành & Thi cử - Giảng viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .custom-alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .custom-alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .custom-alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .time-badge {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .time-badge-future {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        .time-badge-active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .time-badge-expired {
            background: rgba(100, 116, 139, 0.1);
            color: #64748b;
            border: 1px solid rgba(100, 116, 139, 0.2);
        }
    </style>
</head>
<body class="admin-portal">
    <?php if (isAdmin()): ?>
        <?php include '../includes/admin_nav.php'; ?>
        <div class="main-content">
    <?php else: ?>
        <?php include '../includes/teacher_nav.php'; ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-code" style="color:var(--accent)"></i> Quản lý Giờ Thực hành & Thi cử</h1>
            <p style="color: var(--text2); margin-top: 5px;">Mở khóa hoặc giới hạn thời gian sinh viên truy cập Code IDE để thi và làm bài tập thực hành.</p>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="custom-alert custom-alert-<?= $message_type ?>">
            <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
        <!-- Setup practice session form -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">
                    <i class="fa-solid <?= $edit_session ? 'fa-pen-to-square' : 'fa-circle-plus' ?>"></i>
                    <?= $edit_session ? 'Chỉnh sửa phiên' : 'Tạo phiên mới' ?>
                </span>
            </div>
            <div class="card-body" style="padding: 20px;">
                <form method="POST" action="?action=<?= $edit_session ? 'edit' : 'add' ?>" enctype="multipart/form-data">
                    <?php if ($edit_session): ?>
                        <input type="hidden" name="id" value="<?= $edit_session['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Chọn Lớp Áp Dụng</label>
                        <select class="form-select" name="lop" required style="width: 100%; padding: 10px; background: var(--bg3); border: 1px solid var(--border); border-radius: 8px; color: var(--text);">
                            <option value="ALL" <?= ($edit_session && $edit_session['lop'] === 'ALL') ? 'selected' : '' ?>>Tất cả các lớp (ALL)</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= ($edit_session && $edit_session['lop'] === $c) ? 'selected' : '' ?>>Lớp <?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label class="form-label">Tên Buổi Thực Hành / Thi</label>
                        <input type="text" class="form-input" name="mo_ta" placeholder="Ví dụ: Thi học kỳ I - Môn PHP" value="<?= htmlspecialchars($edit_session['mo_ta'] ?? '') ?>" required>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label class="form-label">Tệp đề bài cho sinh viên <span style="color:var(--text2); font-weight:400;">(không bắt buộc)</span></label>
                        <input type="file" class="form-input" name="problem_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.jpg,.jpeg,.png" style="padding:8px 10px;">
                        <div style="font-size:11px; color:var(--text2); margin-top:6px;">Tối đa 20 MB. Sinh viên chỉ xem được đề khi phiên đang diễn ra.</div>
                        <?php if ($edit_session && !empty($edit_session['de_file_path'])): ?>
                            <a href="<?= htmlspecialchars($edit_session['de_file_path']) ?>" target="_blank" rel="noopener" style="display:inline-block; margin-top:7px; font-size:12px; color:var(--accent); font-weight:700;">
                                <i class="fa-solid fa-file-arrow-up"></i> Đề hiện tại: <?= htmlspecialchars($edit_session['de_file_name'] ?: 'Mở tệp đề') ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label class="form-label">Giờ bắt đầu</label>
                        <input type="time" class="form-input" name="start_time" value="<?= $edit_session ? date('H:i', strtotime($edit_session['start_time'])) : '' ?>" required>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label class="form-label">Giờ kết thúc</label>
                        <input type="time" class="form-input" name="end_time" value="<?= $edit_session ? date('H:i', strtotime($edit_session['end_time'])) : '' ?>" required>
                    </div>

                    <div class="form-group" style="margin-top: 20px; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_enabled" id="is_enabled" value="1" <?= (!$edit_session || $edit_session['is_enabled']) ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="is_enabled" style="font-weight: 600; cursor: pointer; color: var(--text);">Kích hoạt phiên ngay</label>
                    </div>

                    <div style="margin-top: 25px; display: flex; gap: 10px;">
                        <button type="submit" class="btn" style="flex: 1; padding: 12px; background: var(--accent); color: white; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">
                            <i class="fa-solid fa-save"></i> <?= $edit_session ? 'Lưu cập nhật' : 'Tạo phiên' ?>
                        </button>
                        <?php if ($edit_session): ?>
                            <a href="quanly_thuchanh.php" class="btn btn-ghost" style="padding: 12px; border-radius: 8px; text-decoration: none; text-align: center; line-height: 1.2;">Hủy</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Practice sessions list -->
        <div class="card">
            <div class="card-head">
                <span class="card-title"><i class="fa-solid fa-list-check"></i> Lịch sử & danh sách phiên thực hành</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="tkb-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr>
                            <th style="padding: 15px 20px;">Lớp</th>
                            <th style="padding: 15px 20px;">Mô tả</th>
                            <th style="padding: 15px 20px;">Thời gian</th>
                            <th style="padding: 15px 20px; text-align: center;">Trạng thái</th>
                            <th style="padding: 15px 20px; text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: var(--text2);">Chưa có phiên thực hành nào được tạo.</td>
                            </tr>
                        <?php else: foreach ($sessions as $ss): 
                            $now = time();
                            $start = strtotime($ss['start_time']);
                            $end = strtotime($ss['end_time']);
                            
                            $status_text = 'Chưa diễn ra';
                            $status_class = 'time-badge-future';
                            
                            if ($now >= $start && $now <= $end) {
                                $status_text = 'Đang diễn ra';
                                $status_class = 'time-badge-active';
                            } elseif ($now > $end) {
                                $status_text = 'Đã kết thúc';
                                $status_class = 'time-badge-expired';
                            }
                        ?>
                            <tr>
                                <td style="padding: 15px 20px;">
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; font-weight: 700; border: 1px solid rgba(59,130,246,0.2);">
                                        <?= htmlspecialchars($ss['lop']) ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($ss['mo_ta'] ?: 'Phiên thực hành') ?></div>
                                    <div style="font-size: 11px; color: var(--text2); margin-top: 3px;">Tạo lúc: <?= date('d/m/Y H:i', strtotime($ss['created_at'])) ?></div>
                                </td>
                                <td style="padding: 15px 20px; font-size: 13.5px;">
                                    <div><i class="fa-regular fa-clock" style="color:#10b981; font-size:12px; margin-right:3px;"></i><?= date('H:i', $start) ?></div>
                                    <div style="margin-top: 3px;"><i class="fa-regular fa-clock" style="color:#ef4444; font-size:12px; margin-right:3px;"></i><?= date('H:i', $end) ?></div>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <?php if ($ss['is_enabled']): ?>
                                        <span class="time-badge <?= $status_class ?>"><?= $status_text ?></span>
                                    <?php else: ?>
                                        <span class="time-badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2)">Đang Khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <a href="?action=view_submissions&session_id=<?= $ss['id'] ?>" class="btn btn-ghost" style="padding: 6px 10px; font-size: 12px; text-decoration: none;" title="Xem bài nộp">
                                        <i class="fa-solid fa-file-zipper text-warning"></i>
                                    </a>
                                    <a href="?action=toggle&id=<?= $ss['id'] ?>" class="btn btn-ghost" style="padding: 6px 10px; font-size: 12px; text-decoration: none;" title="Bật/Tắt phiên">
                                        <i class="fa-solid <?= $ss['is_enabled'] ? 'fa-toggle-on text-success' : 'fa-toggle-off text-secondary' ?>"></i>
                                    </a>
                                    <a href="?action=edit_form&id=<?= $ss['id'] ?>" class="btn btn-ghost" style="padding: 6px 10px; font-size: 12px; text-decoration: none;" title="Chỉnh sửa">
                                        <i class="fa-regular fa-pen-to-square text-primary"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $ss['id'] ?>" class="btn btn-ghost" onclick="return confirm('Bạn có chắc chắn muốn xóa phiên này?')" style="padding: 6px 10px; font-size: 12px; text-decoration: none;" title="Xóa">
                                        <i class="fa-regular fa-trash-can text-danger"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($selected_session_details): ?>
        <div class="card" style="margin-top: 30px;">
            <div class="card-head" style="display:flex; justify-content:space-between; align-items:center; padding: 15px 20px; border-bottom: 1px solid var(--border);">
                <span class="card-title" style="font-weight: 800; font-size: 16px;">
                    <i class="fa-solid fa-file-zipper text-warning" style="margin-right:8px;"></i> 
                    Danh sách bài nộp: <strong><?= htmlspecialchars($selected_session_details['mo_ta']) ?></strong> (Lớp <?= htmlspecialchars($selected_session_details['lop']) ?>)
                </span>
                <a href="quanly_thuchanh.php" class="btn btn-ghost" style="padding: 6px 12px; font-size: 13px; text-decoration:none;"><i class="fa-solid fa-xmark"></i> Đóng bảng</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="tkb-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr>
                            <th style="padding: 15px 20px;">Mã SV</th>
                            <th style="padding: 15px 20px;">Họ tên</th>
                            <th style="padding: 15px 20px;">Lớp</th>
                            <th style="padding: 15px 20px;">Thời gian nộp</th>
                            <th style="padding: 15px 20px; text-align: center;">Điểm</th>
                            <th style="padding: 15px 20px;">Nhận xét</th>
                            <th style="padding: 15px 20px; text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($session_submissions)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: var(--text2);">Chưa có sinh viên nào nộp bài cho phiên này.</td>
                            </tr>
                        <?php else: foreach ($session_submissions as $sub): ?>
                            <tr>
                                <td style="padding: 15px 20px; font-weight: 700; color: var(--accent);"><?= htmlspecialchars($sub['ma_sv']) ?></td>
                                <td style="padding: 15px 20px; font-weight: 600; color: var(--text);">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php 
                                            $sub_av = !empty($sub['avatar']) ? '/tkb/assets/img/avatars/' . htmlspecialchars($sub['avatar']) : '/tkb/assets/img/logo_vkc.jpg';
                                        ?>
                                        <img src="<?= $sub_av ?>" alt="avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--accent); flex-shrink: 0;">
                                        <?= htmlspecialchars($sub['ho_ten']) ?>
                                    </div>
                                </td>
                                <td style="padding: 15px 20px;"><?= htmlspecialchars($sub['lop']) ?></td>
                                <td style="padding: 15px 20px; color: var(--text2); font-size: 13px;">
                                    <?= date('d/m/Y H:i:s', strtotime($sub['submitted_at'])) ?>
                                    <?php if ($selected_session_details && strtotime($sub['submitted_at']) < strtotime($selected_session_details['end_time'])): ?>
                                        <span class="badge" style="display:inline-block; margin-left:6px; background:rgba(59,130,246,.1); color:#2563eb; font-weight:700; font-size:10px; border:1px solid rgba(59,130,246,.2); padding:2px 7px;">Nộp sớm</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <?php if ($sub['diem'] !== null): ?>
                                        <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-weight: 800; font-size: 13px; border: 1px solid rgba(16,185,129,0.2); padding: 4px 10px;">
                                            <?= floatval($sub['diem']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background: rgba(239, 68, 68, 0.08); color: #ef4444; font-weight: 600; font-size: 11px; border: 1px solid rgba(239,68,68,0.15); padding: 3px 8px;">
                                            Chưa chấm
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: var(--text2);">
                                    <?= htmlspecialchars($sub['nhan_xet'] ?: '--') ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <a href="/tkb/teacher/cham_code.php?submission_id=<?= $sub['id'] ?>" class="btn" style="padding: 8px 15px; font-size: 12px; font-weight: 700; background: #8b5cf6; color: white; border-radius: 6px; text-decoration: none; margin-right: 5px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-code"></i> Chấm bài & Chạy thử
                                    </a>
                                    <a href="<?= htmlspecialchars($sub['file_path']) ?>" download="<?= htmlspecialchars($sub['ma_sv']) ?>_<?= htmlspecialchars(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', remove_accents_vietnamese_teacher($sub['ho_ten'])))) ?>_session_<?= $sub['session_id'] ?>.zip" class="btn btn-ghost" style="padding: 8px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; text-decoration: none;" title="Tải zip">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
