<?php
require_once '../config.php';
requireStudent();
$db = getDB();
$sv_id = $_SESSION['student_id'] ?? 0;

// Fetch student info
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $sv_id);
$stmt->execute();
$sv = $stmt->get_result()->fetch_assoc();
$khoa = $sv['khoa'] ?? '';

$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $mid = (int)$_POST['mon_hoc_id'];
    $front = trim($_POST['mat_truoc'] ?? '');
    $back = trim($_POST['mat_sau'] ?? '');
    
    if ($mid && $front && $back) {
        // Since it's added by student, giang_vien_id is set to 0 as a placeholder
        $stmt_add = $db->prepare("INSERT INTO flashcards (giang_vien_id, mon_hoc_id, mat_truoc, mat_sau) VALUES (0, ?, ?, ?)");
        $stmt_add->bind_param("iss", $mid, $front, $back);
        if ($stmt_add->execute()) {
            $msg = "success:Tạo Flashcard tự học thành công!";
        } else {
            $msg = "error:Lỗi tạo Flashcard: " . $db->error;
        }
    } else {
        $msg = "error:Vui lòng nhập đầy đủ nội dung mặt trước và mặt sau.";
    }
} elseif ($action === 'delete') {
    $fid = (int)$_GET['flashcard_id'];
    // Students can only delete flashcards they created themselves (giang_vien_id = 0)
    $stmt_del = $db->prepare("DELETE FROM flashcards WHERE id = ? AND giang_vien_id = 0");
    $stmt_del->bind_param("i", $fid);
    if ($stmt_del->execute() && $db->affected_rows > 0) {
        $msg = "success:Đã xóa Flashcard tự học thành công!";
    } else {
        $msg = "error:Không thể xóa flashcard này (hoặc thẻ được tạo bởi Giảng viên).";
    }
}

// Fetch all subjects in student's department
$subjects = [];
if ($khoa) {
    $stmt_mon = $db->prepare("
        SELECT DISTINCT m.id, m.ten_mon
        FROM thoi_khoa_bieu tkb
        JOIN mon_hoc m ON tkb.mon_hoc_id = m.id
        WHERE LOWER(tkb.khoa) = LOWER(?)
        ORDER BY m.ten_mon
    ");
    $stmt_mon->bind_param("s", $khoa);
    $stmt_mon->execute();
    $subjects = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);
}

$selected_mon_id = (int)($_GET['mon_hoc_id'] ?? ($subjects[0]['id'] ?? 0));

// Fetch flashcards for the selected subject (both created by teacher for this subject, or by student self-study)
$flashcards = [];
if ($selected_mon_id) {
    $stmt_fc = $db->prepare("SELECT * FROM flashcards WHERE mon_hoc_id = ? ORDER BY id DESC");
    $stmt_fc->bind_param("i", $selected_mon_id);
    $stmt_fc->execute();
    $flashcards = $stmt_fc->get_result()->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashcard học tập - Sinh viên</title>
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
        
        /* 3D Flashcard styles */
        .fc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
        }
        .fc-container {
            perspective: 1000px;
            height: 180px;
            cursor: pointer;
        }
        .fc-card {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
        }
        .fc-container:hover .fc-card {
            box-shadow: var(--shadow-md);
        }
        .fc-card.flipped {
            transform: rotateY(180deg);
        }
        .fc-side {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border: 1px solid var(--border);
        }
        .fc-front {
            background: var(--bg2);
            color: var(--text);
        }
        .fc-back {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            transform: rotateY(180deg);
            border-color: transparent;
        }
        .fc-content {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.5;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <?php include '../includes/student_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-square-poll-horizontal" style="color:var(--accent)"></i> Thẻ Flashcard Ôn Tập</h1>
            <p style="color: var(--text2); margin-top: 5px;">Học thuật ngữ, khái niệm lập trình và từ vựng CNTT thông qua thẻ ghi nhớ thông minh</p>
        </div>
    </div>

    <!-- Alert message -->
    <?php if ($msg): 
        $parts = explode(':', $msg);
        $type = $parts[0];
        $text = $parts[1];
    ?>
        <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="display: block; margin-bottom: 20px;">
            <?= htmlspecialchars($text) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
        <!-- Left Column: Add Card Form & Select Subject -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Select Subject -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-filter"></i> Lọc theo học phần</span>
                </div>
                <div class="card-body">
                    <select onchange="window.location.href='?mon_hoc_id='+this.value" class="form-control" style="cursor: pointer; margin-bottom: 0;">
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>" <?= $selected_mon_id === $sub['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sub['ten_mon']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Create Card -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-plus"></i> Tạo Flashcard tự học</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="?action=add&mon_hoc_id=<?= $selected_mon_id ?>">
                        <input type="hidden" name="mon_hoc_id" value="<?= $selected_mon_id ?>">
                        <div class="form-group">
                            <label class="form-label">Mặt trước (Khái niệm / Câu hỏi)</label>
                            <input type="text" name="mat_truoc" class="form-control" placeholder="Ví dụ: IP Address là gì?" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mặt sau (Định nghĩa / Đáp án)</label>
                            <textarea name="mat_sau" class="form-control" rows="3" placeholder="Ví dụ: Địa chỉ định danh thiết bị mạng..." required></textarea>
                        </div>
                        <button type="submit" class="btn-submit"><i class="fa-solid fa-floppy-disk"></i> Tạo thẻ</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Cards Grid -->
        <div>
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-rectangle-list"></i> Thẻ ghi nhớ (<?= count($flashcards) ?>)</span>
                </div>
            </div>

            <?php if (empty($flashcards)): ?>
                <div class="card" style="padding: 40px; text-align: center; color: var(--text2);">
                    <i class="fa-solid fa-box-open" style="font-size: 32px; color: var(--accent); margin-bottom: 15px; display: block;"></i>
                    Chưa có Flashcard nào cho học phần này. Hãy tạo một thẻ tự học ở bên trái!
                </div>
            <?php else: ?>
                <div class="fc-grid">
                    <?php foreach ($flashcards as $fc): 
                        $by_teacher = ($fc['giang_vien_id'] > 0);
                    ?>
                        <div class="fc-container" onclick="this.querySelector('.fc-card').classList.toggle('flipped')">
                            <div class="fc-card">
                                <!-- Front -->
                                <div class="fc-side fc-front">
                                    <span style="font-size: 10px; color: <?= $by_teacher ? 'var(--accent)' : '#10b981' ?>; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; border: 1px solid; padding: 2px 6px; border-radius: 4px;">
                                        <?= $by_teacher ? 'Giảng viên biên soạn' : 'Thẻ tự học' ?>
                                    </span>
                                    <div class="fc-content"><?= htmlspecialchars($fc['mat_truoc']) ?></div>
                                    <span style="font-size: 10px; color: var(--text2); margin-top: 15px; text-transform: uppercase; font-weight:700;"><i class="fa-solid fa-rotate"></i> Click để lật thẻ</span>
                                </div>
                                <!-- Back -->
                                <div class="fc-side fc-back">
                                    <div class="fc-content"><?= htmlspecialchars($fc['mat_sau']) ?></div>
                                    
                                    <?php if (!$by_teacher): ?>
                                        <a href="?action=delete&flashcard_id=<?= $fc['id'] ?>&mon_hoc_id=<?= $selected_mon_id ?>" class="btn-ghost" style="color: #fff; border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); padding: 3px 8px; border-radius: 4px; font-size: 10px; text-decoration: none; position: absolute; bottom: 12px; right: 12px;" onclick="event.stopPropagation(); return confirm('Xóa thẻ tự học này?');">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
